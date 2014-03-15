<?php

namespace Imagepush\ImagepushBundle\Services\Fetcher;

/**
 * Interface for fetching data from RSS/YQL/etc (via API) and from HTTP (via Goutte or other library)
 */
interface FetcherInterface
{

    /**
     * @return string[]
     */
    public function run();

    /**
     * @return false|null
     */
    public function checkAndSaveData();

    /**
     * @return boolean
     */
    public function isWorthToSave($item);
}
