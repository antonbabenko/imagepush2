<?php

namespace Ne\NeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="Ne\NeBundle\Entity\PropertyRepository")
 */
class Property
{

  /**
   * @var integer $id
   *
   * Use values from propertyId to find the Property object itself,
   * but use this $id column to join with other related objects (like PropertyDescription, PropertyParts)
   * 
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="IDENTITY")
   */
  private $id;
  /**
   * @var bigint $propertyId
   *
   * @ORM\Column(type="bigint", unique=true)
   */
  private $propertyId;
  /**
   * @var bigint $extId
   *
   * @ORM\Column(type="bigint")
   */
  private $extId;
  /**
   * @var bigint $price
   *
   * @ORM\Column(type="bigint")
   */
  private $price;
  /**
   * @var text $itmRef
   *
   * @ORM\Column(type="text")
   */
  private $itmRef;
  /**
   * @var string $status
   * 
   * Valid values: live,clarification,deleted,private
   *
   * @ORM\Column(type="string", length=50)
   */
  private $status;
  /**
   * @var string $expiryStatus
   * 
   * Has value "warning" if message has been sent 2 weeks before expiration 
   *
   * @ORM\Column(type="string", length=50)
   */
  private $expiryStatus;
  /**
   * @var bigint $companyId
   *
   * @ORM\Column(type="bigint")
   */
  private $companyId;
  /**
   * @var bigint $secCompanyId
   * 
   * Secondary company which owns the property and wants to show it on their web-site
   *
   * @ORM\Column(type="bigint")
   */
  private $secCompanyId;
  /**
   * @var text $name
   *
   * @ORM\Column(type="text")
   */
  private $name;
  /**
   * @var text $address
   *
   * @ORM\Column(type="text")
   */
  private $address;
  /**
   * @var string $zip
   *
   * @ORM\Column(type="string", length=10)
   */
  private $zip;
  /**
   * @var text $city
   *
   * @ORM\Column(type="text")
   */
  private $city;
  /**
   * @var integer $municipalityId
   *
   * @ORM\Column(type="integer")
   */
  private $municipalityId;
  /**
   * @var integer $gnr
   *
   * @ORM\Column(type="integer")
   */
  private $gnr;
  /**
   * @var integer $bnr
   *
   * @ORM\Column(type="integer")
   */
  private $bnr;
  /**
   * @var integer $snr
   *
   * @ORM\Column(type="integer")
   */
  private $snr;
  /**
   * @var integer $fnr
   *
   * @ORM\Column(type="integer")
   */
  private $fnr;
  /**
   * @var string $energyGrade
   *
   * @ORM\Column(type="string", length=10)
   */
  private $energyGrade;
  /**
   * @var string $heatinggrade
   *
   * @ORM\Column(type="string", length=10)
   */
  private $heatingGrade;
  /**
   * @var integer $parkingSpaces
   *
   * @ORM\Column(type="integer")
   */
  private $parkingSpaces;
  /**
   * @var integer $availabilityTypeId
   * 
   * 1,3,10 - is in use. Table "status"
   *
   * @ORM\Column(type="integer")
   */
  private $availabilityTypeId;
  /**
   * @var bigint $minAreaFree
   *
   * @ORM\Column(type="bigint")
   */
  private $minAreaFree;
  /**
   * @var bigint $totalAreaFree
   *
   * @ORM\Column(type="bigint")
   */
  private $totalAreaFree;
  /**
   * @var text $fCompany
   *
   * @ORM\Column(type="text")
   */
  private $fCompany;
  /**
   * @var text $fAddress
   *
   * @ORM\Column(type="text")
   */
  private $fAddress;
  /**
   * @var text $fPerson
   *
   * @ORM\Column(type="text")
   */
  private $fPerson;
  /**
   * @var text $fRef
   *
   * @ORM\Column(type="text")
   */
  private $fRef;
  /**
   * @var string $fZip
   *
   * @ORM\Column(type="string", length=10)
   */
  private $fZip;
  /**
   * @var text $fCity
   *
   * @ORM\Column(type="text")
   */
  private $fCity;
  /**
   * @var string $mapx
   *
   * @ORM\Column(type="string", length=50)
   */
  private $mapx;
  /**
   * @var string $mapy
   *
   * @ORM\Column(type="string", length=50)
   */
  private $mapy;
  /**
   * @var \DateTime $startDate
   *
   * @ORM\Column(type="datetime")
   */
  private $startDate;
  /**
   * @var \DateTime $endDate
   *
   * @ORM\Column(type="datetime")
   */
  private $endDate;
  /**
   * @var \DateTime $createdAt
   *
   * @ORM\Column(type="datetime")
   */
  private $createdAt;
  /**
   * @var bigint $modifiedAt
   *
   * @ORM\Column(type="datetime")
   */
  private $modifiedAt;
  /**
   * @ORM\OneToMany(targetEntity="PropertyDescription", mappedBy="property", cascade={"all"})
   * @ORM\OrderBy({"weight" = "ASC"})
   */
  private $descriptions;
  /**
   * @ORM\OneToMany(targetEntity="PropertyPart", mappedBy="property", cascade={"all"})
   * @ORM\OrderBy({"weight" = "ASC"})
   */
  private $parts;
  /**
   * @ORM\OneToMany(targetEntity="PropertyFile", mappedBy="property", cascade={"all"})
   * @ORM\OrderBy({"weight" = "ASC"})
   */
  private $files;

