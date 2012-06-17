<?php

namespace Imagepush\ImagepushBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SourceTagTest extends WebTestCase
{

    public function testFindTagsFound()
    {
        $client = static::createClient();

        $service = $client->getContainer()->get('imagepush.processor.tag.source');

        $image = $this->getMock('Imagepush\ImagepushBundle\Document\Image');
        $image->expects($this->any())
            ->method('getSourceTags')
            ->will($this->returnValue(array("Politics", "joke", "hehe", "haha")));

        $tags = $service->find($image);

        $this->assertEquals(array("fun" => 3, "politic" => 1), $tags);
    }

    public function testFindTagsNotFound()
    {
        $client = static::createClient();

        $service = $client->getContainer()->get('imagepush.processor.tag.source');

        $image = $this->getMock('Imagepush\ImagepushBundle\Document\Image');
        $image->expects($this->any())
            ->method('getSourceTags')
            ->will($this->returnValue(null));

        $tags = $service->find($image);

        $this->assertEquals(array(), $tags);
    }

}