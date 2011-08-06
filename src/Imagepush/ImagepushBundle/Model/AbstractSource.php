<?php

namespace Imagepush\ImagepushBundle\Model;

use Imagepush\ImagepushBundle\External\CustomStrings;

/**
 * Imagepush\ImagepushBundle\Model\AbstractSource
 * 
 * This class describes setters and getters for sources fetched.
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
  
  /**
   * @services
   */
  public $router, $redis, $images, $tags, $logger;
  
  /*
   * @string
   */
  public $sourceType;

  /*
   * @params array $allServices
   */
  public function __construct($allServices) {
    $this->router = $allServices["redis"];
    $this->redis = $allServices["redis"];
    $this->images = $allServices["images"];
    $this->tags = $allServices["tags"];
    $this->logger = $allServices["logger"];
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
    
    $pipe = $this->redis->pipeline();
    
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

}