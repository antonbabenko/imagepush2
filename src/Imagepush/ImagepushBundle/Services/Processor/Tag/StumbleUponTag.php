<?php

namespace Imagepush\ImagepushBundle\Services\Processor\Tag;

use Imagepush\ImagepushBundle\Document\Image;

class StumbleUponTag extends Tag implements TagInterface
{

    /**
     * StumbleUpon
     *
     * @param Image $image
     *
     * @link http://www.stumbleupon.com/services/1.01/badge.getinfo?url=http://www.treehugger.com/
     * @link YQL sample URL: SELECT content FROM html WHERE url="http://www.stumbleupon.com/url/www.flickr.com/photos/passiveaggressive/3642661392/sizes/o/" and xpath='//ul[@class="listTopics"]/li/a'
     *
     * @return array|false Array of found tags; Empty array if no tags found; False - if error or not indexed
     */
    public function find(Image $image)
    {

        $url = "http://www.stumbleupon.com/services/1.01/badge.getinfo?url=" . $image->getLink();
        $xpathQueries[] = '//div[@class="subject-dna"]/div[@class="topic-bar"]';

        $content = $this->container->get('imagepush.processor.content');
        $content->get($url);

        if (!$content->isSuccessStatus()) {
            $this->logger->warn(sprintf("StumbleUpon. ID: %d. Link %s returned status code %d", $image->getId(), $image->getLink(), $content->getData()));

            return array();
        }

        $response = @json_decode($content->getContent(), true);

        //\D::debug($response);

        if (empty($response["result"]["in_index"])) {
            return array();
        }

        if (empty($response["result"]["publicid"])) {
            $this->logger->error(sprintf("StumbleUpon. URL %s doesn't have publicid property", $url));

            return array();
        }

        $link = "http://www.stumbleupon.com/content/".$response["result"]["publicid"];

        $content->get($link);

        $contentHtml = $this->container->get('imagepush.processor.content.html');
        $contentHtml->setContent($content);

        $domxpath = new \DOMXPath($contentHtml->getDom());

        $tags = array();

        foreach ($xpathQueries as $xpathQuery) {
            $filtered = $domxpath->query($xpathQuery);

            foreach ($filtered as $item) {
                $tags[$item->nodeValue] = 1;
            }
        }

        $tags = $this->fixTagsArray($tags);

        return $tags;
    }

}
