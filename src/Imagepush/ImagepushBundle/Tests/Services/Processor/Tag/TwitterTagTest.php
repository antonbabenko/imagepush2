<?php

namespace Imagepush\ImagepushBundle\Tests\Services\Processor\Tag;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TwitterTagTest extends WebTestCase
{

    public function testFindTagsFound()
    {
        $client = static::createClient();

        $service = $client->getContainer()->get('imagepush.processor.tag.twitter');

        $image = $this->getMock('Imagepush\ImagepushBundle\Document\Image');
        $image->expects($this->any())
            ->method('getLink')
            ->will($this->returnValue('http://twitter.com/'));
        $image->expects($this->any())
            ->method('getTitle')
            ->will($this->returnValue('Twitter was here'));

        $tags = $service->find($image);

        $this->assertGreaterThanOrEqual(1, $tags, 'There are at least 1 tag');
    }

    public function testFindTagsNotFound()
    {
        $client = static::createClient();

        $service = $client->getContainer()->get('imagepush.processor.tag.twitter');

        $image = $this->getMock('Imagepush\ImagepushBundle\Document\Image');
        $image->expects($this->any())
            ->method('getLink')
            ->will($this->returnValue('unknownlinkishere'));
        $image->expects($this->any())
            ->method('getTitle')
            ->will($this->returnValue('unknowntitleishereunknowntitleishere'));

        $tags = $service->find($image);

        $this->assertInternalType("array", $tags);
        $this->assertCount(0, $tags);
    }

}
