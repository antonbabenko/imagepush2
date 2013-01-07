<?php

namespace Imagepush\ImagepushBundle\Document;

use Doctrine\ODM\MongoDB\DocumentRepository;

class LatestTagRepository extends DocumentRepository
{

    public function getLatestTrends($max)
    {
        $latestTrends = apc_fetch('latest_trends_' . $max, $inCache);

        if (false !== $inCache) {
            return unserialize($latestTrends);
        }

        $tmpTags = $this->createQueryBuilder()
            ->sort('timestamp', 'DESC')
            ->limit($max * 20)
            ->getQuery()
            ->execute();

        if (!count($tmpTags)) {
            return array();
        }

        foreach ($tmpTags as $tmpTag) {
            $tag = $tmpTag->getText();
            $tags[$tag] = (empty($tags[$tag]) ? 1 : $tags[$tag] + 1);
        }

        arsort($tags);

        $tags = array_slice($tags, 0, $max, true);

        apc_store('latest_trends_' . $max, serialize($tags), 1800);

        return $tags;
    }

}
