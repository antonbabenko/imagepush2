<?php

namespace Imagepush\ImagepushBundle\Services\Processor\Content;

use Imagepush\ImagepushBundle\Services\Processor\Config;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Content
{

    public $data;
    public $status = false;
    public $link;

    /*
     * @var Gaufrette $fsImages
     */
    public $fsImages;

    /**
     * @var Container $container
     */
    public $container;

    /**
     * @var imagepush.fetcher.content $fetcher
     */
    public $fetcher;

    /**
     * @var imagepush.processor.html_content $htmlContent
     */
    public $htmlContent;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->fetcher = $container->get('imagepush.fetcher.content');
        $this->fsImages = $container->get('knp_gaufrette.filesystem_map')->get('images');
        $this->htmlContent = $container->get('imagepush.processor.content.html')->setContent($this);
    }

    /**
     * Get status code (if 2xx => true; else => false)
     * @return boolean
     */
    public function isSuccessStatus()
    {
        return $this->status !== false && (int) $this->status && $this->status / 100 == 2;
    }

    public function setLink($link)
    {
        $this->link = $link;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getContent()
    {
        return (array_key_exists("Content", $this->data) ? $this->data["Content"] : null);
    }

    public function getContentType()
    {
        return (array_key_exists("Content-type", $this->data) ? $this->data["Content-type"] : null);
    }

    public function getContentMd5()
    {
        return (array_key_exists("Content-md5", $this->data) ? $this->data["Content-md5"] : null);
    }

    public function isImageType()
    {
        return (!empty($this->data["Content-type"]) && in_array($this->data["Content-type"], Config::$allowedImageContentTypes));
    }

    public function isHTMLType()
    {
        return (!empty($this->data["Content-type"]) && (preg_match('/(x|ht)ml/i', $this->data["Content-type"])));
    }

    public function get($link)
    {
        $this->link = $link;
        $response = $this->fetcher->getRequest($link);
        //\D::dump($response);

        if (is_array($response)) {
            $this->data = $response;
            $this->status = $response["Status"];
        } else {
            $this->data = null;
            $this->status = false;
        }

        return $response;
    }

    /**
     * @param string $link
     * 
     * @return array|false
     */
    public function head($link)
    {
        return $this->fetcher->headRequest($link);
    }

}