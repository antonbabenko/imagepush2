<?php

namespace Imagepush\ImagepushBundle\Repository;

use Imagepush\ImagepushBundle\Document\LatestTag;

class LatestTagRepository extends AbstractRepository
{

    /**
     * @return array()
     */
    public function getLatestTrends($max)
    {
        $latestTrends = apc_fetch('latest_trends_' . $max, $inCache);

        if (false !== $inCache) {
            return unserialize($latestTrends);
        }

        // scan
        $request = [
            'TableName' => 'latest_tags',
            'ExpressionAttributeNames' => [
                '#t' => 'timestamp',
                '#text' => 'text',
            ],
            'ExpressionAttributeValues' => [
                ':t' => ['N' => strval(time()-12*3600)],
            ],
            'FilterExpression' => '#t > :t',
            'ProjectionExpression' => '#text',
            'Limit' => $max*10
        ];

        $results = $this->getScanResults($request, $max*10, 50);

        foreach ($results as & $result) {
            $result = strval(array_values($result['text'])[0]);
        }
        $results = array_count_values($results);
        arsort($results);
        $results = array_keys(array_slice($results, 0, $max, true));

        apc_store('latest_trends_' . $max, serialize($results), 1800);

        return $results;
    }

    /**
     * @param  LatestTag $tag
     * @return bool
     */
    public function save(LatestTag $tag)
    {

        $request = [
            'TableName' => 'latest_tags',
            'Item' => $tag->toItem()
        ];

        $result = $this->putItem($request);

        return $result;

    }

}
