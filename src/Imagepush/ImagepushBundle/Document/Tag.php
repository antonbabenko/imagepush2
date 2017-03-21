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

    public function fromArray(array $data)
    {
        $this->setText(array_values($data['text'])[0]);

        if (isset($data['usedInAvailable'])) {
            $this->setUsedInAvailable(
                array_values($data['usedInAvailable'])[0]
            );
        } else {
            $this->setUsedInAvailable(0);
        }

        if (isset($data['usedInUpcoming'])) {
            $this->setUsedInUpcoming(
                array_values($data['usedInUpcoming'])[0]
            );
        } else {
            $this->setUsedInUpcoming(0);
        }

    }

    public function toItem()
    {
        $item = [
            'text' => [
                'S' => strval($this->getText())
            ],
            'usedInAvailable' => [
                'N' => strval((int) $this->getUsedInAvailable())
            ],
            'usedInUpcoming' => [
                'N' => strval((int) $this->getUsedInUpcoming())
            ],
        ];

        return $item;
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
     * @param integer $usedInAvailable
     */
    public function setUsedInAvailable($usedInAvailable)
    {
        $this->usedInAvailable = $usedInAvailable;
    }

    /**
     * Get usedInAvailable
     *
     * @return integer $usedInAvailable
     */
    public function getUsedInAvailable()
    {
        return $this->usedInAvailable;
    }

    /**
     * Increment usedInAvailable
     *
     * @param integer $inc
     */
    public function incUsedInAvailable($inc = 1)
    {
        $this->usedInAvailable += $inc;
    }

    /**
     * Set usedInUpcoming
     *
     * @param integer $usedInUpcoming
     */
    public function setUsedInUpcoming($usedInUpcoming)
    {
        $this->usedInUpcoming = $usedInUpcoming;
    }

    /**
     * Get usedInUpcoming
     *
     * @return integer $usedInUpcoming
     */
    public function getUsedInUpcoming()
    {
        return $this->usedInUpcoming;
    }

    /**
     * Increment usedInUpcoming
     *
     * @param integer $inc
     */
    public function incUsedInUpcoming($inc = 1)
    {
        $this->usedInUpcoming += $inc;
    }

}
