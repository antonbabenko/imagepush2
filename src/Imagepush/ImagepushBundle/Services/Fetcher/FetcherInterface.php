<?php

namespace Imagepush\ImagepushBundle\Services\Fetcher;

/**
 * Interface for fetching data from Digg/RSS/YQL/etc (via API) and from HTTP (via Goutte or other library) 
 */
interface FetcherInterface
{

    public function run();

    function checkAndSaveData();

    function isWorthToSave($item);
}