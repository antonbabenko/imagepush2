<?php

namespace Imagepush\ImagepushBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;

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

}
