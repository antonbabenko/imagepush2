<?php

namespace Imagepush\ImagepushBundle\Services\Processor;

use Imagepush\ImagepushBundle\Services\Processor\Config;
use Imagepush\ImagepushBundle\Services\Processor\Content;
use Imagepush\ImagepushBundle\External\CustomStrings;

class Tag extends Html
{

  public $source;
  public $imageKey;
  public static $tmpTags;

  /*
   * @services
   */
  public $kernel;

  public function __construct(\AppKernel $kernel)
  {
    $this->kernel = $kernel;
  }

  public function setImageKey($imageKey)
  {
    $this->imageKey = $imageKey;
  }

  public function setSource($source)
  {
    $this->source = $source;
  }

  /**
   * Find tags for the source.
   * @todo: see here:
   * http://sharedcount.com/?url=http%3A%2F%2Fimagepush.to%2F
   * http://www.linkedin.com/cws/share-count?url=http://www.facebook.com
   * @return array()|false Array of found tags or false if nothing found
   */
  public function processTags()
  {
    $images = $this->kernel->getContainer()->get('imagepush.images');

    $this->source["link"] = "http://www.flickr.com/photos/passiveaggressive/3642661392/sizes/o/";
    
    if (!empty($this->source["tags"])) {
      self::$tmpTags[Config::SRC_ORIGINAL] = $this->source["tags"];
    }
    \D::dump(self::$tmpTags);

    /*
      self::$tmpTags[Config::SRC_STUMBLEUPON] = $this->findInStumbleUpon();
      self::$tmpTags[Config::SRC_DELICIOUS] = $this->findInDelicious();
      self::$tmpTags[Config::SRC_TWITTER] = $this->findInTwitter();
      self::$tmpTags[Config::SRC_DIGG] = $this->findInDigg();
      self::$tmpTags[Config::SRC_REDDIT] = $this->findInReddit();

      //echo serialize(self::$tmpTags);
     */
    self::$tmpTags = unserialize('a:5:{i:3;a:2:{s:11:"Photography";i:1;s:6:"Comedy";i:1;}i:2;a:10:{s:5:"funny";i:51;s:5:"humor";i:35;s:6:"photos";i:28;s:9:"carnivore";i:24;s:5:"vegan";i:24;s:7:"awesome";i:20;s:6:"flickr";i:19;s:4:"food";i:17;s:6:"images";i:15;s:7:"culture";i:9;}i:5;a:0:{}i:1;a:0:{}i:4;a:12:{s:4:"pics";i:2;s:5:"funny";i:5;s:11:"Pantyfetish";i:1;s:3:"WTF";i:1;s:17:"reportthespammers";i:2;s:9:"treecipes";i:1;s:3:"veg";i:1;s:5:"vegan";i:6;s:19:"fffffffuuuuuuuuuuuu";i:5;s:11:"Supplements";i:1;s:13:"AdviceAnimals";i:1;s:10:"freeganism";i:1;}}');

    $this->filterTagsNotWorthToBeSaved();

    // Get one current image
    $image = $images->getImageByKey($this->imageKey);
    
    $this->modifyTagListsAndUpcomingStreams($image["timestamp"]);
    
    $images->saveImageTags($this->imageKey, self::$tmpTags);
    
    \D::dump(self::$tmpTags);
  }

  /**
   * StumbleUpon
   * Sample URL: http://www.stumbleupon.com/services/1.01/badge.getinfo?url=http://www.treehugger.com/
   * YQL sample URL: SELECT content FROM html WHERE url="http://www.stumbleupon.com/url/www.flickr.com/photos/passiveaggressive/3642661392/sizes/o/" and xpath='//ul[@class="listTopics"]/li/a'
   * @return array|false Array of found tags; Empty array if no tags found; False - if error or not indexed
   */
  public function findInStumbleUpon()
  {

    $url = "http://www.stumbleupon.com/services/1.01/badge.getinfo?url=" . $this->source["link"];
    $xpathQuery = '//ul[@class="listTopics"]/li/a';

    $this->initAndFetch($url);
    $content = $this->getContent();
    //\D::dump($content);

    if (empty($content))
    {
      return false;
    }

    $response = json_decode($content, true);

    if (true !== $response["result"]["in_index"])
    {
      return false;
    }

    $this->initAndFetch($response["result"]["info_link"]);
    $content = $this->getContent();

    $this->initDom();

    $domxpath = new \DOMXPath($this->dom);
    $filtered = $domxpath->query($xpathQuery);

    foreach ($filtered as $item) {
      $tags[$item->nodeValue] = 1;
    }

    return (empty($tags) ? array() : $tags);
  }

