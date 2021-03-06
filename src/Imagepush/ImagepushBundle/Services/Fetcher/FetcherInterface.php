<?php

namespace Imagepush\ImagepushBundle\Services\Fetcher;

/**
 * Interface for fetching data from RSS/YQL/etc (via API) and from HTTP (via Goutte or other library)
 */
interface FetcherInterface
{

    public function run();

    public function checkAndSaveData();

    public function isWorthToSave($item);
}
