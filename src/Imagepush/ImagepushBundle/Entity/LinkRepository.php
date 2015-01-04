<?php

namespace Imagepush\ImagepushBundle\Entity;

use Doctrine\ORM\EntityRepository;

class LinkRepository extends EntityRepository
{

    /**
     * Whether link is empty or already indexed/failed.
     *
     * @param string $link
     *
     * @return boolean
     */
    public function hasBeenSeen($link = null)
    {

        if (empty($link)) {
            return true;
        }

        $result = $this->createQueryBuilder('l')
            ->where('l.link = :link')
            ->setParameter('link', $link)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return null !== $result;
    }
}
