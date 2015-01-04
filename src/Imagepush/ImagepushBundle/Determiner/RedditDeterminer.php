<?php

namespace Imagepush\ImagepushBundle\Determiner;

use Imagepush\ImagepushBundle\External\CustomStrings;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class RedditDeterminer extends AbstractDeterminer implements DeterminerInterface, ContainerAwareInterface
{

    public function __construct($minScore = 100)
    {
        $this->minScore = $minScore;
    }

    /**
     * @param $item
     *
     * @return boolean
     */
    public function isWorthToSave($item)
    {

        if (CustomStrings::isForbiddenTitle($item->data->title)) {
            return false;
        }

        // @codingStandardsIgnoreStart
        $worthToSave = (
            $item->data->score >= $this->minScore &&
            $item->data->over_18 != true &&
            $item->data->is_self != true &&
            false == $this->getLinkRepository()->hasBeenSeen($item->data->url) &&
            false == $this->getImageRepository()->findOneBy(array("link" => $item->data->url))
        );
        // @codingStandardsIgnoreEnd

        if ($worthToSave) {
            $this->getLogger()->info(
                sprintf("Yes. Score: %s. Link: %s. Title: %s", $item->data->score, $item->data->url, $item->data->title)
            );

            return true;
        } else {
            $this->getLogger()->info(
                sprintf("NO. Score: %s. Link: %s. Title: %s", $item->data->score, $item->data->url, $item->data->title)
            );

            return false;
        }

    }
}
