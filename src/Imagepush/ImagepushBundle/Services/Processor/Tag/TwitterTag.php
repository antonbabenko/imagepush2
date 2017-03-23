<?php

namespace Imagepush\ImagepushBundle\Services\Processor\Tag;

use Imagepush\ImagepushBundle\Document\Image;

class TwitterTag extends Tag implements TagInterface
{

    /**
     * Twitter
     *
     * Twitter search for hashtags (search for url and exact title)
     *
     * Documentation: https://dev.twitter.com/oauth/application-only
     *
     * @todo: Implement OAuth to be able to call API.
     *
     * @return array|false Array of found tags; False - if error or not indexed
     */
    public function find(Image $image)
    {

        // @todo
        return [];

        $urls[] = "https://api.twitter.com/1.1/search/tweets.json?result_type=recent&q=" . urlencode($image->getLink());

        // Search by title can very unspecific, when title is short
        if (mb_strlen($image->getTitle()) >= $this->container->getParameter('imagepush.twitter.min_title_length', 15)) {
            $urls[] = "https://api.twitter.com/1.1/search/tweets.json?result_type=recent&q=" . urlencode('"' . $image->getTitle() . '"');
        }

        $tags = array();

        foreach ($urls as $url) {

            //\D::debug($url);
            $content = $this->container->get('imagepush.processor.content');
            $content->get($url);

            if (!$content->isSuccessStatus()) {
                $this->logger->warn(sprintf("Twitter. ID: %d. Link %s returned status code %d", $image->getId(), $image->getLink(), $content->getData()));

                continue;
            }

            $response = @json_decode($content->getContent(), true);
            //\D::debug($response);

            if (!count($response["results"])) {
                continue;
            }

            foreach ($response["results"] as $tweet) {
                if (preg_match_all("/#([\\d\\w]+)/", $tweet["text"], $out)) {
                    $tags = array_merge($tags, $out[1]);
                }
            }
        }

        $tags = $this->fixTagsArray($tags);
        $tags = $this->filterTagsByScore($tags);

        return $tags;
    }

}
