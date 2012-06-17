<?php

namespace Imagepush\ImagepushBundle\Services\Processor\Tag;

use Imagepush\ImagepushBundle\Document\Image;

interface TagInterface
{

    /**
     * Find tags for the Image
     * 
     * @param Imagepush\ImagepushBundle\Document\Image $image
     * 
     * @return array Array of tags with weight
     */
    function find(Image $image);
}