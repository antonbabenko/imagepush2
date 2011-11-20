<?php

namespace Imagepush\ImagepushBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;

//use Imagepush\ImagepushBundle\External\CustomStrings;
//use Imagepush\ImagepushBundle\Model\AbstractSource;
//use Imagepush\ImagepushBundle\Services\Processor\Config;

/**
 * @MongoDB\Document(repositoryClass="Imagepush\ImagepushBundle\Document\ImageRepository")
 */
class Image
{
    /**
     * @MongoDB\Id(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @MongoDB\Int
     */
    protected $imageId;
    
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

    
    public function __construct() {
    }
    
    /**
     * Get id
     *
     * @return custom_id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set imageId
     *
     * @param int $imageId
     */
    public function setImageId($imageId)
    {
        $this->imageId = $imageId;
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
     * @param string $sourceTags
     */
    public function setSourceTags($sourceTags)
    {
        $this->sourceTags = $sourceTags;
    }

    /**
     * Get sourceTags
     *
     * @return string $sourceTags
     */
    public function getSourceTags()
    {
        return $this->sourceTags;
    }

    /**
     * Set tags
     *
     * @param string $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    /**
     * Get tags
     *
     * @return string $tags
     */
    public function getTags()
    {
        return $this->tags;
    }
    
    //public function getViewUrl() {
      //\D::dump($this->getContainer()->get('router')->generate('viewImage', array('id' => $this->imageId, 'slug' => $this->slug)));
      //return $this->router->generate('viewImage', array('id' => $this->imageId, 'slug' => $this->slug));
    //}
    
    /*public function getOtherStuffViewUrl() {
          $image["_thumb_img"] = $this->getFileUrl($image, "t"); // thumb
    $image["_main_img"] = $this->getFileUrl($image, "m"); // main
    $image["_article_img"] = $this->getFileUrl($image, "a"); // article
    $image["_tags"] = (isset($image["tags"]) && json_decode($image["tags"]) ? $this->tagsManager->getHumanTags(json_decode($image["tags"])) : "");
    
    $image["_share_url"] = $this->router->generate('viewImage', array('id' => $image["id"], 'slug' => $image["slug"]), true);
    $image["_original_host"] = @parse_url($image["link"], PHP_URL_HOST);
    $image["_date"] = date(DATE_W3C, $image["timestamp"]);

    }*/

}
