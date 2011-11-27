<?php

namespace Imagepush\ImagepushBundle\Document;

use Imagepush\ImagepushBundle\External\CustomStrings;
use Imagepush\ImagepushBundle\Services\Processor\Config;

class TagsManager
{
  
  public $redis;
  
  public static $customStrings;
  
  public function __construct($redis) {
    
    $this->redis = $redis;
    self::$customStrings = new CustomStrings();
    
  }

  /**
   * Get tag key (as it is saved in db)
   */
  public function getTagKey($tag, $isCleaned = false) {
    if ($isCleaned) {
      return "tag_" . md5( $tag );
    } else {
      return "tag_" . md5( self::$customStrings->cleanTag($tag) );
    }
  }
  
  /**
   * Get tag keys for array (tag => score) or array(tag)
   */
  public function getTagKeys($tags, $isCleaned = false) {
    $tagKeys = array();
    
    foreach ($tags as $tag => $score) {
      
      if (is_int($tag) && !is_int($score)) { // array(tag)
        $key = $this->getTagKey($score, $isCleaned);
        $tagKeys[$key] = 1;
      } else { // array (tag => score)
        $key = $this->getTagKey($tag, $isCleaned);
        $tagKeys[$key] = $score;
      }
      
    }
    
    return $tagKeys;
  }

  public function getLatestTrends($limit = 20) {

    $tags = $this->redis->zrevrange('latest_trend', 0, (isset($limit) ? $limit*3 : -1));

    $trends = array();
    if ($tags)
    {
      $tags = array_diff($tags, array_map(array($this, "getTagKey"), Config::$hiddenTrends));
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
      $humanTags = $this->redis->mget($tags);
      $humanTags = array_values(array_filter($humanTags, function($tag){return !is_null($tag);}));
    } else {
      $humanTags = array();
    }

    return $humanTags;
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

      $tag = self::$customStrings->cleanTag($originalTag);
      
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

}