  /**
   * Delicious tags
   * Get tags for URL - http://feeds.delicious.com/v2/json/urlinfo/data?hash=md5(http://...)
   */
  public function findInDelicious()
  {

    $url = "http://feeds.delicious.com/v2/json/urlinfo/data?hash=" . md5($this->source["link"]);

    $this->initAndFetch($url);
    $content = $this->getContent();
    //\D::dump($content);

    if (empty($content))
    {
      return false;
    }

    $response = json_decode($content, true);

    if (!count($response) || !count($response[0]["top_tags"]))
    {
      return false;
    }

    $tags = $response[0]["top_tags"];
    $tags = self::filterTagsByScore($tags, 20);

    return (empty($tags) ? array() : $tags);
  }

  /**
   * Twitter
   * Twitter search. Hashtags in twitter can be used as tags
   * Search for url or exact title - https://dev.twitter.com/docs/api/1/get/search
   * @return array|false Array of found tags; Empty array if no tags found; False - if error or not indexed
   */
  public function findInTwitter()
  {

    $urls[] = "http://search.twitter.com/search.json?rpp=100&result_type=mixed&q=" . urlencode($this->source["link"]);

    // Search by title can very unspecific, when title is short
    if (mb_strlen($this->source["title"]) > Config::$minTitleLengthForTwitterSearch)
    {
      $urls[] = "http://search.twitter.com/search.json?rpp=100&result_type=mixed&q=" . urlencode('"' . $this->source["title"] . '"');
    }

    $tags = array();

    foreach ($urls as $url) {
      //\D::dump($url);

      $this->initAndFetch($url);
      $content = $this->getContent();
      //\D::dump($content);

      if (empty($content))
      {
        continue;
      }

      $response = json_decode($content, true);

      if (!count($response["results"]))
      {
        continue;
      }

      foreach ($response["results"] as $tweet) {
        if (preg_match_all("/#([\\d\\w]+)/", $tweet["text"], $out))
        {
          $tags = array_merge($tags, $out[1]);
          //\D::dump($out);
        }
      }
    }

    $tags = self::filterTagsByScore($tags);

    return (empty($tags) ? array() : $tags);
  }

  /**
   * Digg
   * Digg topics/tags (it is useless when we fetch data from digg only, because tags are already saved)
   * Get topics - http://developers.digg.com/version2/story-getinfo
   * @return array|false Array of found tags; Empty array if no tags found; False - if error or not indexed
   */
  public function findInDigg()
  {

    $digg = $this->kernel->getContainer()->get('imagepush.fetcher.digg')->getDiggInstance();

    $result = $digg->story->getInfo(array('links' => urlencode($this->source["link"])));

    $tags = array();

    if (empty($result) || !$result->count)
    {
      return false;
    }

    //\D::dump($result);
    foreach ($result->stories as $story) {
      if ($story->topic->name != "*")
        $tags[] = $story->topic->name;
    }


    return (empty($tags) ? array() : $tags);
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

    $url = "http://www.reddit.com/api/info.json?url=" . urlencode($this->source["link"]);
    $xpathQuery = '//div[contains(concat(" ", @class, " "), " entry ")]/p[@class="tagline"]/a[contains(concat(" ", @class, " "), " subreddit ")]';

    $tags = $subUrls = array();

    $this->initAndFetch($url);
    $content = $this->getContent();
    //\D::dump($content);

    if (empty($content))
    {
      return false;
    }

    $response = json_decode($content, true);

    if (!count($response["data"]["children"]))
    {
      return false;
    }

    // Get subreddits of the main link
    foreach ($response["data"]["children"] as $child) {
      $tag = $child["data"]["subreddit"];
      if (isset($tags[$tag]))
      {
        $tags[$tag] += 1;
      } else
      {
        $tags[$tag] = 1;
      }

      if ($child["data"]["score"] >= Config::$minSubRedditScore)
      {
        $subUrls[] = "http://www.reddit.com" . str_replace("/comments/", "/related/", $child["data"]["permalink"]);
        $subUrls[] = "http://www.reddit.com" . str_replace("/comments/", "/duplicates/", $child["data"]["permalink"]);
      }
    }

    foreach ($subUrls as $subUrl) {
      //\D::dump($subUrl);
      $this->initAndFetch($subUrl);
      $content = $this->getContent();

      $this->initDom(true);

      $domxpath = new \DOMXPath($this->dom);
      $filtered = $domxpath->query($xpathQuery);
      //\D::dump($filtered);

      foreach ($filtered as $item) {
        if (isset($tags[$item->nodeValue]))
        {
          $tags[$item->nodeValue] += 1;
        } else
        {
          $tags[$item->nodeValue] = 1;
        }
      }
    }

    $tags = self::filterTagsByScore($tags);

    return (empty($tags) ? array() : $tags);
  }

