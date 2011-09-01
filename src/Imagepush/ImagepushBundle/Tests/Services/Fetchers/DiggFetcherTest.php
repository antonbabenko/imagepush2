<?php

namespace Imagepush\ImagepushBundle\Tests\Services\Fetchers;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Imagepush\ImagepushBundle\Tests\Bootstrap\RedisConnection;
use Imagepush\ImagepushBundle\Tests\Fixtures\DiggFixture;
use Imagepush\ImagepushBundle\Services\Fetchers\DiggFetcher;

class DiggFetcherTest extends WebTestCase
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

  public function testFetchRealDataFromDigg()
  {

    $client = $this->createClient();
    $kernel = $client->getKernel();

    $digg = new DiggFetcher($kernel);

    $diggResult = $digg->fetchData();

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
    
  }

  public function testDiggResponseHasAllRequiredProperies()
  {

    $data = DiggFixture::getData();
    
    //var_dump($data);
    $this->assertInternalType("array", $data);

    $this->assertNotEmpty($data[0]->title);
    $this->assertNotEmpty($data[0]->diggs);
    $this->assertNotEmpty($data[0]->link);
    $this->assertNotEmpty($data[0]->submit_date);
    $this->assertNotEmpty($data[0]->topic->name);
  }

  /**
   * @depends testFetchData
   */
  /*public function testItemIsWorthToSave($data)
  {

    $client = $this->createClient();
    $kernel = $client->getKernel();

    $digg = new DiggFetcher($kernel);

    $a = $digg->isWorthToSave($data[0]);

    var_dump($a);

  }*/

}
