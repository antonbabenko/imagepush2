<?php

namespace Imagepush\ImagepushBundle\Services\Fetcher;

use Imagepush\ImagepushBundle\Repository\CounterRepository;
use Imagepush\ImagepushBundle\Repository\ImageRepository;
use Imagepush\ImagepushBundle\Repository\LinkRepository;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    /**
     * @var $ddb \Aws\DynamoDb\DynamoDbClient
     */
    protected $ddb;

    /**
     * @var $ddb \Aws\Sqs\SqsClient
     */
    protected $sqs;

    /**
     * @var $imageRepo ImageRepository
     */
    protected $imageRepo;

    /**
     * @var $linkRepo LinkRepository
     */
    protected $linkRepo;

    /**
     * @var $counterRepo CounterRepository
     */
    protected $counterRepo;

    public $parameters;
    public $sqsQueueUrlImages;

    /**
     * @var string $fetcherType
     */
    public $fetcherType;

    /**
     * AbstractFetcher
     *
     * @param ContainerInterface $container
     * @param string             $fetcherType
     *
     * @throws \Exception
     */
    public function __construct(ContainerInterface $container, $fetcherType = null)
    {
        $this->container = $container;
        $this->logger = $container->get('imagepush.fetcher_logger');
        $this->ddb = $container->get('aws.dynamodb');
        $this->sqs = $container->get('aws.sqs');

        $this->imageRepo = $container->get('imagepush.repository.image');
        $this->linkRepo = $container->get('imagepush.repository.link');
        $this->counterRepo = $container->get('imagepush.repository.counter');

        if (!$this->fetcherType = $fetcherType) {
            throw new \Exception("AbstractFetcher should have fetcherType defined before construct");
        }

        if ($container->hasParameter('imagepush.fetcher.' . $this->fetcherType)) {
            $this->parameters = $container->getParameter('imagepush.fetcher.' . $this->fetcherType);
        }

        $this->sqsQueueUrlImages = $container->getParameter('imagepush.sqs_queue_url_images');
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
     * Check if API call is allowed now (check delay)
     */
    public function isAllowedToPerformAPICall()
    {
        /* $lastAPICallTime = (int) apcu_fetch($this->fetcherType . "_last_api_call_time");
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

          apcu_store($this->fetcherType . "_last_api_call_time", time());
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
