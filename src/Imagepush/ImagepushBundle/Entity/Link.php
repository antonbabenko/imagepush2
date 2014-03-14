<?php

namespace Imagepush\ImagepushBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
//use Imagepush\ImagepushBundle\Enum\LinkStatusEnum;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Link
 *
 * @ORM\Entity(repositoryClass="Imagepush\ImagepushBundle\Entity\LinkRepository")
 * @ORM\Table(
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="link_unique_idx", columns={"link"})
 *      },
 *      indexes={
 *          @ORM\Index(name="status_idx", columns={"status"})
 *      }
 * )
 */
class Link
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
     */
    private $link;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="smallint")
     * @Assert\Choice(callback={"LinkStatusEnum", "getValues"})
     */
    private $status;

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
     * @return Link
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
     * Set status
     *
     * @param  integer $status
     * @return Link
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }
}
