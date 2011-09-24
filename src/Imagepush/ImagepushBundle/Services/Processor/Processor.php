<?php

namespace Imagepush\ImagepushBundle\Services\Processor;

/**
 * @todo: Other classes are:
 *   Processor\Processor - super-class which handles all other processors relations and contains business logic
 *   Processor\Html - handle html content (and get the most suitable image inside html content)
 *   Processor\Image - handle image content. Make thumbs.
 *   @todo: Processor\Tags - handle tags
 *   @todo: Processor\Config - handle all config vars
 *   Fetcher\Content - get link content
 */
class Processor
{

  public $source;
  public $id;
  public $link;
  public $imageKey;

  /*
   * @services
   */
  public $kernel;

  public function __construct(\AppKernel $kernel)
  {

    $this->kernel = $kernel;
  }

  static $result;

  /**
   * @todo: Make a black list of domains, if porn/nudes domain - return true
   */
  public function isBlockedDomain()
  {
    return false;
  }

  public function run()
  {

    // 1) get one latest urls, which is unprocessed and not blocked by other working process
    // 2) check what kind of site is it - blocked (nudes, porn) or good
    // 3) get content from URL
    // 4) check type of content - image or html
    // 4a) if single image -> then it is "unsorted" category
    // 4b) if html:
    // 5) try to find large image(s)
    // 6) try to make thumbs from large image
    // 7) try to find category/tags for the page

    $source = $this->kernel->getContainer()->get('imagepush.source');
    $images = $this->kernel->getContainer()->get('imagepush.images');
    $content = $this->kernel->getContainer()->get('imagepush.processor.content');
    $processorImage = $this->kernel->getContainer()->get('imagepush.processor.image');
    $processorHtml = $this->kernel->getContainer()->get('imagepush.processor.html');
    $processorTag = $this->kernel->getContainer()->get('imagepush.processor.tag');

    $this->source = $source->getAndInitUnprocessed();

    if ($this->source === false)
    {
      throw new \Exception("There is no unprocessed source to work on");
    }

    $this->id = $this->source["id"];
    $this->link = $this->source["link"];
    $this->imageKey = $images->getImageKey($this->id);

    \D::dump($this->source);

    if ($this->isBlockedDomain())
    {
      throw new \Exception(sprintf("%s is blacklisted domain (porn, spam, etc)", $this->link));
    }

    /*
      $this->link = "http://www.web-developer.no/img/portfolio/flynytt.jpg";
      $this->link = "http://i.imgur.com/bCK24.jpg";
      $this->link = "http://adayinthalifeof.files.wordpress.com/2009/06/picture-15.png";

      // image_src or og:image
      //$this->link = "http://imagepush.to/i/40033/well-cosplayed-sir";

      // text/html
      $this->link = "http://www.web-developer.no/";
      $this->link = "http://slapblog.com/?p=7888";
      $this->link = "http://localhost/testpages/page1.htm";
      //$this->link = "http://localhost/testpages/page2.htm"; // image_src
      //$this->link = "http://localhost/testpages/page3.html";
     */
    // image_src or og:image
    //$this->link = "http://imagepush.to/i/40033/well-cosplayed-sir";
    // two similar images:
    //$this->link = "http://soshable.com/what-if-facebook-were-a-city/";
    
    // skip top logo, because it has been indexed already
    //$this->link = "http://www.geekfill.com/2011/03/12/hold-still-sonny-comic/";
    
    // Hotlinking forbidden
    //$this->link = "http://www.flickr.com/photos/mtaphotos/6086067175/";

    // ssl problem:
    //$this->link = "http://delaware.metromix.com/music/essay_photo_gallery/most-anticipated-albums-of/2389330/photo/2393050";
    // parse url problem
    //$this->link = "http://www.totalprosports.com/2011/01/19/is-that-a-rocket-in-caroline-wozniackis-pocket-pic/";
    //
    //$this->link = "http://i.imgur.com/SsvPB.jpg";

    $result = false;
    
    /**
     * FIND IMAGE
     */
    //if (false) { // begin of find image comments (to test tags only)
    $content->initAndFetch($this->link);
    
    if (!$content->isFetched())
    {
      $this->kernel->getContainer()->get('logger')->warn(sprintf("ID: %d. Link %s returned status code %d", $this->id, $this->link, $content->getData()));
      return false;
    }

    if ($content->isImage())
    {

      if ($content->isAlreadyProcessedImageHash())
      {
        $this->kernel->getContainer()->get('logger')->warn(sprintf("ID: %d. Image %s has been already processed (hash found)", $this->id, $this->link));
        return false;
      }

      $processorImage->setId($this->id);
      $processorImage->setData($content->getData());

      $result = $processorImage->makeThumbs();

      if ($result && Config::$modifyDB)
      {
        $content->saveProcessedImageHash();
        $images->saveAsProcessed($this->imageKey, array_merge($this->source, $result));
      }
    } elseif ($content->isHTMLLike())
    {

      $functions = array(
        "getFullImageSrc",    /* Try to find <link rel="image_src" /> or <meta property="og:image" /> */
        "getBestImageFromDom" /* Try to find large images inside html DOM */
      );

      foreach ($functions as $function) {

        $processorHtml->setData($content->getData());
        //\D::dump($content);
        $imagesUrl = $processorHtml->$function();

        \D::dump($imagesUrl);

        if (!$imagesUrl)
          continue;

        foreach ($imagesUrl as $imageUrl) {
          $content->fetch($imageUrl["url"]);

          if ($content->isImage() && !$content->isAlreadyProcessedImageHash() )
          {
            //\D::dump($content->getData());
            $processorImage->setId($this->id);
            $processorImage->setData($content->getData());

            $result = $processorImage->makeThumbs();

            //\D::dump($result);
            if ($result && Config::$modifyDB)
            {
              $content->saveProcessedImageHash();
              $images->saveAsProcessed($this->imageKey, array_merge($this->source, $result));
            }

            // Image has been found and saved. No need to search further.
            if ($result) {
              $this->kernel->getContainer()->get('logger')->info(sprintf("ID: %d. Link %s has been processed. Image url: %s", $this->id, $this->link, $imageUrl["url"]));
              break 2;
            }
          }
        }
      }
    } // end of find image block
    
    \D::dump($result);
    
    // No images found - remove link and image key
    if (!$result) {
      $this->kernel->getContainer()->get('logger')->info(sprintf("ID: %d. No images found, so link %s should be removed.", $this->id, $this->link));
      if (Config::$modifyDB) {
        $images->removeKey($this->imageKey, $this->link);
        return false;
      }
    }
    
    /**
     * FIND TAGS
     */
  //$result = true;
    if ($result) {
      $processorTag->setImageKey($this->imageKey);
      $processorTag->setSource($this->source);

      $processorTag->processTags();
      
    }


    return (isset($result) && $result ? $this->id : false);

  }

}