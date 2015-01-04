<?php

namespace Imagepush\ImagepushBundle\DataTransformer;

use Imagepush\ImagepushBundle\Entity\Image;
use Imagepush\ImagepushBundle\External\CustomStrings;

class RedditTransformer extends AbstractTransformer implements TransformerInterface
{

    /**
     * @param $item
     *
     * @return Image
     */
    public function transformItemIntoObject($item)
    {

        $image = new Image();
        $image->setSourceType('reddit');
        $image->setLink($item->data->url);
        $image->setCreatedAt(new \DateTime('@' . (int) $item->data->created));
        $image->setTitle(CustomStrings::cleanTitle($item->data->title));

        if (!empty($item->data->subreddit)) {
            $tag = CustomStrings::cleanTag($item->data->subreddit);
            $image->setSourceTags((array) $tag);
        }

        return $image;
    }

}
