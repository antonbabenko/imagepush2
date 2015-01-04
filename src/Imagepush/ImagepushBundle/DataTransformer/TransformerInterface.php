<?php

namespace Imagepush\ImagepushBundle\DataTransformer;

use Imagepush\ImagepushBundle\Entity\Image;

interface TransformerInterface
{

    /**
     * @param $item
     *
     * @return Image
     */
    public function transformItemIntoObject($item);

}
