<?php

namespace Imagepush\ImagepushBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Best images (defined by admin).
 * Admin add best images from "vote up" email. This is temporary solution to increase quality of images (13.7.2012).
 * 
 * @MongoDB\Document(collection="bestImage")
 * @MongoDB\Indexes({
 *   @MongoDB\UniqueIndex(keys={"imageId"="asc"}, dropDups=true, safe=false)
 * })
 */
class BestImage
{

    /**
     * @MongoDB\Id(strategy="AUTO")
     */
    protected $mongoId;

    /**
     * @MongoDB\Int
     */
    protected $imageId;

    /**
     * @MongoDB\Timestamp
     */
    protected $timestamp;

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
     * Set imageId
     *
     * @param int $imageId
     * 
     * @return BestImage
     */
    public function setImageId($imageId)
    {
        $this->imageId = $imageId;

        return $this;
    }

    /**
     * Get imageId
     *
     * @return int $imageId
     */
    public function getImageId()
    {
        return $this->imageId;
    }

    /**
     * Set timestamp
     *
     * @param timestamp $timestamp
     * 
     * @return BestImage
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
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

}
