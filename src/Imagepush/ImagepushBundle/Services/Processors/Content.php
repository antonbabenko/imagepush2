<?php

namespace Imagepush\ImagepushBundle\Services\Processors;

use Symfony\Component\BrowserKit\Client;

class Content
{
  
  /*
   * @services
   */
  public $kernel;
  
  public function __construct(\AppKernel $kernel) {
    
    $this->kernel = $kernel;
    
  }
  
  /*
   * @return array|integer Array with data or status code
   */
  public function get($uri)
  {
    
    $client = $this->kernel->getContainer()->get('goutte')
            ->getNamedClient('curl');

    $crawler = $client->request('GET', $uri);

    $response = $client->getResponse();
    
    if (200 == $response->getStatus()) {
      return array(
        "Response" => $response,
        "Status" => $response->getStatus(),
        "Content-md5" => md5($response->getContent()),
        "Content" => $response->getContent(),
        "Content-type" => $response->getHeader("Content-type"),
        "Content-length" => $response->getHeader("Content-length"),
      );
    } else {
      return $response->getStatus();
    }
    //echo '<img src="data:image/jpg;base64,'.base64_encode($content).'">';
  }
  
  
}