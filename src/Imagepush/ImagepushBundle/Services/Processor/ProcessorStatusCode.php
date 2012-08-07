<?php

namespace Imagepush\ImagepushBundle\Services\Processor;

/**
 * Constants for Processor status codes to return and for WebServiceConsumer to check
 */
class ProcessorStatusCode
{

    /**
     * OK. Processed.
     */
    const OK = 0;

    /**
     * Retry the same request in service specific interval (normal).
     */
    const RETRY_REQUEST_AGAIN = 1;

    /**
     * Don't retry the same request. It was an error. Skip it.
     */
    const WRONG_REQUEST = 2;

    /**
     * Don't retry this service at all now. Service is down. Retry when service is back.
     */
    const SERVICE_IS_DOWN = 3;

}