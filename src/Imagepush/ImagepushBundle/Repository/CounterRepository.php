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
     * Update value without conditional check
     *
     * @param $key   string
     * @param $value string
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
            'ExpressionAttributeNames' => [
                '#v' => 'value'
            ],
            'ExpressionAttributeValues' => [
                ':v' => ['S' => strval($value)],
            ],
            'UpdateExpression' => 'SET #v = :v',
        ];

        $result = $this->updateItem($request);

        return $result;

    }

//    /**
//     * Update value by appending to the list (with consistent read)
//     *
//     * @param $key   string
//     * @param $value string
//     *
//     * @return integer
//     */
//    public function updateListAppendValue($key, $value)
//    {
//
//        $request = [
//            'TableName' => 'counter',
//            'Key' => [
//                'key' => ['S' => strval($key)]
//            ],
//            'ConsistentRead' => true,
//            'ExpressionAttributeNames' => [
//                '#v' => 'value'
//            ],
//            'ExpressionAttributeValues' => [
//                ':v' => ['SS' => [strval($value)]],
//            ],
//            'UpdateExpression' => 'ADD #v :v',
//        ];
//
//        $result = $this->updateItem($request);
//
//        return $result;
//
//    }

    /**
     * Update value with conditional check (only if new value is larger)
     *
     * @param $key   string
     * @param $value integer
     *
     * @return integer
     */
    public function updateToLargerValue($key, $value)
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
