<?php

namespace Imagepush\ImagepushBundle\Services\Fetcher;

use Imagepush\ImagepushBundle\Document\Image;
use Imagepush\ImagepushBundle\Services\Digg\ImagepushDigg;
use Imagepush\ImagepushBundle\External\CustomStrings;

class DiggFetcher extends AbstractFetcher implements FetcherInterface
{

    /**
     * Recent source data to show in the output.
     */
    public $recentSourceDate;
    public $lastStatus, $lastAccess;

    /**
     * DiggFetcher
     * 
     * @param Container $container
     */
    public function __construct($container)
    {
        parent::__construct($container, "digg");
    }

    /**
     * Check if item is good enough to be saved (Digg counts and unique link hash)
     */
    public function isWorthToSave($item)
    {

        if (!isset($item->title) || CustomStrings::isForbiddenTitle($item->title) || !parent::isWorthToSave($item)) {
            return false;
        }

        $minDiggs = $this->getParameter("min_diggs", 1);

        $worthToSave = (
            isset($item->diggs) &&
            $item->diggs >= $minDiggs &&
            false === (bool) $this->dm->getRepository('ImagepushBundle:Link')->isIndexedOrFailed($item->link) &&
            false === (bool) $this->dm->getRepository('ImagepushBundle:Image')->findOneBy(array("link" => $item->link))
            );

        if ($worthToSave) {
            $log = sprintf("[DiggFetcher] YES. Diggs: %d. Link: %s. Title: %s", $item->diggs, $item->link, $item->title);
            $this->logger->info($log);
            $this->output[] = $log;

            return true;
        } else {
            $log = sprintf("[DiggFetcher] NO. Diggs: %d. Link: %s. Title: %s", $item->diggs, $item->link, $item->title);
            $this->logger->info($log);
            $this->output[] = $log;

            return false;
        }
    }

    /**
     * @return array|boolean
     */
    public function fetchData()
    {

        $attempt = 0;

        while ($attempt < 10) {
            $digg = new ImagepushDigg();
            $digg->setVersion('2.0');

            try {

                $response = $digg->search->search(array(
                    'media' => 'images',
                    'domain' => '*',
                    'sort' => 'date-desc', //'promote_date-asc',
                    'min_date' => time() - 6000,
                    'count' => $this->getParameter("limit", 10)
                    ));
            } catch (\Services_Digg2_Exception $e) {

                /**
                 * Expected error codes from Digg:
                 * 408 - Request timeout
                 * 0 - Service Unavailable
                 */
                if ($e->getCode() == 408 || $e->getCode() == 0) {
                    $attempt += 1;
                    $delay = mt_rand(0, 2);

                    $this->logger->err("DiggFetcher. Error: " . $e->getMessage() . " (Code: " . $e->getCode() . "). Next retry in " . $delay . " seconds. Attempt: " . $attempt);

                    sleep($delay);
                    continue;
                }

                $this->logger->err("DiggFetcher. Error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");

                return array("message" => $e->getMessage(), "code" => $e->getCode());
            }

            // Success => break the loop
            break;
        }
        //\D::dump($digg->getLastResponse()->getHeader());

        if (empty($response->count)) {
            $this->fetchedCounter = 0;
            $this->data = false;
        } else {
            $this->fetchedCounter = $response->count;
            $this->data = $response->stories;
//      echo "<pre>";
//      echo serialize($response->stories);
//      echo "</pre>";
        }

        return true;
    }

    /**
     * Check and save
     * 
     * @return boolean
     * 
     * @throws \Exception 
     */
    public function checkAndSaveData()
    {

        if (!isset($this->data) || $this->data == false) {
            return false;
        }

        foreach ($this->data as $item) {

            if (!$this->isWorthToSave($item)) {
                continue;
            }

            $image = new Image();
            $image->setSourceType("digg");
            $image->setLink($item->link);
            // @codingStandardsIgnoreStart
            $image->setTimestamp((int) $item->submit_date);
            // @codingStandardsIgnoreEnd
            $image->setTitle($item->title);
            $image->setSlug(CustomStrings::slugify($item->title));

            if (!empty($item->topic->name)) {
                $image->setSourceTags((array) $item->topic->name);
            }

            try {
                // increment id
                $nextId = $this->dm->getRepository('ImagepushBundle:Image')->getNextId();

                if ($nextId) {
                    $image->setId($nextId);
                } else {
                    throw new \Exception("Can't find max image ID to increment");
                }

                $this->dm->persist($image);
                $this->dm->flush();

                $this->dm->refresh($image);

                $this->savedCounter++;
            } catch (\Exception $e) {
                $this->logger->err(sprintf("Link: %s has not been saved. Error was: %s", $item->link, $e->getMessage()));
            }

            if (!empty($image->getTimestamp()->sec) && $image->getTimestamp()->sec > $this->recentSourceDate) {
                $this->recentSourceDate = $image->getTimestamp()->sec;
            }
        }
    }

    /**
     * @return type 
     */
    public function run()
    {

        // Get latest timestamp
        $image = $this->dm->getRepository('ImagepushBundle:Image')
            ->createQueryBuilder()
            ->field('sourceType')->equals("digg")
            ->sort('timestamp', 'DESC')
            ->requireIndexes(false)
            ->getQuery()
            ->getSingleResult();

        //\D::dump($image);
        //\D::dump($image->getTimestamp()->sec);

        $minDelay = $this->getParameter("min_delay", 1800);

        if ($image instanceof Image && time() < $image->getTimestamp()->sec + $minDelay) {
            $this->output[] = sprintf("[DiggFetcher] %s: Last access attempt was OK, so wait %d secords between requests (%d sec to wait).", date(DATE_RSS), $minDelay, $image->getTimestamp()->sec + $minDelay - time());
        } else {

            $status = $this->fetchData();
            //\D::dump($this->data);
            //die();

            if ($status === true) {

                $this->checkAndSaveData();

                if ($this->savedCounter == 0) {
                    $this->output[] = sprintf("[DiggFetcher] %s: %d sources received, but nothing has been saved (all filtered out).", date(DATE_RSS), $this->fetchedCounter);
                } else {
                    $this->output[] = sprintf("[DiggFetcher] %s: %d of %d items have been saved. Recent source date was on %s", date(DATE_RSS), $this->savedCounter, $this->fetchedCounter, date(DATE_RSS, $this->recentSourceDate));
                }
            } else {
                $this->output[] = sprintf("[DiggFetcher] %s: Digg replied with error: %s. Code: %s", date(DATE_RSS), $status["message"], $status["code"]);
            }
        }

        return $this->output;
    }

}