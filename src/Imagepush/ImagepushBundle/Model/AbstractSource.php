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
  public $originalTags;
  public $tags;

  /*
   * @string
   */
  protected $sourceType;

  /**
   * @services
   */
  protected $kernel, $redis;

  public function __construct(\AppKernel $kernel)
  {
    $this->kernel = $kernel;
    $this->redis = $kernel->getContainer()->get('snc_redis.default_client');
  }

  public function makeImageKey($id = "")
  {
    // todo: verify that key is correct to not delete all if *. It may happen, but most-likely not.
    return "image_id:" . str_replace("*", "", ($id == "" ? $this->id : $id));
  }

  /**
   * Get next image id
   * @return integer
   */
  public function getNextImageId()
  {
    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');
    return (int) $redis->get('image_id');
  }

  /**
   * Set link (where source image might be found)
   * @param string $link
   */
  public function setLink($link)
  {
    $this->link = $link;
  }

  /**
   * Set timestamp
   * @param integer $timestamp
   */
  public function setTimestamp($timestamp)
  {
    $this->timestamp = $timestamp;
  }

  /**
   * Set title
   * @param string $title
   */
  public function setTitle($title = "")
  {
    $this->title = CustomStrings::cleanTitle($title);
  }

  /**
   * Set slug from title
   * @param string $slug
   */
  public function setSlugFromTitle()
  {
    $this->slug = CustomStrings::slugify($this->title);
  }

  /**
   * Set slug from title
   * @param string $slug
   */
  public function setSourceType($sourceType)
  {
    $this->sourceType = $sourceType;
  }

  /**
   * Set final tags
   * @param array $tags
   */
  public function setTags($tags = array())
  {
    $this->tags = (array) $tags;
  }

  /**
   * Set original tags (found in source)
   * @param string $originalTags
   */
  public function setOriginalTags($originalTags = array())
  {
    $this->originalTags = (array) $originalTags;
  }

  /**
   * Get source data as array
   * @param array $source
   */
  public function sourceToArray()
  {
    $source["id"] = $this->id;
    $source["link"] = $this->link;
    $source["timestamp"] = $this->timestamp;
    $source["title"] = $this->title;
    $source["slug"] = $this->slug;

    $source["tags"] = ''; // empty at start
    $source["original_tags"] = (isset($this->originalTags) ? json_encode($this->originalTags) : '');

    return $source;
  }

  /**
   * Save source object
   * @return true or Exception
   */
  public function saveAsSource()
  {

    if (empty($this->link) || empty($this->timestamp) || empty($this->sourceType))
    {
      throw new \Exception("Source id, sourceType, sourceLink and timestamp can't be empty");
    }

    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');

    $this->id = $this->getNextImageId();
    $this->imageKey = $this->makeImageKey($this->id);

    //\D::dump($this->sourceToArray());

    // save temporary data
    $redis->hmset($this->imageKey, $this->sourceToArray());

    // keep index of indexed links (to keep them once)
    $redis->sadd('indexed_links', $this->link);

    // and save data about link to process
    $redis->zadd('link_list_to_process', $this->timestamp, $this->imageKey);

    // incr counter
    $redis->incr('image_id');

    return true;
  }

  /**
   * @todo: Make a black list of domains, if porn/nudes domain - return true
   */
  public function sourceDomainIsBlocked()
  {
    return false;
  }

}