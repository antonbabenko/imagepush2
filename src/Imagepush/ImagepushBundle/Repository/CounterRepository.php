<?php

namespace Imagepush\ImagepushBundle\Repository;

class CounterRepository extends AbstractRepository
{

    /**
     * @param $key
     *
     * @return integer
     */
    public function getValue($key)
    {

        $request = [
            'TableName' => 'counter',
            'Key' => [
                'key' => [
                    'S' => strval($key)
                ]
            ],
            'ConsistentRead' => true,
        ];

        $result = $this->getItemResult($request);

        if ($result == null) {
            return null;
        }

        if (isset($result['value'])) {
            $result = array_values($result['value'])[0];
        }

        return $result;

    }

    /**
     * @param $key
     *
     * @return integer
     */
    public function updateValue($key, $value)
    {

        $request = [
            'TableName' => 'counter',
            'Key' => [
                'key' => ['S' => strval($key)]
            ],
            'ConsistentRead' => true,
            'ConditionExpression' => '#v < :v',
            'ExpressionAttributeNames' => [
                '#v' => 'value'
            ],
            'ExpressionAttributeValues' => [
                ':v' => ['N' => strval($value)],
            ],
            'UpdateExpression' => 'SET #v = :v',
        ];

        $result = $this->updateItem($request);

        return $result;

    }

}
