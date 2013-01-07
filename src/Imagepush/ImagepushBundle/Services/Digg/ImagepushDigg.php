<?php

namespace Imagepush\ImagepushBundle\Services\Digg;

class ImagepushDigg extends \Services_Digg2
{

    public function __construct()
    {
        // @codingStandardsIgnoreStart
        $this->HTTPRequest2 = new \HTTP_Request2;
        $this->HTTPRequest2->setConfig(array('connect_timeout' => 20, 'timeout' => 20));
        $this->HTTPRequest2->setHeader('user-agent', 'Imagepush.to (v2.0)');
        // @codingStandardsIgnoreEnd
    }

}
