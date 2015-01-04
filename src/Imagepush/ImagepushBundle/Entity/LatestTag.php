<?php

namespace Imagepush\ImagepushBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * LatestTag
 *
 * @ORM\Entity(repositoryClass="Imagepush\ImagepushBundle\Entity\LatestTagRepository")
 * @ORM\Table(
 *      indexes={
 *          @ORM\Index(name="latest_tag_created_at_idx", columns={"created_at"})
 *      }
 * )
 */
class LatestTag
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
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @var \Imagepush\ImagepushBundle\Entity\Tag
     *
     * @ORM\ManyToOne(targetEntity="Tag", inversedBy="latestTags")
     */
    private $tag;

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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return LatestTag
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
     * Set tag
     *
     * @param Tag $tag
     *
     * @return LatestTag
     */
    public function setTag(Tag $tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get tag
     *
     * @return Tag
     */
    public function getTag()
    {
        return $this->tag;
    }
}
