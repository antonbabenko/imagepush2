<?php

namespace Ne\NeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Ne\NeBundle\Entity\PropertyPartRepository")
 */
class PropertyPart
{

  /**
   * @var bigint $id
   *
   * @ORM\Column(name="id", type="bigint", nullable=false)
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="IDENTITY")
   */
  private $id;
  /**
   * @var Property
   *
   * @ORM\ManyToOne(targetEntity="Property", inversedBy="parts")
   * @ORM\JoinColumn(name="property_id", referencedColumnName="id")
   */
  private $property;
  /**
   * @var text $name
   *
   * @ORM\Column(name="name", type="text")
   */
  private $name;
  /**
   * @var integer $typeId
   *
   * @ORM\Column(name="typeId", type="integer")
   */
  private $typeId;
  /**
   * @var integer $fromSize
   *
   * @ORM\Column(name="from_size", type="integer")
   */
  private $fromSize;
  /**
   * @var integer $toSize
   *
   * @ORM\Column(name="to_size", type="integer")
   */
  private $toSize;
  /**
   * @var bigint $weight
   *
   * @ORM\Column(type="bigint")
   */
  private $weight;

  /**
   * Get id
   *
   * @return bigint $id
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set name
   *
   * @param text $name
   */
  public function setName($name)
  {
    $this->name = $name;
  }

  /**
   * Get name
   *
   * @return text $name
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Set typeId
   *
   * @param integer $typeId
   */
  public function setTypeId($typeId)
  {
    $this->typeId = $typeId;
  }

  /**
   * Get typeId
   *
   * @return integer $typeId
   */
  public function getTypeId()
  {
    return $this->typeId;
  }

  /**
   * Set fromSize
   *
   * @param integer $fromSize
   */
  public function setFromSize($fromSize)
  {
    $this->fromSize = $fromSize;
  }

  /**
   * Get fromSize
   *
   * @return integer $fromSize
   */
  public function getFromSize()
  {
    return $this->fromSize;
  }

  /**
   * Set toSize
   *
   * @param integer $toSize
   */
  public function setToSize($toSize)
  {
    $this->toSize = $toSize;
  }

  /**
   * Get toSize
   *
   * @return integer $toSize
   */
  public function getToSize()
  {
    return $this->toSize;
  }

  /**
   * Set weight
   *
   * @param bigint $weight
   */
  public function setWeight($weight)
  {
    $this->weight = $weight;
  }

  /**
   * Get weight
   *
   * @return bigint $weight
   */
  public function getWeight()
  {
    return $this->weight;
  }

  /**
   * Set property
   *
   * @param Ne\NeBundle\Entity\Property $property
   */
  public function setProperty(\Ne\NeBundle\Entity\Property $property)
  {
    $this->property = $property;
  }

  /**
   * Get property
   *
   * @return Ne\NeBundle\Entity\Property $property
   */
  public function getProperty()
  {
    return $this->property;
  }

  /**
   * Set correct type id.
   * In legacy database we had some ids, which in new should be grouped in one ("Annet" = id 11)
   *
   * @return integer $typeId
   */
  public function setCorrectTypeId($typeId)
  {
    $this->typeId = ($typeId <= 4 ? $typeId : 11);
  }

}