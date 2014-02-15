<?php

namespace Imagepush\ImagepushBundle\Tests\Services\Processor\Tag;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RedditTagTest extends WebTestCase
{

    public function testFindTagsFound()
    {
        $client = static::createClient();

        $service = $client->getContainer()->get('imagepush.processor.tag.reddit');

        $image = $this->getMock('Imagepush\ImagepushBundle\Document\Image');
        $image->expects($this->any())
            ->method('getLink')
            ->will($this->returnValue('http://www.google.com/'));

        $tags = $service->find($image);

        $this->assertGreaterThanOrEqual(3, $tags, 'There are at least 3 tags');
        //$this->assertArrayHasKey("technology", $tags, 'Tag is "search"');
    }

    public function testFindTagsNotFound()
    {
        $client = static::createClient();

        $service = $client->getContainer()->get('imagepush.processor.tag.reddit');

        $image = $this->getMock('Imagepush\ImagepushBundle\Document\Image');
        $image->expects($this->any())
            ->method('getLink')
            ->will($this->returnValue('unknownlink'));

        $tags = $service->find($image);

        $this->assertInternalType("array", $tags);
        $this->assertCount(0, $tags);
    }

}
