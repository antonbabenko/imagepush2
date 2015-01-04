<?php

namespace Imagepush\ImagepushBundle\Services\Fetcher;

use Doctrine\ORM\EntityManager;
use Imagepush\ImagepushBundle\DataTransformer\TransformerInterface;
use Psr\Log\LoggerInterface;
use Snc\RedisBundle\Client\Phpredis\Client as RedisClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AbstractFetcher
 */
class AbstractFetcher
{

    /**
     * @var string
     */
    public $fetcherType;
    /**
     * Counters for fetched, saved items, output array.
     */
    public $fetchedCounter = 0;
    public $savedCounter = 0;
    /**
     * @var LoggerInterface $logger
     */
    public $logger;
    /**
     * @var EntityManager
     */
    public $em;
    /**
     * @var TransformerInterface
     */
    public $dataTransformer;
    public $parameters;

    /**
     * AbstractFetcher
     *
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        if (null == $this->fetcherType) {
            throw new \Exception("AbstractFetcher should have fetcherType defined before construct");
        }

        if ($container->hasParameter('imagepush.fetcher.' . $this->fetcherType)) {
            $this->parameters = $container->getParameter('imagepush.fetcher.' . $this->fetcherType);
        }
    }

    /**
     * Wait some seconds before next call, if necessary.
     */
    public function delayBeforeNextApiCall()
    {
        $minDelay = $this->getParameter('min_delay', 60);

        $secFromLastCall = time() - (int) $this->getCache()->get($this->fetcherType . '_last_api_call_time');

        if ($secFromLastCall <= $minDelay) {
            $sleep = $minDelay - $secFromLastCall + 1;

            $this->getLogger()->info(sprintf('Should sleep %s seconds before next API call', $sleep));

            sleep($sleep);
        }

        $this->getCache()->set($this->fetcherType . '_last_api_call_time', time());
    }

    /**
     * Get parameter
     *
     * @param  string  $name
     * @param  integer $default
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
     * @return RedisClient
     */
    public function getCache()
    {
        return $this->container->get('snc_redis.default');
    }

    /**
     * @return TransformerInterface
     */
    public function getDataTransformer()
    {
        return $this->dataTransformer;
    }

    /**
     * @param TransformerInterface $dataTransformer
     */
    public function setDataTransformer(TransformerInterface $dataTransformer)
    {
        $this->dataTransformer = $dataTransformer;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->container->get('imagepush.fetcher.client');
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }

}
