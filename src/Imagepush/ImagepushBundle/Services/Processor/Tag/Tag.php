<?php

namespace Imagepush\ImagepushBundle\Services\Processor\Tag;

use Imagepush\ImagepushBundle\Document\Image;
use Imagepush\ImagepushBundle\External\CustomStrings;
use Imagepush\ImagepushBundle\Repository\ImageRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Tag
{
    /**
     * @var ContainerInterface
     */
    public $container;

    /**
     * @var \Aws\DynamoDb\DynamoDbClient
     */
    protected $ddb;

    /**
     * @var ImageRepository
     */
    protected $imageRepo;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->imageRepo = $container->get('imagepush.repository.image');
        $this->logger = $container->get('logger');
    }

    /**
     * Save all tags merged with existing tags into DB
     * but not passed through filterTagsByScore()
     *
     * @param Image  $image
     * @param string $serviceName
     * @param array  $foundTags
     */
    public function saveTagsFound($image, $serviceName, $foundTags)
    {
        $existingTags = $image->getTagsFound();

        if (isset($existingTags[$serviceName]) && is_array($existingTags[$serviceName])) {
            $existingTags[$serviceName] = array_merge($existingTags[$serviceName], (array) $foundTags);
        } else {
            $existingTags[$serviceName] = (array) $foundTags;
        }

        $image->setTagsFound($existingTags);
        $image->setRequireUpdateTags(true);

        // Count found tags as all items minus count of service names
        $image->setTagsFoundCount(count($existingTags, COUNT_RECURSIVE) - count($existingTags));

        $this->imageRepo->save($image);
    }

    /**
     * @param $array
     * @return array
     */
    public function array_icount_values($array)
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

}
