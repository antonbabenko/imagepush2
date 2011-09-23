<?php

namespace Imagepush\ImagepushBundle\Services;

class Images
{
  
  public $kernel;
  
  public function __construct(\AppKernel $kernel) {
    
    $this->kernel = $kernel;
    
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
    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');
    return (int)$redis->get('image_id');
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
      $tags = $this->kernel->getContainer()->get('imagepush.tags');
      $fieldName .= ':'.$tags->getTagKey($tag);
    }

    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');
    $image_keys = $redis->zrevrangebyscore($fieldName, "+inf", "-inf", array("LIMIT" => array(0, $limit)));
    //\D::dump($image_keys);

    $images = array();
    
    if ($filterDislikedImages) {
      $image_keys = $this->filterDislikedImages($image_keys);
    }

    foreach ($image_keys as $key) {
      $image = $redis->hgetall($key);
      $images[] = $this->normalizeImage($image);
    }

    
    return $images;
    
  }
  
  /*
   * @return array()|false
   */
  public function getOneImage($id) {

    $key = $this->getImageKey($id);
    
    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');

    if ($redis->sismember('available_images', $key)) {
      $image = $redis->hgetall($key);
      $image = $this->normalizeImage($image);
      return $image;
    } else {
      return false;
    }

  }

  /*
   * @return array()|false
   */
  public function getImageByKey($key) {

    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');

    return $redis->hgetall($key);

  }

  /*
   * @return array()|false
   */
  public function getOneImageRelatedToTimestamp($direction, $timestamp) {

    if (!$timestamp || !in_array($direction, array("next", "prev"))) return false;
    
    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');
    
    if ($direction == "next") {
      $key = $redis->zrangebyscore('image_list', $timestamp, "+inf", array("LIMIT" => array(1, 1)));
    } else {
      $key = $redis->zrevrangebyscore('image_list', $timestamp, "-inf", array("LIMIT" => array(1, 1)));
    }
    
    if (!empty($key[0]))
    {
      $image = $redis->hgetall($key[0]);
    }

    return isset($image) ? $image : false;

  }

  /*
   * Filter out images current user disliked
   * @return array()
   */
  public function filterDislikedImages($image_keys = array()) {

    $user = $_SERVER["REMOTE_ADDR"];
    
    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');

    $disliked = $redis->smembers("user_dislikes:".$user);

    if (count($disliked)) {
      $image_keys = array_diff($image_keys, $disliked);
    }

    return $image_keys;

  }
  
  /////
  public static function getFileUrl($image, $format = "thumb") {

    if (empty($format) /*|| !array_key_exists($format, ImageManipulation::$thumb_types)*/) {
      throw new \ErrorException(sprintf("Unknown image format '%s'", $format));
    }

    if (!empty($image[$format."_file"])) {
      $img_src  = /*sfConfig::get("app_site_url").*/"/uploads/".$format."/".$image[$format."_file"];
    } elseif (!empty($image["thumb_src"])) {
      $img_src = $image["thumb_src"];
    } else {
      $img_src = "";
      //sfContext::getInstance()->getLogger()->err(sprintf("There is no data for image id: %d , format: %s", $image["id"], $format));
    }

    return $img_src;

  }

  /*
   * Save link as processed (with thumbs)
   */
  public function saveAsProcessed($key, $data)
  {

    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');

    // save final data
    $this->saveImageData($key, $data);

    // and save data about link to process
    $redis->zrem('link_list_to_process', $key);

    // remove link from in progress list
    $redis->srem('link_list_in_progress', $key);

    // save image to the list and make it available (was: image_list)
    $redis->zadd('upcoming_image_list', $data["timestamp"], $key);

    // was: saving to available_images, but correct -> upcoming_images
    $redis->sadd('upcoming_images', $key);

  }

  /**
   * Save image data for the key
   * @param string $key
   * @param array $data
   */
  public function saveImageData($key, $data)
  {

    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');
    
    $redis->hmset($key, $data);

  }
  
  /**
   * Save image tags for the key
   * @param string $key
   * @param array $tags
   */
  public function saveImageTags($key, $tags)
  {

    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');
    $tag = $this->kernel->getContainer()->get('imagepush.tags');
    
    $data = $redis->hgetall($key);
    $tags = $tag->getTagKeys($tags);
    $data["tags"] = json_encode($tags);
    
    //\D::dump($data);
    
    $this->saveImageData($key, $data);

  }
  
  /**
   * Remove key with all data completely
   */
  public function removeKey($key, $link="")
  {

    \D::dump($key);
    \D::dump($link);
    return;
    
    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');

    // remove data
    $redis->del($key);

    // remove link from set of indexed links
    if (!empty ($link)) {
      $redis->srem('indexed_links', $link);
    //} else {
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

    $this->removeUpcomingImageTags($key);

    // remove cached dom object
    $redis->del("cached_dom_".$key);

  }

  /**
   * Move tagged image from upcoming to available, or remove from upcoming only
   */
  public function removeUpcomingImageTags($key, $make_available = false) {

    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');

    // save final data
    $data = $redis->hgetall($key);
    //D::dump($data);

    $tags = @json_decode($data["tags"]);

    if (count($tags)) {
      foreach($tags as $tag_key) {
        $redis->zrem('upcoming_image_list:'.$tag_key, $key);

        if ($make_available) {
          $redis->zadd('image_list:'.$tag_key, $data["timestamp"], $key);
        }
      }
      
      if ($make_available) {
        $redis->sadd('available_images', $key);
      }
    }

  }

  /*
   * Verify that image has all required fields and define correct url fields
   */
  public function normalizeImage($image) {
    
    if (!count($image)) return false;

    $router = $this->kernel->getContainer()->get('router');
    $tags = $this->kernel->getContainer()->get('imagepush.tags');
    
    $image["_thumb_img"] = $this->getFileUrl($image, "thumb");
    $image["_main_img"] = $this->getFileUrl($image, "m");
    $image["_article_img"] = $this->getFileUrl($image, "a");
    $image["_tags"] = (isset($image["tags"]) && json_decode($image["tags"]) ? $tags->getHumanTags(json_decode($image["tags"])) : "");
    $image["_view_url"] = $router->generate('viewImage', array('id' => $image["id"], 'slug' => $image["slug"]));
    $image["_share_url"] = $router->generate('viewImage', array('id' => $image["id"], 'slug' => $image["slug"]), true);
    $image["_original_host"] = @parse_url($image["link"], PHP_URL_HOST);
    $image["_date"] = date(DATE_W3C, $image["timestamp"]);

    return $image;
    
  }
  
}