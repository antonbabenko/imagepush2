<?php

namespace Imagepush\ImagepushBundle\Imagine;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;

class CustomController
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
     * Allow to generate images without hash verification in debug mode
     * @var boolean 
     */
    protected $debug;

    /**
     * Constructor
     *
     * @param DataManager $dataManager
     * @param FilterManager $filterManager
     * @param CacheManager $cacheManager
     */
    public function __construct(DataManager $dataManager, FilterManager $filterManager, CacheManager $cacheManager, $debug)
    {
        $this->dataManager = $dataManager;
        $this->filterManager = $filterManager;
        $this->cacheManager = $cacheManager;
        $this->debug = $debug;
    }

    /**
     * This action applies a given filter to a given image,
     * optionally saves the image and
     * outputs it to the browser at the same time
     *
     * @param Request $request
     * @param string $path
     * @param string $filter
     *
     * @return Response
     */
    public function filterAction(Request $request, $path, $filter)
    {
        //\D::dump($path);

        $defaultWidth = 100;
        $defaultHeight = 100;
        $maxWidth = $maxHeight = 4000;

        $targetPath = $this->cacheManager->resolve($request, $path, $filter);
        //\D::dump($targetPath);
        if ($targetPath instanceof Response) {
            return $targetPath;
        }

        // Sample: http://dev-anton.imagepush.to/cache/outbound/100x200/new_uploads/file.jpg?hash=abcd
        if (false !== strpos($path, "/")) {

            $path = explode("/", $path);
            $size = explode("x", $path[0]);
            if (count($size) == 2) {

                $width = min($maxWidth, abs(intval($size[0])));
                $height = min($maxHeight, abs(intval($size[1])));

                // remove size from the path
                array_shift($path);

                $path = implode("/", $path);

                if (!$this->debug) {
                    $correctHash = md5($width . '|' . $height . '|' . $path);
                    $hash = $request->query->get('hash');
                    if (false === (strlen($hash) >= 4 && strpos($correctHash, $hash) === 0)) {
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

        $width = (!empty($width) ? $width : $defaultWidth);
        $height = (!empty($height) ? $height : $defaultHeight);
        $config['filters']['thumbnail']['size'] = array($width, $height);

        //\D::dump($config);
        $filterConfig->set($filter, $config);

        $response = $this->filterManager->get($request, $filter, $image, $path);

        if ($targetPath) {
            $response = $this->cacheManager->store($response, $targetPath, $filter);
        }

        return $response;
    }

}