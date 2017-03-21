<?php

namespace Imagepush\ImagepushBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Sequence of latest tags, which is used to show as latest trend
 *
 * @MongoDB\Document(collection="latestTags", requireIndexes=true, repositoryClass="Imagepush\ImagepushBundle\Document\LatestTagRepository")
 * @MongoDB\Indexes({
 *   @MongoDB\Index(keys={"timestamp"="desc"}),
 *   @MongoDB\Index(keys={"text"="asc"})
 * })
 */
class LatestTag
{

    /**
     * @MongoDB\Id(strategy="AUTO")
     */
    protected $id;

    /**
     * @MongoDB\Timestamp
     */
    protected $timestamp;

    /**
     * @MongoDB\String
     */
    protected $text;

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set timestamp
     *
     * @param timestamp $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * Get timestamp
     *
     * @return timestamp $timestamp
     */
    public function getTimestamp()
    {
        return $this->timestamp;
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

    public function toItem()
    {
        $item = [
            'id' => [
                'B' => base64_encode(microtime() . $this->getText())
            ],
            'timestamp' => [
                'N' => strval($this->getTimestamp())
            ],
            'text' => [
                'S' => strval($this->getText())
            ],
        ];

        return $item;
    }

}
