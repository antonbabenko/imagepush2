<?php

namespace Imagepush\ImagepushBundle\Services\Processor;

use Imagepush\ImagepushBundle\Services\Processor\Config;
use Imagepush\ImagepushBundle\Services\Processor\Content;

use Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\ImageInterface;
use Imagine\Filter\Transformation;

class ImageContent extends Content
{

  /*
   * @services
   */
  //public $kernel;
  
  //public $uploadsDir = '/Users/Bob/Sites/imagepush2/web/uploads';
  public $id, $image;

  public function __construct(\AppKernel $kernel)
  {
    parent::__construct($kernel);
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

  public function getFileTypeByContentType($contentType)
  {
    
    if (in_array($contentType, array("image/gif"))) {
      $type = "gif";
    } elseif (in_array($contentType, array("image/png"))) {
      $type = "png";
    } else {
      $type = "jpg";
    }
    
    return $type;
    
  }

  public function makeThumbFilename($prefix, $fileType)
  {

    // For eg: 2567 => /0/2/5/67.jpg
    $filePath = floor($this->getId() / 10000) . "/";
    $filePath .= floor($this->getId() / 1000) . "/";
    $filePath .= floor($this->getId() / 100) . "/";
    $filePath .= ( $this->getId() % 100) . "." . $fileType;

    /*$currentUmask = umask();
    umask(0000);

    //check if dir is exists and writtable
    $dir = dirname($prefix . $filePath);
    if (!is_dir($dir))
    {
      mkdir($dir, 0777, true);
    }

    umask($currentUmask);*/

    return $filePath;
  }

  /**
   * Verify if image has correct size then make thumbs
   */
  public function makeThumbs()
  {

    $imagine = new Imagine\Imagick\Imagine();
    
    $content = $this->getContent();
    $fileType = $this->getFileTypeByContentType($this->getContentType());
    
    if ($content) {
      $image = $imagine->load($content);
    } else {
      return false;
    }
    
    $this->setImage($image);

    if ($image->getSize()->getWidth() >= Config::$minWidth && $image->getSize()->getHeight() >= Config::$minHeight)
    {

      //$message = "Link: " . $this->link . " (" . $image->getImage()->getWidth() . "x" . $image->getImage()->getHeight() . ") - Make thumbs";
      //$this->kernel->getContainer()->get('logger')->info($message);

      $thumbTypes = Config::$thumbTypes;

      foreach ($thumbTypes as $thumbType => $attributes) {
        $prefix = $thumbType . '/';
        $filename = $this->makeThumbFilename($prefix, $fileType);

        $tmpImage = $this->getImage();

        if ($attributes["action"] == "thumbnail_outbound")
        {
          $thumb = $tmpImage->thumbnail(new Box($attributes["width"], $attributes["height"]), ImageInterface::THUMBNAIL_OUTBOUND);
        } elseif ($attributes["action"] == "thumbnail_inset")
        {
          $thumb = $tmpImage->thumbnail(new Box($attributes["width"], $attributes["height"]), ImageInterface::THUMBNAIL_INSET);
        } else
        {
          throw new \Exception("Not thumbnail_inset or thumbnail_outbound action");
        }

        // Save on local disk
        //$saved = $thumb->save($prefix . $filename, array("format" => "jpg", "quality" => 90));
        
        // Get image content
        try {
          $imageContent = $thumb->get($fileType, array("quality" => 90));
        } catch (\ImagickException $e) {
          throw new InvalidArgumentException('Show operation failed', $e->getCode(), $e);
        }
        
        // Save on "images" filesystem
        $saved = $this->fsImages->write($prefix . $filename, $imageContent, true);
        
        if ($saved)
        {
          $data["file"] = $filename;
          $data[$thumbType . "_w"] = $thumb->getSize()->getWidth();
          $data[$thumbType . "_h"] = $thumb->getSize()->getHeight();
        } else
        {
          throw new \Exception("Couldn't save file - " . $prefix . $filename);
        }
      }
    }

    return (isset($data) ? $data : false);

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