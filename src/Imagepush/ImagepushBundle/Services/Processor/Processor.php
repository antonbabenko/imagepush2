<?php

namespace Imagepush\ImagepushBundle\Services\Processor;

use Doctrine\ORM\EntityManager;
use Guzzle\Http\Exception\BadResponseException;
use Imagepush\ImagepushBundle\Consumer\MessageTask;
use Imagepush\ImagepushBundle\Entity\Image;
use Imagepush\ImagepushBundle\Entity\Link;
use Imagepush\ImagepushBundle\Entity\ProcessedHash;
use Imagepush\ImagepushBundle\Entity\ImageRepository;
use Imagepush\ImagepushBundle\Entity\LinkRepository;
use Imagepush\ImagepushBundle\Entity\ProcessedHashRepository;
use Imagepush\ImagepushBundle\Enum\LinkStatusEnum;
use Imagepush\ImagepushBundle\Services\Fetcher\Client;
use Imagepush\ImagepushBundle\Services\Varnish\Varnish;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Processor
 *
 * @todo: Other classes are:
 *   Processor\Processor - super-class which handles all other processors relations and contains business logic
 *   Processor\Html - handle html content (and get the most suitable image inside html content)
 *   Processor\Image - handle image content. Make thumbs.
 * @todo: Processor\Tags - handle tags
 * @todo: Processor\Config - handle all config vars
 *   Fetcher\Content - get link content
 */
class Processor
{

    public static $mimeImages = ['image/gif', 'image/jpeg', 'image/jpg', 'image/png'];
    public static $mimeHtml = ['text/xhtml', 'text/html'];

    /**
     * @var ContainerInterface $container
     */
    public $container;

    public function __construct(ContainerInterface $container, $testMode = false)
    {
        $this->container = $container;

        /**
         * Ignoring data of Processed hashes, Visited links, etc
         */
        $this->testMode = $testMode;

        $this->testMode = true;
    }

