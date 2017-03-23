<?php

namespace Imagepush\ImagepushBundle\Document;

/**
 * Processed image hashes (in order to not index same images more then once)
 */
class ProcessedHash
{

    /**
     * @var string
     */
    protected $hash;

    /**
     * ProcessedHash constructor.
     *
     * @param string $hash
     */
    public function __construct($hash)
    {
        $this->hash = $hash;
    }

    /**
     * Set hash
     *
     * @param string
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * Get hash
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @return array
     */
    public function toItem()
    {

        $item = [
            'hash' => [
                'S' => strval($this->getHash())
            ]
        ];

        return $item;

    }

}
