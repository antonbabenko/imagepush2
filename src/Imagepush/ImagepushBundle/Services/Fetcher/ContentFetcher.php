<?php

namespace Imagepush\ImagepushBundle\Services\Fetcher;

use Goutte\Client;

/**
 * Class which get link content and format response as array
 */
class ContentFetcher
{

    protected $requestType = "GET";

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

        $client = new Client();

        // Increase curl timeout
        $guzzleClient = $client->getClient();
        $guzzleClient->getConfig()->set('curl.CURLOPT_TIMEOUT', 337);

        $client->setClient($guzzleClient);

        //\D::debug($client->getClient()->getConfig()->getAll());

        $client->request($this->requestType, $uri);

        try {
            $response = $client->getResponse();
            //\D::dump($response->getContent());
        } catch (\Guzzle\Http\Exception\CurlException $e) {
            // @todo: catch errors and log them
            return 500;
        }

        if (200 == $response->getStatus()) {
            return array(
                "Response" => $response,
                "Status" => $response->getStatus(),
                "Content" => $response->getContent(),
                "Content-md5" => md5($response->getContent()),
                "Content-type" => $response->getHeader("Content-type"),
                "Content-length" => $response->getHeader("Content-length"),
            );
        } else {
            return $response->getStatus();
        }
    }

}