    /**
     * 1) get one latest urls, which is unprocessed and not blocked by other working process
     * //2) check what kind of site is it - blocked (nudes, porn) or good
     * 3) get content from URL
     * 4) check type of content - image or html
     * 4a) if single image -> verify image size and save
     * 4b) if html -> try to find large image(s) and save thumbs
     * 5) try to find tags for the URL
     */
    public function processSource()
    {

        $result = false;

        /**
         * Create image object based on unprocessed source
         */
        $image = $this->getImageRepository()->findUnprocessedSource();

        if ($image) {

//            $image->setInProcess(true); // @todo: lock in Redis
            $this->getEntityManager()->persist($image);
            $this->getEntityManager()->flush();
            $this->getEntityManager()->refresh($image);

            $this->getLogger()->info(sprintf("ID: %d. Source link to process: %s", $image->getId(), $image->getLink()));
        } else {
            $log = "Ok, but there are no unprocessed images to work on...";
            $this->getLogger()->info($log);

            return $log;
        }

        $image->setLink('http://localhost/elasticsearch-HQ/images/screenie.png');
        //$image->setLink('http://localhost/elasticsearch-HQ/');

        if (!$this->testMode && $this->getLinkRepository()->hasBeenSeen($image->getLink())) {
            return $this->rollback($image);
        }

        /*
          if ($image->sourceDomainIsBlocked()) {
          $this->logger->warn(sprintf("ID: %d. %s is blacklisted domain (porn, spam, etc)", $image->id, $image->link));

          return false;
          }
         */

        /**
         * Get content from the link
         */
//        $content = $this->container->get('imagepush.processor.content');
        try {
            $response = $this->getClient()->get($image->getLink())->send();
            $responseMd5 = $response->getContentMd5() ?: md5($response->getBody(true));
        } catch (BadResponseException $e) {
            $this->getLogger()->info(
                sprintf(
                    'ID: %d. Link %s returned status code %d',
                    $image->getId(),
                    $image->getLink(),
                    $e->getResponse()->getStatusCode()
                )
            );

            return $this->rollback($image);
        }

//        var_dump($response);

//        die();

        /**
         * Content is an image
         */
        if (in_array($response->getContentType(), self::$mimeImages)) {

            if (null != $this->getProcessedHashRepository()->findOneByHash($responseMd5)) {
                $this->getLogger()->info(
                    sprintf(
                        'ID: %d. Image %s has been already processed (hash found)',
                        $image->getId(),
                        $image->getLink()
                    )
                );
            } else {

                $result = $this->processFoundImage($image, $response);

                if ($result) {

                    $this->saveProcessedHash($image, $responseMd5);

                    $this->getLogger()->info(
                        sprintf(
                            "ID: %d. Link %s has been processed as single image.",
                            $image->getId(),
                            $image->getLink()
                        )
                    );
                }
            }
        }

        /**
         * Content is HTML/XML
         */
        if (in_array($response->getContentType(), self::$mimeHtml)) {

            $functions = array(
                "getFullImageSrc", /* Try to find <link rel="image_src" /> or <meta property="og:image" /> */
                "getBestImageFromDom" /* Try to find large images inside html DOM */
            );

            foreach ($functions as $function) {

                // @todo: merge into current class
                $images = $this->container->get('imagepush.processor.content.html')->setContent($response)->$function();

                if ($images) {

                    foreach ($images as $url) {

                        if ($this->getLinkRepository()->hasBeenSeen($url)) {
                            continue;
                        }

                        try {
                            $subResponse = $this->getClient()->get($url)->send();
                            $subResponseMd5 = $subResponse->getContentMd5() ?: md5($subResponse->getBody(true));
                        } catch (BadResponseException $e) {
                            $this->getLogger()->info(
                                sprintf(
                                    'ID: %d. Sub-link %s returned status code %d',
                                    $image->getId(),
                                    $url,
                                    $e->getResponse()->getStatusCode()
                                )
                            );

                            continue;
                        }

                        if (!in_array($subResponse->getContentType(), self::$mimeImages)) {
                            continue;
                        }

                        if (null != $this->getProcessedHashRepository()->findOneByHash($subResponseMd5)) {
                            $this->getLogger()->info(
                                sprintf(
                                    'ID: %d. Image %s has been already processed (hash found)',
                                    $image->getId(),
                                    $image->getLink()
                                )
                            );

                            continue;
                        } else {

                            $result = $this->processFoundImage($image, $subResponse);

                            if ($result) {

                                $this->saveProcessedHash($image, $subResponseMd5);

                                $this->getLogger()->info(
                                    sprintf(
                                        "ID: %d. Link %s has been processed as single image.",
                                        $image->getId(),
                                        $image->getLink()
                                    )
                                );

                                $link = new Link();
                                $link->setLink($image->getLink());
                                $link->setStatus(LinkStatusEnum::INDEXED);

                                $this->getEntityManager()->persist($link);
                                $this->getEntityManager()->flush();

                                // Image has been found and saved. No need to search further.
                                break 2;
                            }
                        }

                    }
                }
            }
        }

        /**
         * Not found
         */
        if (!$result) {
            return $this->rollback($image);
        }

        /**
         * Mark link as indexed
         */
        $link = new Link();
        $link->setLink($image->getLink());
        $link->setStatus(LinkStatusEnum::INDEXED);
        $this->getEntityManager()->persist($link);
        $this->getEntityManager()->flush();

        /**
         * Find tags
         */
        $msg = json_encode(['image_id' => $image->getId(), 'task' => MessageTask::FIND_TAGS_AND_MENTIONS]);
        $this->getRabbitMQProducer()->publish($msg);
        $this->getLogger()->info(sprintf("MESSAGE: %s", $msg));

        $log = sprintf("ID: %d. Source processed.", $image->getId());
        $this->getLogger()->info($log);

        $this->getVarnish()->purgeWhenNewImagesSavedAsUpcoming();

        return $log;
    }

    /**
     * @param  Image  $image
     * @return string
     */
    protected function rollback($image)
    {
        $log = sprintf(
            "ID: %d. No images found, so link %s should be removed.",
            $image->getId(),
            $image->getLink()
        );
        $this->getLogger()->info($log);

        // Mark link as failed
        $link = new Link();
        $link->setLink($image->getLink());
        $link->setStatus(LinkStatusEnum::FAILED);

        $this->getEntityManager()->persist($link);

        // Remove image
        $this->getEntityManager()->remove($image);

        $this->getEntityManager()->flush();

        return $log;
    }

