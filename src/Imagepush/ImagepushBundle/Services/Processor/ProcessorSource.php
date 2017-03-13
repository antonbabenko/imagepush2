<?php

namespace Imagepush\ImagepushBundle\Services\Processor;

use Imagepush\ImagepushBundle\Document\Image;
use Imagepush\ImagepushBundle\Document\Link;
use Imagepush\ImagepushBundle\Document\ProcessedHash;
use Imagepush\ImagepushBundle\Repository\ImageRepository;
use Imagepush\ImagepushBundle\Repository\LinkRepository;
use Imagepush\ImagepushBundle\Repository\ProcessedHashRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Processor
 *
 * @todo: Other classes are:
 *   Processor\Processor - super-class which handles all other processors relations and contains business logic
 *   Processor\Html - handle html content (and get the most suitable image inside html content)
 *   Processor\Image - handle image content. Make thumbs.
 *   @todo: Processor\Tags - handle tags
 *   @todo: Processor\Config - handle all config vars
 *   Fetcher\Content - get link content
 */
class ProcessorSource
{

    /**
     * @var ContainerInterface $container
     */
    public $container;

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
     * @var $processedHashRepo ProcessedHashRepository
     */
    protected $processedHashRepo;

    protected $sqsQueueUrlImages;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $container->get('imagepush.processor_logger');
        $this->varnish = $container->get('imagepush.varnish');

        $this->ddb = $container->get('aws.dynamodb');
        $this->sqs = $container->get('aws.sqs');
        $this->sqsQueueUrlImages = $container->getParameter('imagepush.sqs_queue_url_images');
        $this->sqsQueueUrlFindTags = $container->getParameter('imagepush.sqs_queue_url_find_tags');

        $this->imageRepo = $container->get('imagepush.repository.image');
        $this->linkRepo = $container->get('imagepush.repository.link');
        $this->processedHashRepo = $container->get('imagepush.repository.processed_hash');

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
        $request = [
            'MaxNumberOfMessages' => 1,
            'QueueUrl' => $this->sqsQueueUrlImages,
        ];
        $messages = $this->sqs->receiveMessage($request);

        $messages = $messages->get('Messages');

        if (0 == count($messages)) {
            $log = "Ok, but there is no unprocessed images to work on...";
            $this->logger->info($log);

            return $log;
        }

        $this->sqs->deleteMessage([
            'QueueUrl' => $this->sqsQueueUrlImages,
            'ReceiptHandle' => $messages[0]['ReceiptHandle']
        ]);

        $id = json_decode($messages[0]['Body']);

        $image = $this->imageRepo->findOneBy($id, false);

        if ($image) {
            $this->logger->info(sprintf("ID: %d. Source link to process: %s", $image->getId(), $image->getLink()));
        } else {
            $this->logger->info(sprintf("ID: %d. Image record was no found in DB", $id));

            return false;
        }

