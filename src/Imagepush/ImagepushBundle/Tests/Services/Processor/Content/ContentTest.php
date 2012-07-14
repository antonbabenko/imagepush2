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

        /**
         * Direct link to image
         */
        $link = "http://imagepush.to/images/imagepush-logo.png";
        $content->get($link);

        $this->assertTrue($content->isSuccessStatus());
        $this->assertTrue($content->isImageType());
        $this->assertNotNull($content->getContentMd5());
        $this->assertNotNull($content->getContentType());
        $this->assertNotNull($content->getContent());
        $this->assertEquals($link, $content->getLink());

        /**
         * Link to html page
         */
        $link = "http://imagepush.to/";
        $content->get($link);

        $this->assertTrue($content->isSuccessStatus());
        $this->assertTrue($content->isHTMLType());
        $this->assertNotNull($content->getContentMd5());
        $this->assertNotNull($content->getContentType());
        $this->assertNotNull($content->getContent());
        $this->assertEquals($link, $content->getLink());
    }

    public function testVerifyContentMd5()
    {
        $client = self::createClient();
        $content1 = new Content($client->getKernel()->getContainer());
        $content2 = new Content($client->getKernel()->getContainer());

        // These links has the same content
        $link1 = "http://media.villagevoice.com/8015762.87.jpg";
        $link2 = "http://media.westword.com/8015762.87.jpg";
        $content1->get($link1);
        $content2->get($link2);

        $this->assertTrue($content1->isImageType());
        $this->assertTrue($content2->isImageType());
        $this->assertEquals("8eeea342654ca178ea4bb9e5d8752b39", $content1->getContentMd5());
        $this->assertEquals("8eeea342654ca178ea4bb9e5d8752b39", $content2->getContentMd5());
    }

}
