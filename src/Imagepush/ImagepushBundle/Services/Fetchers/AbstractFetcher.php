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
  
  public $kernel;
  
  public function __construct(\AppKernel $kernel) {
    
    $this->kernel = $kernel;
    
  }
  
  /**
   * @todo: check by domain name and content on that domain (filter porn, xxx, sex)
   */
  public function isWorthToSave($item)
  {
    return true;
  }

}