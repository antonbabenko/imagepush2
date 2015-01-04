<?php

namespace Imagepush\ImagepushBundle\Determiner;

use Imagepush\ImagepushBundle\Entity\ImageRepository;
use Imagepush\ImagepushBundle\Entity\LatestTagRepository;
use Imagepush\ImagepushBundle\Entity\LinkRepository;
use Imagepush\ImagepushBundle\Entity\TagRepository;
use Imagepush\ImagepushBundle\Services\Fetcher\Client;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractDeterminer implements ContainerAwareInterface
{

    /**
     * @var array
     */
    protected $worthToSaveLog;

    /**
     * @var array
     */
    protected $notWorthToSaveLog;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct()
    {
        $this->worthToSaveLog = [];
        $this->notWorthToSaveLog = [];
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return ImageRepository
     */
    protected function getImageRepository()
    {
        return $this->container->get('repository.image');
    }

    /**
     * @return TagRepository
     */
    protected function getTagRepository()
    {
        return $this->container->get('repository.tag');
    }

    /**
     * @return LinkRepository
     */
    protected function getLinkRepository()
    {
        return $this->container->get('repository.link');
    }

    /**
     * @return LatestTagRepository
     */
    protected function getLatestTagRepository()
    {
        return $this->container->get('repository.latest_tag');
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        return $this->container->get('imagepush.fetcher.client');
    }

}
