<?php

namespace Imagepush\ImagepushBundle\Services\Processor;

use Imagepush\ImagepushBundle\Document\Image;
use Imagepush\ImagepushBundle\Document\Link;
use Imagepush\ImagepushBundle\Document\ProcessedHash;
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
class Processor
{

    /**
     * @var Container $container
     */
    public $container;

    /**
     * Is debug mode?
     * Database is not heavily modified (almost no data removed when debug mode is on)
     * 
     * @var boolean $isDebug
     */
    public $isDebug;

    public function __construct(ContainerInterface $container, $isDebug)
    {
        $this->container = $container;
        $this->logger = $container->get('logger');
        $this->dm = $container->get('doctrine.odm.mongodb.document_manager');

        $this->isDebug = $isDebug;
    }

    /**
     * 1) get one latest urls, which is unprocessed and not blocked by other working process
     * 2) check what kind of site is it - blocked (nudes, porn) or good
     * 3) get content from URL
     * 4) check type of content - image or html
     * 4a) if single image -> then it is "unsorted" category
     * 4b) if html:
     * 5) try to find large image(s)
     * 6) try to make thumbs from large image
     * //7) try to find category/tags for the page
     */
    public function processSource()
    {

        $result = false;

        /**
         * Create image object based on unprocessed source
         */
        $image = $this->dm->getRepository('ImagepushBundle:Image')->initUnprocessedSource($this->isDebug);

        if ($image) {
            $this->logger->info(sprintf("ID: %d. Source link to process: %s", $image->getId(), $image->getLink()));
        } else {
            $log = "Ok, but there is no unprocessed images to work on...";
            $this->logger->info($log);

            return $log;
        }

        if (!$this->isDebug && $this->dm->getRepository('ImagepushBundle:Link')->isIndexedOrFailed($image->getLink())) {
            return false;
        }

        /*
          if ($image->sourceDomainIsBlocked())
          {
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

            if (!$this->isDebug && $this->dm->getRepository('ImagepushBundle:ProcessedHash')->findOneBy(array("hash" => $content->getContentMd5()))) {
                $this->logger->info(sprintf("ID: %d. Image %s has been already processed (hash found)", $image->getId(), $image->getLink()));

                return false;
            }

            $result = $this->processFoundImage($image, $content);

            if ($result) {
                $this->logger->info(sprintf("ID: %d. Link %s has been processed as single image.", $image->getId(), $image->getLink()));
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

                        if ($this->dm->getRepository('ImagepushBundle:Link')->isIndexedOrFailed($url)) {
                            continue;
                        }

                        $contentInside = $this->container->get('imagepush.processor.content');
                        $contentInside->get($url);

                        if (!$contentInside->isImageType()) {
                            continue;
                        }

                        if (!$this->isDebug && $this->dm->getRepository('ImagepushBundle:ProcessedHash')->findOneBy(array("hash" => $contentInside->getContentMd5()))) {
                            $this->logger->info(sprintf("ID: %d. Image %s has been already processed (hash found)", $image->getId(), $url));

                            continue;
                        }

                        $result = $this->processFoundImage($image, $contentInside);

                        if ($result) {
                            $this->logger->info(sprintf("ID: %d. Link %s has been processed by function %s. Correct image url: %s", $image->getId(), $image->getLink(), $function, $url));

                            $link = new Link($url, Link::INDEXED);
                            $this->dm->persist($link);
                            $this->dm->flush();

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

            // Remove image
            $this->dm->remove($image);

            // Mark link as failed
            $link = new Link($image->getLink(), Link::FAILED);
            $this->dm->persist($link);

            $this->dm->flush();

            return $log;
        }

        /**
         * Mark link as indexed
         */
        $link = new Link($image->getLink(), Link::INDEXED);
        $this->dm->persist($link);
        $this->dm->flush();

        /**
         * Find tags
         */
        $this->logger->info(sprintf("ID: %d. Searching for tags.", $image->getId()));
        $log = $this->container->get('imagepush.processor.tag')->processTags($image);

        $log .= sprintf("ID: %d. Source processed.", $image->getId());
        $this->logger->info($log);

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

            // Store processed hash
            $processedHash = new ProcessedHash;
            $processedHash->setHash($content->getContentMd5());
            $this->dm->persist($processedHash);

            // Update image object
            $image->setIsInProcess(false);
            $image->setIsAvailable(false);

            $this->dm->persist($image);
            $this->dm->flush();

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