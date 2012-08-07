<?php

namespace Imagepush\ImagepushBundle\Consumer;

/**
 * Constants for message tasks and associated consumers
 */
class MessageTask
{

    const FIND_TAGS_AND_MENTIONS = "find_tags_and_mentions";

    /**
     * Array of associated producers, where task will be published
     */
    public static $producers = array(
        self::FIND_TAGS_AND_MENTIONS => array("twitter", "delicious", "reddit"),
    );

}