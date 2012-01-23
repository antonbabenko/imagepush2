<?php

namespace Imagepush\ImagepushBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;

//use Nekland\FeedBundle\Item\ExtendedItemInterface;

//use Imagepush\ImagepushBundle\External\CustomStrings;
//use Imagepush\ImagepushBundle\Model\AbstractSource;
//use Imagepush\ImagepushBundle\Services\Processor\Config;

/**
 * @MongoDB\Document(collection="images", repositoryClass="Imagepush\ImagepushBundle\Document\ImageRepository")
 * @MongoDB\Indexes({
 *   @MongoDB\UniqueIndex(keys={"id"="asc"}),
 *   @MongoDB\Index(keys={"tags"="asc"}),
 *   @MongoDB\Index(keys={"isAvailable"="asc"})
 * })
 */
class Image
{

  /**
   * @MongoDB\Id(strategy="AUTO")
   */
  protected $mongoId;

  /**
   * @MongoDB\Int
   */
  protected $id;

  /**
   * @MongoDB\String
   */
  protected $link;

  /**
   * @MongoDB\Timestamp
   */
  protected $timestamp;

  /**
   * @MongoDB\String
   */
  protected $file;

  /**
   * @MongoDB\String
   */
  protected $title;

  /**
   * @Gedmo\Slug(fields={"title"}, unique=false)
   * @MongoDB\String
   */
  protected $slug;

  /**
   * @MongoDB\String
   */
  protected $sourceType;

  /**
   * @MongoDB\Collection
   */
  protected $sourceTags;

  /**
   * @MongoDB\Collection
   */
  protected $tags;

  /**
   * @MongoDB\Collection
   * @MongoDB\ReferenceMany(targetDocument="Tag")
   */
  protected $tagsRef;

  /**
   * Available (published) or Upcoming
   * @MongoDB\Boolean
   */
  protected $isAvailable;

  /**
   * @MongoDB\Int
   */
  protected $mWidth;

  /**
   * @MongoDB\Int
   */
  protected $mHeight;

  /**
   * @MongoDB\Int
   */
  protected $tWidth;

  /**
   * @MongoDB\Int
   */
  protected $tHeight;

  /**
   * @MongoDB\Int
   */
  protected $aWidth;

  /**
   * @MongoDB\Int
   */
  protected $aHeight;

  public function __construct()
  {
    $this->tagsRef = new \Doctrine\Common\Collections\ArrayCollection();
  }

  /**
   * Start: Custom methods
   */
  public function get_originalHost()
  {
    return ($this->link ? @parse_url($this->link, PHP_URL_HOST) : null);
  }

  public function get_shareUrl()
  {
    return "http://imagepush.to" . $this->get_viewUrl();
  }

  public function get_viewUrl()
  {
    return "/i/" . $this->id . "/" . $this->slug;
    //$this->container->get('router')->generate('viewImage', array('id' => $this->id, 'slug' => $this->slug), true) : null);
  }

  public function get_mainImg()
  {
    return "http://imagepush.to/uploads/m/" . $this->file;
  }

  public function get_articleImg()
  {
    return "http://imagepush.to/uploads/a/" . $this->file;
  }

  public function get_thumbImg()
  {
    return "http://imagepush.to/uploads/thumb/" . $this->file;
  }

  /**
   * End: Custom methods
   */
  
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
   * Set id
   *
   * @param int $id
   */
  public function setId($id)
  {
    $this->id = $id;
  }

