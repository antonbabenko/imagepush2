<?php

namespace Imagepush\ImagepushBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Imagepush\ImagepushBundle\Entity\Image as RedisImage;
use Imagepush\ImagepushBundle\Document\Image;
use Imagepush\ImagepushBundle\Document\Tag;
use Imagepush\ImagepushBundle\Document\LatestTag;

class ImportController extends Controller
{

  /**
   * @Route("/", name="importIndex")
   * @Template("::base.html.twig")
   */
  public function indexAction()
  {
    $result = $this->importTags();
    $result = $this->importLatestTags();
    $result = $this->importImages();
    
    echo "All done :)";

    return array();
  }

  private function importTags()
  {

    $dm = $this->get('doctrine.odm.mongodb.document_manager');
    $redis = $this->get('snc_redis.default_client');
    $i = 0;

    $dm->getDocumentCollection("ImagepushBundle:Tag")->drop();

    $tags = $this->get('imagepush.tags.manager')->getAllHumanTagsWithIds();
    
    //\D::dump($tags);
    $importedTags = array();
    if (count($tags))
    {
      foreach ($tags as $legacyKey => $tag) {
        $text = $tag;
        
        // replace
        if (strstr($legacyKey, "_replace")) {
          //\D::dump($redis->get(str_replace("_replace", "",$legacyKey)));
          //\D::dump($redis->get($text));
          $legacyKey = $tag;
          $text = $redis->get($tag);
        }
        
        // replace newline
        $text = str_replace("\n", " ", $text);
        
        if (!empty($text) && !in_array($text, $importedTags))
        {
          $importedTags[] = $text;
        } else {
          continue;
        }
        
        $new = new Tag();
        $new->setLegacyKey($legacyKey);
        $new->setText($text);
        
        //$count = $redis->zscore("tag_usage", $legacyKey);
        //$availableCount = $redis->zcard("image_list:". $legacyKey);
        //$upcomingCount = $redis->zcard("upcoming_image_list:". $legacyKey);
        
        //$new->setUsedInAvailable((int)$availableCount);
        //$new->setUsedInUpcoming((int)$upcomingCount);

        $dm->persist($new);

        if (++$i % 100 == 0)
        {
          $dm->flush();
          $dm->clear();
        }
      }

      $dm->flush();
      $dm->clear();
    }
  }

  private function importImages($limit = 9999999)
  {

    $dm = $this->get('doctrine.odm.mongodb.document_manager');
    $i = 0;

    $dm->getDocumentCollection("ImagepushBundle:Image")->drop();

    $allImages = array(
      "current" => $this->get('imagepush.images.manager')->getImages("current", $limit),
      "upcoming" => $this->get('imagepush.images.manager')->getImages("upcoming", $limit)
    );

    //\D::dump($allImages);

    foreach ($allImages as $imageGroup => $images) {

      if (count($images))
      {
        foreach ($images as $image) {
          $new = new Image();
          $new->setIsAvailable($imageGroup == "current");
          $new->setId($image["id"]);
          $new->setLink($image["link"]);
          $new->setTitle($image["title"]);
          $new->setSlug($image["slug"]);
          if (!empty($image["timestamp"]))
          {
            $new->setTimestamp((int) $image["timestamp"]);
          }
          if (!empty($image["m_file"]))
          {
            $new->setFile($image["m_file"]);
          } elseif (!empty($image["file"]))
          {
            $new->setFile($image["file"]);
          }
          if (!empty($image["source_type"]))
          {
            $new->setSourceType($image["source_type"]);
          }
          if (!empty($image["source_tags"]))
          {
            $new->setSourceTags(json_decode($image["source_tags"], true));
          }
          if (!empty($image["m_width"]))
          {
            $new->setMWidth($image["m_width"]);
          }
          if (!empty($image["m_height"]))
          {
            $new->setMHeight($image["m_height"]);
          }
          if (!empty($image["thumb_width"]))
          {
            $new->setTWidth($image["thumb_width"]);
          }
          if (!empty($image["thumb_height"]))
          {
            $new->setTHeight($image["thumb_height"]);
          }
          if (!empty($image["a_width"]))
          {
            $new->setAWidth($image["a_width"]);
          }
          if (!empty($image["a_height"]))
          {
            $new->setAHeight($image["a_height"]);
          }
          if (!empty($image["_tags"]))
          {

            $tags = array();
            
            foreach ($image["_tags"] as $oneTag) {
              $tag = $dm->getRepository("ImagepushBundle:Tag")->findOneBy(array("text" => $oneTag));
              if ($tag)
              {
                $tags[] = $oneTag;
                $new->addTagsRef($tag);
                $tag->addImagesRef($new);
                if ($imageGroup == "current") {
                  $tag->setUsedInAvailable($tag->getUsedInAvailable() + 1);
                } else {
                  $tag->setUsedInUpcoming($tag->getUsedInUpcoming() + 1);
                }
                $dm->persist($tag);
              } else
              {
                \D::dump($oneTag);
              }
            }
            
            //if (count($tags)) {
              $new->setTags($tags);
            //}

          }

          $dm->persist($new);

          if (++$i % 1000 == 0)
          {
            $dm->flush();
            $dm->clear();
          }
        }

        $dm->flush();
        $dm->clear();
      }
    }
  }
  
  
  private function importLatestTags()
  {

    $dm = $this->get('doctrine.odm.mongodb.document_manager');
    $redis = $this->get('snc_redis.default_client');
    
    $i = 0;

    $dm->getDocumentCollection("ImagepushBundle:LatestTag")->drop();

    // latest trend: get last 300 from the list and count how often they used
    $latestTags = $redis->lrange("latest_tags", -300, -1); // put 0 instead of -300 for complete import

    //\D::dump($latestTags);
    //die();
    if (count($latestTags))
    {
      foreach ($latestTags as $latestTag) {
        $new = new LatestTag();
        $new->setTimestamp(time());
        
        $tag = $dm->getRepository("ImagepushBundle:Tag")->findOneBy(array("legacyKey" => $latestTag));
        //\D::dump($tag);
        if ($tag)
        {
          $new->setTag($tag);
          $dm->persist($new);
        } else
        {
          //\D::dump($latestTag);
        }
        
        if (++$i % 100 == 0)
        {
          $dm->flush();
          $dm->clear();
        }
      }

      $dm->flush();
      $dm->clear();
    }
  }

}
