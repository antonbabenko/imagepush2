<?php

namespace Imagepush\ImagepushBundle\Repository;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Monolog\Logger;

class AbstractRepository
{

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * AbstractRepository constructor.
     * @param DynamoDbClient $ddb
     */
    public function __construct(DynamoDbClient $ddb)
    {
        $this->ddb = $ddb;
    }

    /**
     * @param Logger $logger
     */
    public function setLogger($logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param $request array   Associative array with 'request' for scan command
     * @param $limit   integer Desired number of records
     * @param $maxPages int     Max number of pages to try to scan. Set to small number to avoid large scan.
     *
     * @return array|null
     */
    public function getScanResults(array $request, $limit, $maxPages = 3)
    {

        $count = 0;
        $page = 0;
        $results = [];

        # The Scan operation is paginated. Issue the Scan request multiple times.
        try {
            do {
                # Add the ExclusiveStartKey if we got one back in the previous response
                if (isset($response) && isset($response['LastEvaluatedKey'])) {
                    $request['ExclusiveStartKey'] = $response['LastEvaluatedKey'];
                }

                $response = $this->ddb->scan($request);

                $count += $response['Count'];

                $results = array_merge($results, $response['Items']);
            } # If there is no LastEvaluatedKey in the response, there are no more items matching this Scan
            while (isset($response['LastEvaluatedKey']) and $count < $limit and $page++ < $maxPages);
        } catch (DynamoDbException $e) {
            $this->logger->error($e->__toString());
        }

        if (count($results) > $limit) {
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }

    /**
     * @param $request  array   Associative array with 'request' for query command
     * @param $limit    integer Desired number of records
     * @param $maxPages int     Max number of pages to try to scan. Set to small number to avoid large scan.
     *
     * @return array|null
     */
    public function getQueryResults(array $request, $limit, $maxPages = 3)
    {

        $count = 0;
        $page = 0;
        $results = [];

        # The Query operation is paginated. Issue the Query request multiple times.
        try {
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
            while (isset($response['LastEvaluatedKey']) and $count < $limit and $page++ < $maxPages);
        } catch (DynamoDbException $e) {
            $this->logger->error($e->__toString());
        }

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
                    ]
                );

            } while (count($result->get('UnprocessedItems')) > 0 && $attempts++ < $maxAttempts);

            if ($attempts > $maxAttempts) {
                throw new \Exception("Could not complete batchWriteItem after " . $attempts . " attempts!");
            }
        } catch (DynamoDbException $e) {
            $this->logger->error($e->__toString());

            return [];
        } catch (\Exception $e) {
            $this->logger->error($e->__toString());

            return [];
        }

        if (isset($result['Responses'][$tableName])) {
            $results = $result['Responses'][$tableName];
        }

        return $results;
    }

    /**
     * @param $request array   Associative array with 'request' for getItem command
     *
     * @return array|null
     */
    public function getItemResult(array $request)
    {

        $result = null;

        try {
            $response = $this->ddb->getItem($request);

            if (isset($response['Item'])) {
                $result = $response['Item'];
            }
        } catch (DynamoDbException $e) {
            $this->logger->error($e->__toString());
        }

        return $result;
    }

    /**
     * @param $request array   Associative array with 'request' for putItem command
     *
     * @return bool False if there were any exceptions during putItem
     */
    public function putItem(array $request)
    {

        try {
            $this->ddb->putItem($request);
        } catch (DynamoDbException $e) {
            $this->logger->error($e->__toString());

            return false;
        }

        return true;
    }

    /**
     * @param $request array   Associative array with 'request' for deleteItem command
     *
     * @return bool False if there were any exceptions during deleteItem
     */
    public function deleteItem(array $request)
    {

        try {
            $this->ddb->deleteItem($request);
        } catch (DynamoDbException $e) {
            $this->logger->error($e->__toString());

            return false;
        }

        return true;
    }

    /**
     * @param $request array   Associative array with 'request' for putItem command
     *
     * @return bool False if there were any exceptions during putItem
     */
    public function updateItem(array $request)
    {

        try {
            $this->ddb->updateItem($request);
        } catch (DynamoDbException $e) {
            $this->logger->error($e->__toString());

            return false;
        }

        return true;
    }

}
