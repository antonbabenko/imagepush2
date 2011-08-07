<?php

namespace Imagepush\ImagepushBundle\Services;

use Predis\Client as RedisClient;
use Imagepush\ImagepushBundle\Model\Tag;
use Imagepush\ImagepushBundle\External\CustomStrings;
use Imagepush\ImagepushBundle\External\Inflect;

class Tags
{
  
  private $router;
  private $redis;
  private $images;
  
  public function __construct(\AppKernel $kernel) {
    
    $this->router = $kernel->getContainer()->get('router');
    $this->redis = $kernel->getContainer()->get('snc_redis.default_client');
    //$this->images = $kernel->getContainer()->get('imagepush.images');
    $this->customStrings = new CustomStrings();
    
  }

  /**
   * Get tag key (as it is saved in db)
   */
  public function getTagKey($tag, $isCleaned = false) {
    if ($isCleaned) {
      return "tag_" . md5( $tag );
    } else {
      return "tag_" . md5( $this->customStrings->cleanTag($tag) );
    }
  }

  public function getLatestTrends($limit = 20) {

    $tags = $this->redis->zrevrange('latest_trend', 0, (isset($limit) ? $limit*3 : -1));

    $trends = array();
    if ($tags)
    {
      $tags = array_diff($tags, array_map(array($this, "getTagKey"), Tag::$HIDDEN_TRENDS));
      $tags = array_merge($tags);

      if ($limit > 0) {
        $tags = array_slice($tags, 0, $limit);
      }

      $trends = $this->redis->mget($tags);
    }
    
    // filter out nulls
    $trends = array_filter($trends, function($trend){return !is_null($trend);});
    //\D::dump($trends);

    return $trends;

  }
  
  public function getHumanTags($tag)
  {

    $tags = (is_array($tag) ? $tag : array($tag));
    //D::dump($tags);

    if (count($tags)) {
      $human_tags = $this->redis->mget($tags);
    } else {
      $human_tags = array();
    }

    return $human_tags;
  }
  
  /**
   * Clean tags and verify that each tag exists in database. Create new tags, if doesn't exist.
   * @param string|array $originalTags
   * @return array $fixedTags
   */
  public function verifyTags($originalTags)
  {

    $originalTags = (is_array($originalTags) ? $originalTags : array($originalTags));
    //\D::dump($originalTags);

    $insertTags = array();
    $fixedTags = array();
    
    foreach ($originalTags as $originalTag) {

      $tag = $this->customStrings->cleanTag($originalTag);
      
      //\D::dump($tag);

      if (empty($tag)) continue;

      $key = $this->getTagKey($tag, true);

      if (!$this->redis->exists($key))
      {
        $insertTags[$key] = $tag;
      }
      
      $fixedTags[$key] = $tag;
    }
    
    if (count($insertTags)) {
      $this->redis->mset($insertTags);
    }

    return array_keys($fixedTags);
  }

  public function saveRawTags($imageKey, $tagsSrc, $tags)
  {

    if (empty($imageKey) || empty($tagsSrc) || empty($tags))
      return;

    $tagKeys = $this->verifyTags($tags);
    //\D::dump($tagKeys);

    if (count($tagKeys))
    {
      $pipe = $this->redis->pipeline();
      foreach ($tagKeys as $tagKey) {
        $pipe->sadd($imageKey . ":tmp_tags:" . $tagsSrc, $tagKey);
      }
      $pipe->execute();
    }
  }

}