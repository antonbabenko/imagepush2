<?php

namespace Imagepush\ImagepushBundle\Entity;

use Doctrine\ORM\EntityRepository;

class ImageRepository extends EntityRepository
{

    /**
     * Find one image by ID
     *
     * @param integer $id
     * @param bool    $onlyAvailable
     *
     * @return Image
     */
    public function findOneImageBy($id, $onlyAvailable = true)
    {
        $result = $this->createQueryBuilder('i')
            ->select('i, t')
            ->join('i.tags', 't')
            ->where('i.id = :id')
            ->andWhere('i.available = :available')
            ->setParameter('id', $id)
            ->setParameter('available', (bool) $onlyAvailable)
            ->getQuery()
            ->setResultCacheLifetime(600)
            ->getOneOrNullResult();

        return $result;
    }

    /**
     * Find images
     *
     * @param string $type
     * @param int    $limit
     * @param array  $params
     *
     * @throws \ErrorException
     *
     * @return array()
     */
    public function findImages($type, $limit = 20, $params = [])
    {

        if (!in_array($type, array('current', 'upcoming'))) {
            throw new \ErrorException(sprintf('Incorrect image type: %s', $type));
        }

        if (!is_array($params)) {
            throw new \ErrorException(sprintf('Params should be an array, but %s given', gettype($params)));
        }

        $limit = (intval($limit) ?: 20);

        extract($params);

        $imageIds = null;

        // Get list of image IDs before fetching complete images if filtering by tags
        if (isset($tag)) {
            $imageIds = $this->_em->createQueryBuilder()
                ->select('i.id')
                ->from('ImagepushBundle:Image', 'i')
                ->join('i.tags', 't')
                ->andWhere('t.text in (:text)')
                ->andWhere('i.available = :available')
                ->setParameter('text', $tag)
                ->setParameter('available', (int) ($type == 'current'))
                ->getQuery()
                ->setResultCacheLifetime(600)
                ->getScalarResult()
            ;

            if (false == $imageIds = array_column($imageIds, 'id')) {
                return [];
            }
        }

        $query = $this->createQueryBuilder('i')
            ->select('i, t')
            ->join('i.tags', 't')
            ->orderBy('i.createdAt', 'DESC')
            ->addOrderBy('i.id', 'DESC')
        ;

        if ($imageIds) {
            // Add filter by retrieved image IDs
            $query = $query
                ->andWhere('i.id in (:ids)')
                ->setParameter('ids', $imageIds)
            ;
        } else {
            // Add filter by availability
            $query = $query
                ->andWhere('i.available = :available')
                ->setParameter('available', (int) ($type == 'current'))
            ;
        }

        $result = $query
            ->getQuery()
            ->setMaxResults($limit)
            ->setResultCacheLifetime(600)
            ->execute();

        return $result;
    }

    /**
     * Returns one previous or next image related to provided object (DateTime or Image)
     *
     * @param string $direction One of 'prev' or 'next'
     * @param mixed  $object    Image or DateTime object to compare with
     *
     * @return Image|null
     */
    public function findOneImageRelatedToObject($direction, $object = null)
    {

        if (!in_array($direction, ['next', 'prev'])) {
            return null;
        }

        $imageId = null;

        if ($object instanceof Image) {
            $datetime = $object->getCreatedAt();
            $imageId = $object->getId();
        } elseif ($object instanceof \DateTime) {
            $datetime = $object;
        } else {
            $datetime = new \DateTime();
        }

        $query = $this->createQueryBuilder('i');

        // Compare created_at
        if ('next' == $direction) {
            $query = $query
                ->where('i.createdAt >= :datetime')
                ->orderBy('i.createdAt', 'ASC')
                ->addOrderBy('i.id', 'DESC')
            ;
        } else {
            $query = $query
                ->where('i.createdAt <= :datetime')
                ->orderBy('i.createdAt', 'DESC')
                ->addOrderBy('i.id', 'ASC')
            ;
        }

        // Compare image id, because created_at can be equal
        if ($imageId) {
            if ('next' == $direction) {
                $query = $query->andWhere('i.id < :id');
            } else {
                $query = $query->andWhere('i.id > :id');
            }
            $query = $query
                ->setParameter('id', $imageId)
            ;
        }

        return $query
            ->andWhere('i.available = :available')
            ->setParameter('available', true)
            ->setParameter('datetime', $datetime)
            ->getQuery()
            ->setMaxResults(1)
            ->setResultCacheLifetime(600)
            ->getOneOrNullResult();
    }

    /**
     * Get oldest unprocessed image and change status for it to "in process"
     *
     * @return Image|false
     */
    public function findUnprocessedSource()
    {
        $image = $this->createQueryBuilder('i')
            ->andWhere('i.inProcess = :inProcess')
            ->andWhere('i.available = :available')
            ->orderBy('i.createdAt', 'ASC')
            ->setParameter('inProcess', false)
            ->setParameter('available', false)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $image;
    }

}
