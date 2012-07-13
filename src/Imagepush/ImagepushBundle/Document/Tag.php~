<?php

namespace Imagepush\ImagepushBundle\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Tag
 * 
 * @MongoDB\Document(collection="tags", requireIndexes=true, repositoryClass="Imagepush\ImagepushBundle\Document\TagRepository")
 * @MongoDB\Indexes({
 *   @MongoDB\Index(keys={"text"="asc"}),
 *   @MongoDB\Index(keys={"usedInAvailable"="asc"}),
 *   @MongoDB\Index(keys={"usedInUpcoming"="asc"}),
 *   @MongoDB\UniqueIndex(keys={"text"="asc"}, dropDups=true)
 * })
 */
class Tag
{

    /**
     * @MongoDB\Id(strategy="AUTO")
     */
    protected $mongoId;

    /**
     * @MongoDB\String
     */
    protected $text;

    /**
     * Don't rely on it, because it is used only to import from redis db.
     * Remove this field after import.
     * 
     * @MongoDB\String
     */
    protected $legacyKey;

    /**
     * @MongoDB\Increment
     */
    protected $usedInAvailable;

    /**
     * @MongoDB\Increment
     */
    protected $usedInUpcoming;

    /**
     * @MongoDB\Collection
     * @MongoDB\ReferenceMany(targetDocument="Image")
     */
    protected $imagesRef;

    public function __construct()
    {
        $this->imagesRef = new ArrayCollection();
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
     * Set text
     *
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * Get text
     *
     * @return string $text
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set legacyKey
     *
     * @param string $legacyKey
     */
    public function setLegacyKey($legacyKey)
    {
        $this->legacyKey = $legacyKey;
    }

    /**
     * Get legacyKey
     *
     * @return string $legacyKey
     */
    public function getLegacyKey()
    {
        return $this->legacyKey;
    }

    /**
     * Set usedInAvailable
     *
     * @param increment $usedInAvailable
     */
    public function setUsedInAvailable($usedInAvailable)
    {
        $this->usedInAvailable = $usedInAvailable;
    }

    /**
     * Get usedInAvailable
     *
     * @return increment $usedInAvailable
     */
    public function getUsedInAvailable()
    {
        return $this->usedInAvailable;
    }

    /**
     * Increment usedInAvailable
     *
     * @param int $inc
     */
    public function incUsedInAvailable($inc = 1)
    {
        $this->usedInAvailable += $inc;
    }

    /**
     * Set usedInUpcoming
     *
     * @param increment $usedInUpcoming
     */
    public function setUsedInUpcoming($usedInUpcoming)
    {
        $this->usedInUpcoming = $usedInUpcoming;
    }

    /**
     * Get usedInUpcoming
     *
     * @return increment $usedInUpcoming
     */
    public function getUsedInUpcoming()
    {
        return $this->usedInUpcoming;
    }

    /**
     * Increment usedInUpcoming
     *
     * @param int $inc
     */
    public function incUsedInUpcoming($inc = 1)
    {
        $this->usedInUpcoming += $inc;
    }

    /**
     * Add imagesRef
     *
     * @param Imagepush\ImagepushBundle\Document\Image $imagesRef
     */
    public function addImagesRef(\Imagepush\ImagepushBundle\Document\Image $imagesRef)
    {
        $this->imagesRef[] = $imagesRef;
    }

    /**
     * Get imagesRef
     *
     * @return Doctrine\Common\Collections\Collection $imagesRef
     */
    public function getImagesRef()
    {
        return $this->imagesRef;
    }

}
