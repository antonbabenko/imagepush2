<?php

namespace Imagepush\ImagepushBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DiggTagTest extends WebTestCase
{

    public function testFindTagsFound()
    {
        $client = static::createClient();

        $service = $client->getContainer()->get('imagepush.processor.tag.digg');

        $image = $this->getMock('Imagepush\ImagepushBundle\Document\Image');
        $image->expects($this->any())
            ->method('getLink')
            ->will($this->returnValue('http://www.google.com/'));

        $tags = $service->find($image);

        $this->assertCount(1, $tags, 'There is one tag');
        $this->assertArrayHasKey("science", $tags, 'Tag is "science"');
    }

    public function testFindTagsNotFound()
    {
        $client = static::createClient();

        $service = $client->getContainer()->get('imagepush.processor.tag.digg');

        $image = $this->getMock('Imagepush\ImagepushBundle\Document\Image');
        $image->expects($this->any())
            ->method('getLink')
            ->will($this->returnValue('unknownlink'));

        $tags = $service->find($image);

        $this->assertInternalType("array", $tags);
        $this->assertCount(0, $tags);
    }

}