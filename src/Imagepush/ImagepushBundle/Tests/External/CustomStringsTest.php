<?php

namespace Imagepush\ImagepushBundle\Tests\External;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Imagepush\ImagepushBundle\External\CustomStrings;
use Imagepush\ImagepushBundle\Tests\Fixtures\CustomStringsFixture;

class CustomStringsTest extends WebTestCase
{

    public function testSlugify()
    {
        $fixtures = CustomStringsFixture::getSlugifyData();

        foreach ($fixtures as $in => $out) {
            $result = CustomStrings::slugify($in);
            $this->assertEquals($result, $out, "In: " . $in . ' | Out: ' . $out . ' | Expected: ' . $result);
        }
    }

    public function testCheckIfTitleIsForbidden()
    {
        $fixtures = CustomStringsFixture::getForbiddenTitlesData();

        foreach ($fixtures as $in => $out) {
            $result = CustomStrings::isForbiddenTitle($in);
            $this->assertEquals($result, $out, "In: " . $in . ' | Out: ' . $out . ' | Expected: ' . $result);
        }
    }

    public function testCleanTag()
    {
        $fixtures = CustomStringsFixture::getCleanTagsData();

        $cs = new CustomStrings();
        foreach ($fixtures as $in => $out) {
            $result = $cs->cleanTag($in);
            $this->assertEquals($result, $out, "In: " . $in . ' | Out: ' . $out . ' | Expected: ' . $result);
        }
    }

    public function testCleanTitle()
    {
        $fixtures = CustomStringsFixture::getCleanTitleData();

        foreach ($fixtures as $in => $out) {
            $result = CustomStrings::cleanTitle($in);
            $this->assertEquals($result, $out, "In: " . $in . ' | Out: ' . $out . ' | Expected: ' . $result);
        }
    }

}
