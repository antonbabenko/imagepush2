<?php

namespace Imagepush\ImagepushBundle\Services\Processor;

/**
 * Constants for Processor status codes
 */
class ProcessorStatusCode
{

    /**
     * OK. Processed.
     */
    const OK = 0;

    /**
     * Don't retry the same request. It was an error. Skip it.
     */
    const WRONG_REQUEST = 2;

    /**
     * Don't retry this service at all now. Service is down. Retry when service is back.
     */
    const SERVICE_IS_DOWN = 3;

    /**
     * Result codes
     */
    const OK_CODE = 'OK';
    const NO_ITEMS_CODE = 'NO_ITEMS';

}
