<?php

namespace Imagepush\ImagepushBundle\Imagine;

use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Liip\ImagineBundle\Imagine\Cache\Resolver\WebPathResolver,
    Liip\ImagineBundle\Imagine\Cache\CacheManagerAwareInterface,
    Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Knp\Bundle\GaufretteBundle\FilesystemMap;

class CustomCacheResolver extends WebPathResolver implements CacheManagerAwareInterface
{

    private $container;

    /**
     * @var Gaufrette\Filesystem
     */
    private $fs;

    /**
     * Constructs
     *
     * @param FilesystemMap     $filesystem
     * @param string            $mapName
     */
    public function __construct($container, FilesystemMap $filesystem, $mapName)
    {
        $this->container = $container;

        // Get instance of filesystem map
        $this->fs = $filesystem->get($mapName);
    }

    /**
     * Resolves filtered path for rendering in the browser
     *
     * @param Request $request
     * @param string $path
     * @param string $filter
     *
     * @return string target path
     */
    public function resolve(Request $request, $targetPath, $filter)
    {
        //\D::dump($request->get("width"));
        //\D::dump($targetPath);
        //$targetPath = parent::resolve($request, $targetPath, $filter);
//\D::dump($targetPath);
        
        //$config = $this->container->get('liip_imagine.filter.configuration')->get($filter);

        /*if (!empty($config["route"])) {
            $targetPath = basename($this->container->getParameter('liip_imagine.cache_prefix')) . "/" . $filter . "/" . $request->get("width") . "x" . $request->get("height") . "/" . $targetPath;
        } else {
            $targetPath = basename($this->container->getParameter('liip_imagine.cache_prefix')) . "/" . $filter . "/" . $targetPath;
        }*/
        //$targetPath = $this->cacheManager->getWebRoot().$targetPath;

        //\D::dump($targetPath);
        //$targetPath = $this->getFixedTargetPath($targetPath);
        //\D::dump($targetPath);
        // if the file has already been cached, we're probably not rewriting
        // correctly, hence make a 301 to proper location, so browser remembers
            //\D::dump($this->container->getParameter('cdn_images_url')."/".$targetPath);
        if ($this->fs->has($targetPath)) {
            return new RedirectResponse($this->container->getParameter('cdn_images_url') . "/" . $targetPath);
        }

        return $targetPath;
    }

    /**
     * @param Response $response
     * @param string $targetPath
     * @param string $filter
     *
     * @return Response
     */
    public function store(Response $response, $targetPath, $filter)
    {
        
        \D::dump($response->headers->get('Content-Type'));
        
        $metadata = array(
            'Content-Type' => $response->headers->get('Content-Type', "image/jpeg"),
            'Cache-Control' => 'public',
            'Expires' => gmdate(DATE_RFC822, strtotime("+1 year"))
        );

        $this->fs->write($targetPath, $response->getContent(), true, $metadata);

        // Set ACL to public, if using Amazon S3
        if ($this->fs->getAdapter() instanceof \Gaufrette\Adapter\AmazonS3) {
            $bucket = $this->container->getParameter('s3_bucket_name');

            $amazonS3 = $this->container->get('imagepush.amazon.s3');

            $amazonS3->set_object_acl($bucket, $targetPath, \AmazonS3::ACL_PUBLIC);
        }
        
        $response->setEtag(md5($targetPath));
        $response->setLastModified(new \DateTime("now"));
        $response->setExpires(new \DateTime("+1 year"));
        $response->setSharedMaxAge(365*24*3600); // 1 year

        $response->setStatusCode(201);

        return $response;
    }

}