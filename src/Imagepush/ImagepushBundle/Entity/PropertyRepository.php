<?php

namespace Ne\NeBundle\Entity;

use Doctrine\ORM\EntityRepository;

class PropertyRepository extends EntityRepository
{
  /**
     * @return QueryBuilder
     */
    public function createIsActiveQueryBuilder()
    {
        $queryBuilder = $this->_em
            ->createQueryBuilder()
            ->select('p')
            ->from('NeBundle:Property', 'p')
            ->where('p.status = ?1')
            ->setParameter('1', 'live')
            ->orderBy('p.id', 'DESC');

        return $queryBuilder;
    }
}