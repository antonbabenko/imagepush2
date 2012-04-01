<?php

namespace Imagepush\ImagepushBundle\Entity;

class ImagesManager
{
  
  public $router, $redis, $tagsManager;
  
  public function __construct($router, $redis, $tagsManager) {
    
    $this->router = $router;
    $this->redis = $redis;
    $this->tagsManager = $tagsManager;

  }
  
  /**
   * Get image key for db lookup
   * @return string
   */
  public function getImageKey($id = "")
  {
    return "image_id:" . ($id == "" ? $this->getImageId() : $id);
  }
  
  /**
   * Get next image id
   * @return integer
   */
  public function getImageId()
  {
    return (int)$this->redis->get('image_id');
  }
  
  /*
   * @return array()
   */
  public function getImages($type, $limit = 20, $params = array()) {
    
    if (!in_array($type, array("current", "upcoming"))) {
      throw new \ErrorException(sprintf("Incorrect image type: %s", $type));
    }
    
    if (!is_array($params)) {
      throw new \ErrorException(sprintf("Params should be an array, but %s given", gettype($params)));
    }
    
    if ($type == "current") {
      $fieldName = "image_list";
      $filterDislikedImages = false;
    } else {
      $fieldName = "upcoming_image_list";
      $filterDislikedImages = true;
    }
    
    extract($params);

    if (isset($tag)) {
      $fieldName .= ':'.$this->tagsManager->getTagKey($tag);
    }

    $image_keys = $this->redis->zrevrangebyscore($fieldName, "+inf", "-inf", array("LIMIT" => array(0, $limit)));
    //\D::dump($image_keys);

    $images = array();
    
    if ($filterDislikedImages) {
      $image_keys = $this->filterDislikedImages($image_keys);
    }

    foreach ($image_keys as $key) {
      $image = $this->redis->hgetall($key);
      $images[] = $this->normalizeImage($image);
    }

    
    return $images;
    
  }
  
  /*
   * @return array()|false
   */
  public function getOneImage($id) {

    $key = $this->getImageKey($id);
    
    if ($this->redis->sismember('available_images', $key)) {
      $image = $this->redis->hgetall($key);
      return $this->normalizeImage($image);
    } else {
      return false;
    }

  }

  /*
   * @return array()|false
   */
  public function getImageByKey($key) {

    return $this->redis->hgetall($key);

  }

  /*
   * @return array()|false
   */
  public function getOneImageRelatedToTimestamp($direction, $timestamp) {

    if (!$timestamp || !in_array($direction, array("next", "prev"))) return false;
    
    if ($direction == "next") {
      $key = $this->redis->zrangebyscore('image_list', $timestamp, "+inf", array("LIMIT" => array(1, 1)));
    } else {
      $key = $this->redis->zrevrangebyscore('image_list', $timestamp, "-inf", array("LIMIT" => array(1, 1)));
    }
    
    if (!empty($key[0]))
    {
      $image = $this->redis->hgetall($key[0]);
    }

    return isset($image) ? $image : false;

  }

  /*
   * Filter out images current user disliked
   * @return array()
   */
  public function filterDislikedImages($image_keys = array()) {

    $user = $_SERVER["REMOTE_ADDR"];
    
    $disliked = $this->redis->smembers("user_dislikes:".$user);

    if (count($disliked)) {
      $image_keys = array_diff($image_keys, $disliked);
    }

    return $image_keys;

  }
  
  /////
  public static function getFileUrl($image, $format) {

    if (empty($format)) {
      throw new \ErrorException(sprintf("Unknown image format '%s'", $format));
    }
    
    $prefix = "/uploads";
    
    if (!empty($image["file"])) {
      
      $url = $prefix."/".$format."/".$image["file"];
      
    } elseif (!empty($image[$format."_file"])) { // old data in db
      
      $url = $prefix."/".$format."/".$image[$format."_file"];
      
    } elseif ($format == "t" && !empty($image["thumb_file"])) { // folder "thumb" to be renamed to "t"
      
      $url = $prefix."/t/".$image["thumb_file"];
      
    } else {
      $url = "";
    }

    return $url;

  }

  /*
   * Verify that image has all required fields and define correct url fields
   */
  public function normalizeImage($image) {
    
    if (!count($image)) return false;
    
    $image["_thumb_img"] = $this->getFileUrl($image, "t"); // thumb
    $image["_main_img"] = $this->getFileUrl($image, "m"); // main
    $image["_article_img"] = $this->getFileUrl($image, "a"); // article
    $image["_tags"] = (isset($image["tags"]) && json_decode($image["tags"]) ? $this->tagsManager->getHumanTags(json_decode($image["tags"])) : "");
    
    if (!empty($image["id"]) || !empty($image["slug"])) {
        $image["_view_url"] = $this->router->generate('viewImage', array('id' => $image["id"], 'slug' => $image["slug"]));
        $image["_share_url"] = $this->router->generate('viewImage', array('id' => $image["id"], 'slug' => $image["slug"]), true);
    }
    
    $image["_original_host"] = @parse_url($image["link"], PHP_URL_HOST);
    $image["_date"] = date(DATE_W3C, $image["timestamp"]);

    //\D::dump($image);

    return $image;
    
  }
  
}