<?php

namespace Imagepush\ImagepushBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class FrontController extends Controller
{

  /**
   * @Route("/", name="index")
   * @Template()
   */
  public function indexAction()
  {
    $images = $this->get('imagepush.images')->getImages("current", 7);

    return array("images" => $images);
  }

  /**
   * @Route("/upcoming", name="viewUpcoming")
   * @Template()
   */
  public function viewUpcomingAction()
  {
    $response = $this->forward('ImagepushBundle:Front:viewMultiple', array('tag' => null, 'type' => 'upcoming'));

    return $response;
  }

  /**
   * @Route("/tag/{tag}/upcoming", name="viewUpcomingByTag")
   * @Template()
   */
  public function viewUpcomingByTagAction($tag)
  {
    $response = $this->forward('ImagepushBundle:Front:viewMultiple', array('tag' => $tag, 'type' => 'upcoming'));

    return $response;
  }

  /**
   * @Route("/tag/{tag}", name="viewByTag")
   * @Template()
   */
  public function viewByTagAction($tag)
  {
    $response = $this->forward('ImagepushBundle:Front:viewMultiple', array('tag' => $tag, 'type' => 'current'));

    return $response;
  }

  /**
   * Universal function to show images by tags/ by type (upcoming/current)
   * @Template()
   */
  public function viewMultipleAction($tag, $type)
  {

    //\D::dump($tag);
    //return array();

    $params = array();

    $redis = $this->get('snc_redis.default_client');

    $another_page_type_count = 0;

    if (!is_null($tag))
    {
      $params = array("tag" => $tag);

      $tag_key = $this->get('imagepush.tags')->getTagKey($tag);

      // if tag has been ever created
      $count = $redis->zscore("tag_usage", $tag_key);
      if (!$count)
      {
        $this->createNotFoundException(sprintf('There are no images to show by tag: %s', $tag));
      }

      // if there are images to show in opposite page_type (f.eg: show "upcoming" link when view "current" page).
      if ($type == "current")
      {
        $tag_set = "upcoming_image_list:" . $tag_key;
      } else
      {
        $tag_set = "image_list:" . $tag_key;
      }

      $another_page_type_count = $redis->zcard($tag_set); //, $tag_key);
    }

    $images = $this->get('imagepush.images')->getImages($type, 30, $params);

    //\D::dump($images);
    
    //$share_url = $this->get('request')->getUri();

    return array(
      "images" => $images,
      "type" => $type,
      "tag" => $tag,
      "another_page_type_count" => $another_page_type_count);
      //"share_url" => $share_url);
  }

  /**
   * @Route("/i/{id}/{slug}", name="viewImage")
   * @Template()
   */
  public function viewImageAction($id)
  {
    $image = $this->get('imagepush.images')->getOneImage($id);

    if (!$image)
    {
      $this->createNotFoundException('Image doesn\'t exist');
    }

    $next_image = $this->get('imagepush.images')->getOneImageRelatedToTimestamp("next", $image["timestamp"]);
    $prev_image = $this->get('imagepush.images')->getOneImageRelatedToTimestamp("prev", $image["timestamp"]);

    return array("image" => $image, "next_image" => $next_image, "prev_image" => $prev_image);
  }

  /**
   * @Route("/about", name="about")
   * @Template()
   */
  public function aboutAction()
  {
    return array();
  }

