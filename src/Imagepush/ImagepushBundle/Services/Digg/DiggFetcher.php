<?php

namespace Imagepush\ImagepushBundle\Services\Digg;

use Imagepush\ImagepushBundle\Services\Digg\ImagepushDigg;
use Imagepush\ImagepushBundle\Services\AbstractFetcher;
use Imagepush\ImagepushBundle\External\CustomStrings;
use Imagepush\ImagepushBundle\Model\Tag;
use Imagepush\ImagepushBundle\Model\DiggSource;

class DiggFetcher extends AbstractFetcher
{
  
  /**
   * Limit for API call. 200 - max, but often fails. Try with 20-40.
   * @param integer $fetchLimit
   */
  public $fetchLimit = 20;
  
  /**
   * Minimum diggs to save. 4-5 is OK.
   * @param integer $minDiggs
   */
  public $minDiggs = 4; // 1
  
  /**
   * Minimum delay between API requests. Set to 30 mins, but cron runs every 5 minutes, so there are 6 attempts in each interval
   * @var integer $minDelay
   */
  public static $minDelay = 1800; // 10 
  
  /**
   * Recent source data to show in the output.
   */
  public $recentSourceDate;
  
  /*
   * Check if item is good enough to be saved (Digg counts and unique link hash)
   */
  public function isWorthToSave($item)
  {

    if (isset($item->title) && !CustomStrings::isForbiddenTitle($item->title) && parent::isWorthToSave($item)) {

      $result = (
        isset($item->diggs) &&
        $item->diggs >= $this->minDiggs &&
        !$this->redis->sismember('indexed_links', $item->link) &&
        !$this->redis->sismember('failed_links', $item->link)
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

  public function fetchData()
  {

    $digg = new ImagepushDigg();
    $digg->setVersion('2.0');

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
    
    //\D::dump($this->data);
    
    foreach ($this->data as $item) {
      
      if (!$this->isWorthToSave($item))
        continue;
      

      $id = $this->images->getImageId();
      $imageKey = $this->images->getImageKey($id);
      
      $source = new DiggSource($this->allServices);
      $source->setId($id);
      $source->setImageKey($imageKey);
      
      $source->setLink($item->link);
      $source->setTimestamp($item->submit_date);
      $source->setTitle($item->title);
      $source->setSlugFromTitle();
      
      if (!empty($item->topic->name)) {
         $this->tags->saveRawTags($imageKey, Tag::SRC_DIGG, $item->topic->name);
      }

      try {
        if ($source->save()) {
          self::$savedCounter++;
        }
      } catch (\Exception $e) {
        $this->logger->err(sprintf("ImageKey: %s has not been saved. Error was: %s", $imageKey, $e->getMessage()));
      }
      
      if ($source->getTimestamp() > $this->recentSourceDate)
      {
        $this->recentSourceDate = $source->getTimestamp();
      }
      
    }
    

  }

  public function run()
  {
    
    // check status and time
    $this->lastStatus = $this->redis->get("digg_last_status");
    $this->lastAccess = $this->redis->get("digg_last_access");
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

      $this->redis->set("digg_last_status", ($status === true ? "OK" : "FAIL"));
      $this->redis->set("digg_last_access", time());
    }

    return self::$output;

  }


}