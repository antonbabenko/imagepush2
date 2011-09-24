<?php

namespace Imagepush\ImagepushBundle\Model;

use Imagepush\ImagepushBundle\Services\Processor\Config;

class RedisManager
{
  
  // List all keys and types of all fields which are in use in DB
  const PROCESSED_IMAGE_HASH = 'processed_image_hash';

  /**
   * @services
   */
  public $kernel;
  public static $redis;
  
  public function __construct(\AppKernel $kernel) {
    
    self::$redis = $kernel->getContainer()->get('snc_redis.default_client');
    
  }
  
  /**
   * Set id
   * @param integer $id
   */
  public function setId($id) {
    $this->id = $id;
  }
  
  /**
   * Get id
   * @return integer $id
   */
  public function getId() {
    return $this->id;
  }
  
}