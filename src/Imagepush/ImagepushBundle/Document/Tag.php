<?php

namespace Imagepush\ImagepushBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @MongoDB\Document(collection="tags", repositoryClass="Imagepush\ImagepushBundle\Document\TagRepository")
 * @MongoDB\Indexes({
 *   @MongoDB\Index(keys={"text"="asc"}),
 *   @MongoDB\UniqueIndex(keys={"text"="asc"}, dropDups=true)
 * })
 */
class Tag
{
  const SRC_DIGG = 1;
  const SRC_DELICIOUS = 2;
  const SRC_STUMBLEUPON = 3;
  const SRC_REDDIT = 4;
  const SRC_TWITTER = 5;

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
    $this->imagesRef = new \Doctrine\Common\Collections\ArrayCollection();
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