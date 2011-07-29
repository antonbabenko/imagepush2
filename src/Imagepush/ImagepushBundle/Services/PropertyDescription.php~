<?php

namespace Ne\NeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Ne\NeBundle\Entity\PropertyDescriptionRepository")
 */
class PropertyDescription
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
   * @ORM\ManyToOne(targetEntity="Property", inversedBy="descriptions")
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
   * @var text $description
   *
   * @ORM\Column(name="description", type="text")
   */
  private $description;
  /**
   * @var bigint $descOrder
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
   * Set description
   *
   * @param text $description
   */
  public function setDescription($description)
  {
    $this->description = $description;
  }

  /**
   * Get description
   *
   * @return text $description
   */
  public function getDescription()
  {
    return $this->description;
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

}