        if ($this->linkRepo->isIndexedOrFailed($image->getLink())) {
            return false;
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
        $content = $this->container->get('imagepush.processor.content');
        $content->get($image->getLink());

        if (!$content->isSuccessStatus()) {
            $this->logger->info(sprintf("ID: %d. Link %s returned status code %d", $image->getId(), $image->getLink(), $content->getData()));

            return false;
        }

        /**
         * Content is an image
         */
        if ($content->isImageType()) {

            $this->logger->info(sprintf("ID: %d. Hash: %s.", $image->getId(), $content->getContentMd5()));

            if ($this->processedHashRepo->exists($content->getContentMd5())) {
                $this->logger->info(sprintf("ID: %d. Image %s has been already processed (hash found)", $image->getId(), $image->getLink()));
            } else {

                $result = $this->processFoundImage($image, $content);

                if ($result) {
                    $processedHash = new ProcessedHash($content->getContentMd5());
                    $this->processedHashRepo->save($processedHash);

                    $this->logger->info(sprintf("ID: %d. ProcessedHash (hash: %s) is saved.", $image->getId(), $content->getContentMd5()));

                    $this->logger->info(sprintf("ID: %d. Link %s has been processed as single image.", $image->getId(), $image->getLink()));
                }
            }
        }

        /**
         * Content is HTML/XML
         */
        if ($content->isHTMLType()) {

            $functions = array(
                "getFullImageSrc", /* Try to find <link rel="image_src" /> or <meta property="og:image" /> */
                "getBestImageFromDom" /* Try to find large images inside html DOM */
            );

            foreach ($functions as $function) {

                $images = $content->htmlContent->$function();

                //\D::dump($function);
                //\D::dump($images);

                if ($images) {

                    foreach ($images as $url) {

                        if ($this->linkRepo->isIndexedOrFailed($url)) {
                            continue;
                        }

                        $contentInside = $this->container->get('imagepush.processor.content');
                        $contentInside->get($url);

                        if (!$contentInside->isImageType()) {
                            continue;
                        }

                        $this->logger->info(sprintf("ID: %d. Hash: %s.", $image->getId(), $contentInside->getContentMd5()));

                        if ($this->processedHashRepo->exists($contentInside->getContentMd5())) {
                            $this->logger->info(sprintf("ID: %d. Image %s has been already processed (hash found)", $image->getId(), $url));

                            continue;
                        }

                        $result = $this->processFoundImage($image, $contentInside);

                        if ($result) {
                            $processedHash = new ProcessedHash($contentInside->getContentMd5());
                            $this->processedHashRepo->save($processedHash);

                            $this->logger->info(sprintf("ID: %d. ProcessedHash (hash: %s) is saved.", $image->getId(), $contentInside->getContentMd5()));

                            $this->logger->info(sprintf("ID: %d. Link %s has been processed by function %s. Correct image url: %s", $image->getId(), $image->getLink(), $function, $url));

                            $link = new Link($url, Link::INDEXED);
                            $this->linkRepo->save($link);

                            // Image has been found and saved. No need to search further.
                            break 2;
                        }
                    }
                }
            }
        }

        /**
         * Not found
         */
        if (!$result) {
            $log = sprintf("ID: %d. No images found, so link %s should be removed.", $image->getId(), $image->getLink());
            $this->logger->info($log);

            // Mark link as failed
            $link = new Link($image->getLink(), Link::FAILED);
            $this->linkRepo->save($link);

            // Remove image
            $this->imageRepo->deleteById($image->getId());

            return $log;
        }

        /**
         * Mark link as indexed
         */
        $link = new Link($image->getLink(), Link::INDEXED);
        $this->linkRepo->save($link);

        /**
         * Find tags
         */
        $entries = [];
        $services = MessageTask::$services[MessageTask::FIND_TAGS_AND_MENTIONS];
        foreach ($services as $i => $service) {
            $message = json_encode(
                [
                    'id' => $image->getId(),
                    'service' => $service
                ]
            );

            $entries[] = [
                'Id' => $image->getId() . '-' . $service,
                'MessageBody' => $message,
            ];

            $this->logger->info(sprintf("MESSAGE: %s", $message));

            if (count($entries) == 10 || $i == count($services) - 1) {
                $request = [
                    'Entries' => $entries,
                    'QueueUrl' => $this->sqsQueueUrlFindTags,
                ];

                $this->sqs->sendMessageBatch($request);
                $entries = [];
            }
        }

        $log = sprintf("ID: %d. Source processed.", $image->getId());
        $this->logger->info($log);

//        $this->varnish->purgeWhenNewImagesSavedAsUpcoming();
        return $log;
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
        $foundImage = $imagine->load($content->getContent());
        //\D::debug($foundImage->getSize()->getWidth());

        if ($foundImage->getSize()->getWidth() >= $this->container->getParameter('imagepush.image.min_width') &&
            $foundImage->getSize()->getHeight() >= $this->container->getParameter('imagepush.image.min_height')) {

            // Set mime-type from content-type
            $image->setMimeType($content->getContentType());

            // Update filename based on mime-type
            $image->updateFilename();

            $this->logger->info(sprintf("ID: %d. Saving original file as: %s", $image->getId(), $image->getFile()));

            // Save original file (to set corrent permissions as for other thumbnails)
            $this->container
                ->get('imagepush.imagine.files.cache.resolver')
                ->store(new Response($content->getContent()), 'i/' . $image->getFile(), "");

            // Generate required thumbnails
            // Thumbnails won't be regenerated, if there is already same file exists
            $this->generateRequiredThumbs($image);

            // Update image object
//            $image->setIsInProcess(false);
//            $image->setIsAvailable(false);

            $this->imageRepo->save($image);

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
                ->imagepushFilter('i/' . $image->getFile(), $attributes[0], $attributes[1], $attributes[2], $image->getId());

            $this->logger->info(sprintf("ID: %d. Generating thumb (via request): %s", $image->getId(), $url));

            $this->container
                ->get('imagepush.fetcher.content')
                ->getRequest($url);
        }

        return true;
    }

}
