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
        $this->varnish = $container->get('imagepush.varnish');
        $this->redis = $container->get('snc_redis.default');
    }

    public function publishImageWithMostTagsFound()
    {

        // get images with most tags found
        $imagesWithFoundTags = $this->redis->zrevrangebyscore("found_tags_counter", "+inf", "-inf", array("withscores" => true, "limit" => array(0, 1000)));

        //\D::debug($imagesWithFoundTags);

        $minTagsCount = $this->container->getParameter('imagepush.min_tags_to_publish_image', 5);

        foreach ($imagesWithFoundTags as $imageWithFoundTags => $foundTagsCounter) {

            if ($foundTagsCounter < $minTagsCount) {
                $this->logger->info(sprintf("Image (id: %d) has %d tags, but %d is needed to be published! Will try to publish best image (by votes, old manual way).", $imageWithFoundTags, $foundTagsCounter, $minTagsCount));
                break;
            }

            $image = $this->dm
                ->createQueryBuilder('ImagepushBundle:Image')
                ->field('id')->equals($imageWithFoundTags)
                ->field('isAvailable')->equals(false)
                ->getQuery()
                ->getSingleResult();

            // Image has been already published, then reset found tags counter
            if (null === $image) {
                $this->redis->zrem("found_tags_counter", $imageWithFoundTags);
                continue;
            }

            // Filter found tags (bad, duplicates) and merge with already saved tags
            $count = $this->container->get('imagepush.processor.tag')->updateTagsFromFoundTags($image);

            //\D::debug($count);

            if ($count > 0) {
                $this->publishImage($image);

                $this->redis->zrem("found_tags_counter", $imageWithFoundTags);

                $log = sprintf("Image (id: %d) has highest tags score and has been published! Tags: %s were found %d times", $image->getId(), json_encode($image->getTags()), $foundTagsCounter);
                $this->logger->info($log);

                // Publish only one image at the time, so break this loop
                return $log;
            }
        }

        // Fallback
        $log = sprintf("There is no images with most tags, which can be published! Continue to publishLatestUpcomingImage()");
        $this->logger->err($log);

        return $this->publishLatestUpcomingImage();

        /////////
        // If needed keep only images added during last hour.
        // 30.03.2013 - will see if it is needed!
        /////////
    }

    protected function publishImage(Image $image)
    {

        //\D::debug($image->getId());
        //die();
        //\D::dump($image->getMongoId());
        // update timestamp to now
        $image->setTimestamp(time());

        $image->setIsAvailable(true);
        $image->setIsInProcess(false);

        $tags = $image->getTags();
        foreach ($tags as $tag) {
            $oneTag = $this->dm->getRepository("ImagepushBundle:Tag")->findOneBy(array("text" => $tag));
            if ($oneTag) {
                $oneTag->incUsedInAvailable();
                $this->dm->persist($oneTag);
            }
        }

        $this->dm->persist($image);
        $this->dm->flush();
        $this->dm->refresh($image);

        // Purge cached pages
        $this->varnish->purgeWhenNewImageIsPublished($image);
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

        $this->publishImage($image);

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
