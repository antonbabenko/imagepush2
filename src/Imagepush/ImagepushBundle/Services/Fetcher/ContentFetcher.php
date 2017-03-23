<?php

namespace Imagepush\ImagepushBundle\Services\Fetcher;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Monolog\Logger;

/**
 * Class which get link content and format response as array
 */
class ContentFetcher
{

    protected $requestType = "GET";
    protected $userAgent = "imagepush bot v2.0";

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Logger $logger
     */
    public function setLogger($logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Set user agent
     */
    public function setUserAgent($userAgent = true, $extra = "")
    {
        if ($userAgent !== true) {
            $this->userAgent = $userAgent;
        }

        if ($extra !== "") {
            $this->userAgent .= " " . $extra;
        }
    }

    /**
     * Send GET request
     *
     * @return array|integer Array with data or status code
     */
    public function getRequest($uri)
    {
        $this->requestType = "GET";

        return $this->makeRequest($uri);
    }

    /**
     * Send HEAD request
     *
     * @return array|integer Array with data or status code
     */
    public function headRequest($uri)
    {
        $this->requestType = "HEAD";

        return $this->makeRequest($uri);
    }

    /**
     * Make HTTP request to url
     *
     * @return array|integer Array with data or status code
     */
    protected function makeRequest($uri)
    {

        $client = new Client([
            'headers' => [
                'User-Agent' => $this->userAgent
            ],
            'verify' => false,
            'curl.options' => array(
                CURLOPT_SSL_VERIFYHOST => false,
                'CURLOPT_SSL_VERIFYPEER' => false,
                CURLOPT_TIMEOUT => 337,
                'CURLOPT_TIMEOUT' => 337
            ),
            'ssl.certificate_authority' => false,
        ]);

        try {

            if ($this->requestType == "HEAD") {
                $this->logger->info('HEAD: ' . $uri);
                $response = $client->head($uri);
            } else {
                $this->logger->info('GET: ' . $uri);
                $response = $client->get($uri);
            }

        } catch (TransferException $e) {
            $this->logger->error($e->getMessage());

            return 404;
        } catch (\Exception $e) {
            $this->logger->critical('Unknown exception: '.$e->getMessage());

            return 500;
        }

        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $content = $response->getBody()->getContents();
            $contentType = $response->getHeader('content-type');

            if (is_array($contentType)) {
                $contentType = $contentType[0];
            }

            if (false !== strpos($contentType, ';')) {
                $contentType = strstr($contentType, ';', true);
            }

            $result = [
                "Status" => $response->getStatusCode(),
                "Content" => "Content omitted",
                "Content-md5" => md5($content),
                "Content-type" => trim($contentType),
                "Content-length" => strlen($content),
            ];

            $this->logger->info('Result='.json_encode($result));

            return ['Content' => $content] + $result;
        } else {
            return $response->getStatusCode();
        }
    }

}
