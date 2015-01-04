<?php

namespace Imagepush\ImagepushBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProcessedHash
 *
 * @ORM\Entity(repositoryClass="Imagepush\ImagepushBundle\Entity\ProcessedHashRepository")
 * @ORM\Table(
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="hash_unique_idx", columns={"hash"})
 *      }
 * )
 */
class ProcessedHash
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
     * @ORM\Column(name="hash", type="string", length=255)
     */
    private $hash;

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
     * Set hash
     *
     * @param  string        $hash
     * @return ProcessedHash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }
}
