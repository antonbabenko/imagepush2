<?php

namespace Imagepush\ImagepushBundle\Determiner;

interface DeterminerInterface
{
    /**
     * Check if item is good enough to be saved (Score and unique link hash)
     *
     * @param $item
     *
     * @return boolean
     */
    public function isWorthToSave($item);
}
