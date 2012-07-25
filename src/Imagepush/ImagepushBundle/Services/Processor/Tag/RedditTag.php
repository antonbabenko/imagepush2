<?php

namespace Imagepush\ImagepushBundle\Services\Processor\Tag;

use Imagepush\ImagepushBundle\Document\Image;

class RedditTag extends Tag implements TagInterface
{

    /**
     * Reddit
     * 
     * a) search for link by url: http://www.reddit.com/api/info.json?url=http%3A%2F%2Fi.imgur.com%2FM52mw.png
     * b) get subreddits
     * c) get subreddits of the related links
     * YQL is forbidden for some things because of reddit's robots.txt, but it is allowed to fetch reddit pages directly
     * 
     * @param Image $image
     * 
     * @return array|false Array of found tags; Empty array if no tags found; False - if error or not indexed
     */
    public function find(Image $image)
    {

        $url = "http://www.reddit.com/api/info.json?url=" . urlencode($image->getLink());
        $xpathQuery = '//div[contains(concat(" ", @class, " "), " entry ")]/p[@class="tagline"]/a[contains(concat(" ", @class, " "), " subreddit ")]';

        $tags = $subUrls = array();

        $content = $this->container->get('imagepush.processor.content');
        $content->get($url);

        if (!$content->isSuccessStatus()) {
            $this->logger->warn(sprintf("Reddit. ID: %d. Link %s returned status code %d", $image->getId(), $image->getLink(), $content->getData()));

            return array();
        }

        $response = @json_decode($content->getContent(), true);

        if (!count($response["data"]["children"])) {
            return array();
        }

        // Get subreddits of the main link
        foreach ($response["data"]["children"] as $child) {

            $tags[] = $child["data"]["subreddit"];

            if ($child["data"]["score"] >= $this->container->getParameter('imagepush.reddit.min_subreddit_score', 5)) {
                $subUrls[] = "http://www.reddit.com" . str_replace("/comments/", "/related/", $child["data"]["permalink"]);
                $subUrls[] = "http://www.reddit.com" . str_replace("/comments/", "/duplicates/", $child["data"]["permalink"]);
            }
        }

        foreach ($subUrls as $subUrl) {

            sleep(3);

            $contentHtml = $this->container->get('imagepush.processor.content.html');
            $contentHtml->setContent($content);

            $domxpath = new \DOMXPath($contentHtml->getDom());
            $filtered = $domxpath->query($xpathQuery);

            foreach ($filtered as $item) {
                $tags[] = $item->nodeValue;
            }
        }

        $tags = $this->fixTagsArray($tags);
        $tags = $this->filterTagsByScore($tags);

        return $tags;
    }

}