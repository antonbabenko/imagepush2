<?php

namespace Imagepush\ImagepushBundle\Services\Processor\Content;

use Imagepush\ImagepushBundle\Services\Processor\Content\Content;
use Imagepush\ImagepushBundle\External\CustomStrings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class contains methods to look for images in HTML (using DOM/XPath)
 */
class Html
{

    public $dom;

    /**
     * @var Container $container
     */
    public $container;
    public $content;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Set content object
     */
    public function setContent(Content $content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get DOM object for the content
     * 
     * @return \DOMDocument $dom
     */
    public function getDom()
    {
        if (!$this->dom) {
            //\D::debug($this->content->getContent());
            $content = $this->content->getContent();

            libxml_use_internal_errors(true); // to allow html5 tags
            $this->dom = new \DOMDocument();
            $this->dom->loadHTML($content);
            libxml_clear_errors();
        }

        return $this->dom;
    }

    /**
     * Get the full url of images in image_src or og:image
     * 
     * @return array|false Array with urls
     */
    public function getFullImageSrc()
    {

        $domxpath = new \DOMXPath($this->getDom());
        $filtered[] = $domxpath->query("//link[@rel='image_src']");
        $filtered[] = $domxpath->query("//meta[@property='og:image']");

        if (count($filtered)) {
            foreach ($filtered as $blocks) {
                foreach ($blocks as $link) {

                    ($href = $link->getAttribute("content")) || ($href = $link->getAttribute("href"));

                    $images[] = $this->generateFullUrl($href, $this->content->getLink());
                }
            }
        }

        return (!empty($images) ? $images : false);
    }

    /**
     * Find large images, which has correct aspect ratio and min width/height
     */
    public function getBestImageFromDom()
    {

        /**
         * Priority for the best image on the page:
         * 1) get all img inside body
         * 2) keep images, which have good aspect ratio and min width/height
         * 3) get xpath for selected large images
         * 4) todo: find tags inside content area by patterns ("labels:", "keywords:", ...)
         */
        $domxpath = new \DOMXPath($this->getDom());
        $filtered = $domxpath->query("//img[@src]");

        $images = array();

        //\D::debug($filtered);

        if (!count($filtered)) {
            return false;
        }

        foreach ($filtered as $link) {

            $src = $link->getAttribute("src");
            $w = ($link->getAttribute("width") ? : 0);
            $h = ($link->getAttribute("height") ? : 0);
            $r = ($w && $h && $h != 0 ? round($w / $h) : 0); // image ratio

            if (empty($src) || preg_match("/data\:image\//", $src)) {
                continue;
            }

            $url = $this->generateFullUrl($src, $this->content->getLink());

            //\D::debug($url);
            //\D::dump($w);
            //\D::dump($h);
            //\D::dump($r);
            // Check image ratio, min width, min height
            if ($r &&
                $r >= $this->container->getParameter('imagepush.image.min_ratio') &&
                $r <= $this->container->getParameter('imagepush.image.max_ratio') &&
                $w >= $this->container->getParameter('imagepush.image.min_width') &&
                $h >= $this->container->getParameter('imagepush.image.min_height')) {
                $images[] = $url;
                continue;
            }

            // Check min filesize and allowed content type (via HEAD request)
            if ($w || $h) {
                $imgSrcHead = $this->content->head($url);

                //\D::debug($imgSrcHead);
                if (in_array($imgSrcHead["Content-type"], (array) $this->container->getParameter('imagepush.image.allowed_content_types')) &&
                    $imgSrcHead["Content-length"] >= $this->container->getParameter('imagepush.image.min_filesize') &&
                    $imgSrcHead["Content-length"] <= $this->container->getParameter('imagepush.image.max_filesize')) {
                    $images[] = $url;
                    //echo $url . "====\n";
                    continue;
                }
            }
        }

        //\D::debug($images);

        if (!count($images)) {
            $message = sprintf("No suitable image found (among %d available) on this link: %s", count($filtered), $this->content->getLink());
            $this->container->get('logger')->warn($message);

            return false;
        }

        return $images;
    }

    /**
     * Generate full url (with scheme, host, path)
     * 
     * @param string  $link    Link to modify
     * @param string $location Full URI where this $href was found
     * 
     * @return string 
     */
    public function generateFullUrl($link, $location)
    {

        $prefixHost = $prefix = "";

        // Complete link
        if (preg_match('@^' . CustomStrings::$urlPattern . '$@ui', $link)) {
            return $link;
        }

        // Link starts with "/"
        if (preg_match('@^/@', $link)) {
            // Get the complete host name
            preg_match("@^.*://[^/]+/@ui", $location, $host);
            $prefixHost = $host[0];
        } elseif (preg_match('@^\./@', $link)) { // Link is relative to current location
            $link = str_replace("./", "/", $link);
        }

        if ($prefixHost != "") {
            $prefix = $prefixHost;
        } else {
            $prefix = (preg_match("@/$@ui", $location) ? $location : dirname($location) . "/");
        }

        return $prefix . ltrim($link, "/");
    }

}