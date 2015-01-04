<?php

namespace Imagepush\ImagepushBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Imagine\Image\ImagineInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Imagine controller. Generate thumbs of custom size and save them on Amazon S3.
 */
class ImagineController
{

    /**
     * @var DataManager
     */
    protected $dataManager;

    /**
     * @var FilterManager
     */
    protected $filterManager;

    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var Imagine\Image\ImagineInterface
     */
    private $imagine;

    /**
     * Allow to generate images without hash verification in debug mode
     *
     * @var boolean
     */
    protected $debug;

    /**
     * Constructor
     *
     * @param DataManager      $dataManager
     * @param FilterManager    $filterManager
     * @param CacheManager     $cacheManager
     * @param DocumentManager  $dm
     * @param ImagineInterface $imagine
     * @param Logger           $logger
     * @param boolean          $debug
     */
    public function __construct(DataManager $dataManager, FilterManager $filterManager, CacheManager $cacheManager, DocumentManager $dm, ImagineInterface $imagine, Logger $logger, $debug)
    {
        $this->dataManager = $dataManager;
        $this->filterManager = $filterManager;
        $this->cacheManager = $cacheManager;
        $this->dm = $dm;
        $this->imagine = $imagine;
        $this->logger = $logger;
        $this->debug = $debug;
    }

    /**
     * This action applies a given filter to a given image,
     * optionally saves the image and
     * outputs it to the browser at the same time
     *
     * @param Request $request
     * @param string  $path
     * @param string  $filter
     *
     * @return Response
     */
    public function filterAction(Request $request, $path, $filter)
    {
        $width = $height = 0;
        $maxWidth = $maxHeight = 8000;

        $targetPath = $this->cacheManager->resolve($request, $path, $filter);

        //$this->logger->err(sprintf("[!!!!!] Path %s", $path));

        if ($targetPath instanceof Response) {
            return $targetPath;
        }

        // Sample: http://dev-anton.imagepush.to/cache/out/100x200/new_uploads/file.jpg?hash=abcd
        if (false !== strpos($path, "/")) {

            $path = explode("/", $path);
            $size = explode("x", $path[0]);
            if (count($size) == 2) {

                $width = min($maxWidth, abs(intval($size[0])));
                $height = min($maxHeight, abs(intval($size[1])));

                // remove size from the path
                array_shift($path);

                $path = implode("/", $path);

                $imageId = $request->query->get('i');
                if (!$this->debug) {
                    $correctHash = md5($width . '|' . $height . '|' . $path . ($imageId ? '|' . $imageId : ''));
                    $hash = $request->query->get('hash');

                    if (false === (strlen($hash) >= 4 && strpos($correctHash, $hash) === 0)) {
                        $this->logger->err(sprintf("Image id: %d; Path %s; Incorrect hash (%s) is not equal to correct hash (%s)", $imageId, $path, $hash, $correctHash));
                        throw new NotFoundHttpException('Incorrect hash');
                    }
                }
            } else {
                $path = implode("/", $path);
            }
        }

        $image = $this->dataManager->find($filter, $path);

        $filterConfig = $this->filterManager->getFilterConfiguration();
        $config = $filterConfig->get($filter);

        if (empty($width) || empty($height)) {
            $this->logger->err(sprintf("Image id: %d; Missing width (%s) or height (%s)", $imageId, $width, $height));
            throw new NotFoundHttpException('Missing width or height parameters');
        }

        $config['filters']['thumbnail']['size'] = array($width, $height);

        $filterConfig->set($filter, $config);

        $response = $this->filterManager->get($request, $filter, $image, $path);

        if ($targetPath) {
            // Store resized image and get response and filesize
            $stored = $this->cacheManager->store($response, $targetPath, $filter);
            $response = $stored["response"];
            $filesize = $stored["filesize"];

            // Update image thumbs in database to prevent it from generating again
            if (!empty($imageId)) {
                $image = $this->dm
                    ->getRepository('ImagepushBundle:Image')
                    ->findOneBy(array("id" => (int) $imageId));
                if ($image) {
                    // try to get real image size
                    try {
                        $resized = $this->imagine->load($response->getContent());

                        $image->addThumbs($filter, $width . 'x' . $height, $resized->getSize()->getWidth(), $resized->getSize()->getHeight(), $filesize);

                        $this->dm->persist($image);
                        $this->dm->flush();

                        //$this->logger->err(sprintf("[Imagine] Size_content %d; size_file: %d", strlen($response->getContent()), $filesize));
                    } catch (\Exception $e) {
                        // skip imagine exceptions, if image can't be loaded
                        $this->logger->err("Image id: " . $imageId . "; Error on image thumb update: " . $e->getMessage());
                    }
                }
            }
        }

        return $response;
    }

}
