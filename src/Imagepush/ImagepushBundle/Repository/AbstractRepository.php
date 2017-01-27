<?php

namespace Imagepush\ImagepushBundle\Repository;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

class AbstractRepository
{

    /**
     * AbstractRepository constructor.
     * @param DynamoDbClient $ddb
     */
    public function __construct(DynamoDbClient $ddb)
    {
        $this->ddb = $ddb;
    }

    /**
     * @param $request array   Associative array with 'request' for scan command
     * @param $limit   integer Desired number of records
     *
     * @return array|null
     */
    public function getScanResults(array $request, $limit)
    {

        $count = 0;
        $results = [];

        # The Scan operation is paginated. Issue the Scan request multiple times.
        do {
            # Add the ExclusiveStartKey if we got one back in the previous response
            if (isset($response) && isset($response['LastEvaluatedKey'])) {
                $request['ExclusiveStartKey'] = $response['LastEvaluatedKey'];
            }

            $response = $this->ddb->scan($request);

            $count += $response['Count'];

            $results = array_merge($results, $response['Items']);
        } # If there is no LastEvaluatedKey in the response, there are no more items matching this Scan
        while (isset($response['LastEvaluatedKey']) and $count < $limit);

        if (count($results) > $limit) {
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }

    /**
     * @param $request array   Associative array with 'request' for query command
     * @param $limit   integer Desired number of records
     *
     * @return array|null
     */
    public function getQueryResults(array $request, $limit)
    {

        $count = 0;
        $results = [];

        # The Query operation is paginated. Issue the Query request multiple times.
        do {
            # Add the ExclusiveStartKey if we got one back in the previous response
            if (isset($response) && isset($response['LastEvaluatedKey'])) {
                $request['ExclusiveStartKey'] = $response['LastEvaluatedKey'];
            }

            $response = $this->ddb->query($request);

//            \D::dump($response);

            $count += $response['Count'];

            $results = array_merge($results, $response['Items']);
        } # If there is no LastEvaluatedKey in the response, there are no more items matching this Scan
        while (isset($response['LastEvaluatedKey']) and $count < $limit);

        if (count($results) > $limit) {
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }

    /**
     * @param $tableName
     * @param $keys array   Associative array with Keys for batchGetItem command
     *
     * @return array|null
     */
    public function batchGetItemResults($tableName, array $keys)
    {

        $results = [];

        $delay = 0;
        $attempts = 0;
        $maxAttempts = 10;

        // Max 100 keys at one time
        if (count($keys) > 100) {
            $keys = array_slice($keys, 0, 100);
        }

        try {
            do {
                if (isset($result) && count($result->get('UnprocessedItems'))) {
                    $requestItems = $result->get('UnprocessedItems');
                    $delay = $delay * 2 + 1;
                } else {
                    $requestItems[$tableName] = [
                        'Keys' => $keys
                    ];
                }

                sleep($delay);

                $result = $this->ddb->batchGetItem(
                    [
                        'RequestItems' => $requestItems,
                        'ReturnConsumedCapacity' => 'TOTAL',
                    ]
                );

            } while (count($result->get('UnprocessedItems')) > 0 && $attempts++ < $maxAttempts);

            if ($attempts > $maxAttempts) {
                throw new \Exception("Could not complete batchWriteItem after " . $attempts . " attempts!");
            }
        } catch (DynamoDbException $e) {
//            \D::dump($e->getMessage());
        } catch (\Exception $e) {
            return [];
        }

        if (isset($result['Responses'][$tableName])) {
            $results = $result['Responses'][$tableName];
        }

        return $results;
    }

    /**
     * @param $request array   Associative array with 'request' for getItem command
     * @param $tableName
     *
     * @return array|null
     */
    public function getItemResult(array $request)
    {

        $result = null;

        $response = $this->ddb->getItem($request);

        if (isset($response['Item'])) {
            $result = $response['Item'];
        }

        return $result;
    }

}
