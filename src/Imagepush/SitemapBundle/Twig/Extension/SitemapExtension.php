<?php

namespace Imagepush\SitemapBundle\Twig\Extension;

class SitemapExtension extends \Twig_Extension
{

    private $baseUrl;

    public function __construct($baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('sitemap_url_absolute', [$this, 'absoluteUrl']),
            new \Twig_SimpleFilter('sitemap_date', [$this, 'formatDate']),
        );
    }

    public function absoluteUrl($path)
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    public function formatDate($date)
    {
        $date = new \DateTime("@" . $date);

        return $date->format('Y-m-d');
    }

    public function getName()
    {
        return 'sitemap';
    }

}
