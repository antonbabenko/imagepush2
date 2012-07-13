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
            // cut first 200 chars
            "very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here very LONG title GOES here (PHOTOS)" => "very-long-title-goes-here-very-long-title-goes-here-very-long-title-goes-here-very-long-title-goes-here-very-long-title-goes-here-very-long-title-goes-here-very-long-title-goes-here-very-long-title-go",
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
        );

        return $data;
    }

}
