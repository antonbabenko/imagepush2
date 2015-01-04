<?php

namespace Imagepush\ImagepushBundle\Entity;

use Doctrine\ORM\EntityRepository;

class LatestTagRepository extends EntityRepository
{

    public function getLatestTrends($limit)
    {

        $fromDate = date_format(new \DateTime('-6 month'), 'Y-m-d H:m:00');

        $result = $this->createQueryBuilder('l')
            ->select('count(l.id) as counter, l.createdAt, max(l.createdAt) as latest_datetime, t.text')
            ->innerJoin('l.tag', 't')
            ->where('l.createdAt > :from_date')
            ->orderBy('latest_datetime', 'DESC')
            ->groupBy('l.tag')
            ->having('counter > 1')
            ->setParameter('from_date', $fromDate)
            ->setMaxResults($limit)
            ->getQuery()
            ->setResultCacheId('latest_trends')
            ->setResultCacheLifetime(60)
            ->execute();

        return $result;
    }

}