  public function __construct()
  {
    $this->descriptions = new ArrayCollection();
    $this->parts = new ArrayCollection();
    $this->files = new ArrayCollection();
  }

  /**
   * Set id
   *
   * @param integer $id
   */
  public function setId($id)
  {
    $this->id = $id;
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
   * Set extId
   *
   * @param bigint $extId
   */
  public function setExtId($extId)
  {
    $this->extId = $extId;
  }

  /**
   * Get extId
   *
   * @return bigint $extId
   */
  public function getExtId()
  {
    return $this->extId;
  }

  /**
   * Set propertyId
   *
   * @param bigint $propertyId
   */
  public function setPropertyId($propertyId)
  {
    $this->propertyId = $propertyId;
  }

  /**
   * Get propertyId
   *
   * @return bigint $propertyId
   */
  public function getPropertyId()
  {
    return $this->propertyId;
  }

  /**
   * Set price
   *
   * @param bigint $price
   */
  public function setPrice($price)
  {
    $this->price = $price;
  }

  /**
   * Get price
   *
   * @return bigint $price
   */
  public function getPrice()
  {
    return $this->price;
  }

  /**
   * Set itmRef
   *
   * @param text $itmRef
   */
  public function setItmRef($itmRef)
  {
    $this->itmRef = $itmRef;
  }

  /**
   * Get itmRef
   *
   * @return text $itmRef
   */
  public function getItmRef()
  {
    return $this->itmRef;
  }

  /**
   * Set status
   *
   * @param string $status
   */
  public function setStatus($status)
  {
    $this->status = $status;
  }

  /**
   * Get status
   *
   * @return string $status
   */
  public function getStatus()
  {
    return $this->status;
  }

  /**
   * Set expiryStatus
   *
   * @param string $expiryStatus
   */
  public function setExpiryStatus($expiryStatus)
  {
    $this->expiryStatus = $expiryStatus;
  }

  /**
   * Get expiryStatus
   *
   * @return string $expiryStatus
   */
  public function getExpiryStatus()
  {
    return $this->expiryStatus;
  }

  /**
   * Set companyId
   *
   * @param bigint $companyId
   */
  public function setCompanyId($companyId)
  {
    $this->companyId = $companyId;
  }

  /**
   * Get companyId
   *
   * @return bigint $companyId
   */
  public function getCompanyId()
  {
    return $this->companyId;
  }

  /**
   * Set secCompanyId
   *
   * @param bigint $secCompanyId
   */
  public function setSecCompanyId($secCompanyId)
  {
    $this->secCompanyId = $secCompanyId;
  }

  /**
   * Get secCompanyId
   *
   * @return bigint $secCompanyId
   */
  public function getSecCompanyId()
  {
    return $this->secCompanyId;
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
   * Set address
   *
   * @param text $address
   */
  public function setAddress($address)
  {
    $this->address = $address;
  }

  /**
   * Get address
   *
   * @return text $address
   */
  public function getAddress()
  {
    return $this->address;
  }

  /**
   * Set zip
   *
   * @param string $zip
   */
  public function setZip($zip)
  {
    $this->zip = $zip;
  }

  /**
   * Get zip
   *
   * @return string $zip
   */
  public function getZip()
  {
    return $this->zip;
  }

  /**
   * Set city
   *
   * @param text $city
   */
  public function setCity($city)
  {
    $this->city = $city;
  }

  /**
   * Get city
   *
   * @return text $city
   */
  public function getCity()
  {
    return $this->city;
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
   * Set gnr
   *
   * @param integer $gnr
   */
  public function setGnr($gnr)
  {
    $this->gnr = $gnr;
  }

  /**
   * Get gnr
   *
   * @return integer $gnr
   */
  public function getGnr()
  {
    return $this->gnr;
  }

  /**
   * Set bnr
   *
   * @param integer $bnr
   */
  public function setBnr($bnr)
  {
    $this->bnr = $bnr;
  }

  /**
   * Get bnr
   *
   * @return integer $bnr
   */
  public function getBnr()
  {
    return $this->bnr;
  }

  /**
   * Set snr
   *
   * @param integer $snr
   */
  public function setSnr($snr)
  {
    $this->snr = $snr;
  }

  /**
   * Get snr
   *
   * @return integer $snr
   */
  public function getSnr()
  {
    return $this->snr;
  }

  /**
   * Set fnr
   *
   * @param integer $fnr
   */
  public function setFnr($fnr)
  {
    $this->fnr = $fnr;
  }

  /**
   * Get fnr
   *
   * @return integer $fnr
   */
  public function getFnr()
  {
    return $this->fnr;
  }

  /**
   * Set energyGrade
   *
   * @param string $energyGrade
   */
  public function setEnergyGrade($energyGrade)
  {
    $this->energyGrade = $energyGrade;
  }

  /**
   * Get energyGrade
   *
   * @return string $energyGrade
   */
  public function getEnergyGrade()
  {
    return $this->energyGrade;
  }

  /**
   * Set heatingGrade
   *
   * @param string $heatingGrade
   */
  public function setHeatingGrade($heatingGrade)
  {
    $this->heatingGrade = $heatingGrade;
  }

  /**
   * Get heatingGrade
   *
   * @return string $heatingGrade
   */
  public function getHeatingGrade()
  {
    return $this->heatingGrade;
  }

  /**
   * Set parkingSpaces
   *
   * @param integer $parkingSpaces
   */
  public function setParkingSpaces($parkingSpaces)
  {
    $this->parkingSpaces = $parkingSpaces;
  }

  /**
   * Get parkingSpaces
   *
   * @return integer $parkingSpaces
   */
  public function getParkingSpaces()
  {
    return $this->parkingSpaces;
  }

  /**
   * Set availabilityTypeId
   *
   * @param integer $availabilityTypeId
   */
  public function setAvailabilityTypeId($availabilityTypeId)
  {
    $this->availabilityTypeId = $availabilityTypeId;
  }

  /**
   * Get availabilityTypeId
   *
   * @return integer $availabilityTypeId
   */
  public function getAvailabilityTypeId()
  {
    return $this->availabilityTypeId;
  }

  /**
   * Set minAreaFree
   *
   * @param bigint $minAreaFree
   */
  public function setMinAreaFree($minAreaFree)
  {
    $this->minAreaFree = $minAreaFree;
  }

  /**
   * Get minAreaFree
   *
   * @return bigint $minAreaFree
   */
  public function getMinAreaFree()
  {
    return $this->minAreaFree;
  }

  /**
   * Set totalAreaFree
   *
   * @param bigint $totalAreaFree
   */
  public function setTotalAreaFree($totalAreaFree)
  {
    $this->totalAreaFree = $totalAreaFree;
  }

  /**
   * Get totalAreaFree
   *
   * @return bigint $totalAreaFree
   */
  public function getTotalAreaFree()
  {
    return $this->totalAreaFree;
  }

  /**
   * Set fCompany
   *
   * @param text $fCompany
   */
  public function setFCompany($fCompany)
  {
    $this->fCompany = $fCompany;
  }

  /**
   * Get fCompany
   *
   * @return text $fCompany
   */
  public function getFCompany()
  {
    return $this->fCompany;
  }

  /**
   * Set fAddress
   *
   * @param text $fAddress
   */
  public function setFAddress($fAddress)
  {
    $this->fAddress = $fAddress;
  }

  /**
   * Get fAddress
   *
   * @return text $fAddress
   */
  public function getFAddress()
  {
    return $this->fAddress;
  }

  /**
   * Set fPerson
   *
   * @param text $fPerson
   */
  public function setFPerson($fPerson)
  {
    $this->fPerson = $fPerson;
  }

  /**
   * Get fPerson
   *
   * @return text $fPerson
   */
  public function getFPerson()
  {
    return $this->fPerson;
  }

  /**
   * Set fRef
   *
   * @param text $fRef
   */
  public function setFRef($fRef)
  {
    $this->fRef = $fRef;
  }

  /**
   * Get fRef
   *
   * @return text $fRef
   */
  public function getFRef()
  {
    return $this->fRef;
  }

  /**
   * Set fZip
   *
   * @param string $fZip
   */
  public function setFZip($fZip)
  {
    $this->fZip = $fZip;
  }

  /**
   * Get fZip
   *
   * @return string $fZip
   */
  public function getFZip()
  {
    return $this->fZip;
  }

  /**
   * Set fCity
   *
   * @param text $fCity
   */
  public function setFCity($fCity)
  {
    $this->fCity = $fCity;
  }

  /**
   * Get fCity
   *
   * @return text $fCity
   */
  public function getFCity()
  {
    return $this->fCity;
  }

  /**
   * Set mapx
   *
   * @param string $mapx
   */
  public function setMapx($mapx)
  {
    $this->mapx = $mapx;
  }

  /**
   * Get mapx
   *
   * @return string $mapx
   */
  public function getMapx()
  {
    return $this->mapx;
  }

  /**
   * Set mapy
   *
   * @param string $mapy
   */
  public function setMapy($mapy)
  {
    $this->mapy = $mapy;
  }

  /**
   * Get mapy
   *
   * @return string $mapy
   */
  public function getMapy()
  {
    return $this->mapy;
  }

  /**
   * Set startDate
   *
   * @param datetime $startDate
   */
  public function setStartDate($startDate)
  {
    $this->startDate = $startDate;
  }

  /**
   * Get startDate
   *
   * @return datetime $startDate
   */
  public function getStartDate()
  {
    return $this->startDate;
  }

  /**
   * Set endDate
   *
   * @param datetime $endDate
   */
  public function setEndDate($endDate)
  {
    $this->endDate = $endDate;
  }

  /**
   * Get endDate
   *
   * @return datetime $endDate
   */
  public function getEndDate()
  {
    return $this->endDate;
  }

  /**
   * Set createdAt
   *
   * @param datetime $createdAt
   */
  public function setCreatedAt($createdAt)
  {
    $this->createdAt = $createdAt;
  }

  /**
   * Get createdAt
   *
   * @return datetime $createdAt
   */
  public function getCreatedAt()
  {
    return $this->createdAt;
  }

  /**
   * Set modifiedAt
   *
   * @param datetime $modifiedAt
   */
  public function setModifiedAt($modifiedAt)
  {
    $this->modifiedAt = $modifiedAt;
  }

  /**
   * Get modifiedAt
   *
   * @return datetime $modifiedAt
   */
  public function getModifiedAt()
  {
    return $this->modifiedAt;
  }

  /**
   * Add descriptions
   *
   * @param Ne\NeBundle\Entity\PropertyDescription $descriptions
   */
  public function addDescriptions(\Ne\NeBundle\Entity\PropertyDescription $descriptions)
  {
    $this->descriptions[] = $descriptions;
  }

  /**
   * Get descriptions
   *
   * @return Doctrine\Common\Collections\Collection $descriptions
   */
  public function getDescriptions()
  {
    return $this->descriptions;
  }

  /**
   * Add parts
   *
   * @param Ne\NeBundle\Entity\PropertyPart $parts
   */
  public function addParts(\Ne\NeBundle\Entity\PropertyPart $parts)
  {
    $this->parts[] = $parts;
  }

  /**
   * Get parts
   *
   * @return Doctrine\Common\Collections\Collection $parts
   */
  public function getParts()
  {
    return $this->parts;
  }

  /**
   * Get parts types (for search index)
   *
   * @return array|false $partTypeId
   */
  public function getSearchPartTypes()
  {
    $parts = $this->getParts();
    if (count($parts)) {
      foreach ($parts as $part) {
        $types[] = $part->getTypeId();
      }
      //echo count($types)."\n";
      return $types;
    }
    return false;
  }

  /**
   * Add files
   *
   * @param Ne\NeBundle\Entity\PropertyFile $files
   */
  public function addFiles(\Ne\NeBundle\Entity\PropertyFile $files)
  {
    $this->files[] = $files;
  }

  /**
   * Get files
   *
   * @return Doctrine\Common\Collections\Collection $files
   */
  public function getFiles()
  {
    return $this->files;
  }

  /**
   * Get files with type "image"
   *
   * @return Doctrine\Common\Collections\ArrayCollection $images
   */
  public function getImages()
  {
    return $this->files->filter( function ($item) { return $item->getType() == "image"; } );
  }

  /**
   * Get files with type "document"
   *
   * @return Doctrine\Common\Collections\ArrayCollection $document
   */
  public function getDocuments()
  {
    return $this->files->filter( function ($item) { return $item->getType() == "document"; } );
  }

}