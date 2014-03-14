<?php

namespace Imagepush\ImagepushBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

class VarnishController extends Controller
{

    /**
     * Purge Varnish cache
     *
     * @Extra\Route("/purge{url}", name="purgeUrl", requirements={"url"=".*"})
     */
    public function purgeAction($url)
    {
        $ok = $this->get('imagepush.varnish')->purgeUrl($url);

        return new Response($url . ' - ' . ($ok ? "200. Purged" : "404. Not in cache"));
    }

}
