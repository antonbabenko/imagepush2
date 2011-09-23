<?php

namespace Imagepush\ImagepushBundle\Tests\Services\Processor;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Imagepush\ImagepushBundle\Tests\Bootstrap\RedisConnection;
use Imagepush\ImagepushBundle\Services\Processor\Processor;

class ProcessorTest extends WebTestCase
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

  public function testRun()
  {

    $client = $this->createClient();
    $kernel = $client->getKernel();

    $processor = new Processor($kernel);
    $result = $processor->run();
    
    \D::dump($result);
/*
    if (isset($digg->data))
    {
      $this->assertTrue($diggResult);
    } elseif (is_array($diggResult))
    {
      $this->markTestSkipped('Digg service is not available or return wrong response. Response message: ' . $diggResult["message"] . "; code: " . $diggResult["code"]);
    } else
    {
      $this->fail();
    }
*/    
  }

}
