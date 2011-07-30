<?php

namespace Imagepush\ImagepushBundle\Services;

use Predis\Client as RedisClient;
use Imagepush\ImagepushBundle\Model\Tag;
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
    
  }

  /**
   * Get tag key (as it is saved in db)
   */
  public function getTagKey($tag) {
     return "tag_" . md5( $this->cleanTag($tag) );
  }

  /**
   * Remove useless chars, spaces, newlines, singularize, etc and return it.
   */
  public function cleanTag($tag)
  {

    $tag = mb_strtolower($tag, 'UTF-8');

    $tag = preg_replace('/\n/', ' ', $tag);       // newlines to spaces

    $tag = trim($tag);
    $tag = preg_replace('/\p{P}+\s*$/', '', $tag); // all punctuation at the end
    $tag = preg_replace('/^\s*\p{P}+/', '', $tag); // all punctuation at the beginning
    //$tag = preg_replace('/\p{P}+/', '', $tag); // all punctuation in the middle
    $tag = preg_replace('/\s\s+/', ' ', $tag);    // not more than one space

    $tag = trim($tag);
    $tag = Inflect::singularize($tag);

    return $tag;

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
  
}