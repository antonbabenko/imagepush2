<?php

namespace Ne\NeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Ne\NeBundle\Entity\County
 *
 * @ORM\Entity(repositoryClass="Ne\NeBundle\Entity\CountyRepository")
 */
class County
{

  /**
   * @var integer $id
   *
   * @ORM\Column(type="integer", nullable=false)
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="IDENTITY")
   */
  private $id;
  /**
   * @var string $name
   *
   * @ORM\Column(type="string", length=50, nullable=false)
   */
  private $name;

  /**
   * @return string $name
   */
  public function __toString()
  {
    return $this->name;
  }

  /**
   * Get id
   *
   * @return integer $id
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set name
   *
   * @param string $name
   */
  public function setName($name)
  {
    $this->name = $name;
  }

  /**
   * Get name
   *
   * @return string $name
   */
  public function getName()
  {
    return $this->name;
  }

}