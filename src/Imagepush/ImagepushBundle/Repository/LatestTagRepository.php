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

        $slot = 0;
        $maxSlots = 12;
        $results = [];

        do {
            $timeslot = intdiv(time() - $slot * 3600, 3600);

            $request = [
                'TableName' => 'latest_tags',
                'IndexName' => 'timeslot-index',
                'ExpressionAttributeNames' => [
                    '#text' => 'text',
                ],
                'ExpressionAttributeValues' => [
                    ':timeslot' => ['N' => strval($timeslot)],
                ],
                'ProjectionExpression' => '#text',
                'KeyConditionExpression' => 'timeslot = :timeslot',
                'Limit' => $max
            ];

            $tmpResults = $this->getQueryResults($request, $max);

            foreach ($tmpResults as & $result) {
                $result = strval(array_values($result['text'])[0]);
            }

            $results = array_merge($results, $tmpResults);

        } while (++$slot < $maxSlots);

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
