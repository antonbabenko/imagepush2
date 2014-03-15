<?php

namespace Imagepush\ImagepushBundle\Templating;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ImagepushExtension extends \Twig_Extension
{

    /**
     * @var Container
     */
    private $container;

    /**
     * Constructs by setting $container
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * (non-PHPdoc)
     * @see Twig_Extension::getFilters()
     */
    public function getFilters()
    {
        return array(
            'imagepush_filter' => new \Twig_Filter_Method($this, 'imagepushFilter'),
            'cdn_imagepush_filter' => new \Twig_Filter_Method($this, 'cdnImagepushFilter'),
        );
    }

    /**
     * Gets cache path of an image to be filtered
     *
     * @param string  $file
     * @param string  $filter
     * @param integer $width
     * @param integer $height
     * @param integer $imageId Optional image id (if object is Image)
     *
     * @return string
     */
    public function imagepushFilter($file, $filter, $width, $height, $imageId = null)
    {

        $hash = substr(md5($width . '|' . $height . '|' . $file . ($imageId ? '|' . $imageId : '')), 0, 4);
        $url = rtrim($this->container->getParameter('site_url'), '/');
        $url .= '/cache/' . $filter . '/' . $width . 'x' . $height . '/' . ltrim($file, '/');
        $url .= '?hash=' . $hash;

        if ($imageId) {
            $url .= '&i=' . $imageId;
        }

        return $url;
    }

    /**
     * Gets CDN url of an image which is already saved there
     *
     * @param string  $file
     * @param string  $filter
     * @param integer $width
     * @param integer $height
     *
     * @return string
     */
    public function cdnImagepushFilter($file, $filter, $width, $height)
    {

        $url = rtrim($this->container->getParameter('cdn_images_url'), '/');
        $url .= '/' . $filter . '/' . $width . 'x' . $height . '/' . ltrim($file, '/');

        return $url;
    }

    /**
     * (non-PHPdoc)
     * @see Twig_ExtensionInterface::getName()
     */
    public function getName()
    {
        return 'imagepush_filter';
    }

}
