<?php

namespace Imagepush\ImagepushBundle\Services\Processor;

class Config
{
    // IDs of sources

    const SRC_SOURCE = 0; // original source
    const SRC_DIGG = 1;
    const SRC_DELICIOUS = 2;
    const SRC_STUMBLEUPON = 3;
    const SRC_REDDIT = 4;
    const SRC_TWITTER = 5;

    // Tag group value
    public static $tagGroupValue = array(
        self::SRC_SOURCE => 3, // Importance of the tag coming from the original source is quiet high (for example, if source if digg, then its category is highly important)
        self::SRC_DIGG => 2,
        self::SRC_DELICIOUS => 2,
        self::SRC_STUMBLEUPON => 2.5,
        self::SRC_REDDIT => 1.5,
        self::SRC_TWITTER => 3,
    );

    /**
     * Set $modifyDB to false for prod.
     */
    public static $modifyDB = true;
    //static $modifyDB = false;

    public static $allowedImageContentTypes = array("image/gif", "image/jpeg", "image/jpg", "image/png");
    public static $minWidth = 450;
    public static $minHeight = 180;
    public static $minRatio = 0.3;
    public static $maxRatio = 2.5;
    public static $minFilesize = 20480;   // 20KB in bytes
    public static $maxFilesize = 8388608; // 8MB in bytes

    /**
     * Array of thumbnails to generate during processing
     * @var array
     */
    public static $thumbTypes = array(
        array("in", 463, 1548),
        array("out", 140, 140),
        array("in", 625, 2090)
    );

    /*
      static $thumbTypes = array(
      "m" => array(// main page
      "action" => "thumbnail_inset",
      "width" => 463,
      "height" => 1548, // was 1510
      ),
      "t" => array(// thumb
      "action" => "thumbnail_outbound",
      "width" => 140,
      "height" => 140,
      ),
      "a" => array(// article
      "action" => "thumbnail_inset",
      "width" => 625,
      "height" => 2090,
      ),
      );
     */

    /**
     * Tags
     */
    public static $minTitleLengthForTwitterSearch = 15;
    public static $minSubRedditScore = 5;
    // These tags will not be saved or displayed, because they are not specific enough
    public static $uselessTags = array("reddit.com", "reddit", "askreddit", "pic", "digg", "digguser", "diggrt", "fun", "funny", "pict", "lol", "humor", "humour", "image", "img", "imagepush", "reportthespammer", "fffffffuuuuuuuuuuuu", "flickr", "filetype:jpg", "media:image");
    // These tags will not be displayed in the top trends, because they are almost not changeable there, though they are very informative, so we can't put them to $USELESS_TAGS
    public static $hiddenTrends = array("offbeat", "lifestyle", "entertainment", "technology", "science");
    // These tags will be replaced as synonyms. Should be in single form (not plural).
    public static $tagSynonyms = array(
        "photography" => "photo",
        "picture" => "pic",
        "img" => "pic",
        "image" => "pic",
        "humor" => "fun",
        "lol" => "fun",
        "haha" => "fun",
        "hehe" => "fun",
        "joke" => "fun",
        "worldnews" => "world news",
        "busines" => "business",
        "busine" => "business",
    );

}