    /**
     * @return ImageRepository
     */
    protected function getImageRepository()
    {
        return $this->container->get('repository.image');
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }

    /**
     * @return Logger
     */
    protected function getLogger()
    {
        return $this->container->get('imagepush.processor_logger');
    }

    /**
     * @return LinkRepository
     */
    protected function getLinkRepository()
    {
        return $this->container->get('repository.link');
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        return $this->container->get('imagepush.fetcher.client');
    }

    /**
     * @return Varnish
     */
    protected function getVarnish()
    {
        return $this->container->get('imagepush.varnish');
    }

    /**
     * @return ProcessedHashRepository
     */
    protected function getProcessedHashRepository()
    {
        return $this->container->get('repository.processed_hash');
    }

    /**
     * Process content of found image
     * (verify image size, save original image, generate required thumbnails)
     *
     * @param Image   $image   Image
     * @param Content $content Content
     *
     * @return boolean True if image has been successfully saved
     */
    private function processFoundImage(Image $image, $content)
    {
        $imagine = $this->container->get('liip_imagine');
        $foundImage = $imagine->load($content->getBody(true));
        //\D::debug($foundImage->getSize()->getWidth());

        if ($foundImage->getSize()->getWidth() >= $this->container->getParameter('imagepush.image.min_width') &&
            $foundImage->getSize()->getHeight() >= $this->container->getParameter('imagepush.image.min_height')
        ) {

            // Set mime-type from content-type
            $image->setMimeType($content->getContentType());

            // Update filename based on mime-type
            $image->updateFilename();

            $this->getLogger()->info(sprintf("ID: %d. Saving original file as: %s", $image->getId(), $image->getFile()));

            // Save original file (to set correct permissions as for other thumbnails)
            // @fixit !!!
//            $this->container
//                ->get('imagepush.imagine.files.cache.resolver')
//                ->store(new Response($content->getBody(true)), 'i/' . $image->getFile(), "");

            // Generate required thumbnails
            // Thumbnails won't be regenerated, if there is already same file exists
//            $this->generateRequiredThumbs($image);

            // Update image object
            $image->setInProcess(false);
            $image->setAvailable(false);

            $this->getEntityManager()->persist($image);
            $this->getEntityManager()->flush();

            return true;
        } else {
            return false;
        }
    }

    /**
     * Generate thumbnails which has to be generated always
     */
    public function generateRequiredThumbs(Image $image)
    {
        $thumbs = $this->container->getParameter('imagepush.thumbs');

        foreach ($thumbs as $attributes) {
            $url = $this->container
                ->get('twig.extension.imagepush')
                ->imagepushFilter(
                    'i/' . $image->getFile(),
                    $attributes[0],
                    $attributes[1],
                    $attributes[2],
                    $image->getId()
                );

            $this->logger->info(sprintf("ID: %d. Generating thumb (via request): %s", $image->getId(), $url));

            $this->container
                ->get('imagepush.fetcher.client')
                ->get($url)
                ->send();
        }

        return true;
    }

    protected function saveProcessedHash($image, $hash)
    {
        // Store processed hash
        try {
            $processedHash = new ProcessedHash();
            $processedHash->setHash($hash);

            $this->getEntityManager()->persist($processedHash);
            $this->getEntityManager()->flush();

            $this->getLogger()->info(sprintf("ID: %d. ProcessedHash (hash: %s) is saved.", $image->getId(), $hash));
        } catch (\Exception $e) {
            $this->getLogger()->critical(
                sprintf(
                    "ID: %d. ProcessedHash (hash: %s) was not saved. Error was: %s",
                    $image->getId(),
                    $hash,
                    $e->getMessage()
                )
            );
        }
    }

    protected function getRabbitMQProducer()
    {
        return $this->container->get('old_sound_rabbit_mq.primary_producer');
    }

}
