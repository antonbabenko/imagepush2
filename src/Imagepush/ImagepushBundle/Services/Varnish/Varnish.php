<?php

namespace Imagepush\ImagepushBundle\Services\Varnish;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Purge Varnish cache
 */
class Varnish
{

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $container->get('imagepush.varnish_logger');
        $this->dm = $container->get('doctrine.odm.mongodb.document_manager');
        $this->router = $container->get('router');
    }

    public function purgeUrl($url = "/")
    {

        if ($url == "") {
            $url = "/";
        }

        /*
          // This didn't work:
          $varnish = $this->get('liip_cache_control.varnish');
          $result = $varnish->invalidatePath('/i/43523/you-know-what');
         */

        $client = new Client($this->container->getParameter('varnish_url'));
        $request = $client->createRequest('PURGE', $url);

        try {
            $request->send();

            $this->logger->info(sprintf("Purged: %s", $url));

            $result = true;
        } catch (RequestException $e) {

            $this->logger->info(sprintf('Not in cache: %s', $url));

            $result = false;
        }

        return $result;
    }

    /**
     * When new image is published on homepage
     * 
     * @param Image $image
     */
    public function purgeWhenNewImageIsPublished($image = null)
    {
        $this->purgeUrl("/");
        $this->purgeUrl("/upcoming");

        // Get prelast published image (to update prev/next links)
        if ($image) {
            $prevImage = $this->dm
                ->getRepository('ImagepushBundle:Image')
                ->getOneImageRelatedToTimestamp("prev", $image->getTimestamp());

            if ($prevImage) {
                $url = $this->router->generate('viewImage', array('id' => $prevImage->getId(), 'slug' => $prevImage->getSlug()));
                $this->purgeUrl($url);
            }
        }
    }

    /**
     * When new image is saved as upcoming
     * 
     * @todo: update tag pages for added images
     */
    public function purgeWhenNewImagesSavedAsUpcoming()
    {
        $this->purgeUrl("/upcoming");
    }

}