<?php

namespace Imagepush\ImagepushBundle\Imagine;

use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Liip\ImagineBundle\Imagine\Cache\Resolver\WebPathResolver,
    Liip\ImagineBundle\Imagine\Cache\CacheManagerAwareInterface,
    Liip\ImagineBundle\Imagine\Cache\CacheManager;
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
     * @return Response
     */
    public function store(Response $response, $targetPath, $filter)
    {

        //\D::dump($response->headers->get('Content-Type'));
        //$targetPath = $filter . "/". $targetPath;
        //\D::dump($targetPath);

        $contentType = $response->headers->get('Content-Type', "image/jpeg");

        // max-age=31536000 -> 1 year
        $metadata = array(
            'Content-Type' => $contentType,
            'Cache-Control' => 'max-age=31536000, public',
            'Expires' => gmdate(DATE_RFC822, strtotime("+1 year"))
        );

        $filesize = $this->fs->write($targetPath, $response->getContent(), true, $metadata);

        // Set ACL to public, if using Amazon S3
        if ($this->fs->getAdapter() instanceof \Gaufrette\Adapter\AmazonS3) {
            $bucket = $this->container->getParameter('s3_bucket_name');
            $opt['headers']['Cache-Control'] = "max-age=31536000, public";

            $amazonS3 = $this->container->get('imagepush.amazon.s3');

            //\D::dump($amazonS3);
            //\D::dump($bucket);
            //\D::dump($targetPath);
            //\D::dump(\AmazonS3::ACL_PUBLIC);

            $amazonS3->set_object_acl($bucket, $targetPath, \AmazonS3::ACL_PUBLIC);
            $amazonS3->change_content_type($bucket, $targetPath, $contentType, $opt);
        }

        $response->setEtag(md5($targetPath));
        $response->setLastModified(new \DateTime("now"));
        $response->setExpires(new \DateTime("+1 year"));
        $response->setMaxAge(365 * 24 * 3600); // 1 year // was sharedMaxAge

        $response->setStatusCode(201);

        return array("response" => $response, "filesize" => $filesize);
    }

}