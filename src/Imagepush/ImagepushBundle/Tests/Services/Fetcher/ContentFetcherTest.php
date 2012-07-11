<?php

namespace Imagepush\ImagepushBundle\Tests\Services\Fetcher;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContentFetcherTest extends WebTestCase
{

    public function testGetRequest()
    {

        $client = self::createClient();

        $service = $client->getContainer()->get('imagepush.fetcher.content');

        $link = "http://imagepush.to/";
        $result = $service->getRequest($link);

        $this->assertArrayHasKey("Response", $result);
        $this->assertArrayHasKey("Status", $result);
        $this->assertArrayHasKey("Content", $result);
        $this->assertArrayHasKey("Content-md5", $result);
        $this->assertArrayHasKey("Content-type", $result);
        $this->assertArrayHasKey("Content-length", $result);
    }

    public function testHeadRequest()
    {

        $client = self::createClient();

        $service = $client->getContainer()->get('imagepush.fetcher.content');

        $link = "http://imagepush.to/";
        $result = $service->headRequest($link);

        $this->assertArrayHasKey("Response", $result);
        $this->assertArrayHasKey("Status", $result);
        $this->assertArrayHasKey("Content", $result);
        $this->assertArrayHasKey("Content-md5", $result);
        $this->assertArrayHasKey("Content-type", $result);
        $this->assertArrayHasKey("Content-length", $result);
    }

}
