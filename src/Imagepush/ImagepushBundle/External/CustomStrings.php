<?php

namespace Imagepush\ImagepushBundle\External;

class CustomStrings
{

  static protected $separator_pattern =
    '[|\-«»—~:\@]+';

  static protected $general_endings_pattern =
    '@[{(\[][\d\s]*(pic|pics|image|images|img|imgs|graphic|graphics|graph|photo|photos|picture|pictures|gif|gifs|flowchart|comic|cartoon|gallery|slideshow|infographic|infographics|info\-graphic|infograph|w/vid|w/video|w/ vid|w/ video|w/ pics&vid|vid + pics|chart|video)+\s*[)\]}]$@ui';

  static protected $forbidden_endings_pattern =
    '@[{(\[]\s*(nsfw)+\s*[)\]}]@ui';

  static protected $url_pattern =
    '((https?://)*([-\w\.]+\.[\w]{2,})+(:\d+)?(/([-\w/_\.]*((\?|\#)*\S+)?)?)?)';

  /**
   * Modifies a string to remove all non ASCII characters and spaces.
   */
  static public function slugify($text)
  {
    // replace non letter or digits by -
    $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
    $text = trim($text, '-');

    // transliterate
    if (function_exists('iconv'))
    {
      $text = iconv('utf-8', 'us-ascii//TRANSLIT//IGNORE', $text);
    }

    // lowercase
    if (function_exists('mb_strtolower')) {
      $text = mb_strtolower($text, 'UTF-8');
    } else {
      $text = strtolower($text);
    }

    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    if (empty($text))
    {
      return 'n-a';
    }

    return $text;
  }

  /*
   * Check if text has words like "nsfw".
   * | title here (nsfw) , for example, will return true
   */
  public static function isForbiddenTitle($text) {

    return (bool)preg_match(self::$forbidden_endings_pattern, trim($text));
    
  }

  public static function makeSafeRegexFromArray($values = array()) {
    $result = array();
    foreach ($values as $value) {
      $value = trim($value);
      if ($value != "") {
        $result[] = str_replace("|", "\|", $value);
      }
    }
    return $result;
  }

  /**
   * Removes endings like (pic), (pics), (image), also remove urls, site titles, etc from the end of the title to make it look nice.
   */
  static public function cleanTitle($text)
  {

    $ends = <<<EOF
    The Oatmeal
    TheJourneyPoint
    Catastrophe Monitor
    Dzinepress
    The D-Photo
    InspireFirst
    INDEZINER
    FunnyandSpicy
    SmashingShowcase
    Professional photo gallery | Create and buy flash xml photo gallery
    Analysis & Opinion
    CNET
    Seven by Five
    Green Gardener
    Flickr - Photo Sharing!
    Photography Blog
    SO IMG
    StuffKit
    Watches World Scoop
    The EPIC Indicator!
    JaiPals
    Mail Online
    Discover Magazine
    Gamersbook
    Fortune China
    StudioEightOneSix
    Funny Pics, Interesting Facts
    HDR Creme
    lovendel-all things beautiful
    China Photos Pictures
    The Big Picture
    Telegraph
    Green Buzz Photography
    Green Buzz
    Cruzine
    Mobile Phone Lover
    AnimHuT
    The Finished Box
    Chrome Story
    How To Grow Bud
    Gadgetrance
    Alligator Sunglasses
    Infographic
    Picture Bulk
    Mad 4 Red
    Travel Media Ninja
    Web Crawler Blog
    Wired Science
    Look
    Digital Photography Shots
    Priv-Memory
    Latest Gadget Info.
    View China - Hot internet stories, pictures, & videos in China
    Megaodd.com - Odd Events, Weird Places, Strange People, Bizarre News and A Lot More
    Lava360
    Toowacky
    Designs Collage
    Tattoo Ideas - Artist and Pics
    10 Most
    Tsimpountiii Community
    Internet Marketing Advice
    State Gardienz
    GeekFill
    Weird Hut
    Idiot Duck
    Blogvibe
    State Gardienz
    koikoikoi.com - Visual Arts Magazine, graphic design, illustration, photography, interviews, inspiration, tutorials
EOF;

    $starts = <<<EOF
    The Underfold
EOF;

    $starts_pattern = implode("|", self::makeSafeRegexFromArray(explode("\n", $starts)));
    $ends_pattern = implode("|", self::makeSafeRegexFromArray(explode("\n", $ends)));
    
    // just url
    $patterns[] = '@^'.self::$url_pattern . '$@ui';

    // "read more"
    $patterns[] = '@(Read more:*\s*' . self::$url_pattern . '*\s*)$@ui';

    // separator with url
    $patterns[] = '@('.self::$separator_pattern.'\s*' . self::$url_pattern . '*\s*)$@ui';

    // (pics, images, etc)
    $patterns[] = self::$general_endings_pattern;

    // site-specific beginning or ending
    $patterns[] = '@^(' . $starts_pattern . ')?\s*' . self::$separator_pattern . '@ui';
    $patterns[] = '@' . self::$separator_pattern . '\s*(' . $ends_pattern . ')?\s*$@ui';

    // (pics, images, etc) again
    $patterns[] = self::$general_endings_pattern;

    foreach ($patterns as $pattern) {
      $text = trim($text);
      $text = preg_replace($pattern, '', $text);
    }
    
    // replace newlines with max 1 space
    $text = str_replace("\n", "", $text);
    $text = preg_replace('/\s\s+/', ' ', $text);

    $text = trim($text);

    if ($text == "") $text = "Untitled";

    return $text;
  }
  
  /**
   * Remove useless chars, spaces, newlines, singularize, etc and return it.
   */
  public function cleanTag($tag)
  {

    $tag = mb_strtolower($tag, 'UTF-8');

    $tag = preg_replace('/\n/', ' ', $tag);       // newlines to spaces

    $tag = trim($tag);
    $tag = preg_replace('/\p{P}+\s*$/', '', $tag); // all punctuation at the end
    $tag = preg_replace('/^\s*\p{P}+/', '', $tag); // all punctuation at the beginning
    //$tag = preg_replace('/\p{P}+/', '', $tag); // all punctuation in the middle
    $tag = preg_replace('/\s\s+/', ' ', $tag);    // not more than one space

    $tag = trim($tag);
    $tag = Inflect::singularize($tag);

    return $tag;

  }
  

}