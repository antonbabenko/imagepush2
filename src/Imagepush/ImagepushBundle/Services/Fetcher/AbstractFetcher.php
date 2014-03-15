<?php

namespace Imagepush\ImagepushBundle\Services\Fetcher;

/**
 * AbstractFetcher
 */
class AbstractFetcher
{

    /**
     * Fetched data
     *
     * @param object $data
     */
    public $data;

    /**
     * Counters for fetched, saved items, output array.
     */
    public $fetchedCounter = 0;
    public $savedCounter = 0;
    public $output;

    /**
     * @var Logger $logger
     */
    public $logger;
    public $dm;
    public $parameters;

    /**
     * @var string $fetcherType
     */
    public $fetcherType;

    /**
     * AbstractFetcher
     *
     * @param ContainerInterface $container
     * @param string $fetcherType
     */
    public function __construct($container, $fetcherType = null)
    {
        $this->container = $container;
        $this->logger = $container->get('imagepush.fetcher_logger');
        $this->dm = $container->get('doctrine.odm.mongodb.document_manager');

        if (!$this->fetcherType = $fetcherType) {
            throw new \Exception("AbstractFetcher should have fetcherType defined before construct");
        }

        if ($container->hasParameter('imagepush.fetcher.' . $this->fetcherType)) {
            $this->parameters = $container->getParameter('imagepush.fetcher.' . $this->fetcherType);
        }
    }

    /**
     * Get parameter
     *
     * @param string $name
     * @param integer $default
     * @return integer
     */
    public function getParameter($name, $default = null)
    {
        if (isset($this->parameters[$name])) {
            return $this->parameters[$name];
        } else {
            return $default;
        }
    }

    /**
     * Check if API call is allowed now (check delay)
     */
    public function isAllowedToPerformAPICall()
    {
        /* $lastAPICallTime = (int) apc_fetch($this->fetcherType . "_last_api_call_time");
          if ($lastAPICallTime + $this->getParameter("min_delay", 60) >= time()) {
          return false;
          }

          return true; */
    }

    /**
     * Wait some seconds before next call, if necessary.
     *
     */
    public function delayBeforeNextApiCall()
    {
        // APC doesn't work in CLI mode, so do delay manually:
        sleep($this->getParameter("min_delay", 60));

        /* if (!$this->isAllowedToPerformAPICall()) {
          sleep($this->getParameter("min_delay", 60));
          }

          apc_store($this->fetcherType . "_last_api_call_time", time());
         */
    }

    /**
     * @todo: check by domain name and content on that domain (filter porn, xxx, sex)
     */
    public function isWorthToSave($item)
    {
        return true;
    }

}
