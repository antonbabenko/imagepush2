<?php

namespace Imagepush\ImagepushBundle\Services\Fetcher\Digg;

//use Imagepush\ImagepushBundle\Model\Tag;
//use Imagepush\ImagepushBundle\Model\AbstractSource;
use Imagepush\ImagepushBundle\Entity\Image;
use Imagepush\ImagepushBundle\Services\Fetcher\AbstractFetcher;
use Imagepush\ImagepushBundle\External\CustomStrings;

class DiggFetcher extends AbstractFetcher
{
  
  /**
   * Limit for API call. 200 - max, but often fails. Try with 20-40.
   * @param integer $fetchLimit
   */
  public $fetchLimit = 10;
  
  /**
   * Minimum diggs to save. 4-5 is OK.
   * @param integer $minDiggs
   */
  public $minDiggs = 4; // 1
  
  /**
   * Minimum delay between API requests. Set to 30 mins, but cron runs every 5 minutes, so there are 6 attempts in each interval
   * @var integer $minDelay
   */
  public static $minDelay = 10; //1800; // 10 
  
  /**
   * Recent source data to show in the output.
   */
  public $recentSourceDate;
  
  public $lastStatus, $lastAccess;
  
  /*
   * Check if item is good enough to be saved (Digg counts and unique link hash)
   */
  public function isWorthToSave($item)
  {

    if (isset($item->title) && !CustomStrings::isForbiddenTitle($item->title) && parent::isWorthToSave($item)) {
      
      $redis = $this->kernel->getContainer()->get('snc_redis.default_client');
      
      $result = (
        isset($item->diggs) &&
        $item->diggs >= $this->minDiggs &&
        !$redis->sismember('indexed_links', $item->link) &&
        !$redis->sismember('failed_links', $item->link)
      );

      /*if ($result) {
        echo "<br>isWorthToSave = true";
        \D::dump($item);
      }*/

    } else {

      $result = false;

    }

    return $result;

  }

  public function getDiggInstance() {
    
    $digg = new ImagepushDigg();
    $digg->setVersion('2.0');
    
    return $digg;
  }
  
  public function fetchData()
  {

    $digg = $this->getDiggInstance();

    try {
      $response = $digg->search->search(array(
        'media' => 'images',
        'domain' => '*',
        'sort' => 'date-desc', //'promote_date-asc',
        'min_date' => $this->lastAccess - 6000,
        'count' => $this->fetchLimit
      ));
    } catch (\Services_Digg2_Exception $e) {
      return array("message" => $e->getMessage(), "code" => $e->getCode());
    }
    
    //\D::dump($digg->getLastResponse()->getHeader());
    
    self::$fetchedCounter = (!empty($response->count) ? $response->count : 0);
    
    $this->data = (!empty($response->count) ? $response->stories : false);
    
    return true;
    
  }

  public function checkAndSaveData()
  {

    if (!isset($this->data) || $this->data == false) {
      return false;
    }
    
    //$images = $this->kernel->getContainer()->get('imagepush.images');
    //$tags = $this->kernel->getContainer()->get('imagepush.tags');
    
    foreach ($this->data as $item) {
      
      if (!$this->isWorthToSave($item))
        continue;
      
      $image = new Image($this->kernel);
      $image->setSourceType("digg");
      $image->setLink($item->link);
      $image->setTimestamp($item->submit_date);
      $image->setTitle($item->title);
      $image->setSlugFromTitle();
      
      if (!empty($item->topic->name)) {
        $image->setOriginalTags($item->topic->name);
         //$tags->saveRawTags($imageKey, Tag::SRC_DIGG, $item->topic->name);
      }

      try {
        
        if ($image->saveAsSource()) {
          self::$savedCounter++;
        }
      } catch (\Exception $e) {
        $this->logger->err(sprintf("Link: %s has not been saved. Error was: %s", $item->link, $e->getMessage()));
      }
      
      if ($image->timestamp > $this->recentSourceDate)
      {
        $this->recentSourceDate = $image->timestamp;
      }
      
    }

  }

  public function run()
  {
    
    $redis = $this->kernel->getContainer()->get('snc_redis.default_client');
    
    // check status and time
    $this->lastStatus = $redis->get("digg_last_status");
    $this->lastAccess = $redis->get("digg_last_access");
    //echo $this->lastAccess;
    
    if ($this->lastStatus == "OK" && time() < $this->lastAccess + self::$minDelay)
    {
      self::$output[] = sprintf("[DiggFetcher] %s: Last access attempt was OK, so wait %d secords between requests (%d sec to wait).", date(DATE_RSS), self::$minDelay, $this->lastAccess + self::$minDelay - time());
    } else
    {

      $status = $this->fetchData();
      //\D::dump($this->data);

      if ($status === true)
      {

        $this->checkAndSaveData();

        if (self::$savedCounter == 0) {
          self::$output[] = sprintf("[DiggFetcher] %s: %d sources received, but nothing has been saved (all filtered out).", date(DATE_RSS), self::$fetchedCounter);
        } else {
          self::$output[] = sprintf("[DiggFetcher] %s: %d of %d items has been saved. Recent source date was on %s", date(DATE_RSS), self::$savedCounter, self::$fetchedCounter, date(DATE_RSS, $this->recentSourceDate));
        }
      } else
      {
        self::$output[] = sprintf("[DiggFetcher] %s: Digg replied with error: %s. Code: %s", date(DATE_RSS), $status["message"], $status["code"]);
      }

      $redis->set("digg_last_status", ($status === true ? "OK" : "FAIL"));
      $redis->set("digg_last_access", time());
    }

    return self::$output;

  }
  
  /*
   * @return serialized data to use as test fixtures
   */
  private function serializeDataAsFixtures() {
    return serialize($this->data);
  }

}