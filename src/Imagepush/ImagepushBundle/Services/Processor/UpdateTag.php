<?php

namespace Imagepush\ImagepushBundle\Services\Processor;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Imagepush\ImagepushBundle\Document\Image;
use Imagepush\ImagepushBundle\Document\LatestTag as DocumentLatestTag;
use Imagepush\ImagepushBundle\Document\Tag as DocumentTag;
use Imagepush\ImagepushBundle\Repository\ImageRepository;
use Imagepush\ImagepushBundle\Repository\LatestTagRepository;
use Imagepush\ImagepushBundle\Repository\TagRepository;
use Imagepush\ImagepushBundle\Services\Processor\Tag\Tag as TagHelpers;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Update tags which has been already saved in database by ProcessorTag
 */
class UpdateTag
{

    /**
     * @var ContainerInterface $container
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

    /**
     * @var LatestTagRepository
     */
    protected $latestTagRepo;

    /**
     * @var TagHelpers
     */
    protected $tagHelpers;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $container->get('imagepush.processor_logger');
        $this->ddb = $container->get('aws.dynamodb');

        $this->imageRepo = $container->get('imagepush.repository.image');
        $this->tagRepo = $container->get('imagepush.repository.tag');
        $this->latestTagRepo = $container->get('imagepush.repository.latest_tag');

        $this->tagHelpers = $container->get('imagepush.processor.tag.tag');
    }

    /**
     * Update tags on all images, where tagsFound has been changed recently
     */
    public function updateTags()
    {
        $images = $this->imageRepo->findImagesRequireUpdateTags();

        if (empty($images)) {
            $this->logger->info('No images found to update tags');

            return;
        }

        foreach ($images as $image) {
            $this->updateTagsFromFoundTags($image);
        }

        $this->logger->info(sprintf('Updated tags for %d images', count($images)));

        return;
    }

    /**
     * Merge tags with summarized found tags (remove bad and filter by score)
     * @param  Image $image
     * @return int
     */
    public function updateTagsFromFoundTags(Image $image)
    {
        $foundTags = $image->getTagsFound();

        if (empty($foundTags)) {
            return 0;
        }

        foreach ($foundTags as $service => $tags) {
            $foundTags[$service] = $this->tagHelpers->array_icount_values($foundTags[$service]);
        }

        $foundTags = $this->tagHelpers->calculateTagsScore($foundTags);

        $goodTags = $this->tagHelpers->filterTagsByScore($foundTags, 20);

        $goodTags = array_keys($goodTags);

        if (count($goodTags)) {
            $goodTags = array_unique(array_merge((array) $image->getTags(), $goodTags));
            $this->logger->info(sprintf('Image ID: %d. Saving final tags: %s', $image->getId(), implode(", ", $goodTags)));
            $this->saveTagsForImage($image, $goodTags);
        }

        return count($goodTags);
    }

    public function saveTagsForImage(Image $image, array $goodTags)
    {

        foreach ($goodTags as $goodTag) {
            ///////////////////////////////////
            // Latest tag
            ///////////////////////////////////
            $latestTag = new DocumentLatestTag();
            $latestTag->setTimestamp(time());
            $latestTag->setText($goodTag);
            $this->latestTagRepo->save($latestTag);
            ///////////////////////////////////

            ///////////////////////////////////
            // Tag
            ///////////////////////////////////
            $tag = $this->tagRepo->findOneByText($goodTag);

            if (null === $tag) {
                $tag = new DocumentTag();
                $tag->setText($goodTag);
            }

            $tag->incUsedInUpcoming(1);
            $this->tagRepo->save($tag);
            ///////////////////////////////////

            ///////////////////////////////////
            // Images tags table (many-to-many)
            ///////////////////////////////////
            $request = [
                'TableName' => 'images_tags',
                'Item' => [
                    'key' => [
                        'B' => base64_encode($image->getId() . $goodTag)
                    ],
                    'id' => [
                        'N' => (string) $image->getId()
                    ],
                    'tag' => [
                        'S' => (string) $goodTag
                    ],
                ]
            ];

            try {
                $this->ddb->putItem($request);
            } catch (DynamoDbException $e) {
                $this->logger->error($e->__toString());
            }

        }

        ///////////////////////////////////
        // Images table
        ///////////////////////////////////
        $image->setTags($goodTags);

        $image->setRequireUpdateTags(false);

        $this->imageRepo->save($image);
    }

}
