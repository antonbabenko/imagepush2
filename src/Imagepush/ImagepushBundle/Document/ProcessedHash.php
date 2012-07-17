<?php

namespace Imagepush\ImagepushBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Processed image hashes (in order to not index same images more then once)
 * 
 * @MongoDB\Document(collection="processedHash", repositoryClass="Imagepush\ImagepushBundle\Document\ProcessedHashRepository")
 * @MongoDB\Indexes({
 *   @MongoDB\UniqueIndex(keys={"hash"="asc"}, safe=true)
 * })
 */
class ProcessedHash
{

    /**
     * @MongoDB\Id(strategy="AUTO")
     */
    protected $mongoId;

    /**
     * @MongoDB\String
     */
    protected $hash;

    /**
     * Get mongoId
     *
     * @return id $mongoId
     */
    public function __construct($hash = "")
    {
        $this->hash = $hash;
    }

    /**
     * Get mongoId
     *
     * @return id $mongoId
     */
    public function getMongoId()
    {
        return $this->mongoId;
    }

    /**
     * Set hash
     *
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * Get hash
     *
     * @return string $hash
     */
    public function getHash()
    {
        return $this->hash;
    }

}
