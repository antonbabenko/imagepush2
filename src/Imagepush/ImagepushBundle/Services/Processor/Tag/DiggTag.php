<?php

namespace Imagepush\ImagepushBundle\Services\Processor\Tag;

use Imagepush\ImagepushBundle\Document\Image;
use Imagepush\ImagepushBundle\Services\Fetcher\Digg\ImagepushDigg;

class DiggTag extends Tag implements TagInterface
{

    /**
     * Digg topics/tags (it is useless when data is fetched from digg only, because tags are already saved)
     * 
     * @param Image $image
     * 
     * @link http://developers.digg.com/version2/story-getinfo
     * 
     * @return array|false Array of found tags; Empty array if no tags found; False - if error or not indexed
     */
    public function find(Image $image)
    {

        $digg = new ImagepushDigg();
        $digg->setVersion('2.0');

        $result = $digg->story->getInfo(array('links' => urlencode($image->getLink())));

        //\D::debug($result);

        $tags = array();

        if (empty($result) || !$result->count) {
            return array();
        }

        foreach ($result->stories as $story) {
            if ($story->topic->name != "*") {
                $tags[] = $story->topic->name;
            }
        }

        $tags = $this->fixTagsArray($tags);

        return $tags;
    }

}