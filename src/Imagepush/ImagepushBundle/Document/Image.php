<?php

namespace Imagepush\ImagepushBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Image
 * 
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
     * Is "in process"
     * @MongoDB\Boolean
     */
    protected $isInProcess;

    /**
     * Created thumbs with actual dimensions
     * @MongoDB\Hash
     */
    protected $thumbs;

    public function __construct()
    {
        $this->tagsRef = new \Doctrine\Common\Collections\ArrayCollection();
        $this->thumbs = array();
    }

    /**
     * Start: Custom methods
     */
    public function get_originalHost()
    {
        return $this->link ? @parse_url($this->link, PHP_URL_HOST) : null;
    }

    /* public function get_shareUrl()
      {
      return "http://imagepush.to" . $this->get_viewUrl();
      } */

    /* public function get_viewUrl()
      {
      return "/i/" . $this->id . "/" . $this->slug;
      //$this->container->get('router')->generate('viewImage', array('id' => $this->id, 'slug' => $this->slug), true) : null);
      } */

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
     * @param \MongoTimestamp $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * Get timestamp
     *
     * @return \MongoTimestamp $timestamp
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Get timestamp as \DateTime (to be able to use in templates)
     *
     * @return \DateTime $datetime
     */
    public function getDatetime()
    {
        return new \DateTime("@" . $this->timestamp->__toString());
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
     * Set isInProcess
     *
     * @param boolean $isInProcess
     */
    public function setIsInProcess($isInProcess)
    {
        $this->isInProcess = $isInProcess;
    }

    /**
     * Get isInProcess
     *
     * @return boolean $isInProcess
     */
    public function getIsInProcess()
    {
        return $this->isInProcess;
    }

    /**
     * Generate filename based on ID and content type
     *
     * @return string $file
     */
    public function updateFilename($contentType)
    {

        if (in_array($contentType, array("image/gif"))) {
            $fileExt = "gif";
        } elseif (in_array($contentType, array("image/png"))) {
            $fileExt = "png";
        } else {
            $fileExt = "jpg";
        }

        // For eg: 2567 => /0/2/5/67.jpg
        $file = floor($this->getId() / 10000) . "/";
        $file .= floor($this->getId() / 1000) . "/";
        $file .= floor($this->getId() / 100) . "/";
        $file .= ( $this->getId() % 100) . "." . $fileExt;

        $this->setFile($file);

        return $file;
    }

    /**
     * Get array of created thumbs.
     *
     * @return array $thumbs
     */
    public function getThumbs()
    {
        return $this->thumbs;
    }

    /**
     * Add created thumb.
     */
    public function addThumbs($filter, $size, $actualWidth = 0, $actualHeight = 0)
    {
        $key = $filter . "/" . $size;

        $this->thumbs[$key] = array("w" => (int) $actualWidth, "h" => (int) $actualHeight);
    }

    /**
     * Get thumb information.
     * If requested thumb is NOT created (means not on S3/CDN), then file has to be generated on first request.
     *
     * @return array|false $thumbs
     */
    public function getThumbByFilterAndSize($filter, $size)
    {

        $key = $filter . "/" . $size;

        if (array_key_exists($key, $this->thumbs)) {
            return $this->thumbs[$key];
        } else {
            return false;
        }
    }

    /**
     * Get thumb information.
     * If thumb has been already created then we have actual image property (width/height).
     *
     * @param string  $filter   Filter Name
     * @param integer $width    Width
     * @param integer $height   Height
     * @param string  $property Property name for the height is "h", for width is "w"
     * 
     * @return array|false $thumbs
     */
    public function getThumbSize($filter, $width, $height, $property)
    {

        $key = $filter . "/" . $width . "x" . $height;

        //\D::dump($key);

        if (array_key_exists($key, $this->thumbs)) {

            $thumb = $this->thumbs[$key];

            if (!empty($thumb[$property])) {
                return $thumb[$property];
            }

            if ($property == "w") {
                return $width;
            } elseif ($property == "h") {
                return $height;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

}
