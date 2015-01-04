<?php

namespace Imagepush\ImagepushBundle\Tests\Services\Processor\Tag;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DeliciousTagTest extends WebTestCase
{

    public function testFindTagsFound()
    {
        $client = static::createClient();

        $service = $client->getContainer()->get('imagepush.processor.tag.delicious');

        $image = $this->getMock('Imagepush\ImagepushBundle\Document\Image');
        $image->expects($this->any())
            ->method('getLink')
            ->will($this->returnValue('http://twitter.com/'));

        $tags = $service->find($image);

        $this->assertGreaterThanOrEqual(1, $tags, 'There are at least 1 tag');
        $this->assertArrayHasKey("twitter", $tags, 'Tag is "twitter"');
    }

    public function testFindTagsNotFound()
    {
        $client = static::createClient();

        $service = $client->getContainer()->get('imagepush.processor.tag.delicious');

        $image = $this->getMock('Imagepush\ImagepushBundle\Document\Image');
        $image->expects($this->any())
            ->method('getLink')
            ->will($this->returnValue('unknownlinkishere'));

        $tags = $service->find($image);

        $this->assertInternalType("array", $tags);
        $this->assertCount(0, $tags);
    }

}
