<?php

namespace Imagepush\ImagepushBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Users votes
 *
 * ---------------------
 * 9 July 2012: This is not completed yet, because it is not in much use! Use redis to count votes.
 * ---------------------
 *
 * @MongoDB\Document(collection="vote", requireIndexes=true, repositoryClass="Imagepush\ImagepushBundle\Document\VoteRepository")
 * @MongoDB\Indexes({
 *   @MongoDB\Index(keys={"imageId"="asc"}),
 *   @MongoDB\Index(keys={"userIp"="asc"}),
 *   @MongoDB\Index(keys={"timestamp"="asc"})
 * })
 */
class Vote
{

    const SCORE_GOOD = 1;
    const SCORE_BAD = -3;

    /**
     * @MongoDB\Id(strategy="AUTO")
     */
    protected $mongoId;

    /**
     * @MongoDB\Int
     */
    protected $imageId;

    /**
     * @MongoDB\String
     */
    protected $userIp;

    /**
     * Vote score
     * @MongoDB\Int
     */
    protected $score;

    /**
     * Vote timestamp
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
     * @return Vote
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
     * Set userIp
     *
     * @param string $userIp
     *
     * @return Vote
     */
    public function setUserIp($userIp)
    {
        $this->userIp = $userIp;

        return $this;
    }

    /**
     * Get userIp
     *
     * @return string $userIp
     */
    public function getUserIp()
    {
        return $this->userIp;
    }

    /**
     * Set score
     *
     * @param int $score
     *
     * @return Vote
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score
     *
     * @return int $score
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Set timestamp
     *
     * @param timestamp $timestamp
     *
     * @return Vote
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
