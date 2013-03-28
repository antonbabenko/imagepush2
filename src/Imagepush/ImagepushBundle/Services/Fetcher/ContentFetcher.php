<?php

namespace Imagepush\ImagepushBundle\Services\Fetcher;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\CurlException;

/**
 * Class which get link content and format response as array
 */
class ContentFetcher
{

    protected $requestType = "GET";
    protected $userAgent = "imagepush bot v2.0";

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

        $client = new Client($uri);
        $client->setUserAgent($this->userAgent);

        // Increase curl timeout
        $client->getConfig()->set('curl.CURLOPT_TIMEOUT', 337);

        if ($this->requestType == "HEAD") {
            $request = $client->head();
        } else {
            $request = $client->get();
        }

        try {
            $response = $request->send();
        } catch (CurlException $e) {
            // @todo: catch errors and log them
            return 500;
        }

        if ($response->isSuccessful()) {
            return array(
                "Status" => $response->getStatusCode(),
                "Content" => $response->getBody(true),
                "Content-md5" => md5($response->getBody(true)),
                "Content-type" => $response->getContentType(),
                "Content-length" => $response->getContentLength(),
            );
        } else {
            return $response->getStatusCode();
        }
    }

}
