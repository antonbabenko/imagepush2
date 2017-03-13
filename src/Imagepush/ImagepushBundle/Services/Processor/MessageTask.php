<?php

namespace Imagepush\ImagepushBundle\Services\Processor;

/**
 * Constants for message tasks and associated services/consumers
 */
class MessageTask
{

    const FIND_TAGS_AND_MENTIONS = "find_tags_and_mentions";

    /**
     * Array of associated services, where task will be published
     */
    public static $services = [
        self::FIND_TAGS_AND_MENTIONS => ["twitter", "delicious", "reddit", "stumble_upon", "source"],
    ];

}
