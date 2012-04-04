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
     * @param Container $container
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
        );
    }

    /**
     * Gets cache path of an image to be filtered
     *
     * @param string $string
     *
     * @return string
     */
    public function imagepushFilter($file, $filter, $width, $height)
    {
        
        $hash = substr(md5($width . '|' . $height . '|' . $file), 0, 4);
        $url = rtrim($this->container->getParameter('site_url'), '/');
        $url .= '/cache/' . $filter . '/' . $width . 'x' . $height . '/' . ltrim($file, '/');
        $url .= '?hash=' . $hash;

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
