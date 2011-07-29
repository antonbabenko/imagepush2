<?php

namespace Imagepush\ImagepushBundle\Entity;

/**
 * Imagepush\ImagepushBundle\Entity\Image
 */
class Image
{

  /**
   * @var integer $id
   */
  private $id;
  
  /**
   * @var string $name
   */
  private $name;

  /**
   * @return string $name
   */
  public function __toString()
  {
    return $this->name;
  }

  /**
   * Get id
   *
   * @return integer $id
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set name
   *
   * @param string $name
   */
  public function setName($name)
  {
    $this->name = $name;
  }

  /**
   * Get name
   *
   * @return string $name
   */
  public function getName()
  {
    return $this->name;
  }

  /*
   * Verify that image has all required fields and define correct url fields
   */
  public static function normalizeImage($image) {
    
    if (!count($image)) return false;

    // todo: make all these
    /* sample:
     $uri = $this->get('router')->generate(
    'admin_testimonial', 
    array('id' => $testimonial->getId())
);*/
    $image["_thumb_img"] = ''; //self::getFileUrl($image, "thumb");
    $image["_main_img"] = ''; //self::getFileUrl($image, "m");
    $image["_article_img"] = ''; //self::getFileUrl($image, "a");
    $image["_tags"] = ''; //(isset($image["tags"]) && json_decode($image["tags"]) ? Tags::getHumanTags(json_decode($image["tags"])) : "");
    $image["_view_url"] = ''; //sfContext::getInstance()->getRouting()->generate("view_image", array("id" => $image["id"], "slug" => $image["slug"]));
    $image["_share_url"] = ''; //sfContext::getInstance()->getRouting()->generate("view_image", array("id" => $image["id"], "slug" => $image["slug"]), true);
  

    return $image;
    
  }


}