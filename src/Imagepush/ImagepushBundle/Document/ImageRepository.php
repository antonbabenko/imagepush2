<?php

namespace Imagepush\ImagepushBundle\Document;

use Doctrine\ODM\MongoDB\DocumentRepository;

class ImageRepository extends DocumentRepository
{

    public function findAllOrderedByName()
    {
        return $this->createQueryBuilder()
                ->sort('name', 'ASC')
                ->getQuery()
                ->execute();
    }

    /**
     * @return array()
     */
    public function findImages($type, $limit = 20, $params = array())
    {

        if (!in_array($type, array("current", "upcoming"))) {
            throw new \ErrorException(sprintf("Incorrect image type: %s", $type));
        }

        if (!is_array($params)) {
            throw new \ErrorException(sprintf("Params should be an array, but %s given", gettype($params)));
        }

        //\D::dump($params);
        extract($params);

        $query = $this->createQueryBuilder();

        // Tag or tags
        if (isset($tag)) {
            $query = $query
                    ->field('tags')->in((array) $tag);
        }

        $query = $query
            ->field('isAvailable')->equals($type == "current")
            ->sort('timestamp', 'DESC')
            ->limit($limit)
            ->getQuery();

        return $query->toArray();
    }

    /**
     * @return array()|false
     */
    /* public function getOneImage($id)
      {

      $key = $this->getImageKey($id);

      if ($this->redis->sismember('available_images', $key)) {
      $image = $this->redis->hgetall($key);
      return $this->normalizeImage($image);
      } else {
      return false;
      }
      } */

    /**
     * @return array()|false
     */
    public function getOneImageRelatedToTimestamp($direction, $timestamp)
    {

        if (!$timestamp || !in_array($direction, array("next", "prev"))) {
            return false;
        }

        $query = $this->createQueryBuilder();

        if ($direction == "next") {
            $query = $query
                ->field('timestamp')->gt($timestamp)
                ->sort('timestamp', 'ASC');
        } else {
            $query = $query
                ->field('timestamp')->lt($timestamp)
                ->sort('timestamp', 'DESC');
        }

        $query = $query
            ->field('isAvailable')->equals(true)
            ->getQuery();

        return $query->getSingleResult();
    }

    /**
     * @return int|false
     */
    public function getNextId()
    {
        $maxId = $this->createQueryBuilder()
            ->sort('id', 'DESC')
            ->limit(1)
            ->getQuery()
            ->getSingleResult();

        if ($maxId && $maxId->getId()) {
            return $maxId->getId() + 1;
        } else {
            return false;
        }
    }

    /**
     * Get unprocessed image and change status for it to "in process"
     * 
     * @return Image|false
     */
    public function initUnprocessedSource($isDebug = false)
    {
        $image = $this->createQueryBuilder()
            ->field('isAvailable')->exists(false)
            ->field('isInProcess')->notEqual(true)
            ->sort('timestamp', 'DESC')
            ->limit(1)
            ->getQuery()
            ->getSingleResult();

        if ($image) {

            /**
             * There is no database update in debug mode 
             */
            if (!$isDebug) {
                $image->setIsInProcess(true);

                $this->dm->persist($image);
                $this->dm->flush();
                $this->dm->refresh($image);
            }

            return $image;
        } else {
            return false;
        }
    }

}