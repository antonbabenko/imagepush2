<?php

namespace Imagepush\ImagepushBundle\Tests\Controller;

use Imagepush\DevBundle\Test\Phpunit\WebTestCase;
use Imagepush\DevBundle\Test\Phpunit\Extension\ResponseExtensionTrait;

class FrontControllerTest extends WebTestCase
{
    use ResponseExtensionTrait;

    public function testHomepage()
    {
        $crawler = static::$client->request('GET', '/');

        $this->assertClientResponseStatus(200);

        $this->assertCount(1, $crawler->filter('figure.bigImg'));
        $this->assertCount(1, $crawler->filter('a#main_image_link'));
        $this->assertEquals('http://cdn.imagepush.to/in/463x1548/i/file5.jpg', $crawler->filter('img#main_image_img')->attr('src'));

        $this->assertEquals('Article title 5', $crawler->filter('h1#main_image_title')->text(), 'Current latest image');
        $this->assertCount(7, $crawler->filter('.thumbnails ul li'), '3 images (latest hits) + 4 images (latest in tag1)');

        $trendsFilter = $crawler->filter('#latest_trends ul li a')->each(function ($node) { return $node->text(); });
        $this->assertEquals(['tag1', 'tag5'], $trendsFilter, 'There are 2 trending tags');
    }

    public function testUpcoming()
    {
        $crawler = static::$client->request('GET', '/upcoming');

        $this->assertClientResponseStatus(200);

        $imagesFilter = $crawler->filter('li header ul li h1')->each(function ($node) { return $node->text(); });
        $this->assertEquals(['Article title 7', 'Article title 6'], $imagesFilter, 'There are 2 upcoming images');

        $tagsFilter = $crawler->filter('li#item-6 header p.details a')->each(function ($node) { return $node->text(); });
        $this->assertEquals(['tag1', 'tag2'], $tagsFilter, 'Image 6 has correct tags');

        $tagsFilter = $crawler->filter('li#item-7 header p.details a')->each(function ($node) { return $node->text(); });
        $this->assertEquals(['tag1', 'tag4'], $tagsFilter, 'Image 7 has correct tags');
    }

    public function testUpcomingByTag()
    {
        $crawler = static::$client->request('GET', '/tag/tag1/upcoming');

        $this->assertClientResponseStatus(200);

        $imagesFilter = $crawler->filter('li header ul li h1')->each(function ($node) { return $node->text(); });
        $this->assertEquals(['Article title 7', 'Article title 6'], $imagesFilter, 'There are 2 upcoming images');

        $tagsFilter = $crawler->filter('li#item-6 header p.details a')->each(function ($node) { return $node->text(); });
        $this->assertEquals(['tag1', 'tag2'], $tagsFilter, 'Image 6 has correct tags');

        $tagsFilter = $crawler->filter('li#item-7 header p.details a')->each(function ($node) { return $node->text(); });
        $this->assertEquals(['tag1', 'tag4'], $tagsFilter, 'Image 7 has correct tags');
    }

//    public function testFeeds()
//    {
//        $crawler = static::$client->request('GET', '/rss');
//
//        $this->assertClientResponseStatus(200);
//        $this->assertEquals(1, $crawler->filter('html:contains("Imagepush.to")')->count());
//    }

}
