<?php

namespace Imagepush\ImagepushBundle\Services;

class Images
{
  
  private $router;
  private $redis;
  private $tags;
  
  public function __construct(\AppKernel $kernel) {
    
    //\D::dump($this->getServiceIds());
    
    $this->router = $kernel->getContainer()->get('router');
    $this->redis = $kernel->getContainer()->get('snc_redis.default_client');
    $this->tags = $kernel->getContainer()->get('imagepush.tags');
    
  }
  
  public function getImageKey($id = "")
  {
    return "image_id:" . ($id == "" ? $this->getImageId() : $id);
  }
  
  public function getImageId()
  {
    return (int)$this->redis->get('image_id');
  }

  public function getCurrentImages($limit = 20, $params = array()) {

    extract($params);

    $tag_key = (isset($tag) ? $this->tags->getTagKey($tag) : '');

    $image_keys = $this->redis->zrevrangebyscore('image_list'.($tag_key ? ":".$tag_key : ""), "+inf", "-inf", array("LIMIT" => array(0, $limit)));
    //\D::dump($image_keys);

    $images = array();
    
    foreach ($image_keys as $key) {
      $image = $this->redis->hgetall($key);
      $images[] = $this->normalizeImage($image);
    }

    return $images;

  }
  
  public function getOneImage($id) {

    $key = $this->getImageKey($id);

    if ($this->redis->sismember('available_images', $key)) {
      $image = $this->redis->hgetall($key);
      $image = $this->normalizeImage($image);
      return $image;
    } else {
      return false;
    }

  }

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
   * Verify that image has all required fields and define correct url fields
   */
  public function normalizeImage($image) {
    
    if (!count($image)) return false;

    $image["_thumb_img"] = $this->getFileUrl($image, "thumb");
    $image["_main_img"] = $this->getFileUrl($image, "m");
    $image["_article_img"] = $this->getFileUrl($image, "a");
    $image["_tags"] = (isset($image["tags"]) && json_decode($image["tags"]) ? $this->tags->getHumanTags(json_decode($image["tags"])) : "");
    $image["_view_url"] = $this->router->generate('viewImage', array('id' => $image["id"], 'slug' => $image["slug"]));
    $image["_share_url"] = $this->router->generate('viewImage', array('id' => $image["id"], 'slug' => $image["slug"]), true);
    $image["_original_host"] = @parse_url($image["link"], PHP_URL_HOST);
    $image["_date"] = date(DATE_W3C, $image["timestamp"]);

    return $image;
    
  }

}