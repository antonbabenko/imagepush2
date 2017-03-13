<?php

namespace Imagepush\ImagepushBundle\Repository;

use Imagepush\ImagepushBundle\Document\Link;

class LinkRepository extends AbstractRepository
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

        $request = [
            'TableName' => 'links',
            'Key' => [
                'link' => [
                    'S' => strval((int) $link)
                ]
            ],
            'ConsistentRead' => true
        ];

        $result = $this->getItemResult($request, 1);

        if ($result == null) {
            return false;
        }

        $status = array_values($result['status'])[0];

        if (in_array($status, [Link::INDEXED, Link::FAILED, Link::BLOCKED])) {
            return true;
        }

        return false;
    }

    /**
     * @param  Link    $link
     * @return boolean
     */
    public function save(Link $link)
    {

        $request = [
            'TableName' => 'links',
            'Item' => $link->toItem()
        ];

//        echo "\nInserting item:\n";
//        \D::debug($request);

        $result = $this->putItem($request);

        return $result;

    }
}
