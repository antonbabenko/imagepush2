<?php

namespace Imagepush\ImagepushBundle\Tests\Services\Processor;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Imagepush\ImagepushBundle\Tests\Bootstrap\RedisConnection;
use Imagepush\ImagepushBundle\Services\Processor\Content;

class ContentTest extends WebTestCase
{

  public $redis;
  static $useFixtures = true;

  protected function setUp()
  {
    $this->redis = RedisConnection::getConnection();
    $this->redis->flushdb();
  }

  protected function tearDown()
  {
    
  }

  public function testContentWithDirectImageLink()
  {

    $client = $this->createClient();
    $kernel = $client->getKernel();

    $imageLinks = array(
      // direct images
      "http://i.imgur.com/bCK24.jpg",
      //"http://adayinthalifeof.files.wordpress.com/2009/06/picture-15.png",

      // text/html
      //"http://www.web-developer.no/",
      //"http://slapblog.com/?p=7888",
    );
    
    foreach($imageLinks as $link) {
      $content = new Content($kernel);
      $content->init($link);
    
      $this->assertTrue($content->isFetched());
      $this->assertTrue($content->isImage());
      
    //\D::dump($result);
    }
     
  }

}
