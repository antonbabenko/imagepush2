<?php

namespace Imagepush\ImagepushBundle\Tests\Model;

class AbstractSourceTest
{

  public function getAndInitUnprocessed() {
    
    $result = array(
     'id' => "24831",
     'title' => "Break Free!",
     'slug' => "break-free",
     'link' => "http://www.heavingdeadcats.com/wp-content/uploads/2011/06/255015_206281919415938_100001023958913_641844_51587_n.jpg",
     'timestamp' => "1314507881",
    );
    
    return $result;
  }

}