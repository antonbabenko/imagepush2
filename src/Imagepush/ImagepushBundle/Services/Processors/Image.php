<?php

namespace Imagepush\ImagepushBundle\Services\Processors;

use Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Filter\Transformation;
//use Avalanche\Bundle\ImagineBundle\Imagine\Filter\FilterManager;

class Image
{
  /*
   * Settings for allowed images
   */
  static $minWidth = 450;
  static $minHeight = 180;
  static $minRatio = 0.3;
  static $maxRatio = 2.5;
  //static $minFilesize = 20480;   // 20KB in bytes
  //static $maxFilesize = 4194304; // 4MB in bytes
  
  /*
   * Thumb types and manipulations
   */
  static $thumb_types = array(
      "m" => array(/* main page */
        "action" => "resize_down",
        "max_width" => 463,
        "max_height" => 1510,
      ),
      "thumb" => array(/* thumb */
        "action" => "thumbnail",
        "thumbnail_width" => 140,
        "thumbnail_height" => 140,
      ),
      "a" => array(/* article */
        "action" => "resize_down",
        "max_width" => 625,
        "max_height" => 2090,
      ),
    );

  /*
   * @services
   */
  public $kernel;
  
  public function __construct(\AppKernel $kernel) {
    
    $this->kernel = $kernel;
    
  }
  
  /*
   * Just for test
   */
  public function testMakeThumbs($contentString, $contentType)
  {
    $imagine = new Imagine\GD\Imagine();
    $image = $imagine->load($contentString);
    
    echo $image->getSize()->getWidth();
    //$transformation->thumbnail($size)
    \D::dump($image);
    \D::dump(gd_info());
    // , ImageInterface::THUMBNAIL_OUTBOUND
    
    $transformation = new Imagine\Filter\Transformation();
    $transformation->thumbnail(new Box(30, 30))
        ->save("/Users/Bob/Sites/imagepush2/web/test_images/out/test.jpg");
    
    $newImage = $transformation->apply($image);
    
    \D::dump($newImage);
    
    echo $newImage->getSize()->getWidth();
    
    //$image->save("/Users/Bob/Sites/imagepush2/web/test_images/out/test.jpg", array("format" => "jpg"));
    
    //\D::dump($image);
    return $image->get('png');
  }
  
  /*
   * Verify if image has correct size and make thumbs
   */
  public function makeThumbs($contentString, $contentType)
  {
    
/*
$image = $imagine->load($contentString);
$box = new Box(40, 50);
$image->resize($box);
$image->save("/Users/Bob/Sites/imagepush2/web/test_images/out/test.jpg", array("format" => "jpg"));
\D::dump($image);
return;
*/
    $imagine = new Imagine();
    $image = $imagine->load($contentString);
    
    $image->getSize()->getWidth();
    // OLD: $image->setImageId($this->id);

    if ($image->getSize()->getWidth() >= self::$minWidth && $image->getSize()->getHeight() >= self::$minHeight)
    {

      //$message = "Link: " . $this->link . " (" . $image->getImage()->getWidth() . "x" . $image->getImage()->getHeight() . ") - Make thumbs";
      //$this->kernel->getContainer()->get('logger')->info($message);

      $thumbs_data = $image->storeThumbs();
      //D::dump($thumbs_data);

      if ($thumbs_data)
      {
        Images::saveUniqueImageHash($content);
        Images::saveAsProcessed($this->key, array_merge($this->working_link, $thumbs_data));
        $saved = true;
      } else
      {
        if ($this->removeKeyIfImageIsSmallOrError)
        {
          Images::removeKey($this->key, $this->link);
        }

        $message = "Link: " . $this->link . " - Didn't make thumbs, so link will be removed (key: " . $this->key . ")";
        sfContext::getInstance()->getLogger()->info($message);
      }
    } else
    { // very small image
      if ($this->removeKeyIfImageIsSmallOrError)
      {
        Images::removeKey($this->key, $this->link);
      }

      $message = "Link: " . $this->link . " (" . $image->getImage()->getWidth() . "x" . $image->getImage()->getHeight() . ") - Small image, remove link";
      sfContext::getInstance()->getLogger()->info($message);
    }

    return $saved;
  }

}