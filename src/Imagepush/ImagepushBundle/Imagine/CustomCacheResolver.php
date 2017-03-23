<?php

namespace Imagepush\ImagepushBundle\Imagine;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Liip\ImagineBundle\Imagine\Cache\Resolver\WebPathResolver;
use Liip\ImagineBundle\Imagine\Cache\CacheManagerAwareInterface;
use Gaufrette\Filesystem;

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
     * @param ContainerInterface $container
     * @param Filesystem         $fs
     */
    public function __construct($container, Filesystem $fs)
    {
        $this->container = $container;
        $this->fs = $fs;
    }

    /**
     * Resolves filtered path for rendering in the browser
     *
     * @param Request $request
     * @param string  $path
     * @param string  $filter
     *
     * @return string target path
     */
    public function resolve(Request $request, $targetPath, $filter)
    {
        $targetPath = $filter . "/" . $targetPath;
        if ($this->fs->has($targetPath)) {
            return new RedirectResponse($this->container->getParameter('cdn_images_url') . "/" . $targetPath);
        }

        return $targetPath;
    }

    /**
     * @param Response $response
     * @param string   $targetPath
     * @param string   $filter
     *
     * @return array
     */
    public function store(Response $response, $targetPath, $filter)
    {

        $contentType = $response->headers->get('Content-Type', "image/jpeg");

        // max-age=31536000 -> 1 year
        $metadata = array(
            'Content-Type' => $contentType,
            'Cache-Control' => 'max-age=31536000, public',
            'Expires' => gmdate(DATE_RFC822, strtotime("+1 year"))
        );

        $filesize = $this->fs->write($targetPath, $response->getContent(), true, $metadata);

        $response->setEtag(md5($targetPath));
        $response->setLastModified(new \DateTime("now"));
        $response->setExpires(new \DateTime("+1 year"));
        $response->setMaxAge(365 * 24 * 3600); // 1 year // was sharedMaxAge

        $response->setStatusCode(201);

        return array("response" => $response, "filesize" => $filesize);
    }

}
