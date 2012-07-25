<?php

namespace Imagepush\ImagepushBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Sequence of latest tags, which is used to show as latest trend
 * 
 * @MongoDB\Document(collection="latestTags", requireIndexes=true, repositoryClass="Imagepush\ImagepushBundle\Document\LatestTagRepository")
 * @MongoDB\Indexes({
 *   @MongoDB\Index(keys={"timestamp"="desc"}),
 *   @MongoDB\Index(keys={"tag"="asc"})
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
   * @MongoDB\EmbedOne(targetDocument="Tag")
   */
  protected $tag;

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
   * Set tag
   *
   * @param Imagepush\ImagepushBundle\Document\Tag $tag
   */
  public function setTag(\Imagepush\ImagepushBundle\Document\Tag $tag)
  {
    $this->tag = $tag;
  }

  /**
   * Get tag
   *
   * @return Imagepush\ImagepushBundle\Document\Tag $tag
   */
  public function getTag()
  {
    return $this->tag;
  }

}
