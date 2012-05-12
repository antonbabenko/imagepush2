<?php

namespace Imagepush\ImagepushBundle\Services\Processor;

use Imagepush\ImagepushBundle\Services\Processor\Config;
use Imagepush\ImagepushBundle\Services\Processor\Content;
use Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\ImageInterface;
use Imagine\Filter\Transformation;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ImageContent extends Content
{

    public $id, $image;

    /**
     * @var Container $container
     */
    public $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
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

    /*
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

      return $filePath;
      }
     */

    /**
     * Verify if image has correct size then make thumbs
     */
    /* public function makeThumbsUrls()
      {
      $urls = array();
      $thumbTypes = Config::$thumbTypes;

      foreach ($thumbTypes as $attributes) {
      $urls[] = $this->container->get('twig.extension.imagepush')->imagepushFilter($image->getFile(), $attributes[0], $attributes[1], $attributes[2], $image->getId());
      }

      return $urls;
      } */

    /**
     * @return boolean
     * @throws \Exception
     * @throws InvalidArgumentException 
     */
    public function oldMakeThumbs()
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

        if ($image->getSize()->getWidth() >= Config::$minWidth && $image->getSize()->getHeight() >= Config::$minHeight) {

            //$message = "Link: " . $this->link . " (" . $image->getImage()->getWidth() . "x" . $image->getImage()->getHeight() . ") - Make thumbs";
            //$this->kernel->getContainer()->get('logger')->info($message);

            $thumbTypes = Config::$thumbTypes;

            foreach ($thumbTypes as $thumbType => $attributes) {
                $prefix = $thumbType . '/';
                $filename = $this->makeThumbFilename($prefix, $fileType);

                $tmpImage = $this->getImage();

                if ($attributes["action"] == "thumbnail_outbound") {
                    $thumb = $tmpImage->thumbnail(new Box($attributes["width"], $attributes["height"]), ImageInterface::THUMBNAIL_OUTBOUND);
                } elseif ($attributes["action"] == "thumbnail_inset") {
                    $thumb = $tmpImage->thumbnail(new Box($attributes["width"], $attributes["height"]), ImageInterface::THUMBNAIL_INSET);
                } else {
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

                if ($saved) {
                    $data["file"] = $filename;
                    $data[$thumbType . "_w"] = $thumb->getSize()->getWidth();
                    $data[$thumbType . "_h"] = $thumb->getSize()->getHeight();
                } else {
                    throw new \Exception("Couldn't save file - " . $prefix . $filename);
                }
            }
        }

        return (isset($data) ? $data : false);
    }

    /**
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