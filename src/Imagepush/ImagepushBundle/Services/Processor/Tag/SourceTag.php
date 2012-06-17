<?php

namespace Imagepush\ImagepushBundle\Services\Processor\Tag;

use Imagepush\ImagepushBundle\Document\Image;

class SourceTag extends Tag implements TagInterface
{

    /**
     * Only format tags already received from source.
     * 
     * @param Image $image
     * 
     * @return array Array of found tags
     */
    public function find(Image $image)
    {

        return $this->fixTagsArray($image->getSourceTags());
    }

}