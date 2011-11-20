<?php

namespace Imagepush\ImagepushBundle\Services\Publisher;

use Imagepush\ImagepushBundle\Entity\Image;

/**
 * Publish best image to the client
 */
class Publisher
{

  public function __construct(\AppKernel $kernel)
  {

    $this->kernel = $kernel;
    $this->logger = $kernel->getContainer()->get('logger');
    $this->redis = $kernel->getContainer()->get('snc_redis.default_client');
    $this->imagesManager = $kernel->getContainer()->get('imagepush.images.manager');
    
  }
  
  public function publishLatestUpcomingImage($skip_delay = false)
  {

    $data = $this->imagesManager->getImages("upcoming", 1);
    \D::dump($data);
    
    if (!empty($data[0]["id"])) {
      $id = $data[0]["id"];
    } else {
      $this->logger->info("There are no upcoming images to publish now.");
      return false;
    }
      
    $image = new Image($this->kernel);
    $image->load($id);
    $image->migrateUpcomingToAvailable();
    //  self::publishSingleImage($id[0], $skip_delay);
  }
  
}