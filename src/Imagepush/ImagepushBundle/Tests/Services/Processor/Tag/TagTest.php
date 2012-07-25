<?php

namespace Imagepush\ImagepushBundle\Tests\Services\Processor\Tag;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TagTest extends WebTestCase
{

    public function testRequiredContainerParameters()
    {
        $client = static::createClient();

        $this->assertInternalType("array", $client->getContainer()->getParameter('imagepush.tag_group_value'));
        $this->assertInternalType("array", $client->getContainer()->getParameter('imagepush.useless_tags'));
        $this->assertInternalType("array", $client->getContainer()->getParameter('imagepush.synonyms_tags'));
    }

    public function testProcessTags()
    {
        $client = static::createClient();
        $service = $client->getContainer()->get('imagepush.processor.tag');
        $dm = $client->getContainer()->get('doctrine.odm.mongodb.document_manager');

        $image = new \Imagepush\ImagepushBundle\Document\Image();
        $image->setLink('http://www.google.com/');
        $image->setTitle('Google is what you probably need');
        $image->setSourceTags(array("Technology", "Search"));
        $dm->persist($image);
        $dm->flush();

        $tags = $service->processTags($image);

        $this->assertGreaterThan(1, count($tags));
    }

    public function testFixTagsArray()
    {
        $client = static::createClient();
        $service = $client->getContainer()->get('imagepush.processor.tag');

        $sourceTags = array("Politics", "joke", "hehe", "haha");
        $tags = $service->fixTagsArray($sourceTags);

        $this->assertCount(2, $tags);
        $this->assertArrayHasKey("politic", $tags, "Plural => Singular");
        $this->assertArrayHasKey("fun", $tags, "Synonyms");
        $this->assertEquals(3, $tags["fun"], "Synonyms counter");
        $this->assertEquals(array("fun", "politic"), array_keys($tags), "Result ordered correctly");

        $sourceTags = array();
        $tags = $service->fixTagsArray($sourceTags);

        $this->assertEquals(array(), $tags);
    }

    public function testCalculateTagsScore()
    {
        $client = static::createClient();
        $service = $client->getContainer()->get('imagepush.processor.tag');

        $sourceTags = array(
            "digg" => array("fun" => 3, "politic" => 1),
            "stumbleupon" => array("fun" => 4, "sport" => 2),
        );

        $tags = $service->calculateTagsScore($sourceTags);

        $this->assertEquals(array("fun" => 16, "sport" => 5, "politic" => 2), $tags);



        $sourceTags = array();
        $tags = $service->calculateTagsScore($sourceTags);

        $this->assertEquals(array(), $tags);
    }

    public function testCalculateTagsScoreTagGroupValue()
    {

        $client = static::createClient();
        $service = $client->getContainer()->get('imagepush.processor.tag');

        $sourceTags = array(
            "source" => array("source_tag" => 1),
            "digg" => array("digg_tag" => 1),
            "stumbleupon" => array("stumbleupon_tag" => 1),
            "twitter" => array("twitter_tag" => 1),
            "reddit" => array("reddit_tag" => 1),
            "delicious" => array("delicious_tag" => 1),
        );

        $tags = $service->calculateTagsScore($sourceTags);

        $tagGroupValue = $client->getContainer()->getParameter('imagepush.tag_group_value');

        $this->assertEquals($tagGroupValue["source"], $tags["source_tag"]);
        $this->assertEquals($tagGroupValue["digg"], $tags["digg_tag"]);
        $this->assertEquals($tagGroupValue["stumbleupon"], $tags["stumbleupon_tag"]);
        $this->assertEquals($tagGroupValue["twitter"], $tags["twitter_tag"]);
        $this->assertEquals($tagGroupValue["reddit"], $tags["reddit_tag"]);
        $this->assertEquals($tagGroupValue["delicious"], $tags["delicious_tag"]);
    }

    public function testFilterTagsByScore()
    {
        $client = static::createClient();
        $service = $client->getContainer()->get('imagepush.processor.tag');

        $tags = $service->filterTagsByScore(array("fun" => 16, "sport" => 5, "politic" => 2), 10);
        $this->assertEquals(array("fun" => 16), $tags, "Score difference should be less than half of max");

        $tags = $service->filterTagsByScore(array("fun" => 16, "sport" => 8, "politic" => 2), 10);
        $this->assertEquals(array("fun" => 16, "sport" => 8), $tags);

        $tags = $service->filterTagsByScore(array("fun" => 3, "sport" => 2, "politic" => 2), 10);
        $this->assertEquals(array("fun" => 3, "sport" => 2, "politic" => 2), $tags);

        $tags = $service->filterTagsByScore(array("fun" => 3, "sport" => 2, "politic" => 2), 2);
        $this->assertEquals(array("fun" => 3, "sport" => 2), $tags, "Max 2 items");

        $tags = $service->filterTagsByScore(array());
        $this->assertEquals(array(), $tags);
    }

}