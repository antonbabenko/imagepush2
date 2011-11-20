<?php

namespace Imagepush\ImagepushBundle\Services\Processor;

use Imagepush\ImagepushBundle\Services\Processor\Config;
use Imagepush\ImagepushBundle\Services\Processor\Content;
use Imagepush\ImagepushBundle\External\CustomStrings;

class HtmlContent extends Content
{

  public $dom;

  public function __construct(\AppKernel $kernel)
  {
    parent::__construct($kernel);
  }

  public function getDom($reload = false)
  {
    if (!$this->dom || $reload)
    {

      $content = $this->getContent();

      libxml_use_internal_errors(true); // to allow html5 tags
      $this->dom = new \DOMDocument();
      $this->dom->loadHTML($content);
      libxml_clear_errors();
    }

    return $this->dom;
  }

  /**
   * Get the full url of images in image_src or og:image
   * @return array|false Array with urls
   */
  public function getFullImageSrc()
  {

    $domxpath = new \DOMXPath($this->getDom());
    $filtered[] = $domxpath->query("//link[@rel='image_src']");
    $filtered[] = $domxpath->query("//meta[@property='og:image']");
    //\D::dump($filtered);

    if (count($filtered))
    {
      foreach ($filtered as $blocks) {
        foreach ($blocks as $link) {

          ($href = $link->getAttribute("content")) || ($href = $link->getAttribute("href"));

          $imgUrl = self::generateFullUrl($href, $this->getLink());
          
          $images[] = array("url" => $imgUrl, "xpath" => $link->getNodePath());
        }
      }
    }

    return (!empty($images) ? $images : false);
  }

  /**
   * Find large image, which pass the checking (sizes, format, ratio) and save it
   */
  public function getBestImageFromDom()
  {

    /*
     * Priority for the best image on the page:
     * 1) get all img inside body
     * 2) keep images, which have aspect ratio between 0.3 and 2.5 AND width >= 450
     * 3) get xpath for selected large images
     * 4) find tags inside content area by patterns ("labels:", "keywords:", ...)
     *
     */

    $domxpath = new \DOMXPath($this->getDom());
    $filtered = $domxpath->query("//img[@src]");
    
    $images = array();
    
    \D::dump($filtered);

    if (!count($filtered))
      return false;

    foreach ($filtered as $link) {

      $src = $link->getAttribute("src");
      $w = ($link->getAttribute("width") ? : 0);
      $h = ($link->getAttribute("height") ? : 0);
      $r = ($w && $h && $h != 0 ? round($w / $h) : 0); // image ratio

      if (empty($src) || preg_match("/data\:image\//", $src))
      {
        continue;
      }
      
      $imgUrl = self::generateFullUrl($src, $this->getLink());

      //\D::dump($imgUrl);
      //\D::dump($w);
      //\D::dump($h);
      //\D::dump($r);

      // Push to array images with good ratio, width, height or when width or height is empty (banners, placeholders, etc)
      if ($r && $r >= Config::$minRatio && $r <= Config::$maxRatio && $w >= Config::$minWidth && $h >= Config::$minHeight)
      {
        $images[] = array("url" => $imgUrl, "xpath" => $link->getNodePath());
        continue;
      }

      // do HEAD request and check min filesize and content-type
      if ((!$w || !$h) &&
        ($imgSrcHead = $this->head($imgUrl)) &&
        in_array($imgSrcHead["Content-type"], Config::$allowedImageContentTypes) &&
        $imgSrcHead["Content-length"] >= Config::$minFilesize &&
        $imgSrcHead["Content-length"] <= Config::$maxFilesize)
      {
        $images[] = array("url" => $imgUrl, "xpath" => $link->getNodePath());
        continue;
      }
    }
    
    \D::dump($images);

    // fetch images
    if (!count($images))
    {
      $message = sprintf("No suitable image found (among %d available) on this link: %s", count($filtered), $this->getLink());
      $this->kernel->getContainer()->get('logger')->warn($message);
    }

    return (!empty($images) ? $images : false);

  }

  /**
   * Generate full url (with scheme, host, path)
   * @param sting $href Link to modify
   * @param string $fetchedLink Link, where this $href was found
   * @return string 
   */
  public static function generateFullUrl($href, $fetchedLink)
  {

    // Is full url?
    if (preg_match('@^' . CustomStrings::$url_pattern . '$@ui', $href))
    {
      $fullUrl = $href;
    } else
    {
      if (preg_match('@^/@', $href))
      { // link is absolute, so get the host name
        $prefix = parse_url($fetchedLink, PHP_URL_SCHEME) . "://" . parse_url($fetchedLink, PHP_URL_HOST);
      } else
      { // link is relative
        $prefix = dirname($fetchedLink) . "/";
      }
      $fullUrl = $prefix . $href;
    }

    return $fullUrl;
  }

}