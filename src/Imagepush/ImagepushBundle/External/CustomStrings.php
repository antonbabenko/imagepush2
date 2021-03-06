<?php

namespace Imagepush\ImagepushBundle\External;

class CustomStrings
{

    public static $separatorPattern =
        '[|\-«»—~:\@]+';
    public static $generalEndingsPattern =
        '@[-{(\[][\d\s]*(pic|pics|pic\.|image|images|img|imgs|graphic|graphics|graph|photo|photos|picture|pictures|gif|gifs|flowchart|comic|cartoon|gallery|slideshow|infographic|infographics|info\-graphic|infograph|w/vid|w/video|w/ vid|w/ video|w/ pics&vid|vid + pics|chart|video|i\.imgur\.com)+\s*[)\]}]*$@ui';
    public static $forbiddenEndingsPattern =
        '@[-{(\[]\s*(nsfw)+\s*[)\]}]@ui';
    public static $urlPattern =
        '(https?://([-\w\.]+\.[\w]{2,})+(:\d+)?(/([-\w/_\.]*((\?|\#)*\S+)?)?)?)';

    /**
     * @var $urlPatternInText URI, which is usually in title (protocol part is not required)
     */
    public static $urlPatternInText =
        '((https?://)*([-\w\.]+\.[\w]{2,})+(:\d+)?(/([-\w/_\.]*((\?|\#)*\S+)?)?)?)';
    public static $removeStart = '
    The Underfold
    Photos
    Photo
';
    public static $removeEnd = '
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
    The Fonda Theatre
    Imgur
    The Standard Downtown
    Slideshows
';

    /**
     * Modifies a string to remove all non ASCII characters and spaces.
     */
    public static function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
        $text = trim($text, '-');

        // lowercase
        if (function_exists('mb_strtolower')) {
            $text = mb_strtolower($text, 'UTF-8');
        } else {
            $text = strtolower($text);
        }

        // remove unwanted characters
        $text = preg_replace('~[^-\\pL\d]+~u', '', $text);
        if ($text === "") {
            return 'n-a';
        }

        // keep it max 200 chars
        $text = substr($text, 0, 200);

        return $text;
    }

    /**
     * Check if text has words like "nsfw".
     * | title here (nsfw) , for example, will return true
     */
    public static function isForbiddenTitle($text)
    {
        return (bool) preg_match(self::$forbiddenEndingsPattern, trim($text));
    }

    public static function makeSafeRegexFromArray($values = array())
    {
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
     * Decode all html entities into UTF-8 character
     *
     * @param  string $text
     * @return string
     */
    public static function decodeHtmlEntities($text)
    {
        $text = html_entity_decode($text, ENT_QUOTES, "UTF-8");
        $text = preg_replace_callback('/&#(\d+);/m', function($matches) {return chr($matches[0]);}, $text); # decimal notation
        $text = preg_replace_callback('/&#x([a-f0-9]+);/mi', function($matches) {return chr("0x".$matches[0]);}, $text); # hex notation

        return $text;
    }

    /**
     * Removes endings like (pic), (pics), (image), also remove urls, site titles, etc from the end of the title to make it look nice.
     */
    public static function cleanTitle($text)
    {

        $text = self::decodeHtmlEntities($text);

        $startsPattern = implode("|", self::makeSafeRegexFromArray(explode("\n", trim(self::$removeStart))));
        $endsPattern = implode("|", self::makeSafeRegexFromArray(explode("\n", trim(self::$removeEnd))));

        // just url
        $patterns[] = '@^' . self::$urlPatternInText . '$@ui';

        // "read more"
        $patterns[] = '@(Read more:*\s*' . self::$urlPatternInText . '*\s*)$@ui';

        // separator with url
        $patterns[] = '@(' . self::$separatorPattern . '\s*' . self::$urlPatternInText . '*\s*)$@ui';

        // (pics, images, etc)
        $patterns[] = self::$generalEndingsPattern;

        // site-specific beginning or ending
        $patterns[] = '@^(' . $startsPattern . ')?\s*' . self::$separatorPattern . '@ui';
        $patterns[] = '@' . self::$separatorPattern . '\s*(' . $endsPattern . ')?\s*$@ui';

        // (pics, images, etc) again
        $patterns[] = self::$generalEndingsPattern;

        foreach ($patterns as $pattern) {
            $text = trim($text);
            $text = preg_replace($pattern, '', $text);
        }

        // replace newlines with max 1 space
        $text = str_replace("\n", "", $text);
        $text = preg_replace('/\s\s+/', ' ', $text);

        $text = trim($text);

        // replace many dots or punctuation marks (like "!", "?") with 3 items
        $text = preg_replace('/([\w\s])(\p{P}){3,}$/ui', '${1}${2}${2}${2}', $text);

        // remove dot from the end, if not abbreviated (\p{Ll} - means lower case letter)
        $text = preg_replace('/([\p{Ll}\s])\.{0,2}$/ui', '${1}', $text);
        $text = trim($text);

        if ($text == "") {
            $text = "Untitled";
        }

        // keep it max 200 chars
        $text = substr($text, 0, 200);

        return (string) $text;
    }

    /**
     * Remove useless chars, spaces, newlines, singularize, etc and return it.
     */
    public static function cleanTag($tag)
    {

        $tag = self::decodeHtmlEntities($tag);

        $tag = mb_strtolower($tag, 'UTF-8');

        $tag = preg_replace('/\n/', ' ', $tag);       // newlines to spaces

        $tag = trim($tag);
        $tag = preg_replace('/\p{P}+\s*$/', '', $tag); // all punctuation at the end
        $tag = preg_replace('/^\s*\p{P}+/', '', $tag); // all punctuation at the beginning
        //$tag = preg_replace('/\p{P}+/', '', $tag); // all punctuation in the middle
        $tag = preg_replace('/\s\s+/', ' ', $tag);    // not more than one space

        $tag = trim($tag);
        $tag = Inflect::singularize($tag);

        return (string) $tag;
    }

}
