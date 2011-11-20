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

  public function __construct(\AppKernel $kernel) {
    parent::__construct($kernel);
  }
  
  public function load($id) {
    
    $imageKey = $this->makeImageKey($id);
    $data = $this->redis->hgetall($imageKey);
    
    // init Image object
    $this->initFromArray($data);
    
    return $this;
    
  }
  
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
    
    // refresh image data
    $this->refresh();
    
    return true;

  }
  
  /**
   * Save image tags. Saving best tags and all tags (keep for later)
   * @param array $bestTags
   * @param array $allTags
   */
  public function saveAsProcessedWithTags($bestTags, $allTags)
  {

    $bestTagsKeys = $this->tagsManager->getTagKeys($bestTags, true);
    $allTagsKeys = $this->tagsManager->getTagKeys($allTags, true);
    
    // Merge current image data with tags data
    $newData = array_merge(
      $this->toArray(),
      array(
        "tags" => json_encode(array_keys($bestTagsKeys)),
        "all_tags" => json_encode($allTagsKeys)
      )
    );
    
    \D::dump($newData);
    
    $pipe = $this->redis->pipeline();

    // save final data
    $pipe->hmset($this->imageKey, $newData);

    $pipe->execute();
    
    // refresh image data
    $this->refresh();
    
    return true;

  }
  
  /**
   * @todo Add checking if this image is still in upcoming.
   */
  public function migrateUpcomingToAvailable()
  {
    
    // update timestamp
    $this->setTimestamp(time());

    $pipe = $this->redis->pipeline();
    
    // save data
    $pipe->hmset($this->imageKey, $this->toArray());

    // add into available lists
    $pipe->zadd('image_list', $this->timestamp, $this->imageKey);

    $pipe->sadd('available_images', $this->imageKey);

    // remove from upcoming lists
    $pipe->srem('upcoming_images', $this->imageKey);

    $pipe->zrem('upcoming_image_list', $this->imageKey);
    
    $pipe->execute();

    // migrate image tags
    $this->removeFromUpcomingTags(true);

  }

  /**
   * Get latest unprocessed source and set it "in progress"
   * @return array|false Source as array or false if there is no unprocessed link
   */
  public function initUnprocessedSource() {

    $redis = $this->redis;

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
   * Refresh with updated data after save.
   */
  public function refresh() {

    $data = $this->redis->hgetall($this->imageKey);
    $this->initFromArray($data);

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

    $this->removeFromUpcomingTags(false);
    
    // remove data
    $this->redis->del($key);

    return true;

  }
  
  /**
   * Move tagged image from upcoming to available, or remove from upcoming only
   */
  public function removeFromUpcomingTags($makeAvailable)
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