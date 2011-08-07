<?php

namespace Imagepush\ImagepushBundle\Services\Fetchers;

use Imagepush\ImagepushBundle\External\CustomStrings;

/*
 * Universal class to fetch from Digg/RSS/YQL/etc
 */

class AbstractFetcher
{

  /**
   * Fetched data
   * @param object $data
   */
  public $data;
  
  /**
   * Counters for fetched and saved items, output array.
   */
  public static $fetchedCounter, $savedCounter, $output;
  
  public $kernel, $router, $redis, $images, $tags, $logger, $allServices;
  
  public static $isDebug;
  
  public function __construct(\AppKernel $kernel) {
    
    //var_dump($this->getServiceIds());
    $this->kernel = $kernel;
    
    $this->router = $kernel->getContainer()->get('router');
    $this->redis = $kernel->getContainer()->get('snc_redis.default_client');
    $this->images = $kernel->getContainer()->get('imagepush.images');
    $this->tags = $kernel->getContainer()->get('imagepush.tags');
    $this->logger = $kernel->getContainer()->get('logger');
    
    $this->allServices = array(
      "kernel" => $this->kernel,
      "router" => $this->router,
      "redis" => $this->redis,
      "images" => $this->images,
      "tags" => $this->tags,
      "logger" => $this->logger,
    );
    
    self::$isDebug = $this->kernel->isDebug();
    
  }
  
  /**
   * @todo: check by domain name and content on that domain (filter porn, xxx, sex)
   */
  public function isWorthToSave($item)
  {
    return true;
  }

}