  /**
   * Filter out useless tags, spam, bad words, etc.
   * @return array()|true Array of good tags
   */
  public function filterTagsNotWorthToBeSaved()
  {

    //\D::dump(self::$tmpTags);

    if (!count(self::$tmpTags) || !count(Config::$uselessTags))
      return false;

    foreach (self::$tmpTags as $tagGroup => $tags) {

      // get value for each tag group
      $tagGroupValue = (isset(Config::$tagGroupValue[$tagGroup]) ? Config::$tagGroupValue[$tagGroup] : 1);

      foreach ($tags as $tag => $tagMentioned) {
        $tag = CustomStrings::cleanTag($tag);

        if (mb_strlen($tag, "UTF-8") < 2 || in_array($tag, Config::$uselessTags))
          continue;

        // Replace tags with synonyms
        if (in_array($tag, array_keys(Config::$tagSynonyms)))
        {
          $tag = Config::$tagSynonyms[$tag];
        }

        if (isset($finalTags[$tag]))
        {
          $finalTags[$tag] += $tagMentioned * $tagGroupValue;
        } else
        {
          $finalTags[$tag] = $tagMentioned * $tagGroupValue;
        }
      }
    }

    //\D::dump($finalTags);
    $finalTags = self::filterTagsByScore($finalTags, 20);
    //\D::dump($finalTags);

    return self::$tmpTags = $finalTags;

  }
  
  /**
   * When tags are found for sources - modify tag usage counter, push tags to the latest tags list and mark image with such tags in upcoming list.
   * @return true
   */
  public function modifyTagListsAndUpcomingStreams($timestamp) {
    
    if (count(self::$tmpTags)) {
      
      $redis = $this->kernel->getContainer()->get('snc_redis.default_client');
      $tags = $this->kernel->getContainer()->get('imagepush.tags');
      
      $pipe = $redis->pipeline();
      foreach (self::$tmpTags as $tag => $tagValue) {
        // add to timeline list
        $pipe->rpush("latest_tags", $tag);

        // increase each tag usage with 1 or $tagValue (?)
        $pipe->zincrby("tag_usage", 1, $tag);
        
        // mark image with this tag
        $pipe->zadd('upcoming_image_list:'.$tags->getTagKey($tag, true), $timestamp, $this->imageKey);
      }
      $pipe->execute();
    }
    
    return true;
      
  }

  public static function filterTagsByScore($tags, $maxCount = 5)
  {

    if (!count($tags))
      return array();

    arsort($tags);

    $newTags = array();
    foreach ($tags as $tag => $score) {
      if (!count($newTags))
      {
        $newTags[] = $tag;
        $maxScore = $score;
      } elseif ($score >= ceil($maxScore / 2) && count($newTags) < $maxCount)
      {
        $newTags[] = $tag;
      } else
      {
        break;
      }
    }

    return $newTags;
  }

}