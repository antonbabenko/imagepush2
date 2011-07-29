<?php

namespace Imagepush\ImagepushBundle\Services;

use Predis\Client as RedisClient;
use Imagepush\ImagepushBundle\Entity\Image;

class Images
{
  
  private $redis;
  
  public function __construct(RedisClient $redis) {
    
    if ($redis instanceof RedisClient) {
      $this->redis = $redis;
    } else {
      throw new \ErrorException("Redis instance is not valid.");
    }
    
  }

  public function getLatestImages($limit = 20) {

    $images = array();
    
    //$redis = $this->redis;
    
    $count = $this->redis->zcard('image_list');

    // get just latest one
    $image_keys = $this->redis->zrangebyscore('image_list', "-inf", "+inf", array("withscores" => 1, "LIMIT" => array($count - $limit, $limit)));
    //\D::dump($image_keys);

    if (count($image_keys))
    {

      $image_keys_new = array();

      foreach ($image_keys as $key) {
        $image_keys_new[$key[1]] = $key[0];
      }

      $image_keys_new = array_reverse($image_keys_new, true);

      foreach ($image_keys_new as $time => $key) {
        $image = $this->redis->hgetall($key);
        $images[] = Image::normalizeImage($image);
      }
    }

    return $images;

  }
  
}