<?php

namespace Imagepush\ImagepushBundle\Enum;

/**
 * Class LinkStatusEnum
 */
class LinkStatusEnum extends AbstractEnum
{

    const INDEXED = 'indexed'; // Link has been successfully indexed
    const FAILED = 'failed';   // Link has been processed, but no suitable content found
    const BLOCKED = 'blocked'; // Link has bad content (no need to process it further)

    protected static $_list = array(
        self::INDEXED => 1,
        self::FAILED => 2,
        self::BLOCKED => 3,
    );
}
