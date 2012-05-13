<?php

namespace Imagepush\ImagepushBundle\Document;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Imagepush\ImagepushBundle\Document\Link;

class LinkRepository extends DocumentRepository
{

    /**
     * Whether link is empty or already indexed/failed.
     * 
     * @param string $link
     * 
     * @return boolean
     */
    public function isIndexedOrFailed($link = "")
    {

        if (empty($link)) {
            return true;
        }

        $count = $this->createQueryBuilder()
            ->field('link')->equals($link)
            ->field('status')->in(array(Link::INDEXED, Link::FAILED, Link::BLOCKED))
            ->getQuery()
            ->count();

        return (boolean) $count;
    }

}