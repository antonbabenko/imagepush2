<?php

namespace Imagepush\ImagepushBundle\Imagine;

class ImagepushAmazonS3 extends \AmazonS3
{

    public function __construct()
    {
        parent::__construct();

        // Set region to be able to use CNAME
        $this->set_region(\AmazonS3::REGION_EU_W1);

        $this->path_style = true;

        $this->ssl_verification = false;
    }

}
