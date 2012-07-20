<?php

namespace Imagepush\ImagepushBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

class VarnishController extends Controller
{

    /**
     * Purge Varnish cache
     * 
     * @Route("/purge{url}", name="purgeUrl", requirements={"url"=".*"})
     */
    public function purgeAction($url)
    {
        $ok = $this->get('imagepush.varnish')->purgeUrl($url);

        return new Response($url . ' - ' . ($ok ? "200. Purged" : "404. Not in cache"));
    }

}
