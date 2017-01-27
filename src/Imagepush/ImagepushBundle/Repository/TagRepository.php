<?php

namespace Imagepush\ImagepushBundle\Repository;

use Imagepush\ImagepushBundle\Document\Tag;

class TagRepository extends AbstractRepository
{

    /**
     * @return array()
     */
    public function findOneByText($text)
    {

        // query
        $request = [
            'TableName' => 'tags',
            'ExpressionAttributeNames' => [
                '#text' => 'text'
            ],
            'ExpressionAttributeValues' => [
                ':text' => ['S' => strval($text)],
            ],
            'KeyConditionExpression' => '#text = :text',
//            'IndexName' => 'text-index',
            'ScanIndexForward' => false,
            'Limit' => 1
        ];

//        \D::dump($request);

        $results = $this->getQueryResults($request, 1);

        foreach ($results as & $result) {
            $tag = new Tag();
            $tag->fromArray($result);

            $result = $tag;
//            \D::dump($tag);
        }

        if (count($results)) {
            return $results[0];
        } else {
            return null;
        }
    }

}
