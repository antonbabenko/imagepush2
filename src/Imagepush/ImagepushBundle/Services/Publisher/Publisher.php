<?php

namespace Imagepush\ImagepushBundle\Services\Publisher;

use Imagepush\ImagepushBundle\Document\Image;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Publish best image to the client
 */
class Publisher
{

    /**
     * @var Container $container
     */
    public $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $container->get('logger');
        $this->dm = $container->get('doctrine.odm.mongodb.document_manager');
    }

    public function publishLatestUpcomingImage($skipDelay = false)
    {

        //$data = $this->imagesManager->getImages("upcoming", 1);
        $images = $this->dm
            ->getRepository('ImagepushBundle:Image')
            ->findImages("upcoming", 1);

        if (count($images)) {
            $images = array_values($images);
            $image = $images[0];
        } else {
            $this->logger->info("There are no upcoming images to publish now.");

            return false;
        }

        //\D::dump($image->getId());
        //\D::dump($image->getMongoId());

        // update timestamp to now
        $image->setTimestamp(time());

        $image->setIsAvailable(true);
        $image->setIsInProcess(false);

        $this->dm->persist($image);
        $this->dm->flush();
        $this->dm->refresh($image);

        return $image;
    }

}