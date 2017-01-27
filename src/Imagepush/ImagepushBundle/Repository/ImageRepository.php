<?php

namespace Imagepush\ImagepushBundle\Repository;

use Imagepush\ImagepushBundle\Document\Image;

class ImageRepository extends AbstractRepository
{

    /**
     * @param $type
     * @param  int             $limit
     * @return array
     * @throws \ErrorException
     */
    public function findCurrentImages($limit = 20)
    {

        $request = [
            'TableName' => 'images',
            'IndexName' => 'isAvailable-timestamp-index',
            'ExpressionAttributeValues' => [
                ':isAvailable' => ['N' => '1'],
            ],
            'KeyConditionExpression' => 'isAvailable = :isAvailable',
            'ScanIndexForward' => false,
            'Limit' => $limit
        ];

        $results = $this->getQueryResults($request, $limit);

        foreach ($results as & $result) {
            $image = new Image();
            $image->fromArray($result);

            $result = $image;
        }

        return $results;
    }

    /**
     * @return array()
     */
    public function findImagesIdByTag($tag, $limit = 10)
    {

        // Tag should be only single (arrays are not supported here)
        $request = [
            'TableName' => 'images_tags',
            'IndexName' => 'tag-id-index',
            'ExpressionAttributeValues' => [
                ':tag' => ['S' => strval($tag)],
            ],
            'KeyConditionExpression' => 'tag = :tag',
            'ScanIndexForward' => false,
            'ProjectionExpression' => 'id',
            'Limit' => $limit
        ];

        $results = $this->getQueryResults($request, $limit);

        foreach ($results as & $result) {
            $result = intval(array_values($result['id'])[0]);
        }

        return $results;

    }

    /**
     * @param $id
     * @param  bool  $onlyAvailable
     * @return array
     */
    public function findOneByOldAndSlow($id, $onlyAvailable = true)
    {

        $request = [
            'TableName' => 'images',
            'ExpressionAttributeValues' => [
                ':id' => ['N' => strval((int) $id)],
                ':isAvailable' => ['N' => strval(max(0, (int) $onlyAvailable))],
                # Allow previews. 0 = any. 1 = only available
            ],
            'KeyConditionExpression' => 'id = :id',
            'FilterExpression' => 'isAvailable >= :isAvailable',
            'Limit' => 1
        ];

        $t1 = microtime(true);

        $results = $this->getQueryResults($request, 1);

        foreach ($results as & $result) {
            $image = new Image();
            $image->fromArray($result);

            $result = $image;
        }
        $t2 = microtime(true);

        echo (float) ($t2 - $t1);

        if (count($results)) {
            return $results[0];
        } else {
            return null;
        }

    }

    /**
     * @param $id
     * @param  bool  $onlyAvailable
     * @return array
     */
    public function findOneBy($id, $onlyAvailable = true)
    {

        $request = [
            'TableName' => 'images',
            'Key' => [
                'id' => [
                    'N' => strval((int) $id)
                ]
            ],
        ];

        $result = $this->getItemResult($request);

        if ($result == null) {
            return null;
        }

        # Allow previews. 0 = any. 1 = only available
        if ($onlyAvailable && array_values($result['isAvailable'])[0] != (int) $onlyAvailable) {
            return null;
        }

        $image = new Image();
        $image->fromArray($result);

        return $image;
    }

    /**
     * @param  array $ids
     * @param  bool  $onlyAvailable
     * @param  bool  $sortByTimestamp
     * @return array
     */
    public function findManyByIds($ids, $onlyAvailable = true, $sortByTimestamp = true)
    {

        $keys = [];
        $tableName = 'images';

        if (!count($ids)) {
            return [];
        }

        foreach ($ids as $id) {
            $keys[] = [
                'id' => [
                    'N' => strval($id),
                ],
            ];
        }

        $tmpResults = $this->batchGetItemResults($tableName, $keys);

        $results = [];
        $maxLength = strlen(max($ids));

        foreach ($tmpResults as $result) {

            if (array_values($result['isAvailable'])[0] != $onlyAvailable) {
                continue;
            }

            $image = new Image();
            $image->fromArray($result);

            if ($sortByTimestamp) {
                # Make unique sort key, if timestamp is the same to avoid overwrite
                $sortValue = $image->getTimestamp() . str_pad(
                        $image->getId(),
                        $maxLength,
                        "0",
                        STR_PAD_LEFT
                    );
            } else {
                $sortValue = $image->getId();
            }
            $results[$sortValue] = $image;
        }

        krsort($results);

        return $results;
    }

    /**
     * @return array()|false
     */
    public function getOneImageRelatedToTimestamp($direction, $timestamp)
    {

        if (!$timestamp || !in_array($direction, array("next", "prev"))) {
            return false;
        }

        $request = [
            'TableName' => 'images',
            'IndexName' => 'isAvailable-timestamp-index',
            'ExpressionAttributeNames' => [
                '#t' => 'timestamp'
            ],
            'ExpressionAttributeValues' => [
                ':isAvailable' => ['N' => '1'],
                ':t' => ['N' => strval($timestamp)],
            ],
            'Limit' => 1
        ];

        if ($direction == "next") {
            $request['KeyConditionExpression'] = 'isAvailable = :isAvailable AND #t > :t';
            $request['ScanIndexForward'] = true;
        } else {
            $request['KeyConditionExpression'] = 'isAvailable = :isAvailable AND #t < :t';
            $request['ScanIndexForward'] = false;
        }

        $results = $this->getQueryResults($request, 1);

        foreach ($results as & $result) {
            $image = new Image();
            $image->fromArray($result);

            $result = $image;
        }

        if (count($results)) {
            return $results[0];
        } else {
            return null;
        }
    }

    /**
     * @return int|false
     */
    public function getNextId()
    {
        $maxId = $this->createQueryBuilder()
            ->sort('id', 'DESC')
            ->limit(1)
            ->getQuery()
            ->getSingleResult();

        if ($maxId && $maxId->getId()) {
            return $maxId->getId() + 1;
        } else {
            return false;
        }
    }

    /**
     * Get oldest unprocessed image and change status for it to "in process"
     *
     * @return Image|false
     */
    public function initUnprocessedSource()
    {
        $image = $this->createQueryBuilder()
            ->field('isAvailable')->exists(false)
            ->field('isInProcess')->notEqual(true)
            ->sort('timestamp', 'ASC')
            ->limit(1)
            ->requireIndexes(false)
            ->getQuery()
            ->getSingleResult();

        if ($image) {
            $image->setIsInProcess(true);

            $this->dm->persist($image);
            $this->dm->flush();
            $this->dm->refresh($image);

            return $image;
        } else {
            return false;
        }
    }

}
