<?php

namespace Ne\NeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Ne\NeBundle\Entity\PropertyFileRepository")
 */
class PropertyFile
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
   * @ORM\ManyToOne(targetEntity="Property", inversedBy="files")
   * @ORM\JoinColumn(name="property_id", referencedColumnName="id")
   */
  private $property;
  /**
   * @var string $type
   * 
   * Type can be image or document
   *
   * @ORM\Column(name="type", type="string", length=20)
   */
  private $type;
  /**
   * @var text $filename
   *
   * @ORM\Column(name="filename", type="string", length=100)
   */
  private $filename;
  /**
   * @var text $description
   *
   * @ORM\Column(name="description", type="text")
   */
  private $description;
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
   * Set type
   *
   * @param string $type
   */
  public function setType($type)
  {
    $this->type = $type;
  }

  /**
   * Get type
   *
   * @return string $type
   */
  public function getType()
  {
    return $this->type;
  }

  /**
   * Set filename
   *
   * @param string $filename
   */
  public function setFilename($filename)
  {
    $this->filename = $filename;
  }

  /**
   * Get filename
   *
   * @return string $filename
   */
  public function getFilename()
  {
    return $this->filename;
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