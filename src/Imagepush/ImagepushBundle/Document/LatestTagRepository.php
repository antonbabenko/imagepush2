<?php

namespace Imagepush\ImagepushBundle\Document;

use Doctrine\ODM\MongoDB\DocumentRepository;

class LatestTagRepository extends DocumentRepository
{

  public function getLatestTrends($max)
  {
    $tmpTags = $this->createQueryBuilder()
      ->sort('timestamp', 'DESC')
      ->limit($max * 20)
      ->getQuery()
      ->toArray();

    if (!count($tmpTags))
    {
      return array();
    }

    $tmpTags = array_values($tmpTags);

    foreach ($tmpTags as $tmpTag) {
      $tag = $tmpTag->getTag()->getText();
      $tags[$tag] = (empty($tags[$tag]) ? 1 : $tags[$tag] + 1);
    }

    arsort($tags);

    $tags = array_slice($tags, 0, $max, true);

    return $tags;
  }

}