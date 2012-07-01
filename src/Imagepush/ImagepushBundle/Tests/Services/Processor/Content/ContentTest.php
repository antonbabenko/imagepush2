<?php

namespace Imagepush\ImagepushBundle\Tests\Services\Processor\Content;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Imagepush\ImagepushBundle\Services\Processor\Content\Content;

class ContentTest extends WebTestCase
{

    public function testContentImageAndHTMLLink()
    {
        $client = self::createClient();
        $content = new Content($client->getKernel()->getContainer());

        // Direct link to image
        $link = "http://imagepush.to/images/imagepush-logo.png";
        $content->get($link);

        $this->assertTrue($content->isSuccessStatus());
        $this->assertTrue($content->isImageType());
        $this->assertNotNull($content->getContentMd5());
        $this->assertNotNull($content->getContentType());
        $this->assertNotNull($content->getContent());
        $this->assertEquals($link, $content->getLink());

        // Link to html page
        $link = "http://imagepush.to/";
        $content->get($link);

        $this->assertTrue($content->isSuccessStatus());
        $this->assertTrue($content->isHTMLType());
        $this->assertNotNull($content->getContentMd5());
        $this->assertNotNull($content->getContentType());
        $this->assertNotNull($content->getContent());
        $this->assertEquals($link, $content->getLink());
    }

}
