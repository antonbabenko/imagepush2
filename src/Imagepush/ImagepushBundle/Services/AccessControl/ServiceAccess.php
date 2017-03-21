<?php

namespace Imagepush\ImagepushBundle\Services\AccessControl;

use Imagepush\ImagepushBundle\Repository\CounterRepository;

/**
 * Web-services access limits (delays, max attempts, status messages)
 */
class ServiceAccess
{
    /**
     * Server status messages
     */

    const STATUS_OK = "OK";
    const STATUS_FAIL = "FAIL";

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
     * @var $counterRepo CounterRepository
     */
    protected $counterRepo;

    /**
     * @param CounterRepository $counterRepo
     * @param array             $settings
     */
    public function __construct(CounterRepository $counterRepo, $settings)
    {
        $this->counterRepo = $counterRepo;
        $this->settings = $settings;
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

        $lastAccess = (float) $this->counterRepo->getValue('service_access_' . $this->key);

        if ($this->serviceIsOK()) {
            $expDelay = $this->delay;
        } else {
            $expDelay = $this->getExpDelay();
        }

        $delay = $lastAccess + $expDelay - microtime(true) + 1;

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
        $this->counterRepo->updateValue('service_access_' . $this->key, microtime(true));
    }

    /**
     * Update service status message
     *
     * @param string $status "OK" or "FAIL" status message
     */
    public function updateServiceStatus($status = self::STATUS_OK)
    {
        $this->counterRepo->updateValue('service_status_' . $this->key, $status);

        // Update last access at the end to make sure that next delay will be correct.
        $this->updateLastAccess();
    }

    /**
     * Returns true if last service access was OK
     */
    public function serviceIsOK()
    {
        $lastStatus = $this->counterRepo->getValue('service_status_' . $this->key);

        return self::STATUS_OK == $lastStatus;
    }

    /**
     * Returns exponential delay for this service.
     *
     * @return int
     */
    public function getExpDelay()
    {
        $serviceExtent = (int) $this->counterRepo->getValue('service_extent_' . $this->key);

        $extent = min(12, $serviceExtent);

        // increment extent
        $this->counterRepo->updateValue('service_extent_' . $this->key, $serviceExtent + 1);

        return max($this->delay, pow(2, $extent - 1));
    }

    /**
     * Reset delay's extent to zero
     */
    public function resetExtentDelay()
    {
        $this->counterRepo->updateValue('service_extent_' . $this->key, 0);
    }

}
