<?php

namespace Imagepush\ImagepushBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Imagepush\ImagepushBundle\Entity\Image as RedisImage;
use Imagepush\ImagepushBundle\Document\Image;

class ImportController extends Controller
{

  /**
   * @Route("/", name="importIndex")
   * @Template()
   */
  public function indexAction()
  {
    $result = $this->importImages();
    
    return new Response("OK");
  }
  
  private function importImages() {
    
    $dm = $this->get('doctrine.odm.mongodb.document_manager');
    $i = 0;
    
    // current
    $images = $this->get('imagepush.images.manager')->getImages("current", 5);

    \D::dump($images);
    
    if (count($images))
    {
      foreach ($images as $image) {
        $new = new Image();
        $new->setImageId($image["id"]);
        $new->setLink($image["link"]);
        $new->setTitle($image["title"]);
        $new->setSlug($image["slug"]);
        if (!empty($image["timestamp"])) {
          $new->setTimestamp((int)$image["timestamp"]);
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
        if (!empty($image["t_width"]))
        {
          $new->setTWidth($image["t_width"]);
        }
        if (!empty($image["t_height"]))
        {
          $new->setTHeight($image["t_height"]);
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
          $new->setTags($image["_tags"]);
        }
        
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

}