  /**
   * RSS feed (only in RSS2 format)
   *
   * @Route("/rss{version}", name="rss", defaults={"version"=""}, requirements={"version"="|2"}))
   */
  public function rssAction($version)
  {
    
    $images = $this->get('imagepush.images')->getImages("current", 20);

    // MAMP 2.0.1 fails on "iconv_strlen", so this function is not ready yet!!!
    // For a while I have commented lines around line 622 in /Users/Bob/Sites/imagepush2/vendor/zendframework2/library/Zend/Validator/Hostname.php
    // This changes will go away when vendors install...
    // Fail case ====> echo iconv_strlen($str, "UTF-8"); die();

    $feed = new \Zend\Feed\Writer\Feed();
    
    if (count($images))
    {
/*
      if ($feed_format == "RSS2") {
        $this->feed = new sfRss201Feed();
      } else {
        $this->feed = new sfRss10Feed();
      }*/
      //$feed->setType("rss");

      $feed->setTitle("Imagepush.to - Best images hourly");
      $feed->addAuthor("Anton Babenko");
      $feed->setLanguage("en");
      $feed->setDescription("Best images hourly");
      $feed->setGenerator("Manually");
      
      $feed->setLink('http://imagepush.com');
      $feed->setDateModified($images[0]["timestamp"]);

      foreach ($images as $image) {
        $entry = new \Zend\Feed\Writer\Entry();
        $entry->setTitle($image["title"]);
        $entry->setLink($image["_share_url"]);
        //$entry->setAuthor($image["link"]);
        $entry->setId($image["_share_url"]);

        if (count($image["_tags"])) {
          foreach ($image["_tags"] as $tag) {
            $entry->addCategory(array("term" => $tag));
          }
        }

        $entry->setDateCreated($image["timestamp"]);

        //$img_src = Images::getFileUrl($image, "m");
        $enclosure["uri"] = 'http://imagepush.to' . $image["_main_img"];
        //if ($file = sfConfig::get("sf_upload_dir") . "/m/" . $image["m_file"]) {
          $enclosure["length"] = 1;//@filesize($file);
        //}
        $enclosure["type"] = $image["m_content_type"];

        $entry->setEnclosure($enclosure);

        $entry->setDescription('<a href="'.$image["_share_url"].'"><img src="http://imagepush.to' . $image["_main_img"] . '" alt="'.str_replace('"', '\"', $image["title"]).'" border="0" width="'.$image["m_width"].'" height="'.$image["m_height"].'" /></a>');

        $feed->addEntry($entry);
      }
    }
    
    return new Response($feed->export("rss"));
  }

  /**
   * Display top box with trending tags
   * 
   * @Template()
   */
  public function _trendingNowAction($max = 20)
  {
    $tags = $this->get('imagepush.tags')->getLatestTrends($max);
    //\D::dump($tags);

    return array("tags" => $tags);
  }

  /**
   * Display comment box
   * 
   * @Template()
   */
  public function _commentsAction($href)
  {
    return array("href" => $href);
  }

  /**
   * Display thumb box
   * 
   * @Template()
   */
  public function _thumbBoxAction($_tags = array(), $skip_image_id = false)
  {

    //\D::dump($_tags);
    if (count($_tags))
    {
      $tags = $_tags;
      $group_by_tags = false;
    } else
    {
      $tags = $this->get('imagepush.tags')->getLatestTrends(100);
      $group_by_tags = true;
    }

    if (!count($tags))
    {
      return;
    }

    $total_images = 0;
    $all_images = $used_images = array();

    // skip main image
    if (isset($skip_image_id))
    {
      $used_images[] = $skip_image_id;
    }

    foreach ($tags as $tag) {

      if (count($all_images) >= 10)
        break;

      $tag_images = array();

      $images = $this->get('imagepush.images')->getImages('current', 20, array("tag" => $tag));

      //\D::dump($images);
      if (count($images) >= 2) // 4
      {
        // make sure that each image is shown just once in all tags, if image belongs to multiple tags
        foreach ($images as $image) {

          if (count($tag_images) == 4)
            break;

          if (!in_array($image["id"], $used_images))
          {
            $tag_images[] = $image;
            $used_images[] = $image["id"];
          }
        }

        if (count($tag_images) >= 3)
        {
          $all_images[] = array("tag" => $tag, "images" => $tag_images);
        }

        $total_images += count($tag_images);
      }
    }

    // Images related to other images by tags are not grouped
    if (!$group_by_tags && count($all_images))
    {
      $all_images_list = $used_tags = array();
      foreach ($all_images as $images) {
        $used_tags[] = $images["tag"];
        $all_images_list = array_merge($all_images_list, $images["images"]);
      }
      unset($all_images);
      $all_images[] = array("used_tags" => $used_tags, "images" => $all_images_list);
    }

    //\D::dump($all_images);

    return array("all_images" => $all_images, "_tags" => $_tags, "skip_image_id" => $skip_image_id);
  }

}
