<?php

namespace Imagepush\ImagepushBundle\Services\Processor;

use Imagepush\ImagepushBundle\Services\Processor\Config;

class Content
{

  public static $data;
  
  public static $isFetched = false;
  
  public static $link;
  
  /*
   * @services
   */
  public $kernel;
  
  public function __construct(\AppKernel $kernel) {
    
    $this->kernel = $kernel;
    
  }

  public function isFetched() {
    return self::$isFetched;
  }

  public function setLink($link) {
    self::$link = $link;
  }

  public function getLink() {
    return self::$link;
  }

  public function setData($data) {
    self::$data = $data;
  }
  
  public function getData() {
    return self::$data;
  }

  public function getContent()
  {
    return self::$data["Content"];
  }

  public function isImage() {
    
    return (!empty(self::$data["Content-type"]) && in_array(self::$data["Content-type"], Config::$allowedImageContentTypes));
    
  }

  public function isHTMLLike() {
    
    return (!empty(self::$data["Content-type"]) && (preg_match('/(x|ht)ml/i', self::$data["Content-type"])));
    
  }
  
  public function isAlreadyProcessedImageHash() {
    
    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');

    return $redis->sismember('processed_image_hash', self::$data["Content-md5"]);
    
  }
  
  public function saveProcessedImageHash() {
    
    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');

    return $redis->sadd('processed_image_hash', self::$data["Content-md5"]);
    
  }
  
  public function fetch($link) {
    $fetcher = $this->kernel->getContainer()->get('imagepush.fetcher.content');
    
    $response = $fetcher->get($link);
    
    if (is_array($response)) {
      self::$data = $response;
      self::$isFetched = true;
    } else {
      self::$data = null;
      self::$isFetched = false;
    }
    return $response;
  }
  
  public function head($link) {
    $fetcher = $this->kernel->getContainer()->get('imagepush.fetcher.content');
    
    return $fetcher->head($link);
    
  }
  
  /**
   * Init the main link and fetch the data
   */
  public function initAndFetch($link) {
    $this->setLink($link);
    return $this->fetch($link);
  }
  
}