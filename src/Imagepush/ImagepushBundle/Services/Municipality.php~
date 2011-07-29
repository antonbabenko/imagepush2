<?php

namespace Ne\NeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Ne\NeBundle\Entity\Municipality
 *
 * @ORM\Entity(repositoryClass="Ne\NeBundle\Entity\MunicipalityRepository")
 */
class Municipality
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
   * @var integer $municipalityId
   *
   * @ORM\Column(type="integer", nullable=false)
   */
  private $municipalityId;
  /**
   * @var integer $countyId
   *
   * @ORM\Column(type="integer", nullable=false)
   */
  private $countyId;
  /**
   * @var string $name
   *
   * @ORM\Column(type="string", length=100, nullable=false)
   */
  private $name;

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
   * Set countyId
   *
   * @param integer $countyId
   */
  public function setCountyId($countyId)
  {
    $this->countyId = $countyId;
  }

  /**
   * Get countyId
   *
   * @return integer $countyId
   */
  public function getCountyId()
  {
    return $this->countyId;
  }

  /**
   * Set municipalityId
   *
   * @param integer $municipalityId
   */
  public function setMunicipalityId($municipalityId)
  {
    $this->municipalityId = $municipalityId;
  }

  /**
   * Get municipalityId
   *
   * @return integer $municipalityId
   */
  public function getMunicipalityId()
  {
    return $this->municipalityId;
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