<?php

namespace Imagepush\ImagepushBundle\Services\Processor;

use Imagepush\ImagepushBundle\Services\Processor\Config;
use Imagepush\ImagepushBundle\Document\Image;
use Imagepush\ImagepushBundle\External\CustomStrings;
use Imagepush\ImagepushBundle\Services\Fetcher\Digg\ImagepushDigg;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TagProcessor extends HtmlContent
{

    /**
     * Image entity
     * @var Image
     */
    public $image;

    /**
     * All tags, which should be saved for image, but not all has a high score.
     * @var array
     */
    public static $allTags = array();

    /**
     * Best tags with highest score. Use this to save in image entity.
     * @var array
     */
    public static $bestTags;

    /**
     * @services
     */
        public $container, $tagsManager;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->tagsManager = $container->get('imagepush.tags.manager');
    }

    /*
      public function setImage(Image $image)
      {
      $this->image = $image;
      }
     */

    /**
     * Find tags for the source.
     * @todo: see here:
     * http://sharedcount.com/?url=http%3A%2F%2Fimagepush.to%2F
     * http://www.linkedin.com/cws/share-count?url=http://www.facebook.com
     * @return array()|false Array of found tags or false if nothing found
     */
    public function processTags(Image $image)
    {
        //$this->image->link = "http://www.flickr.com/photos/passiveaggressive/3642661392/sizes/o/";
        //$this->image->link = "http://i.imgur.com/NTAwq.jpg";

        \D::dump($image->getSourceTags());

        if (count($image->getSourceTags())) {
            $sourceTags = $this->tagsManager->getHumanTags($image->getSourceTags());
        } else {
            $sourceTags = array();
        }

        self::$allTags[Config::SRC_SOURCE] = self::fixTagsArray($sourceTags);
        self::$allTags[Config::SRC_STUMBLEUPON] = $this->findInStumbleUpon();
        self::$allTags[Config::SRC_DELICIOUS] = $this->findInDelicious();
        self::$allTags[Config::SRC_TWITTER] = $this->findInTwitter();
        self::$allTags[Config::SRC_DIGG] = $this->findInDigg();
        self::$allTags[Config::SRC_REDDIT] = $this->findInReddit();

        //\D::dump(self::$allTags);
        //echo serialize(self::$allTags);
        //self::$allTags = unserialize('a:5:{i:3;a:2:{s:11:"Photography";i:1;s:6:"Comedy";i:1;}i:2;a:10:{s:5:"funny";i:51;s:5:"humor";i:35;s:6:"photos";i:28;s:9:"carnivore";i:24;s:5:"vegan";i:24;s:7:"awesome";i:20;s:6:"flickr";i:19;s:4:"food";i:17;s:6:"images";i:15;s:7:"culture";i:9;}i:5;a:0:{}i:1;a:0:{}i:4;a:12:{s:4:"pics";i:2;s:5:"funny";i:5;s:11:"Pantyfetish";i:1;s:3:"WTF";i:1;s:17:"reportthespammers";i:2;s:9:"treecipes";i:1;s:3:"veg";i:1;s:5:"vegan";i:6;s:19:"fffffffuuuuuuuuuuuu";i:5;s:11:"Supplements";i:1;s:13:"AdviceAnimals";i:1;s:10:"freeganism";i:1;}}');

        self::$allTags = self::filterTagsNotWorthToBeSaved();
        //\D::dump(self::$allTags);
        self::$bestTags = self::filterTagsByScore(self::$allTags, 20);
        //\D::dump(self::$bestTags);

        $this->modifyTagListsAndUpcomingStreams($this->image->timestamp);

        $this->image->saveAsProcessedWithTags(self::$bestTags, self::$allTags);
    }

    /**
     * StumbleUpon
     * Sample URL: http://www.stumbleupon.com/services/1.01/badge.getinfo?url=http://www.treehugger.com/
     * YQL sample URL: SELECT content FROM html WHERE url="http://www.stumbleupon.com/url/www.flickr.com/photos/passiveaggressive/3642661392/sizes/o/" and xpath='//ul[@class="listTopics"]/li/a'
     * @return array|false Array of found tags; Empty array if no tags found; False - if error or not indexed
     */
    public function findInStumbleUpon()
    {

        $url = "http://www.stumbleupon.com/services/1.01/badge.getinfo?url=" . $this->image->link;
        $xpathQuery = '//ul[@class="listTopics"]/li/a';

        $content = new Content($this->kernel);
        $content->get($url);

        $response = @json_decode($content->getContent(), true);

        if (true !== $response["result"]["in_index"]) {
            return array();
        }

        $content->get($response["result"]["info_link"]);

        $this->setData($content->getData());

        $domxpath = new \DOMXPath($this->getDom());
        $filtered = $domxpath->query($xpathQuery);

        $tags = array();

        foreach ($filtered as $item) {
            $tags[$item->nodeValue] = 1;
        }

        $tags = self::fixTagsArray($tags);

        return $tags;
    }

    /**
     * Delicious tags
     * Get tags for URL - http://feeds.delicious.com/v2/json/urlinfo/data?hash=md5(http://...)
     */
    public function findInDelicious()
    {

        $url = "http://feeds.delicious.com/v2/json/urlinfo/data?hash=" . md5($this->image->link);

        $content = new Content($this->kernel);
        $content->get($url);
        //\D::dump($content);

        $response = json_decode($content->getContent(), true);

        if (!$response || !count($response) || !count($response[0]["top_tags"])) {
            return array();
        }

        $tags = self::fixTagsArray($response[0]["top_tags"]);
        $tags = self::filterTagsByScore($tags, 20);

        return $tags;
    }

    /**
     * Twitter
     * Twitter search. Hashtags in twitter can be used as tags
     * Search for url or exact title - https://dev.twitter.com/docs/api/1/get/search
     * @return array|false Array of found tags; Empty array if no tags found; False - if error or not indexed
     */
    public function findInTwitter()
    {

        $urls[] = "http://search.twitter.com/search.json?rpp=100&result_type=mixed&q=" . urlencode($this->image->link);

        // Search by title can very unspecific, when title is short
        if (mb_strlen($this->image->title) > Config::$minTitleLengthForTwitterSearch) {
            $urls[] = "http://search.twitter.com/search.json?rpp=100&result_type=mixed&q=" . urlencode('"' . $this->image->title . '"');
        }

        $tags = array();

        foreach ($urls as $url) {

            $content = new Content($this->kernel);
            $content->get($url);

            $response = @json_decode($content->getContent(), true);

            if (!count($response["results"])) {
                continue;
            }

            foreach ($response["results"] as $tweet) {
                if (preg_match_all("/#([\\d\\w]+)/", $tweet["text"], $out)) {
                    $tags = array_merge($tags, $out[1]);
                    //\D::dump($out);
                }
            }
        }

        $tags = self::fixTagsArray($tags);
        $tags = self::filterTagsByScore($tags);

        return $tags;
    }

    /**
     * Digg
     * Digg topics/tags (it is useless when we fetch data from digg only, because tags are already saved)
     * Get topics - http://developers.digg.com/version2/story-getinfo
     * @return array|false Array of found tags; Empty array if no tags found; False - if error or not indexed
     */
    public function findInDigg()
    {

        $digg = new ImagepushDigg();
        $digg->setVersion('2.0');

        $result = $digg->story->getInfo(array('links' => urlencode($this->image->link)));

        $tags = array();

        if (empty($result) || !$result->count) {
            return array();
        }

        foreach ($result->stories as $story) {
            if ($story->topic->name != "*") {
                $tags[] = $story->topic->name;
            }
        }

        $tags = self::fixTagsArray($tags);

        return $tags;
    }

    /**
     * Reddit
     * a) search for link by url: http://www.reddit.com/api/info.json?url=http%3A%2F%2Fi.imgur.com%2FM52mw.png
     * b) get subreddits
     * c) get subreddits of the related links
     * YQL is forbidden for some things because of reddit's robots.txt, but it is allowed to fetch reddit pages directly
     * @return array|false Array of found tags; Empty array if no tags found; False - if error or not indexed
     */
    public function findInReddit()
    {

        $url = "http://www.reddit.com/api/info.json?url=" . urlencode($this->image->link);
        $xpathQuery = '//div[contains(concat(" ", @class, " "), " entry ")]/p[@class="tagline"]/a[contains(concat(" ", @class, " "), " subreddit ")]';

        $tags = $subUrls = array();

        $content = new Content($this->kernel);
        $content->get($url);

        $response = @json_decode($content->getContent(), true);

        if (!count($response["data"]["children"])) {
            return array();
        }

        // Get subreddits of the main link
        foreach ($response["data"]["children"] as $child) {

            $tags[] = $child["data"]["subreddit"];

            if ($child["data"]["score"] >= Config::$minSubRedditScore) {
                $subUrls[] = "http://www.reddit.com" . str_replace("/comments/", "/related/", $child["data"]["permalink"]);
                $subUrls[] = "http://www.reddit.com" . str_replace("/comments/", "/duplicates/", $child["data"]["permalink"]);
            }
        }

        foreach ($subUrls as $subUrl) {

            $content->get($subUrl);
            $this->setData($content->getData());

            $domxpath = new \DOMXPath($this->getDom());
            $filtered = $domxpath->query($xpathQuery);

            foreach ($filtered as $item) {
                $tags[] = $item->nodeValue;
            }
        }

        $tags = self::fixTagsArray($tags);
        $tags = self::filterTagsByScore($tags);

        return $tags;
    }

    /**
     * Filter out useless tags, spam, bad words, etc.
     * @return array()|true Array of good tags
     */
    public static function filterTagsNotWorthToBeSaved()
    {

        $finalTags = array();

        if (!count(self::$allTags) || !count(Config::$uselessTags)) {
            return $finalTags;
        }

        foreach (self::$allTags as $tagGroup => $tags) {

            if (!$tags) {
                continue;
            }

            // get value for each tag group
            $tagGroupValue = (isset(Config::$tagGroupValue[$tagGroup]) ? Config::$tagGroupValue[$tagGroup] : 1);

            foreach ($tags as $tag => $tagMentioned) {

                // Cleanup is done in fixTagsArray already
                // Replace tags with synonyms
                if (in_array($tag, array_keys(Config::$tagSynonyms))) {
                    $tag = Config::$tagSynonyms[$tag];
                }

                if (isset($finalTags[$tag])) {
                    $finalTags[$tag] += $tagMentioned * $tagGroupValue;
                } else {
                    $finalTags[$tag] = $tagMentioned * $tagGroupValue;
                }
            }
        }

        arsort($finalTags);

        return $finalTags;
    }

    /**
     * When tags are found for sources - modify tag usage counter, push tags to the latest tags list and mark image with such tags in upcoming list.
     * @return true
     */
    public function modifyTagListsAndUpcomingStreams($timestamp)
    {

        if (count(self::$bestTags)) {

            $pipe = $this->redis->pipeline();
            foreach (self::$bestTags as $tag => $tagScore) {

                $tagKey = $this->tagsManager->getTagKey($tag, true);

                // add to timeline list
                $pipe->rpush("latest_tags", $tagKey);

                // increase each tag usage with 1 or $tagScore (?)
                $pipe->zincrby("tag_usage", 1, $tagKey);

                // mark image with this tag
                $pipe->zadd('upcoming_image_list:' . $tagKey, $timestamp, $this->image->imageKey);
            }
            $pipe->execute();
        }

        return true;
    }

    /**
     * Return array with tag value and tag mention counter as key/value. Flip if neccessary.
     * 
     * @param array $tags
     * 
     * @return array Example: array("photo" => 2, "fun" => 1); 
     */
    public static function fixTagsArray($tags = array())
    {

        if (!count($tags)) {
            return array();
        }

        $newTags = array();
        foreach ($tags as $tag => $score) {

            // If array of tags doesn't have score, but just text, then use value as key and set score to 1
            if (is_int($tag) && !is_int($score)) {
                $tag = $score;
                $score = 1;
            }

            $tag = CustomStrings::cleanTag($tag);

            if (mb_strlen($tag, "UTF-8") < 3 || in_array($tag, Config::$uselessTags)) {
                continue;
            }

            if (isset($newTags[$tag])) {
                $newTags[$tag] += $score;
            } else {
                $newTags[$tag] = $score;
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
    public static function filterTagsByScore($tags, $maxCount = 10)
    {

        if (!count($tags)) {
            return array();
        }

        $newTags = array();
        $maxScore = max(array_values($tags));

        foreach ($tags as $tag => $score) {

            /* if (!count($newTags))
              {
              $newTags[$tag] = $score;
              //$maxScore = $score;
              } else */
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