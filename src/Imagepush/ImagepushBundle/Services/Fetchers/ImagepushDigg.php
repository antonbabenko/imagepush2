<?php

namespace Imagepush\ImagepushBundle\Services\Fetchers;

require_once dirname(__FILE__).'/../../../../../vendor/digg/Services/Digg2.php';

class ImagepushDigg extends \Services_Digg2
{

  public function __construct()
  {
    $this->HTTPRequest2 = new \HTTP_Request2;
    $this->HTTPRequest2->setConfig(array('connect_timeout' => 20, 'timeout' => 20));
  }

}

