<?php

namespace Imagepush\ImagepushBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tag
 *
 * @ORM\Entity(repositoryClass="Imagepush\ImagepushBundle\Entity\TagRepository")
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(name="tag_text_idx", columns={"text"})
 *      }
 * )
 */
class Tag
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
     * @ORM\Column(name="text", type="string", length=255)
     */
    private $text;

    /**
     * @var integer
     *
     * @ORM\Column(name="usedInAvailable", type="integer")
     */
    private $usedInAvailable;

    /**
     * @var integer
     *
     * @ORM\Column(name="usedInUpcoming", type="integer")
     */
    private $usedInUpcoming;

    /**
     * @ORM\ManyToMany(targetEntity="Image", mappedBy="tags")
     */
    private $images;

    /**
     * @ORM\OneToMany(targetEntity="LatestTag", mappedBy="tag")
     */
    private $latestTags;

    public function __construct()
    {
        $this->usedInAvailable = 0;
        $this->usedInUpcoming = 0;
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
     * Set text
     *
     * @param  string $text
     * @return Tag
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set usedInAvailable
     *
     * @param  integer $usedInAvailable
     * @return Tag
     */
    public function setUsedInAvailable($usedInAvailable)
    {
        $this->usedInAvailable = $usedInAvailable;

        return $this;
    }

    /**
     * Get usedInAvailable
     *
     * @return integer
     */
    public function getUsedInAvailable()
    {
        return $this->usedInAvailable;
    }

    /**
     * Set usedInUpcoming
     *
     * @param  integer $usedInUpcoming
     * @return Tag
     */
    public function setUsedInUpcoming($usedInUpcoming)
    {
        $this->usedInUpcoming = $usedInUpcoming;

        return $this;
    }

    /**
     * Get usedInUpcoming
     *
     * @return integer
     */
    public function getUsedInUpcoming()
    {
        return $this->usedInUpcoming;
    }

    /**
     * @param Collection $images
     *
     * @return Tag
     */
    public function setImages(Collection $images)
    {
        $this->images = $images;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getImages()
    {
        return $this->images;
    }

}
