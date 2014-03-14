<?php

namespace Imagepush\ImagepushBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Image
 *
 * @ORM\Entity(repositoryClass="Imagepush\ImagepushBundle\Entity\ImageRepository")
 * @ORM\Table(
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="image_link_unique_idx", columns={"link"})
 *      },
 *      indexes={
 *          @ORM\Index(name="image_created_at_idx", columns={"created_at"}),
 *          @ORM\Index(name="image_updated_at_idx", columns={"updated_at"}),
 *      }
 * )
 */
class Image
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="link", type="string", length=255)
     * @Assert\Url
     */
    private $link;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="file", type="string", length=255)
     */
    private $file;

    /**
     * @var string
     *
     * @ORM\Column(name="mimeType", type="string", length=255)
     */
    private $mimeType;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @Assert\NotBlank
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="slug", type="string", length=255)
     * @Gedmo\Slug(fields={"title"}, updatable=true)
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="sourceType", type="string", length=255)
     */
    private $sourceType;

    /**
     * @var array
     *
     * @ORM\Column(name="sourceTags", type="array")
     */
    private $sourceTags;

    /**
     * @var array
     *
     * @ORM\ManyToMany(targetEntity="Tag", inversedBy="images")
     */
    private $tags;

    /**
     * @var array
     *
     * @ORM\Column(name="tagsFound", type="array")
     */
    private $tagsFound;

    /**
     * @var boolean
     *
     * @ORM\Column(name="available", type="boolean")
     */
    private $available;

    /**
     * @var boolean
     *
     * @ORM\Column(name="inProcess", type="boolean")
     */
    private $inProcess;

    /**
     * @var array
     *
     * @ORM\Column(name="thumbs", type="array")
     */
    private $thumbs;

    /**
     * Set id (only for imagepush:migrate command)
     *
     * @todo remove after migration from mongo is done
     *
     * @param  integer $id
     * @return Image
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set link
     *
     * @param  string $link
     * @return Image
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set createdAt
     *
     * @param  \DateTime $createdAt
     * @return Image
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param  \DateTime $updatedAt
     * @return Image
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set file
     *
     * @param  string $file
     * @return Image
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Get file
     *
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set mimeType
     *
     * @param  string $mimeType
     * @return Image
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Get mimeType
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Set title
     *
     * @param  string $title
     * @return Image
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set slug
     *
     * @param  string $slug
     * @return Image
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set sourceType
     *
     * @param  string $sourceType
     * @return Image
     */
    public function setSourceType($sourceType)
    {
        $this->sourceType = $sourceType;

        return $this;
    }

    /**
     * Get sourceType
     *
     * @return string
     */
    public function getSourceType()
    {
        return $this->sourceType;
    }

    /**
     * Set sourceTags
     *
     * @param  array $sourceTags
     * @return Image
     */
    public function setSourceTags($sourceTags)
    {
        $this->sourceTags = $sourceTags;

        return $this;
    }

    /**
     * Get sourceTags
     *
     * @return array
     */
    public function getSourceTags()
    {
        return $this->sourceTags;
    }

    /**
     * @param Collection $tags
     *
     * @return Image
     */
    public function setTags(Collection $tags)
    {
        $this->tags = $tags;

        return $this;

    }

    /**
     * @return Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return array
     */
    public function getTagsTextAsArray()
    {
        $texts = [];

        foreach ($this->tags as $tag) {
            $texts[] = $tag->getText();
        }

        return $texts;
    }

    /**
     * Set tagsFound
     *
     * @param array $tagsFound
     *
     * @return Image
     */
    public function setTagsFound($tagsFound)
    {
        $this->tagsFound = $tagsFound;

        return $this;
    }

    /**
     * Get tagsFound
     *
     * @return array
     */
    public function getTagsFound()
    {
        return $this->tagsFound;
    }

    /**
     * Set available
     *
     * @param  boolean $available
     * @return Image
     */
    public function setAvailable($available)
    {
        $this->available = $available;

        return $this;
    }

    /**
     * Get available
     *
     * @return boolean
     */
    public function getAvailable()
    {
        return $this->available;
    }

    /**
     * Set inProcess
     *
     * @param  boolean $inProcess
     * @return Image
     */
    public function setInProcess($inProcess)
    {
        $this->inProcess = $inProcess;

        return $this;
    }

    /**
     * Get inProcess
     *
     * @return boolean
     */
    public function getInProcess()
    {
        return $this->inProcess;
    }

    /**
     * Set thumbs
     *
     * @param  array $thumbs
     * @return Image
     */
    public function setThumbs($thumbs)
    {
        $this->thumbs = $thumbs;

        return $this;
    }

    /**
     * Get thumbs
     *
     * @return array
     */
    public function getThumbs()
    {
        return $this->thumbs;
    }

    /**
     * Get original host (to show in template)
     *
     * @return string
     */
    public function get_originalHost()
    {
        return $this->link ? @parse_url($this->link, PHP_URL_HOST) : null;
    }

    /**
     * Generate filename based on ID and specified mime-type
     *
     * @return string
     */
    public function updateFilename()
    {

        if (in_array($this->getMimeType(), array("image/gif"))) {
            $fileExt = "gif";
        } elseif (in_array($this->getMimeType(), array("image/png"))) {
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
     * Add created thumb.
     *
     * @todo Optimize image handling
     *
     * @param type $filter       Filter name ("in", "out")
     * @param type $size         Filter size (eg, "120x150")
     * @param type $actualWidth  Actual width
     * @param type $actualHeight Actual height
     * @param type $filesize     File size
     */
    public function addThumbs($filter, $size, $actualWidth = 0, $actualHeight = 0, $filesize = 0)
    {
        $key = $filter . "/" . $size;

        $this->thumbs[$key] = array(
            "w" => (int) $actualWidth,
            "h" => (int) $actualHeight,
            "s" => (int) $filesize
        );
    }

    /**
     * Get thumb information.
     * If requested thumb is NOT created (means not on S3/CDN), then file has to be generated on first request.
     *
     * @todo Optimize image handling
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
     * Get thumb property.
     * If thumb has been already created then there were properties saved (width/height/filesize).
     *
     * @todo Optimize image handling
     *
     * @param string  $filter   Filter Name
     * @param integer $width    Width
     * @param integer $height   Height
     * @param string  $property Property name (height => "h", width => "w", filesize => "s")
     *
     * @return integer|false
     */
    public function getThumbProperty($filter, $width, $height, $property)
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
