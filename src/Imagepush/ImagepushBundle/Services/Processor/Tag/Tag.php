<?php

namespace Imagepush\ImagepushBundle\Services\Processor\Tag;

//use Imagepush\ImagepushBundle\Services\Processor\Config;
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
    public $allTags;

    /**
     * Best tags with highest score. Use this to save in image entity.
     * @var array
     */
    public $bestTags;

    /**
     * @services
     */
    public $container;
    public $dm;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        //$this->tagsManager = $container->get('imagepush.tags.manager');
        $this->dm = $container->get('doctrine.odm.mongodb.document_manager');
    }

    /**
     * Find tags for the source.
     * 
     * @return array()|false Array of found tags or false if nothing found
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
        }

        $allTags = $this->calculateTagsScore($allTags);
        //\D::debug($allTags);

        $bestTags = $this->filterTagsByScore($allTags, 20);
        //\D::debug($bestTags);
        //echo serialize($allTags);

        $bestTags = array_keys($bestTags);

        //$bestTags = array("science", "sport", "technology");

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
                $latestTag->setTag($tag);
                \D::dump($latestTag);

                $tag->addImagesRef($image);
                $tag->incUsedInUpcoming(1);

                $this->dm->persist($latestTag);
                $this->dm->persist($tag);
            }

            $image->setTags($bestTags);
            $this->dm->persist($image);

            $this->dm->flush();
        }

        return $allTags;
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