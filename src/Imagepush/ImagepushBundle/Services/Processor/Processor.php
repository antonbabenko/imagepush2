<?php

namespace Imagepush\ImagepushBundle\Services\Processor;

use Imagepush\ImagepushBundle\Entity\Image;

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

  public function __construct(\AppKernel $kernel)
  {

    $this->kernel = $kernel;
    $this->logger = $kernel->getContainer()->get('logger');
    
  }

/**
 * 
 * 1) get one latest urls, which is unprocessed and not blocked by other working process
 * 2) check what kind of site is it - blocked (nudes, porn) or good
 * 3) get content from URL
 * 4) check type of content - image or html
 * 4a) if single image -> then it is "unsorted" category
 * 4b) if html:
 * 5) try to find large image(s)
 * 6) try to make thumbs from large image
 * //7) try to find category/tags for the page
 */
  public function processSource()
  {
    
    $result = false;
    
    /**
     * Create image object based on unprocessed source
     */
    $image = new Image($this->kernel);
    
    if ($image->initUnprocessedSource()) {
      $this->logger->info(sprintf("ID: %d. Source link to process: %s", $image->id, $image->link));
    } else {
      $this->logger->info("There is no unprocessed source to work on");
      return false;
    }
    \D::dump($image);
    
    if ($image->sourceDomainIsBlocked())
    {
      $this->logger->warn(sprintf("ID: %d. %s is blacklisted domain (porn, spam, etc)", $image->id, $image->link));
      return false;
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
    
    //$image->id = 24818;
    //$image->link = "http://adayinthalifeof.files.wordpress.com/2009/06/picture-15.png";
    
    /**
     * Get content from the link
     */
    $content = new Content($this->kernel);
    $content->get($image->link);
    
    if (!$content->isSuccessStatus())
    {
      $this->logger->warn(sprintf("ID: %d. Link %s returned status code %d", $image->id, $image->link, $content->getData()));
      return false;
    }

    /**
     * Content is image
     */
    if ($content->isImageType())
    {

      if ($content->isAlreadyProcessedImageHash())
      {
        $this->logger->warn(sprintf("ID: %d. Image %s has been already processed (hash found)", $image->id, $image->link));
        return false;
      }
      
      $processorImage = new ImageContent($this->kernel);
      $processorImage->setId($image->id);
      $processorImage->setData($content->getData());

      $result = $processorImage->makeThumbs();

      if ($result) {
        if (Config::$modifyDB) {
          $content->saveProcessedImageHash();
          $image->saveAsProcessed($result);
        }
        
        $this->logger->info(sprintf("ID: %d. Link %s has been processed as single image.", $image->id, $image->link));

      }
      
    }
    
    /**
     * Content is HTML/XML
     */
    if ($content->isHTMLType())
    {

      $functions = array(
        "getFullImageSrc",    /* Try to find <link rel="image_src" /> or <meta property="og:image" /> */
        "getBestImageFromDom" /* Try to find large images inside html DOM */
      );

      $processorHtml = new HtmlContent($this->kernel);
      $processorHtml->setLink($image->link);
      $processorHtml->setData($content->getData());
      
      foreach ($functions as $function) {

        $imagesUrl = $processorHtml->$function();

        if ($imagesUrl)
        {

          //\D::dump($function);
          //\D::dump($imagesUrl);

          foreach ($imagesUrl as $imageUrl) {

            $content->get($imageUrl["url"]);

            if ($content->isImageType() && !$content->isAlreadyProcessedImageHash())
            {
              $processorImage = new ImageContent($this->kernel);
              $processorImage->setId($image->id);
              $processorImage->setData($content->getData());

              $result = $processorImage->makeThumbs();

              if ($result)
              {
                if (Config::$modifyDB)
                {
                  $content->saveProcessedImageHash();
                  $image->saveAsProcessed($result);
                }

                $this->logger->info(sprintf("ID: %d. Link %s has been processed by function %s. Correct image url: %s", $image->id, $image->link, $function, $imageUrl["url"]));
                
                // Image has been found and saved. No need to search further.
                break 2;
              }
            }
          }
        }
      }
      
    } // end of find image block
    
    //\D::dump($result);
    
    /**
     * No images found - remove link and image key
     */
    if (!$result) {
      $this->logger->info(sprintf("ID: %d. No images found, so link %s should be removed.", $image->id, $image->link));
      if (Config::$modifyDB) {
        $image->remove();
        return false;
      }
    }
  
    /**
     * Find tags (optional)
     */
    $processorTag = $this->kernel->getContainer()->get('imagepush.processor.tag');
    $processorTag->setImage($image);
    $processorTag->processTags();
    
    return ($result ? $image->id : false);

  }

}