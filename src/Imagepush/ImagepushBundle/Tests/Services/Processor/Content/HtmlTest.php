<?php

namespace Imagepush\ImagepushBundle\Tests\Services\Processor\Content;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Imagepush\ImagepushBundle\Services\Processor\Content\Content;
use Imagepush\ImagepushBundle\Services\Processor\Content\Html;

class HtmlTest extends WebTestCase
{

    public $content, $html;

    public function setUp()
    {
        $client = self::createClient();
        $this->html = new Html($client->getKernel()->getContainer());
        $this->content = new Content($client->getKernel()->getContainer());
        parent::setUp();
    }

    public function tearDown()
    {
        unset($this->html);
        unset($this->content);
        parent::tearDown();
    }

    private function getFixtureFile($file)
    {
        // @todo: replace with public uri or put html files into web folder
        return "http://dev-anton.imagepush.to/test_html/" . $file;

        // file protocol is not working with Goutte
        // return "file://" . realpath(__DIR__ . "/../../../Fixtures/html/" . $file);
    }

    public function testSetContent()
    {
        $this->content->get($this->getFixtureFile("html5.html"));
        $this->html->setContent($this->content);

        $this->assertEquals($this->content, $this->html->content);
    }

    public function testGetDomHtml5Valid()
    {
        $this->content->get($this->getFixtureFile("html5.html"));
        $this->html->setContent($this->content);

        $dom = $this->html->getDom();

        $this->assertInstanceOf("DOMDocument", $dom);
    }

    public function testGetDomHtml5Invalid()
    {
        $this->content->get($this->getFixtureFile("invalid.html"));
        $this->html->setContent($this->content);

        $dom = $this->html->getDom();

        // Invalid document
        $this->assertInstanceOf("DOMDocument", $dom);
    }

    public function testGetFullImageSrc()
    {
        $this->content->get($this->getFixtureFile("image_src_and_og.html"));
        $this->html->setContent($this->content);

        $images = $this->html->getFullImageSrc();

        $this->assertEquals(array(
            'http://dev-anton.imagepush.to/test_html/logo_image_src.png',
            'http://dev-anton.imagepush.to/test_html/logo_og.png'
            ), $images);
    }

    public function testGetFullImageSrcEmpty()
    {
        $this->content->get($this->getFixtureFile("html5.html"));
        $this->html->setContent($this->content);

        $images = $this->html->getFullImageSrc();

        $this->assertEquals(false, $images);
    }

    public function testGetBestImageFromDomEmpty()
    {
        $this->content->get($this->getFixtureFile("html5.html"));
        $this->html->setContent($this->content);

        $images = $this->html->getBestImageFromDom();

        // No images with predefined width or height attributes
        $this->assertEquals(false, $images);
    }

    public function testGetBestImageFromDomWithImages()
    {
        // Images with specified width or height
        $this->content->get($this->getFixtureFile("with_images.html"));
        $this->html->setContent($this->content);

        $images = $this->html->getBestImageFromDom();

        $this->assertEquals(array(
            'http://dev-anton.imagepush.to/test_html/images/img_girl.jpg',
            'http://dev-anton.imagepush.to/test_html/images/img_taxi.jpg',
            'http://dev-anton.imagepush.to/test_html/images/img_taxi.jpg',
            'http://dev-anton.imagepush.to/test_html/images/img_taxi.jpg',
            'http://dev-anton.imagepush.to/test_html/images/img_taxi.jpg',
            'http://dev-anton.imagepush.to/test_html/images/wrong_url.jpg',
            ), $images);
    }

    public function testGenerateFullUrl()
    {
        $this->assertEquals("http://site.com/a.html", $this->html->generateFullUrl("a.html", "http://site.com/"));
        $this->assertEquals("http://site.com/a.html", $this->html->generateFullUrl("./a.html", "http://site.com/"));
        $this->assertEquals("http://site.com/a.html", $this->html->generateFullUrl("/a.html", "http://site.com/"));

        $this->assertEquals("http://site.com/dir/a.html", $this->html->generateFullUrl("a.html", "http://site.com/dir/"));
        $this->assertEquals("http://site.com/dir/a.html", $this->html->generateFullUrl("./a.html", "http://site.com/dir/"));
        $this->assertEquals("http://site.com/a.html", $this->html->generateFullUrl("/a.html", "http://site.com/dir/"));

        $this->assertEquals("http://site.com/dir/a.html", $this->html->generateFullUrl("a.html", "http://site.com/dir/page"));
        $this->assertEquals("http://site.com/dir/a.html", $this->html->generateFullUrl("./a.html", "http://site.com/dir/page"));
        $this->assertEquals("http://site.com/a.html", $this->html->generateFullUrl("/a.html", "http://site.com/dir/page"));

        $this->assertEquals("http://user:password@site.com:80/dir/a.html", $this->html->generateFullUrl("a.html", "http://user:password@site.com:80/dir/"));
        $this->assertEquals("http://user:password@site.com:80/dir/a.html", $this->html->generateFullUrl("./a.html", "http://user:password@site.com:80/dir/"));
        $this->assertEquals("http://user:password@site.com:80/a.html", $this->html->generateFullUrl("/a.html", "http://user:password@site.com:80/dir/"));
    }

}
