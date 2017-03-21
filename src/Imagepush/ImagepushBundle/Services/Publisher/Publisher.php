<?php

namespace Imagepush\ImagepushBundle\Services\Publisher;

use Aws\DynamoDb\DynamoDbClient;
use Imagepush\ImagepushBundle\Document\Image;
use Imagepush\ImagepushBundle\Repository\ImageRepository;
use Imagepush\ImagepushBundle\Repository\TagRepository;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Publish best image to the web-site
 */
class Publisher
{

    /**
     * @var ContainerInterface
     */
    public $container;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var DynamoDbClient
     */
    protected $ddb;

    /**
     * @var ImageRepository
     */
    protected $imageRepo;

    /**
     * @var TagRepository
     */
    protected $tagRepo;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $container->get('imagepush.processor_logger');
        $this->ddb = $container->get('aws.dynamodb');

        $this->imageRepo = $container->get('imagepush.repository.image');
        $this->tagRepo = $container->get('imagepush.repository.tag');
    }

    public function publishImageWithMostTagsFound()
    {

//        $minTagsCount = $this->container->getParameter('imagepush.min_tags_to_publish_image', 5);
        $minTagsCount = 3; // @todo: 6 - was originally, but maybe it is too high. Should check.

        $images = $this->imageRepo->findNotPublishedImagesWithMostTagsFound($minTagsCount);

        $log = 'There are no images to publish!';

        foreach ($images as $image) {

            $this->publishImage($image);

            $log = sprintf(
                "Image (id: %d) has highest number of tags found and has been published! Finalized tags: %s. Found tags: %s were found %d times",
                $image->getId(),
                json_encode($image->getTags()),
                json_encode($image->getTagsFound()),
                $image->getTagsFoundCount()
            );
            $this->logger->info($log);

            // Publish only one image at the time, so break this loop
            break;
        }

        return $log;

        // Fallback
//        $log = sprintf("There is no images with most tags, which can be published! Continue to publishLatestUpcomingImage()");
//        $this->logger->err($log);
//
//        return $this->publishLatestUpcomingImage();

    }

    protected function publishImage(Image $image)
    {

        // update timestamp to now
        $image->setTimestamp(time());
        $image->setIsAvailable(true);

        $tags = $image->getTags();
        foreach ($tags as $tag) {
            $tag = $this->tagRepo->findOneByText($tag);

            if ($tag) {
                $tag->incUsedInAvailable(1);
                $this->tagRepo->save($tag);
            }
        }

        $this->imageRepo->save($image);

        // Purge cached pages
//        $this->varnish->purgeWhenNewImageIsPublished($image);
    }
//
//    public function publishLatestUpcomingImage()
//    {
//
//        $useBestImageId = false;
//
//        $bestImages = $this->dm
//            ->createQueryBuilder('ImagepushBundle:BestImage')
//            ->sort("timestamp", "DESC")
//            ->getQuery()
//            ->toArray();
//
//        if (count($bestImages)) {
//
//            $bestImages = array_values($bestImages);
//
//            foreach ($bestImages as $bestImage) {
//
//                $image = $this->dm
//                    ->createQueryBuilder('ImagepushBundle:Image')
//                    ->field('id')->equals($bestImage->getImageId())
//                    ->field('isAvailable')->equals(false)
//                    ->getQuery()
//                    ->getSingleResult();
//
//                if (!count($image)) {
//                    continue;
//                } else {
//                    $useBestImageId = $bestImage->getImageId();
//                    break;
//                }
//            }
//        }
//
//        if (empty($image)) {
//            $this->logger->info("There are no BEST upcoming images to publish now, so we try with the latest upcoming");
//
//            $images = $this->dm
//                ->getRepository('ImagepushBundle:Image')
//                ->findImages("upcoming", 1);
//
//            if (count($images)) {
//                $images = array_values($images);
//                $image = $images[0];
//            } else {
//                $this->logger->info("There are no upcoming images to publish now.");
//
//                return false;
//            }
//        }
//
//        $this->publishImage($image);
//
//        if ($useBestImageId) {
//            // remove from BestImage
//            $this->dm->createQueryBuilder('ImagepushBundle:BestImage')
//                ->remove()
//                ->field('imageId')->equals($useBestImageId)
//                ->getQuery()
//                ->execute();
//
//            $log = sprintf("BEST Image id: %d has been published", $image->getId());
//        } else {
//            $log = sprintf("NOT_BEST_IMAGE id: %d has been published", $image->getId());
//        }
//
//        $this->logger->info($log);
//
//        return $log;
//    }

}
