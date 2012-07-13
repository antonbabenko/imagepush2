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

    public function publishLatestUpcomingImage()
    {

        $useBestImageId = false;

        $bestImages = $this->dm
            ->createQueryBuilder('ImagepushBundle:BestImage')
            ->sort("timestamp", "DESC")
            ->getQuery()
            ->toArray();

        if (count($bestImages)) {

            $bestImages = array_values($bestImages);

            foreach ($bestImages as $bestImage) {

                $image = $this->dm
                    ->createQueryBuilder('ImagepushBundle:Image')
                    ->field('id')->equals($bestImage->getImageId())
                    ->field('isAvailable')->equals(false)
                    ->getQuery()
                    ->getSingleResult();

                if (!count($image)) {
                    continue;
                } else {
                    $useBestImageId = $bestImage->getImageId();
                    break;
                }
            }
        }

        if (empty($image)) {
            $this->logger->info("There are no BEST upcoming images to publish now, so we try with the latest upcoming");

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
        }

        //\D::debug($image->getId());
        //die();
        //\D::dump($image->getMongoId());
        // update timestamp to now
        $image->setTimestamp(time());

        $image->setIsAvailable(true);
        $image->setIsInProcess(false);

        $this->dm->persist($image);
        $this->dm->flush();
        $this->dm->refresh($image);

        if ($useBestImageId) {
            // remove from BestImage
            $this->dm->createQueryBuilder('ImagepushBundle:BestImage')
                ->remove()
                ->field('imageId')->equals($useBestImageId)
                ->getQuery()
                ->execute();

            $log = sprintf("BEST Image id: %d has been published", $image->getId());
        } else {
            $log = sprintf("NOT_BEST_IMAGE id: %d has been published", $image->getId());
        }

        $this->logger->info($log);

        return $log;
    }

}