<?php

namespace Imagepush\ImagepushBundle\Services\Processor\Tag;

use Imagepush\ImagepushBundle\Document\Image;

class DeliciousTag extends Tag implements TagInterface
{

    /**
     * Delicious tags
     * 
     * Get tags for URL - http://feeds.delicious.com/v2/json/urlinfo/data?hash=md5(http://...)
     * 
     * @return array|false Array of found tags; Empty array if no tags found; False - if error or not indexed
     */
    public function find(Image $image)
    {

        $url = "http://feeds.delicious.com/v2/json/urlinfo/data?hash=" . md5($image->getLink());

        $content = $this->container->get('imagepush.processor.content');
        $content->get($url);

        if (!$content->isSuccessStatus()) {
            $this->logger->warn(sprintf("Delicious. ID: %d. Link %s returned status code %d", $image->getId(), $image->getLink(), $content->getData()));

            return array();
        }

        $response = @json_decode($content->getContent(), true);

        if (!$response || !count($response) || !count($response[0]["top_tags"])) {
            return array();
        }

        $tags = $this->fixTagsArray($response[0]["top_tags"]);
        $tags = $this->filterTagsByScore($tags, 20);

        return $tags;
    }

}