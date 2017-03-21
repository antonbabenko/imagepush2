<?php

namespace Imagepush\ImagepushBundle\Repository;

use Imagepush\ImagepushBundle\Document\Tag;

class TagRepository extends AbstractRepository
{

    /**
     * @return array|null
     */
    public function findOneByText($text)
    {

        $request = [
            'TableName' => 'tags',
            'ExpressionAttributeNames' => [
                '#text' => 'text'
            ],
            'ExpressionAttributeValues' => [
                ':text' => ['S' => strval($text)],
            ],
            'KeyConditionExpression' => '#text = :text',
            'Limit' => 1
        ];

        $results = $this->getQueryResults($request, 1);

        foreach ($results as & $result) {
            $tag = new Tag();
            $tag->fromArray($result);

            $result = $tag;
        }

        if (count($results)) {
            return $results[0];
        } else {
            return null;
        }
    }

    /**
     * @param  Tag     $tag
     * @return boolean
     */
    public function save(Tag $tag)
    {

        $request = [
            'TableName' => 'tags',
            'Item' => $tag->toItem()
        ];

        $result = $this->putItem($request);

        return $result;

    }

}
