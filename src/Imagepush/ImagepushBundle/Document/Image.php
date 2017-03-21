<?php

namespace Imagepush\ImagepushBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Image
 *
 * @MongoDB\Document(collection="images", requireIndexes=true, repositoryClass="Imagepush\ImagepushBundle\Document\ImageRepository")
 * @MongoDB\Indexes({
 *   @MongoDB\UniqueIndex(keys={"id"="asc"}),
 *   @MongoDB\UniqueIndex(keys={"link"="asc"}, dropDups=true),
 *   @MongoDB\Index(keys={"timestamp"="desc"}),
 *   @MongoDB\Index(keys={"tags"="asc"}),
 *   @MongoDB\Index(keys={"isAvailable"="asc"}),
 *   @MongoDB\Index(keys={"sourceType"="asc"})
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
    protected $mimeType;

    /**
     * @MongoDB\String
     */
    protected $title;

    /**
     * @Gedmo\Slug(fields={"title"}, unique=false)
     */
    protected $slug;

    /**
     * Source type
     */
    protected $sourceType;

    /**
     * List of original tags fetched from the source
     */
    protected $sourceTags;

    /**
     * List of final tags
     */
    protected $tags;

    /**
     * Hash of found tags (not finalized)
     */
    protected $tagsFound;

    /**
     * Count of found tags
     */
    protected $tagsFoundCount;

    /**
     * Boolean. If true then this image has new tagsFound, so that tags for this image should to be updated.
     */
    protected $requireUpdateTags;

    /**
     * Available (published) or Upcoming
     * @MongoDB\Boolean
     */
    protected $isAvailable;

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

    public function fromArray(array $data)
    {
        $this->setId(intval(array_values($data['id'])[0]));
        $this->setTitle(array_values($data['title'])[0]);
        $this->setSlug(array_values($data['slug'])[0]);
        $this->setTimestamp(array_values($data['timestamp'])[0]);
        $this->setLink(array_values($data['link'])[0]);
        $this->setSourceType(array_values($data['sourceType'])[0]);
        $this->setIsAvailable(array_values($data['isAvailable'])[0]);

        if (isset($data['requireUpdateTags'])) {
            $this->setRequireUpdateTags(
                array_values($data['requireUpdateTags'])[0]
            );
        }

        if (isset($data['tagsFoundCount'])) {
            $this->setTagsFoundCount(
                array_values($data['tagsFoundCount'])[0]
            );
        }

        if (isset($data['file'])) {
            $this->setFile(
                array_values($data['file'])[0]
            );
        }

        if (isset($data['mimeType'])) {
            $this->setMimeType(
                array_values($data['mimeType'])[0]
            );
        }

        if (isset($data['tags'])) {
            $this->setTags(
                array_values($data['tags'])[0]
            );
        }

        if (isset($data['sourceTags'])) {
            $this->setSourceTags(
                array_values($data['sourceTags'])[0]
            );
        }

        if (isset($data['thumbs'])) {
            $t = [];
            foreach (array_values($data['thumbs'])[0] as $tk => $tv) {
                $tvv = [];
                foreach (array_values($tv)[0] as $tvk => $tvvalue) {
                    $tvv += [$tvk => intval(array_values($tvvalue)[0])];
                }
                $t += [$tk => $tvv];
            }

            $this->setThumbs($t);
        }

        if (isset($data['tagsFound'])) {
            $t = [];
            foreach (array_values($data['tagsFound'])[0] as $tk => $tv) {
                $tvv = [];
                foreach (array_values($tv)[0] as $tvk => $tvvalue) {
                    $tvv += [$tvk => intval(array_values($tvvalue)[0])];
                }
                $t += [$tk => $tvv];
            }

            $this->setTagsFound($t);
        }

    }

    public function toItem()
    {
        $item = [
            'id' => [
                'N' => strval($this->getId())
            ],
            'title' => [
                'S' => strval($this->getTitle())
            ],
            'timestamp' => [
                'N' => strval($this->getTimestamp())
            ],
            'link' => [
                'S' => strval($this->getLink())
            ],
            'sourceType' => [
                'S' => strval($this->getSourceType())
            ],
            'isAvailable' => [
                'N' => strval((int) $this->getIsAvailable())
            ],
            'requireUpdateTags' => [
                'N' => strval((int) $this->getRequireUpdateTags())
            ],
        ];

        if ($this->getSlug()) {
            $item['slug'] = [
                'S' => strval($this->getSlug())
            ];
        }

        if ($this->getTagsFoundCount()) {
            $item['tagsFoundCount'] = [
                'N' => strval($this->getTagsFoundCount())
            ];
        }

        if ($this->getFile()) {
            $item['file'] = [
                'S' => strval($this->getFile())
            ];
        }

        if ($this->getMimeType()) {
            $item['mimeType'] = [
                'S' => strval($this->getMimeType())
            ];
        }

        if ($this->getSourceTags()) {
            $item['sourceTags'] = [
                'SS' => array_values(array_unique(array_map('strval', (array) $this->getSourceTags())))
            ];
        }

        if ($this->getTags()) {
            $item['tags'] = [
                'SS' => array_values(array_unique(array_map('strval', (array) $this->getTags())))
            ];
        }

        if ($this->getTagsFound()) {
            $t = [];
            foreach ($this->getTagsFound() as $tk => $tv) {
                $tags = [];
                foreach ($tv as $tag => $mentioned) {
                    $tags[strval($tag)] = ['N' => strval($mentioned)];
                }
                $t += [$tk => ['M' => $tags]];
            }

            $item['tagsFound'] = [
                'M' => $t
            ];
        }

        if ($this->getThumbs()) {
            $t = [];
            foreach ($this->getThumbs() as $tk => $tv) {
                $tvv = [];
                foreach ($tv as $tvk => $tvvalue) {
                    $tvv += [$tvk => ['N' => strval($tvvalue)]];
                }
                $t += [$tk => ['M' => $tvv]];
            }

            $item['thumbs'] = [
                'M' => $t
            ];
        }

        return $item;

    }

    /**
     * Get original host (to show in template)
     */
    public function get_originalHost()
    {
        return $this->link ? @parse_url($this->link, PHP_URL_HOST) : null;
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
        // done... @todo: check after import if all timestamps are \MongoTimestamp, then remove the if
        if ($this->timestamp instanceof \MongoTimestamp) {
            return new \DateTime("@" . $this->timestamp->__toString());
        } else {
            return new \DateTime("@" . $this->timestamp);
        }
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
     * @param array $sourceTags
     */
    public function setSourceTags($sourceTags)
    {
        $this->sourceTags = $sourceTags;
    }

    /**
     * Get sourceTags
     *
     * @return array $sourceTags
     */
    public function getSourceTags()
    {
        return $this->sourceTags;
    }

    /**
     * Set tags
     *
     * @param array $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    /**
     * Get tags
     *
     * @return array $tags
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set tagsFound
     *
     * @param  array $tagsFound
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
     * @return array $tagsFound
     */
    public function getTagsFound()
    {
        return $this->tagsFound;
    }

    /**
     * Set tagsFoundCount
     *
     * @param  integer $tagsFoundCount
     * @return Image
     */
    public function setTagsFoundCount($tagsFoundCount)
    {
        $this->tagsFoundCount = $tagsFoundCount;

        return $this;
    }

    /**
     * Get tagsFoundCount
     *
     * @return integer $tagsFoundCount
     */
    public function getTagsFoundCount()
    {
        return $this->tagsFoundCount;
    }

    /**
     * Set requireUpdateTags
     *
     * @param  boolean $requireUpdateTags
     * @return Image
     */
    public function setRequireUpdateTags($requireUpdateTags)
    {
        $this->requireUpdateTags = (bool) $requireUpdateTags;

        return $this;
    }

    /**
     * Get requireUpdateTags
     *
     * @return bool $requireUpdateTags
     */
    public function getRequireUpdateTags()
    {
        return $this->requireUpdateTags;
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
     * Set mimeType
     *
     * @param string $mimeType
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
    }

    /**
     * Get mimeType
     *
     * @return string $mimeType
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Generate filename based on ID and specified mime-type
     *
     * @return string $file
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
        $file .= ($this->getId() % 100) . "." . $fileExt;

        $this->setFile($file);

        return $file;
    }

    /**
     * Set thumbs
     *
     * @param array $thumbs
     *
     * @return Image
     */
    public function setThumbs($thumbs)
    {
        $this->thumbs = $thumbs;

        return $this;
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
     *
     * @param string  $filter       Filter name ("in", "out")
     * @param string  $size         Filter size (eg, "120x150")
     * @param integer $actualWidth  Actual width
     * @param integer $actualHeight Actual height
     * @param integer $filesize     File size
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
