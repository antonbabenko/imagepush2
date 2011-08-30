<?php

namespace Imagepush\ImagepushBundle\Services\Processors;

use Goutte\Client;

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
    
    $client = new Client();
    
    $crawler = $client->request('GET', $uri);

    $response = $client->getResponse();
    //\D::dump($response->getContent());
    
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
  
  
  public function getFullImageSrc($dom)
  {

    \D::dump($dom);
    $dom_link = $dom->documentElement->getElementsByTagName("link");
    if ($dom_link_count = $dom_link->length)
    {
      for ($i = 0; $i < $dom_link_count; $i++) {
        if ($dom_link->item($i)->getAttribute("rel") == "image_src")
        {
          $image_src = $dom_link->item($i)->getAttribute("href");
          $full_image_src = dirname($this->link) . "/" . $image_src;
          break;
        }
      }
    }

    return (!empty($full_image_src) ? $full_image_src : false);
  }
}