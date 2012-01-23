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
    $dm = $this->get('doctrine.odm.mongodb.document_manager');

    $images = $dm
      ->getRepository('ImagepushBundle:Image')
      ->findImages("current", 7);

    return array("images" => array_values($images));
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

    $dm = $this->get('doctrine.odm.mongodb.document_manager');

    $params = array();

    $isOppositeTypeExists = false;

    if (!is_null($tag))
    {
      $params = array("tag" => $tag);

      $tagObject = $dm->createQueryBuilder('ImagepushBundle:Tag')
        ->field('text')->equals($tag)
        ->getQuery()
        ->getSingleResult();
      
      if (!$tagObject)
      {
        throw new NotFoundHttpException(sprintf('There are no %s images to show by tag: %s', $type, $tag));
      }

      //$typeField = 'getUsedIn' . ($type == "current" ? "Available" : "Upcoming");
      // && $tagObject->{$typeField}() > 0))
      
      // Opposite type field has number of images in each tag, so we can show or hide the opposite type link
      $oppositeTypeField = 'getUsedIn' . ($type !== "current" ? "Available" : "Upcoming");

      $isOppositeTypeExists = (bool) $tagObject->{$oppositeTypeField}();
    }

    $images = $dm
      ->getRepository('ImagepushBundle:Image')
      ->findImages($type, 30, $params);

    //\D::dump($images);

    return array(
      "type" => $type,
      "tag" => $tag,
      "images" => $images,
      "isOppositeTypeExists" => $isOppositeTypeExists
    );
  }

  /**
   * @Route("/i/{id}/{slug}", requirements={"id"="\d+", "slug"=".*"}, name="viewImage")
   * @Template()
   */
  public function viewImageAction($id)
  {
    $dm = $this->get('doctrine.odm.mongodb.document_manager');

    $image = $dm
      ->getRepository('ImagepushBundle:Image')
      ->findOneBy(array("id" => (int) $id, "isAvailable" => true));

    //\D::dump($id);
    //\D::dump($image);

    if (!$image)
    {
      throw new NotFoundHttpException('Image doesn\'t exist');
    }

    $nextImage = $dm
      ->getRepository('ImagepushBundle:Image')
      ->getOneImageRelatedToTimestamp("next", $image->getTimestamp());

    $prevImage = $dm
      ->getRepository('ImagepushBundle:Image')
      ->getOneImageRelatedToTimestamp("prev", $image->getTimestamp());

    return array("image" => $image, "nextImage" => $nextImage, "prevImage" => $prevImage);
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
   * @Route("/rss{version}", name="rssFeed", defaults={"version"=""}, requirements={"version"="|2"}))
   */
  public function rssAction($version)
  {
    $dm = $this->get('doctrine.odm.mongodb.document_manager');

    $images = $dm
      ->getRepository('ImagepushBundle:Image')
      ->findImages("current", 20);
    
    $factory = $this->get('nekland_feed.factory');
    
    // "post" is the name of the feed in the configuration
    //\D::dump($factory);
    
    //$get = $factory->get('images_feed'); 
    //\D::dump($get);
    
    //$factory->load('images_feed', 'loader');

foreach (array_values($images) as $image) {
$factory->get('images_feed')->add(new \Imagepush\ImagepushBundle\Document\ImageFeedItem($image));
}

// For this example we are using rss_file but this is the default value, and if you extend the bundle you can use another loader
    $output = $factory->render('images_feed'); // we want to render the feed into rss format but you can use the atom format
    
    \D::dump($output);
    die();
    $response = new Response($output);
    
    return $response;

  }
  /**
   * RSS feed (only in RSS2 format)
   *
   * @Route("/oldrss{version}", name="rss", defaults={"version"=""}, requirements={"version"="|2"}))
   */
  public function oldrssAction($version)
  {

    
    $images = $this->get('imagepush.images.manager')->getImages("current", 20);

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
        } */
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

        if (count($image["_tags"]))
        {
          foreach ($image["_tags"] as $tag) {
            $entry->addCategory(array("term" => $tag));
          }
        }

        $entry->setDateCreated($image["timestamp"]);

        //$img_src = Images::getFileUrl($image, "m");
        $enclosure["uri"] = 'http://imagepush.to' . $image["_main_img"];
        //if ($file = sfConfig::get("sf_upload_dir") . "/m/" . $image["m_file"]) {
        $enclosure["length"] = 1; //@filesize($file);
        //}
        $enclosure["type"] = $image["m_content_type"];

        $entry->setEnclosure($enclosure);

        $entry->setDescription('<a href="' . $image["_share_url"] . '"><img src="http://imagepush.to' . $image["_main_img"] . '" alt="' . str_replace('"', '\"', $image["title"]) . '" border="0" width="' . $image["m_width"] . '" height="' . $image["m_height"] . '" /></a>');

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
    $dm = $this->get('doctrine.odm.mongodb.document_manager');

    $tags = $dm
      ->getRepository('ImagepushBundle:LatestTag')
      ->getLatestTrends($max);

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
  public function _thumbBoxAction($initialTags = array(), $skipImageId = false, $withAd = false)
  {

    $dm = $this->get('doctrine.odm.mongodb.document_manager');

    //\D::dump($initialTags);
    if (count($initialTags))
    {
      $tags = $initialTags;
      $groupTags = false;
      $maxImages = 16;
    } else
    {
      $tags = $dm
        ->getRepository('ImagepushBundle:LatestTag')
        ->getLatestTrends(100);
      //\D::dump($tags);

      if (!count($tags))
      {
        return;
      }

      $tags = array_flip($tags);

      $groupTags = true;
      $maxImages = 4;
    }


    $allImages = $usedImages = array();
    $totalImages = 0;

    // skip main image
    if (!empty($skipImageId))
    {
      $usedImages[] = $skipImageId;
    }

    foreach ($tags as $tagId => $tag) {

      $tagImages = $foundTags = array();

      if (count($allImages) >= 10)
        break;

      // make just one search, if thumbs will be shown in one merged box
      if (!$groupTags)
      {
        $lookupTags = $tags;
      } else
      {
        $lookupTags = array($tag);
      }

      $images = $dm
        ->getRepository('ImagepushBundle:Image')
        ->findImages("current", 10*count($lookupTags), array("tag" => $lookupTags));
      
      if (count($images) >= 3)
      {
        // make sure that each image is shown just once in all tags, if image belongs to multiple tags
        foreach (array_values($images) as $image) {

          if (count($tagImages) == $maxImages)
            break;

          if (!in_array($image->getId(), $usedImages))
          {
            $tagImages[] = $image;
            $usedImages[] = $image->getId();
            $foundTags = array_merge($foundTags, $image->getTags());
          }
        }
        //\D::dump($tagImages);
        //\D::dump($foundTags);

        if (count($tagImages) >= 3)
        {
          $foundTags = array_count_values($foundTags);
          arsort($foundTags);
          $foundTags = array_slice(array_flip($foundTags), 0, 5);

          $allImages[] = array("tag" => $foundTags, "images" => $tagImages);

          $totalImages += count($tagImages);
        }
      }

      // Break the loop, if group of images received
      if (!$groupTags)
      {
        break;
      }
    }
    
    // Images related to other images by tags are not grouped
    if (!$groupTags && count($allImages))
    {
      $allImagesList = $usedTags = array();
      foreach ($allImages as $images) {
        $usedTags = $images["tag"];
        $allImagesList = array_merge($allImagesList, $images["images"]);
      }
      unset($allImages);
      $allImages[] = array("usedTags" => $usedTags, "images" => $allImagesList);
    }

    //\D::dump($allImages);
    //\D::dump($withAd);
    //\D::dump($initialTags);

    return array(
      "allImages" => $allImages,
      "initialTags" => $initialTags,
      "skipImageId" => $skipImageId,
      "withAd" => $withAd,
      "bannerPlacement" => mt_rand(0, $totalImages - 1));
  }

}
