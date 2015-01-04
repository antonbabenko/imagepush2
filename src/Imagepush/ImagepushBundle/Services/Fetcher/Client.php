<?php

namespace Imagepush\ImagepushBundle\Services\Fetcher;

use Guzzle\Http\Client as GuzzleHttpClient;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\ClientErrorResponseException;

/**
 * Class which get link content
 */
class Client extends GuzzleHttpClient
{

    protected $userAgent = 'imagepush bot v2.0';

    public function __construct()
    {
        parent::__construct('', [
            'curl.options' => [
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 337,
            ],
            'ssl.certificate_authority' => false,
        ]);

        $this->setUserAgent($this->userAgent);
    }

    /**
     * Send GET request
     *
     * @return array|integer Array with data or status code
     */
//    public function getRequest($uri)
//    {
//        $this->requestType = "GET";
//
//        return $this->makeRequest($uri);
//    }

    /**
     * Send HEAD request
     *
     * @return array|integer Array with data or status code
     */
//    public function headRequest($uri)
//    {
//        $this->requestType = "HEAD";
//
//        return $this->makeRequest($uri);
//    }

    /**
     * Make HTTP request to url
     *
     * @return array|integer Array with data or status code
     */
//    protected function makeRequest($uri)
//    {
//
//        $client = new Client($uri, array(
//            'curl.options' => array(
//                CURLOPT_SSL_VERIFYHOST => false,
//                'CURLOPT_SSL_VERIFYPEER' => false,
//                CURLOPT_TIMEOUT => 337,
//                'CURLOPT_TIMEOUT' => 337
//            ),
//            'ssl.certificate_authority' => false,
//        ));
//        $client->setUserAgent($this->userAgent);
//
//        if ($this->requestType == "HEAD") {
//            $request = $client->head();
//        } else {
//            $request = $client->get();
//        }
//
//        try {
//            $response = $request->send();
//        } catch (ClientErrorResponseException $e) {
//            return 404;
//        } catch (CurlException $e) {
//            // @todo: catch errors and log them
//            return 500;
//        }
//
//        if ($response->isSuccessful()) {
//            return array(
//                "Status" => $response->getStatusCode(),
//                "Content" => $response->getBody(true),
//                "Content-md5" => md5($response->getBody(true)),
//                "Content-type" => $response->getContentType(),
//                "Content-length" => $response->getContentLength(),
//            );
//        } else {
//            return $response->getStatusCode();
//        }
//    }

}