  /**
   * Get id
   *
   * @return int $id
   */
  public function getId()
  {
    return $this->id;
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
   * Set file
   *
   * @param string $file
   */
  public function setFile($file)
  {
    $this->file = $file;
  }

  /**
   * Get file
   *
   * @return string $file
   */
  public function getFile()
  {
    return $this->file;
  }

  /**
   * Set title
   *
   * @param string $title
   */
  public function setTitle($title)
  {
    $this->title = $title;
  }

  /**
   * Get title
   *
   * @return string $title
   */
  public function getTitle()
  {
    return $this->title;
  }

  /**
   * Set slug
   *
   * @param string $slug
   */
  public function setSlug($slug)
  {
    $this->slug = $slug;
  }

  /**
   * Get slug
   *
   * @return string $slug
   */
  public function getSlug()
  {
    return $this->slug;
  }

  /**
   * Set sourceType
   *
   * @param string $sourceType
   */
  public function setSourceType($sourceType)
  {
    $this->sourceType = $sourceType;
  }

  /**
   * Get sourceType
   *
   * @return string $sourceType
   */
  public function getSourceType()
  {
    return $this->sourceType;
  }

  /**
   * Set sourceTags
   *
   * @param collection $sourceTags
   */
  public function setSourceTags($sourceTags)
  {
    $this->sourceTags = $sourceTags;
  }

  /**
   * Get sourceTags
   *
   * @return collection $sourceTags
   */
  public function getSourceTags()
  {
    return $this->sourceTags;
  }

  /**
   * Set tags
   *
   * @param collection $tags
   */
  public function setTags($tags)
  {
    $this->tags = $tags;
  }

  /**
   * Get tags
   *
   * @return collection $tags
   */
  public function getTags()
  {
    return $this->tags;
  }

  /**
   * Add tagsRef
   *
   * @param Imagepush\ImagepushBundle\Document\Tag $tagsRef
   */
  public function addTagsRef(\Imagepush\ImagepushBundle\Document\Tag $tagsRef)
  {
    $this->tagsRef[] = $tagsRef;
  }

  /**
   * Get tagsRef
   *
   * @return Doctrine\Common\Collections\Collection $tagsRef
   */
  public function getTagsRef()
  {
    return $this->tagsRef;
  }

  /**
   * Set isAvailable
   *
   * @param boolean $isAvailable
   */
  public function setIsAvailable($isAvailable)
  {
    $this->isAvailable = $isAvailable;
  }

  /**
   * Get isAvailable
   *
   * @return boolean $isAvailable
   */
  public function getIsAvailable()
  {
    return $this->isAvailable;
  }

  /**
   * Set mWidth
   *
   * @param int $mWidth
   */
  public function setMWidth($mWidth)
  {
    $this->mWidth = $mWidth;
  }

  /**
   * Get mWidth
   *
   * @return int $mWidth
   */
  public function getMWidth()
  {
    return $this->mWidth;
  }

  /**
   * Set mHeight
   *
   * @param int $mHeight
   */
  public function setMHeight($mHeight)
  {
    $this->mHeight = $mHeight;
  }

  /**
   * Get mHeight
   *
   * @return int $mHeight
   */
  public function getMHeight()
  {
    return $this->mHeight;
  }

  /**
   * Set tWidth
   *
   * @param int $tWidth
   */
  public function setTWidth($tWidth)
  {
    $this->tWidth = $tWidth;
  }

  /**
   * Get tWidth
   *
   * @return int $tWidth
   */
  public function getTWidth()
  {
    return $this->tWidth;
  }

  /**
   * Set tHeight
   *
   * @param int $tHeight
   */
  public function setTHeight($tHeight)
  {
    $this->tHeight = $tHeight;
  }

  /**
   * Get tHeight
   *
   * @return int $tHeight
   */
  public function getTHeight()
  {
    return $this->tHeight;
  }

  /**
   * Set aWidth
   *
   * @param int $aWidth
   */
  public function setAWidth($aWidth)
  {
    $this->aWidth = $aWidth;
  }

  /**
   * Get aWidth
   *
   * @return int $aWidth
   */
  public function getAWidth()
  {
    return $this->aWidth;
  }

  /**
   * Set aHeight
   *
   * @param int $aHeight
   */
  public function setAHeight($aHeight)
  {
    $this->aHeight = $aHeight;
  }

  /**
   * Get aHeight
   *
   * @return int $aHeight
   */
  public function getAHeight()
  {
    return $this->aHeight;
  }

}
