<?php

namespace Imagepush\ImagepushBundle\Services\AccessControl;

/**
 * Web-services access limits (delays, max attempts, status messages)
 */
class ServiceAccess
{

    /**
     * Service key
     * 
     * @var string
     */
    public $key;

    /**
     * Service access settings
     * 
     * @var array
     */
    public $settings;

    /**
     * Server status messages
     */

    const STATUS_OK = "OK";
    const STATUS_FAIL = "FAIL";

    /**
     * @param Predis\Client $redis
     * @param array         $settings
     */
    public function __construct($redis, $settings)
    {
        $this->redis = $redis;
        $this->settings = $settings;
    }

    /**
     * @param Monolog\Logger $logger
     */
    public function setLogger($logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Set service key
     * 
     * @param string $key
     * 
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        if (array_key_exists($key, $this->settings)) {
            $this->settings = $this->settings[$key];
        }

        if (isset($this->settings["delay"])) {
            $this->delay = (int) $this->settings["delay"];
        } else {
            $this->delay = 5;
        }

        if (isset($this->settings["max_attempts"])) {
            $this->maxAttempts = (int) $this->settings["delay"];
        } else {
            $this->maxAttempts = 5;
        }

        return $this;
    }

    /**
     * Get minimum allowed exponential delay before next attempt to access web-service.
     * 
     * @return int
     */
    public function getDelay()
    {

        $lastAccess = (int) $this->redis->get('service_access_' . $this->key);

        if ($this->serviceIsOK()) {
            $expDelay = $this->delay;
        } else {
            $expDelay = $this->getExpDelay();
        }

        $delay = $lastAccess + $expDelay - time();

        // Last access was long time ago = no delay
        if ($delay < 0) {
            $this->resetExtentDelay();

            return 0;
        }
        // Last access was short time ago = time difference delay
        elseif ($lastAccess > 0) {
            return $delay;
        }
        // Never accessed before = normal delay
        else {
            $this->resetExtentDelay();

            return $expDelay;
        }
    }

    /**
     * Update when service was last accessed.
     */
    public function updateLastAccess()
    {
        $this->redis->set('service_access_' . $this->key, time());
    }

    /**
     * Update service status message
     *
     * @param string $status "OK" or "FAIL" status message
     */
    public function updateServiceStatus($status = self::OK)
    {
        $this->redis->rpush('service_status_' . $this->key, $status);

        // Update last access at the end to make sure that next delay will be correct.
        $this->updateLastAccess();
    }

    /**
     * Returns true if last service access was OK
     */
    public function serviceIsOK()
    {
        $lastStatus = $this->redis->lrange('service_status_' . $this->key, -1, -1);

        return (isset($lastStatus[0]) && self::STATUS_OK == $lastStatus[0]);
    }

    /**
     * Returns exponential delay for this service.
     * 
     * @return int
     */
    public function getExpDelay()
    {
        $extent = min(12, (int) $this->redis->incr('service_extent_' . $this->key));

        return max($this->delay, pow(2, $extent - 1));
    }

    /**
     * Reset delay's extent to zero
     */
    public function resetExtentDelay()
    {
        $this->redis->set('service_extent_' . $this->key, 0);
    }

}