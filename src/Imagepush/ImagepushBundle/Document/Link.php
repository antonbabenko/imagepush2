<?php

namespace Imagepush\ImagepushBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Original links (indexed, failed, blocked).
 * 
 * @MongoDB\Document(collection="link", repositoryClass="Imagepush\ImagepushBundle\Document\LinkRepository")
 * @MongoDB\Indexes({
 *   @MongoDB\Index(keys={"text"="asc"}),
 *   @MongoDB\UniqueIndex(keys={"text"="asc"}, dropDups=true)
 * })
 */
class Link
{

  /**
   * @MongoDB\Id(strategy="AUTO")
   */
  protected $mongoId;

  /**
   * @MongoDB\String
   */
  protected $link;

  /**
   * Link status - indexed, failed, blocked
   * @MongoDB\String
   */
  protected $status;

  public function __construct()
  {
    
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
   * Set link
   *
   * @param string $link
   */
  public function setLink($link)
  {
    $this->link = $link;
  }

  /**
   * Get link
   *
   * @return string $link
   */
  public function getLink()
  {
    return $this->link;
  }

  /**
   * Set status
   *
   * @param string $status
   */
  public function setStatus($status)
  {
    $this->status = $status;
  }

  /**
   * Get status
   *
   * @return string $status
   */
  public function getStatus()
  {
    return $this->status;
  }

}