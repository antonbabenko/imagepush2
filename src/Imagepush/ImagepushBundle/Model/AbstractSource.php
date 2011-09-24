<?php

namespace Imagepush\ImagepushBundle\Model;

use Imagepush\ImagepushBundle\External\CustomStrings;
use Imagepush\ImagepushBundle\Services\Processor\Config;

/**
 * Imagepush\ImagepushBundle\Model\AbstractSource
 * 
 * 
 * This class describes setters and getters for fetched sources.
 */
class AbstractSource
{

  /**
   * @required
   */
  public $id;
  public $imageKey;
  public $link;
  public $timestamp;
  
  /**
   * @optional
   */
  public $title = "";
  public $slug = "";
  
  /*
   * @string
   */
  public $sourceType;

  /**
   * @services
   */
  public $kernel;
  
  public function __construct(\AppKernel $kernel) {
    
    $this->kernel = $kernel;
    
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
  
  /**
   * Set image key
   * @param string $imageKey
   */
  public function setImageKey($imageKey) {
    $this->imageKey = $imageKey;
  }
  
  /**
   * Get image key
   * @return string $imageKey
   */
  public function getImageKey() {
    return $this->imageKey;
  }
  
  /**
   * Set link
   * @param string $link
   */
  public function setLink($link) {
    $this->link = $link;
  }
  
  /**
   * Get link
   * @return string $link
   */
  public function getLink() {
    return $this->link;
  }
  
  /**
   * Set timestamp
   * @param integer $timestamp
   */
  public function setTimestamp($timestamp) {
    $this->timestamp = $timestamp;
  }
  
  /**
   * Get timestamp
   * @return integer $timestamp
   */
  public function getTimestamp() {
    return $this->timestamp;
  }
  
  /**
   * Set title
   * @param string $title
   */
  public function setTitle($title = "") {
    $this->title = CustomStrings::cleanTitle($title);
  }
  
  /**
   * Get title
   * @param string $title
   */
  public function getTitle() {
    return $this->title;
  }
  
  /**
   * Set tags
   * @param array $tags
   */
  public function setTags($tags = array()) {
    $this->tags = (array)$tags;
  }
  
  /**
   * Get tags
   * @param array $tags
   */
  public function getTags() {
    return $this->tags;
  }
  
  /**
   * Set slug from title
   * @param string $slug
   */
  public function setSlugFromTitle() {
    $this->slug = CustomStrings::slugify($this->title);
  }
  
  /**
   * Get slug
   * @param string $slug
   */
  public function getSlug() {
    return $this->slug;
  }
  
  /**
   * Get all data as array
   * @param array $source
   */
  public function toArray() {
    return array(
      "id" => $this->id,
      "link" => $this->link,
      "timestamp" => $this->timestamp,
      "title" => $this->title,
      "slug" => $this->link,
      "tags" => $this->tags,
    );
  }
  
  /**
   * Save source object
   * @return true or Exception
   */
  public function save() {
    
    if (empty($this->id) || empty($this->link) || empty($this->timestamp) || empty($this->sourceType)) {
      throw new \Exception("Source id, sourceType, link and timestamp can't be empty");
    }
    
    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');
    
    $pipe = $redis->pipeline();
    
    // save temporary data
    $pipe->hmset($this->imageKey, $this->toArray());
    
    // keep index of indexed links (to keep them once)
    $pipe->sadd('indexed_links', $this->link);
      
    // and save data about link to process
    $pipe->zadd('link_list_to_process', $this->timestamp, $this->imageKey);
    
    // incr counter
    $pipe->incr('image_id');
    
    $pipe->execute();
    
    return true;
    
  }
  
  /**
   * Get latest unprocessed source and set it "in progress"
   * @return array|false Source as array or false if there is no unprocessed link
   */
  public function getAndInitUnprocessed() {

    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');

    $imageKeys = $redis->zrevrangebyscore('link_list_to_process', "+inf", "-inf");
    //\D::dump($imageKeys);
    
    if (count($imageKeys))
    {

      foreach ($imageKeys as $key) {
        if (!$redis->sismember("link_list_in_progress", $key)) {
          if (Config::$modifyDB) {
            $redis->sadd("link_list_in_progress", $key);
          }
          
          return $redis->hgetall($key);
        }
      }

    }

    return false;

  }

  /**
   * Remove image key with all data completely
   */
  public static function removeKey($key, $link="")
  {

    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');

    // remove data
    // todo: verify that key is correct to not delete all if *
    $redis->del($key);

    // remove link from set of indexed links
    if (!empty ($link)) {
      $redis->srem('indexed_links', $link);
      $redis->sadd('failed_links', $link);
    }

    // remove link from process list
    $redis->zrem('link_list_to_process', $key);

    // remove link from in progress list
    $redis->srem('link_list_in_progress', $key);

    // remove image from all sets to make it available for user
    $redis->zrem('image_list', $key);
    $redis->srem('available_images', $key);
    $redis->srem('upcoming_images', $key);
    $redis->zrem('upcoming_image_list', $key);

    self::removeUpcomingImageTags($key);

    // remove cached dom object
    $redis->del("cached_dom_".$key);

  }



}