<?php

namespace Imagepush\ImagepushBundle\Tests\Fixtures;

class CustomStringsFixture
{

    public static function getSlugifyData()
    {
        $data = array(
            "" => "n-a",
            "!0-+" => "0",
            "Привет!" => "привет",
            "Hello!" => "hello",
            "åøæ!123" => "åøæ-123",
            "'\"quote\"'" => "quote",
            "---(Anton)---" => "anton",
            "keep first 200 chars in this very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here (PHOTOS)" => "keep-first-200-chars-in-this-very-long-title-goes-here-very-long-title-goes-here-very-long-title-goes-here-very-long-title-goes-here-very-long-title-goes-here-very-long-title-goes-here-very-long-title",
        );

        return $data;
    }

    public static function getForbiddenTitlesData()
    {
        $data = array(
            "title | (nsfw)" => true,
            "title | [nsfw]" => true,
            "title | [sex]" => false,
            "title | [not nsfw]" => false,
        );

        return $data;
    }

    public static function getCleanTagsData()
    {
        $data = array(
            " ,!!! привет ..., " => "привет",
            "test     tag" => "test tag",
            "\ntest \n\n tag2" => "test tag2",
            "tags2" => "tags2",
            "tags" => "tag",
            "tag" => "tag",
            "tag & tag" => "tag & tag",
            "tag &amp; tag" => "tag & tag",
            "tag &amp;&nbsp;&copy; tag" => "tag & © tag", // nbsp is not the same as space!
        );

        return $data;
    }

    public static function getCleanTitleData()
    {
        $data = array(
            "title | The Oatmeal" => "title",
            "title | CNET" => "title",
            "title - CNET" => "title",
            "title « CNET" => "title",
            "title » CNET" => "title",
            "title — CNET" => "title",
            "title ~ CNET" => "title",
            "title : CNET" => "title",
            "title @ CNET" => "title",
            "title @ CNET (img)" => "title",
            "title @ CNET (images)" => "title",
            "title @ CNET (10 images)" => "title",
            "title @ CNET [10 pics]" => "title",
            "title -PICS" => "title",
            "title - PICS" => "title",
            "http://www.site.com/" => "Untitled",
            "www.site.com" => "Untitled",
            "site.com" => "Untitled",
            "http://site.no" => "Untitled",
            "https://site.no" => "Untitled",
            "ftp://site.no" => "ftp://site.no",
            "www.site.com?yo=man" => "www.site.com?yo=man",
            "http://www.site.com?yo=man" => "http://www.site.com?yo=man",
            // cut first 200 chars
            "very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here (PHOTOS)" => "very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GO",
            "Photos: title" => "title",
            "Photos - title" => "title",
            "title ?????" => "title ???",
            "title !!!!!" => "title !!!",
            "title ....." => "title ...",
            "title ..." => "title ...",
            "title .." => "title",
            "title ." => "title",
            "title....." => "title...",
            "title..." => "title...",
            "title.." => "title",
            "title." => "title",
            "привет©÷јџќ®†њѓѕў“ъэ…∆љ°}©÷ћыƒђ≈≠µи™~≤≥“ ." => "привет©÷јџќ®†њѓѕў“ъэ…∆љ°}©÷ћыƒђ≈≠µи™~≤≥“",
            "title. (Pic.)" => "title",
            "title. (i.imgur.com)" => "title",
            "A.B.C." => "A.B.C.",
            "title A.B.C." => "title A.B.C.",
            "title. A.B.C." => "title. A.B.C.",
            "title. abc." => "title. abc",
            "title. ab.c." => "title. ab.c",
            "title & title" => "title & title",
            "title &amp; title" => "title & title",
            "title &amp;&nbsp;&copy; title" => "title & © title", // nbsp is not the same as space!
            //"[pic] title" => "title",
        );

        return $data;
    }

}
