<?php

namespace Imagepush\ImagepushBundle\Model;

use Imagepush\ImagepushBundle\External\CustomStrings;
use Imagepush\ImagepushBundle\Services\Processor\Config;

/**
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
   * @optional - thumb images (main, thumb, article). Width and height
   */
  public $file;
  public $mWidth, $mHeight, $tWidth, $tHeight, $aWidth, $aHeight;

  /**
   * @optional
   */
  public $title = "";
  public $slug = "";
  public $sourceType = "";
  public $sourceTags = array();
  public $tags = array();
  public $allTags = array();

  /**
   * @services
   */
  protected $kernel, $redis, $tagsManager;

  public function __construct(\AppKernel $kernel)
  {
    $this->kernel = $kernel;
    $this->redis = $kernel->getContainer()->get('snc_redis.default_client');
    $this->tagsManager = $kernel->getContainer()->get('imagepush.tags.manager');
  }

  public function makeImageKey($id = "")
  {
    // todo: verify that key is correct to not delete all if *. It may happen, but most-likely not.
    return "image_id:" . str_replace("*", "", ($id == "" ? $this->id : $id));
  }

  /**
   * Get next image id
   */
  public function getNextImageId()
  {
    return (int) $this->redis->get('image_id');
  }

  /**
   * Set link (where source image might be found)
   */
  public function setLink($link)
  {
    $this->link = $link;
  }

  /**
   * Set timestamp
   */
  public function setTimestamp($timestamp)
  {
    $this->timestamp = $timestamp;
  }

  /**
   * Set title
   */
  public function setTitle($title = "")
  {
    $this->title = CustomStrings::cleanTitle($title);
  }

  /**
   * Set slug from title
   */
  public function setSlugFromTitle()
  {
    $this->slug = CustomStrings::slugify($this->title);
  }

  /**
   * Set slug from title
   */
  public function setSourceType($sourceType)
  {
    $this->sourceType = $sourceType;
  }

  /**
   * Set final tags
   */
  public function setTags($tags = array())
  {
    $this->tags = (array) $tags;
  }

  /**
   * Set source tags. Convert tag to tag_key.
   */
  public function setSourceTags($sourceTags = array())
  {
    $this->sourceTags = $this->tagsManager->verifyTags($sourceTags);
  }

  /**
   * Save source object
   * @return true or Exception
   */
  public function saveAsSource()
  {

    if (empty($this->link) || empty($this->timestamp) || empty($this->sourceType))
    {
      throw new \Exception("Source id, sourceType, link and timestamp can't be empty");
    }

    $redis = $this->redis;

    $this->id = $this->getNextImageId();
    $this->imageKey = $this->makeImageKey($this->id);

    //\D::dump($this->sourceToArray());

    // save temporary data
    $redis->hmset($this->imageKey, $this->toArray());

    // keep index of indexed links (to keep them once)
    $redis->sadd('indexed_links', $this->link);

    // and save data about link to process
    $redis->zadd('link_list_to_process', $this->timestamp, $this->imageKey);

    // incr counter
    $redis->incr('image_id');

    return true;
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
    
    
    if (isset($data["file"])) { // new filename
      $this->file = $data["file"];
    } elseif (isset($data["m_file"])) { // old filename was here
      $this->file = $data["m_file"];
    } else {
      $this->file = '';
    }
    
    $this->mW = (isset($data["m_width"]) ? $data["m_width"] : '');
    $this->mH = (isset($data["m_height"]) ? $data["m_height"] : '');
    $this->tW = (isset($data["t_width"]) ? $data["t_width"] : '');
    $this->tH = (isset($data["t_height"]) ? $data["t_height"] : '');
    $this->aW = (isset($data["a_width"]) ? $data["a_width"] : '');
    $this->aH = (isset($data["a_height"]) ? $data["a_height"] : '');
    
    $this->sourceType = (isset($data["source_type"]) ? $data["source_type"] : '');
    $this->sourceTags = (isset($data["source_tags"]) && json_decode($data["source_tags"], true) ? json_decode($data["source_tags"], true) : array());
    $this->tags = (isset($data["tags"]) && json_decode($data["tags"], true) ? json_decode($data["tags"], true) : array());
    $this->allTags = (isset($data["all_tags"]) && json_decode($data["all_tags"], true) ? json_decode($data["all_tags"], true) : array());
    
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
    
    $result["file"] = $this->file;
    $result["m_width"] = $this->mWidth;
    $result["m_height"] = $this->mHeight;
    $result["t_width"] = $this->tWidth;
    $result["t_height"] = $this->tHeight;
    $result["a_width"] = $this->aWidth;
    $result["a_height"] = $this->aHeight;
    
    $result["source_type"] = $this->sourceType;
    $result["source_tags"] = json_encode((array)$this->sourceTags);
    $result["tags"] = json_encode((array)$this->tags);
    $result["all_tags"] = json_encode((array)$this->allTags);
    
    return $result;
    
  }
  
  /**
   * @todo: Make a black list of domains, if porn/nudes domain - return true
   */
  public function sourceDomainIsBlocked()
  {
    return false;
  }

}