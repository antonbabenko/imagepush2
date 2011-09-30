<?php

namespace Imagepush\ImagepushBundle\Services\Fetcher;

use Imagepush\ImagepushBundle\External\CustomStrings;

/*
 * Abstract class to fetch data from Digg/RSS/YQL/etc (via API) and from HTTP (via Goutte or other library) 
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
    $this->logger = $kernel->getContainer()->get('logger');
    
  }
  
  /**
   * @todo: check by domain name and content on that domain (filter porn, xxx, sex)
   */
  public function isWorthToSave($item)
  {
    return true;
  }

}