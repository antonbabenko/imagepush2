<?php

namespace Imagepush\ImagepushBundle\Entity;

use Imagepush\ImagepushBundle\External\CustomStrings;
use Imagepush\ImagepushBundle\Model\AbstractSource;
use Imagepush\ImagepushBundle\Services\Processor\Config;

/**
 * Image object itself together with deps (tags, mentions)
 */
class Image extends AbstractSource
{

  //public $id;
  //public $imageKey;
  //public $source;
  //public $link;
  //public $timestamp;
  //public $title = "";
  //public $slug = "";

  public function __construct(\AppKernel $kernel) {
    parent::__construct($kernel);
  }
  
  /**
   * Init all properties from source array
   * @param array $source
   */
  public function initFromArray($data) {
    $this->id = $data["id"];
    $this->imageKey = $this->makeImageKey($data["id"]);
    $this->link = $data["link"];
    $this->timestamp = $data["timestamp"];
    $this->title = $data["title"];
    $this->slug = $data["slug"];
    
    $this->tags = (isset($data["tags"]) ? $data["tags"] : '');
    $this->originalTags = (isset($data["original_tags"]) ? $data["original_tags"] : '');
  }
  
  /**
   * Get all finalized data as array to save data
   * @return array
   */
  public function toArray() {
    $result["id"] = $this->id;
    $result["link"] = $this->link;
    $result["timestamp"] = $this->timestamp;
    $result["title"] = $this->title;
    $result["slug"] = $this->slug;
    
    $result["tags"] = (isset($this->tags) ? $this->tags : '');
    $result["original_tags"] = (isset($this->originalTags) ? $this->originalTags : '');
    
    return $result;
    
  }
  
  /**
   * Get image key
   * @return string $imageKey
   */
  /*public function getImageKey() {
    return "image_id:".$this->id;
  }*/
  
  /*
   * Save image as processed (with thumbs)
   */
  public function saveAsProcessed($data)
  {
    // Merge current image data with new image data
    $newData = array_merge($this->toArray(), $data);
    
    \D::dump($newData);
    
    $pipe = $this->redis->pipeline();

    // save final data
    $pipe->hmset($this->imageKey, $newData);

    // and save data about link to process
    $pipe->zrem('link_list_to_process', $this->imageKey);

    // remove link from in progress list
    $pipe->srem('link_list_in_progress', $this->imageKey);

    // save image to the list and make it available (was: image_list)
    $pipe->zadd('upcoming_image_list', $newData["timestamp"], $this->imageKey);

    // was: saving to available_images, but correct -> upcoming_images
    $pipe->sadd('upcoming_images', $this->imageKey);
    
    $pipe->execute();
    
    return true;

  }

  /**
   * Get latest unprocessed source and set it "in progress"
   * @return array|false Source as array or false if there is no unprocessed link
   */
  public function initUnprocessedSource() {

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
          
          $this->initFromArray($redis->hgetall($key));
          
          return true;
        }
      }

    }

    return false;

  }

  /**
   * Remove image key with all data completely
   */
  public function remove()
  {

    $pipe = $this->redis->pipeline();
    
    $key = $this->imageKey;
    $link = $this->link;
    
    \D::dump($key);
    \D::dump($link);
    
    // remove link from set of indexed links
    if (!empty ($link)) {
      $pipe->srem('indexed_links', $link);
      $pipe->sadd('failed_links', $link);
    }

    // remove link from process list
    $pipe->zrem('link_list_to_process', $key);

    // remove link from in progress list
    $pipe->srem('link_list_in_progress', $key);

    // remove image from all sets to make it available for user
    $pipe->zrem('image_list', $key);
    $pipe->srem('available_images', $key);
    $pipe->srem('upcoming_images', $key);
    $pipe->zrem('upcoming_image_list', $key);
    
    $pipe->execute();

    $this->removeFromUpcomingTags();
    
    // remove data
    $this->redis->del($key);

    return true;

  }
  
  /**
   * Move tagged image from upcoming to available, or remove from upcoming only
   */
  public function removeFromUpcomingTags($makeAvailable = false)
  {

    $tags = @json_decode($this->tags);
    
    \D::dump($tags);

    if ($tags && count($tags))
    {

      $pipe = $this->redis->pipeline();

      foreach ($tags as $tagKey) {
        $pipe->zrem('upcoming_image_list:' . $tagKey, $this->imageKey);

        if ($makeAvailable)
        {
          $pipe->zadd('image_list:' . $tagKey, $this->timestamp, $this->imageKey);
        }
      }

      if ($makeAvailable)
      {
        $pipe->sadd('available_images', $this->imageKey);
      }

      $pipe->execute();
      
    }

    return true;
  }

}