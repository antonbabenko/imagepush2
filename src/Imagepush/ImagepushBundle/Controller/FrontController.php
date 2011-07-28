<?php

namespace Imagepush\ImagepushBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\ORM\EntityRepository;

class FrontController extends Controller
{

  /**
   * @Route("/vis/{id}", name="viewProperty")
   * @Template()
   */
  public function viewPropertyAction($id)
  {
    // Access legacy DB:
    /*
      $property = $this->get('doctrine')
      ->getEntityManager('legacy')
      ->getRepository('NeLegacyBundle:Property')
      ->findOneById($id);
     */

    // Access correct DB:
    $property = $this->get('doctrine')
      ->getEntityManager()
      ->getRepository('NeBundle:Property')
      ->findOneByPropertyId($id);

    if (!$property)
    {
      throw new NotFoundHttpException('The property does not exist.');
    }

    //$descriptions = $property->getDescriptions();
    //$a = $descriptions[0]->getName();
    //$parts = $property->getParts();
    //$a = $parts[0]->getName();
    //$images = $property->getImages();
    //\D::dump($images);
    //$a = $images[0]->getFilename();
    //\D::dump($parts[0]->get);
    //\D::dump($parts[0]->getTypeId());

    return array("property" => $property, "propertyAvailability" => new PropertyAvailability);
  }

  /**
   * @Route("/", name="index")
   * @Template()
   */
  public function indexAction()
  {
/*
    $searchForm = $this->createFormBuilder()
      //->add('name', 'text')
      ->add('county', 'entity', array(
        'class' => 'NeBundle:County',
        'query_builder' => function(EntityRepository $er)
        {
          return $er->createQueryBuilder('c')->orderBy('c.id', 'ASC');
        },
        'multiple' => true,
        'expanded' => true,
      ))
      ->add('type', 'entity', array(
        'class' => 'NeBundle:PropertyType',
        'query_builder' => function(EntityRepository $er)
        {
          return $er->createQueryBuilder('t')->where('t.id <= 4')->orderBy('t.id', 'ASC');
        },
        'property' => 'name',
        'multiple' => true,
        'expanded' => true,
      ))
      ->add('from_size', 'integer', array(
        'required' => false,
      ))
      ->add('to_size', 'integer', array(
        'required' => false,
      ))
      ->getForm();

    return array('search' => $searchForm->createView());*/
    return array();
  }

  /**
   * @Route("/search", name="searchResult")
   * @Template()
   */
  public function searchResultAction()
  {

    $propertyFinder = $this->get('foq_elastica.finder.website.property');
    $municipalityFinder = $this->get('foq_elastica.finder.website.municipality');

    $form = $this->get('request')->get('form');

    $queryFields = array();

    if (!empty($form["county"]))
    {

      $counties = array_filter(
        array_map(
          function($value)
          {
            return (is_int($value) && $value >= 1 && $value <= 19 ? "countyId:" . $value : false);
          }, array_keys($form["county"])
        )
      );

      // Get all municipalities in specified counties
      if (!empty($counties))
      {
        //\D::dump($counties);

        $countiesQueryString = implode(' ', $counties);

        $q = new Elastica_Query(new Elastica_Query_QueryString($countiesQueryString));
        $q->setSize(99999);

        $municipalities = $municipalityFinder->find($q);

        //\D::dump($q->getQuery());
        //\D::dump($municipalities);

        if (count($municipalities))
        {
          foreach ($municipalities as $oneMunicipality) {
            $municipalityIds[] = $oneMunicipality->getMunicipalityId();
          }
          //\D::dump($municipalityIds);
        }
      }
    }

    // Property types
    if (!empty($form["type"]))
    {

      $types = array_filter(array_map(function($value)
          {
            return (is_int($value) && $value >= 1 && $value <= 15 ? $value : false);
          }, array_keys($form["type"])));

      //\D::dump($types);
    }


    //\D::dump($queryFields);


    $queryString = new \Elastica_Query_MatchAll();
    //$queryString->setDefaultOperator("AND");
    //$queryString->setQueryString('name:Oslo'); //implode(' OR ', $municipalityFields));//  . " AND name:Oslo");

    $filter = new \Elastica_Filter_And();
    if (!empty($municipalityIds))
    {
      $filter1 = new \Elastica_Filter_Or();
      foreach ($municipalityIds as $municipalityId) {
        $municipalityFilter = new \Elastica_Filter_Term(array('municipalityId' => $municipalityId));
        $filter1->addFilter($municipalityFilter);
      }
      $filter->addFilter($filter1);
    }

    if (!empty($types))
    {
      $filter2 = new \Elastica_Filter_Or();
      foreach ($types as $type) {
        $typeFilter = new \Elastica_Filter_Term(array('searchPartTypes' => $type));
        $filter2->addFilter($typeFilter);
      }
      $filter->addFilter($filter2);
    }

    $query = new \Elastica_Query();
    $query->setQuery($queryString);
    
    if (!empty($filter1) || !empty($filter2)) {
      $query->setFilter($filter);
    }

    //\D::dump($query->getQuery());

    //$query->setQuery($queryString);
    //$query->setFilter($typeFilter);
    //$query->setSize(3);
    //$query->setSort(array("createdAt" => "asc"));
//$query->setExplain(true);


    $facet = new \Elastica_Facet_Range('price');
    $facet->setField("price");
    $facet->setGlobal(false);
    $facet->setRanges(array(
      array("from" => 0, "to" => 2000000),
      array("from" => 2000001, "to" => 5000000),
      array("from" => 5000001, "to" => 10000000),
      array("from" => 10000001, "to" => 20000000),
      array("from" => 20000001, "to" => 30000000),
      array("from" => 30000001, "to" => 40000000),
      array("from" => 40000001),
    ));
    
    // totalSize is from 20 to 20000, if no size has been defined

    $query->addFacet($facet);

    //\D::dump($query->toArray());
    //$results = $propertyFinder->find('name:oslo', 20);

    $results = $propertyFinder->find($query);
    $count = $this->get('foq_elastica.index.website.property')->count($query);

    $search = $this->get('foq_elastica.index.website.property')->search($query);

    //\D::dump($facet->toArray());
    //\D::dump($search->getFacets());
    //\D::dump($results);
    //\D::dump($search->getResults());

    return array("results" => $results, "count" => $count, "facets" => $search->getFacets());
  }

}
