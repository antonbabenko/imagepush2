<?php

namespace Imagepush\ImagepushBundle\Services\Processor\Tag;

use Imagepush\ImagepushBundle\Document\Image;

interface TagInterface
{

    /**
     * Find tags for the Image
     *
     * @param Image $image
     *
     * @return array Array of tags with weight
     */
    public function find(Image $image);
}
