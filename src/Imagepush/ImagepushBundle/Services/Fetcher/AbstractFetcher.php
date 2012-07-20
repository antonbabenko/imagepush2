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
    public $fetchedCounter;
    public $savedCounter;
    public $output;

    /**
     * @var Logger $logger
     */
    public $logger;
    public $dm;
    public $varnish;
    public $parameters;

    /**
     * @var string $fetcherType
     */
    public $fetcherType;

    /**
     * AbstractFetcher
     * 
     * @param ContainerInterface $container
     */
    public function __construct($container, $fetcherType = null)
    {
        $this->logger = $container->get('imagepush.fetcher_logger');
        $this->dm = $container->get('doctrine.odm.mongodb.document_manager');
        $this->varnish = $container->get('imagepush.varnish');

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
     * @return $name
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
     * @todo: check by domain name and content on that domain (filter porn, xxx, sex)
     */
    public function isWorthToSave($item)
    {
        return true;
    }

}