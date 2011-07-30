<?php

namespace Imagepush\ImagepushBundle\Model;

/**
 * Imagepush\ImagepushBundle\Model\Tag
 */
class Tag
{
  // These tags will not be saved or displayed
  static $BLACKLIST_TAGS = array("reddit.com", "imagepush", "reportthespammer");

  // These tags will not be saved or displayed, because they are not specific enough
  static $USELESS_TAGS = array("reddit.com", "reddit", "askreddit", "pic", "digg", "digguser", "diggrt", "fun", "funny", "pict", "lol", "humor", "humour", "image", "img");

  // These tags will not be displayed in the top trends, because they are almost not changeable there, though they are very informative, so we can't put them to $USELESS_TAGS
  static $HIDDEN_TRENDS = array("offbeat", "lifestyle", "entertainment", "technology", "science");

}