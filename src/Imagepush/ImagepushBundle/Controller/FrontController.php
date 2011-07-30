<?php

namespace Imagepush\ImagepushBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
    $images = $this->get('imagepush.images')->getCurrentImages(7);
    
    return array("images" => $images);
  }

  /**
   * @Route("/upcoming", name="viewUpcoming")
   * @Template()
   */
  public function viewUpcomingAction()
  {
    return array("images" => $images);
  }

  /**
   * @Route("/upcoming/{tag}", name="viewUpcomingByTag")
   * @Template()
   */
  public function viewUpcomingByTagAction()
  {
    return array("images" => $images);
  }

  /**
   * @Route("/tag/{tag}", name="viewByTag")
   * @Template()
   */
  public function viewByTagAction($tag)
  {
    $response = $this->forward('ImagepushBundle:Front:viewMultiple', array('tag' => $tag));
    
    return $response;
  }
  
  /**
   * @Template()
   */
  public function viewMultipleAction($tag, $type = "current")
  {
    
    //\D::dump($tag);
    //return array();
    
    $params = array();

    //$this->tag = $request->getParameter("tag");
    //$this->page_type = $request->getParameter("type", "current");
    $redis = $this->get('snc_redis.default_client');

    $another_page_type_count = 0;

    if (!is_null($tag)) {
      $params = array("tag" => $tag);

      $tag_key = $this->get('imagepush.tags')->getTagKey($tag);

      // if tag has been ever created
      $count = $redis->zscore("tag_usage", $tag_key);
      if (!$count) {
        $this->createNotFoundException(sprintf('There are no images to show by tag: %s', $tag));
      }

      // if there are images to show in opposite page_type (f.eg: show "upcoming" link when view "current" page).
      if ($type == "current") {
        $tag_set = "upcoming_image_list:".$tag_key;
      } else {
        $tag_set = "image_list:".$tag_key;
      }

      $another_page_type_count = $redis->zcard($tag_set);//, $tag_key);
      
    }

    if ($type == "current") {
      $images = $this->get('imagepush.images')->getCurrentImages(30, $params);
    } else {
      $images = $this->get('imagepush.images')->getUpcomingImages(30, $params);
    }
    //\D::dump($images);
    
    return array("images" => $images, "type" => $type, "tag" => $tag, "another_page_type_count" => $another_page_type_count);
  }

  /**
   * @Route("/i/{id}/{slug}", name="viewImage")
   * @Template()
   */
  public function viewImageAction($id)
  {
    $image = $this->get('imagepush.images')->getOneImage($id);

    if (!$image) {
      $this->createNotFoundException('Image doesn\'t exist');
    }

    $next_image = $this->get('imagepush.images')->getOneImageRelatedToTimestamp("next", $image["timestamp"]);
    $prev_image = $this->get('imagepush.images')->getOneImageRelatedToTimestamp("prev", $image["timestamp"]);

    return array("image" => $image, "next_image" => $next_image, "prev_image" => $prev_image);
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
    
    if (count($_tags)) {
      $tags = $_tags;
      $group_by_tags = false;
    } else {
      $tags = $this->get('imagepush.tags')->getLatestTrends(100);
      $group_by_tags = true;
    }

    if (!count($tags)) {
      return;
    }

    $total_images = 0;
    $all_images = $used_images = array();

    // skip main image
    if (isset($skip_image_id)) {
      $used_images[] = $skip_image_id;
    }

    foreach ($tags as $tag) {

      if (count($all_images) >= 10) break;

      $tag_images = array();

      $images = $this->get('imagepush.images')->getCurrentImages(20, array("tag" => $tag));

      //\D::dump($images);
      if (count($images) >= 2) // 4
      {
        // make sure that each image is shown just once in all tags, if image belongs to multiple tags
        foreach ($images as $image) {

          if (count($tag_images) == 4) break;

          if (!in_array($image["id"], $used_images)) {
            $tag_images[] = $image;
            $used_images[] = $image["id"];
          }
        }

        if (count($tag_images) >= 3) {
          $all_images[] = array("tag" => $tag, "images" => $tag_images);
        }

        $total_images += count($tag_images);
      }
    }
    
    if (!$group_by_tags && count($all_images)) {
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
