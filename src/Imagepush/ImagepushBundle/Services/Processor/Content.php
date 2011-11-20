<?php

namespace Imagepush\ImagepushBundle\Services\Processor;

use Imagepush\ImagepushBundle\Services\Processor\Config;

class Content
{

  public $data;
  public $status = false;
  public $link;

  /*
   * @services
   */
  public $kernel, $fs, $fsImages;

  public function __construct(\AppKernel $kernel)
  {
    $this->kernel = $kernel;
    //$this->fs = $this->kernel->get('knp_gaufrette.filesystem_map');
    $this->fsImages = $kernel->getContainer()->get('knp_gaufrette.filesystem_map')->get('images');
    
  }

  /**
   * Get status code (if 2xx => true; else => false)
   * @return boolean
   */
  public function isSuccessStatus()
  {
    return $this->status !== false && $this->status / 100 == 2;
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

  public function isImageType()
  {
    return (!empty($this->data["Content-type"]) && in_array($this->data["Content-type"], Config::$allowedImageContentTypes));
  }

  public function isHTMLType()
  {
    return (!empty($this->data["Content-type"]) && (preg_match('/(x|ht)ml/i', $this->data["Content-type"])));
  }

  public function isAlreadyProcessedImageHash()
  {

    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');

    return $redis->sismember('processed_image_hash', $this->data["Content-md5"]);
  }

  public function saveProcessedImageHash()
  {

    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');

    return $redis->sadd('processed_image_hash', $this->data["Content-md5"]);
  }

  public function get($link)
  {
    $fetcher = $this->kernel->getContainer()->get('imagepush.fetcher.content');

    $this->link = $link;
    $response = $fetcher->getRequest($link);

    if (is_array($response))
    {
      $this->data = $response;
      $this->status = $response["Status"];
    } else
    {
      $this->data = null;
      $this->status = false;
    }
    return $response;
  }

  public function head($link)
  {
    $fetcher = $this->kernel->getContainer()->get('imagepush.fetcher.content');

    return $fetcher->headRequest($link);
  }

}