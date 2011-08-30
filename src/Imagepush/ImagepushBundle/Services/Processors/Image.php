<?php

namespace Imagepush\ImagepushBundle\Services\Processors;

use Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\ImageInterface;
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
      "action" => "thumbnail_inset",
      "width" => 463,
      "height" => 1510,
    ),
    "thumb" => array(/* thumb */
      "action" => "thumbnail",
      "width" => 140,
      "height" => 140,
    ),
    "a" => array(/* article */
      "action" => "thumbnail_inset",
      "width" => 625,
      "height" => 2090,
    ),
  );

  /*
   * @services
   */
  public $kernel;
  public $uploadsDir = '/Users/Bob/Sites/imagepush2/web/uploads';
  public $id, $image;

  public function __construct(\AppKernel $kernel)
  {

    $this->kernel = $kernel;
  }

  public function setId($id)
  {
    $this->id = $id;
  }

  public function getId()
  {
    return $this->id;
  }

  public function setImage($image)
  {
    $this->image = $image;
  }

  public function getImage()
  {
    return $this->image;
  }

  public function getFileExtension()
  {
    return "jpg"; //$this->types[$this->image->getMIMEType()][0];
  }

  public function makeThumbFilename($prefix)
  {

    // For eg: 2567 => /0/2/5/67.jpg
    $filePath = floor($this->getId() / 10000) . "/";
    $filePath .= floor($this->getId() / 1000) . "/";
    $filePath .= floor($this->getId() / 100) . "/";
    $filePath .= ( $this->getId() % 100) . "." . $this->getFileExtension();

    $currentUmask = umask();
    umask(0000);

    //check if dir is exists and writtable
    $dir = dirname($prefix . $filePath);
    if (!is_dir($dir))
    {
      mkdir($dir, 0777, true);
    }

    umask($currentUmask);

    return $filePath;
  }

  /*
   * Verify if image has correct size and make thumbs
   */

  public function makeThumbs($id, $imageData)
  {

    $imagine = new Imagine\Imagick\Imagine();
    $image = $imagine->load($imageData["Content"]);

    $this->setId($id);
    $this->setImage($image);

    if ($image->getSize()->getWidth() >= self::$minWidth && $image->getSize()->getHeight() >= self::$minHeight)
    {

      //$message = "Link: " . $this->link . " (" . $image->getImage()->getWidth() . "x" . $image->getImage()->getHeight() . ") - Make thumbs";
      //$this->kernel->getContainer()->get('logger')->info($message);



      $thumb_types = self::$thumb_types;

      foreach ($thumb_types as $thumb_type => $attributes) {
        $prefix = $this->uploadsDir . '/' . $thumb_type . '/';
        $filename = $this->makeThumbFilename($prefix);

        $tmp_image = $this->getImage();

        if ($attributes["action"] == "thumbnail")
        {
          $thumb = $tmp_image->thumbnail(new Box($attributes["width"], $attributes["height"]), ImageInterface::THUMBNAIL_OUTBOUND);
          //$thumb = $tmp_image->thumbnail($attributes["thumbnail_width"], $attributes["thumbnail_height"], 'center');
        } elseif ($attributes["action"] == "thumbnail_inset")
        {
          $thumb = $tmp_image->thumbnail(new Box($attributes["width"], $attributes["height"]), ImageInterface::THUMBNAIL_INSET);
          //$thumb = $tmp_image->resize($attributes["max_width"], $attributes["max_height"], false, true);
        } else
        {
          throw new \Exception("Not thumbnail_inset or thumbnail action");
        }

        
        $saved = $thumb->save($prefix . $filename, array("format" => "jpg", "quality" => 90));

        //\D::dump($saved);
        //$saved = $thumb->saveAs($path_prefix . $filename);
        if ($saved)
        {
          $saved_data[$thumb_type . "_file"] = $filename;
          $saved_data[$thumb_type . "_content_type"] = "image/jpeg"; //$saved->getMIMEType();
          $saved_data[$thumb_type . "_width"] = $saved->getSize()->getWidth();
          $saved_data[$thumb_type . "_height"] = $saved->getSize()->getHeight();
        } else
        {
          throw new \Exception("Couldn't save file - " . $prefix . $filename);
        }
        //\D::dump($saved_data);
      }
    }

    return (isset($saved_data) ? $saved_data : false);



    //D::dump($thumbs_data);

    /* if ($thumbs_data)
      {
      //Images::saveUniqueImageHash($content);
      //Images::saveAsProcessed($this->key, array_merge($this->working_link, $thumbs_data));
      //$saved = true;
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

      return $saved; */
  }

  /*
   * Just for test
   */

  public function testMakeThumbs($contentString, $contentType)
  {
    $imagine = new Imagine\Imagick\Imagine();
    $image = $imagine->load($contentString);

    echo $image->getSize()->getWidth();
    //$transformation->thumbnail($size)
    \D::dump($image);
    // , ImageInterface::THUMBNAIL_OUTBOUND

    $transformation = new Imagine\Filter\Transformation();
    $transformation->thumbnail(new Box(400, 400))
      ->save("/Users/Bob/Sites/imagepush2/web/test_images/out/test.jpg");

    $newImage = $transformation->apply($image);

    \D::dump($newImage);

    echo $newImage->getSize()->getWidth();

    //$image->save("/Users/Bob/Sites/imagepush2/web/test_images/out/test.jpg", array("format" => "jpg"));
    //\D::dump($image);
    return $image->get('png');
  }

}