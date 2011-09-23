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
   * @return array|integer Array with data or status code
   */
  public function get($uri)
  {
    $this->requestType = "GET";
    return $this->makeRequest($uri);
  }
  
  /**
   * Send HEAD request
   * @return array|integer Array with data or status code
   */
  public function head($uri)
  {
    $this->requestType = "HEAD";
    return $this->makeRequest($uri);
  }
  
  /**
   * Make HTTP request to url
   * @return array|integer Array with data or status code
   */
  protected function makeRequest($uri) {

    $client = new Client();
    
    $crawler = $client->request('GET', $uri);

    $response = $client->getResponse();
    //\D::dump($response->getContent());
    
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