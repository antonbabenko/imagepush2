<?php

namespace Imagepush\ImagepushBundle\Services\Processor\Tag;

use Imagepush\ImagepushBundle\Document\Image;
use Imagepush\ImagepushBundle\Document\LatestTag;
use Imagepush\ImagepushBundle\Document\Tag as DocumentTag;
use Imagepush\ImagepushBundle\External\CustomStrings;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Tag
{
    /**
     * All tags, which should be saved for image, but not all has a high score.
     * @var array
     */
    //public $allTags;

    /**
     * Best tags with highest score. Use this to save in image entity.
     * @var array
     */
    //public $bestTags;

    /**
     * @services
     */
    public $container;
    public $dm;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->redis = $container->get('snc_redis.default');
        $this->dm = $container->get('doctrine.odm.mongodb.document_manager');
        $this->logger = $container->get('logger');
    }

    /**
     * Save all tags into MongoDB, but not passed through filterTagsByScore()
     *
     * @param integer $imageId
     * @param string  $serviceName
     * @param array   $foundTags
     */
    public function saveTagsFound($imageId, $serviceName, $foundTags)
    {
        $message = "Found tags: " . implode(", ", $foundTags);
        $this->logger->info($message);

        if (count($foundTags)) {
            $redisLockKey = "image_is_locked_for_updates_" . $imageId;

            while ($this->redis->get($redisLockKey)) {
                usleep(mt_rand(100, 1000));
            }

            // Lock for max 30 seconds
            $this->redis->setex($redisLockKey, 30, $serviceName);

            $image = $this->dm->getRepository("ImagepushBundle:Image")->findOneBy(array("id" => $imageId));

            if (null === $image) {
                $this->logger->crit("Image {$imageId} doesn't exist");

                return false;
            }

            // Add tags found
            $existingTags = $image->getTagsFound();
            if (isset($existingTags[$serviceName]) && is_array($existingTags[$serviceName])) {
                $existingTags[$serviceName] = array_merge($existingTags[$serviceName], (array) $foundTags);
            } else {
                $existingTags[$serviceName] = (array) $foundTags;
            }
            $image->setTagsFound($existingTags);

            $this->dm->persist($image);
            $this->dm->flush();

            // Keep tags counter (score) updated for image
            $this->redis->zincrby("found_tags_counter", count($foundTags), $imageId);

            // Keep images where new tags were found
            $this->redis->sadd("images_with_new_found_tags", $imageId);

            // Unlock
            $this->redis->del($redisLockKey);
        }

        $this->logger->err("!!!!!!" . $imageId . $serviceName . "==" . json_encode($foundTags));
    }

    /**
     * Update all images tags where new tags were found
     *
     * @return int Number of updated images
     */
    public function updateTagsFromFoundTagsForAllImages()
    {
        $imageIds = $this->redis->smembers("images_with_new_found_tags");
        $imageIds = array_map("intval", $imageIds); // ID is an integer

        if (empty($imageIds)) {
            return 0;
        }

        $counter = 0;

        $images = $this->dm
            ->createQueryBuilder('ImagepushBundle:Image')
            ->field('id')->in($imageIds)
            ->getQuery()
            ->toArray();

        $images = array_values($images);

        foreach ($images as $image) {
            $this->updateTagsFromFoundTags($image);
            $this->redis->srem("images_with_new_found_tags", $image->getId());

            $counter++;
        }

        return $counter;
    }

    private function array_icount_values($array)
    {
        $ret_array = array();
        foreach ($array as $key => $value) {
            if (isset($ret_array[strtolower($key)])) {
                $ret_array[strtolower($key)] += $value;
            } else {
                $ret_array[strtolower($key)] = $value;
            }
        }

        return $ret_array;
    }

    /**
     * Merge tags with summarized found tags (remove bad and filter by score)
     */
    public function updateTagsFromFoundTags(Image $image)
    {

        $foundTags = $image->getTagsFound();

        if (empty($foundTags)) {
            return 0;
        }
        foreach ($foundTags as $service => $tags) {
            $foundTags[$service] = $this->array_icount_values($foundTags[$service]);
        }
        $foundTags = $this->calculateTagsScore($foundTags);

        $goodTags = $this->filterTagsByScore($foundTags, 20);

        $goodTags = array_keys($goodTags);

        if (count($goodTags)) {
            $goodTags = array_unique(array_merge((array) $image->getTags(), $goodTags));
            $this->logger->info("Saving tags: " . implode(", ", $goodTags));
            $this->saveTagsForImage($image, $goodTags);
        }

        return count($goodTags);
    }

    public function saveTagsForImage(Image $image, array $goodTags)
    {

        $image->getTagsRef()->clear();

        foreach ($goodTags as $goodTag) {
            $tag = $this->dm->getRepository("ImagepushBundle:Tag")->findOneBy(array("text" => $goodTag));

            if (null === $tag) {
                $tag = new DocumentTag();
                $tag->setText($goodTag);
            }

            $image->addTagsRef($tag);

            $latestTag = new LatestTag();
            $latestTag->setTimestamp(time());
            $latestTag->setText($goodTag);
            //\D::dump($latestTag);

            $tag->addImagesRef($image);
            $tag->incUsedInUpcoming(1);

            $this->dm->persist($latestTag);
            $this->dm->persist($tag);
        }

        $image->setTags($goodTags);
        $this->dm->persist($image);

        $this->dm->flush();
    }

    /**
     * Find tags for the source.
     *
     * @return array|false Array of found tags or false if nothing found
     */
    public function processTags(Image $image)
    {

        $services = array_keys((array) $this->container->getParameter('imagepush.tag_group_value'));

        if (!count($services)) {
            $this->logger->err("There are no tag services configured in imagepush.tag_group_value parameter");

            return false;
        }

        $allTags = array();

        foreach ($services as $service) {

            if (!$this->container->has("imagepush.processor.tag." . $service)) {
                $this->logger->err("Can't find service imagepush.processor.tag." . $service);
                continue;
            }

            $allTags[$service] = $this->container->get("imagepush.processor.tag." . $service)->find($image);

            $message = "All found tags (" . $service . "): " . print_r($allTags[$service], true);
            $this->logger->info($message);
        }

        $allTags = $this->calculateTagsScore($allTags);
        //\D::debug($allTags);

        $bestTags = $this->filterTagsByScore($allTags, 20);
        //\D::debug($bestTags);

        $bestTags = array_keys($bestTags);

        $message = "Best found tags: " . implode(", ", $bestTags);
        $this->logger->info($message);

        if (count($bestTags)) {

            foreach ($bestTags as $bestTag) {
                $tag = $this->dm->getRepository("ImagepushBundle:Tag")->findOneBy(array("text" => $bestTag));

                if (null === $tag) {
                    $tag = new DocumentTag();
                    $tag->setText($bestTag);
                }

                $image->addTagsRef($tag);

                $latestTag = new LatestTag();
                $latestTag->setTimestamp(time());
                $latestTag->setText($bestTag);
                //\D::dump($latestTag);

                $tag->addImagesRef($image);
                $tag->incUsedInUpcoming(1);

                $this->dm->persist($latestTag);
                $this->dm->persist($tag);
            }

            $image->setTags($bestTags);
            $this->dm->persist($image);

            $this->dm->flush();
        }

        return $bestTags;
    }

    /**
     * Calculate tags score
     *
     * @param array $allTags All tags
     *
     * @return array()|true Array of good tags
     */
    public function calculateTagsScore($allTags = array())
    {

        if (!count($allTags)) {
            return array();
        }

        $finalTags = array();

        $tagGroupValue = (array) $this->container->getParameter('imagepush.tag_group_value');

        foreach ($allTags as $group => $tags) {

            if (!$tags) {
                continue;
            }

            // get value for each tag group
            $groupValue = (isset($tagGroupValue[$group]) ? $tagGroupValue[$group] : 1);

            foreach ($tags as $tag => $tagMentioned) {
                if (isset($finalTags[$tag])) {
                    $finalTags[$tag] += $tagMentioned * $groupValue;
                } else {
                    $finalTags[$tag] = $tagMentioned * $groupValue;
                }
            }
        }

        arsort($finalTags);

        return $finalTags;
    }

    /**
     * Return array with tag text (as key) and tag mentions counter (as value).
     * Also does synonyms replacement and sort by mentions.
     *
     * @param array $tags
     *
     * @return array Example: array("photo" => 2, "fun" => 1);
     */
    public function fixTagsArray($tags = array())
    {

        if (!count($tags)) {
            return array();
        }

        $newTags = array();

        $uselessTags = (array) $this->container->getParameter('imagepush.useless_tags');
        $tagSynonyms = (array) $this->container->getParameter('imagepush.synonyms_tags');

        foreach ($tags as $tag => $mention) {

            // If array of tags doesn't have score, but just text, then use value as key and set score to 1
            if (is_int($tag) && !is_int($mention)) {
                $tag = $mention;
                $mention = 1;
            }

            $tag = CustomStrings::cleanTag($tag);

            // Skip short and meaningless tags
            if (mb_strlen($tag, "UTF-8") < 3 || in_array($tag, $uselessTags)) {
                continue;
            }

            // Replace tags with synonyms
            if (in_array($tag, array_keys($tagSynonyms))) {
                $tag = $tagSynonyms[$tag];
            }

            // Sum up mentions
            if (isset($newTags[$tag])) {
                $newTags[$tag] += $mention;
            } else {
                $newTags[$tag] = $mention;
            }
        }

        arsort($newTags);

        return $newTags;
    }

    /**
     * Return array of tags which have mentions.
     *
     * @return array
     */
    public function filterTagsByScore($tags, $maxCount = 10)
    {

        if (!count($tags)) {
            return array();
        }

        $newTags = array();
        $maxScore = max(array_values($tags));

        foreach ($tags as $tag => $score) {

            if (count($newTags) >= $maxCount) {
                break;
            }

            if ($score >= ($maxScore / 2)) {
                $newTags[$tag] = $score;
            }
        }

        return $newTags;
    }

}
