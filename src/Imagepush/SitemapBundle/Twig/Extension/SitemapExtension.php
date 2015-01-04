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
            'sitemap_url_absolute' => new \Twig_Filter_Method($this, 'absoluteUrl'),
            'sitemap_date' => new \Twig_Filter_Method($this, 'formatDate'),
        );
    }

    public function absoluteUrl($path)
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    public function formatDate($date)
    {
        return $date->format('Y-m-d');
    }

    public function getName()
    {
        return 'sitemap';
    }

}
