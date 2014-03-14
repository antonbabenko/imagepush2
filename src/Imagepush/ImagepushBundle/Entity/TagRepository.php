<?php

namespace Imagepush\ImagepushBundle\Entity;

use Doctrine\ORM\EntityRepository;

class TagRepository extends EntityRepository
{

    /**
     * @param string $text
     *
     * @return Tag|null
     */
    public function findOneByText($text)
    {
        $result = $this->createQueryBuilder('t')
            ->where('t.text = :text')
            ->setParameter('text', $text)
            ->getQuery()
            ->setMaxResults(1)
            ->setResultCacheLifetime(600)
            ->getOneOrNullResult();

        return $result;
    }

}
