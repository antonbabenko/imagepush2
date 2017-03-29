<?php

namespace Imagepush\ImagepushBundle\Repository;

use Imagepush\ImagepushBundle\Document\ProcessedHash;

class ProcessedHashRepository extends AbstractRepository
{

    /**
     * @param  ProcessedHash $hash
     * @return boolean
     */
    public function save(ProcessedHash $hash)
    {

        $request = [
            'TableName' => 'processed_hashes',
            'Item' => $hash->toItem()
        ];

        $result = $this->putItem($request);

        return $result;

    }

    /**
     * Whether hash was processed or not.
     *
     * @param string $hash
     *
     * @return boolean
     */
    public function exists($hash = "")
    {

        if (empty($hash)) {
            return true;
        }

        $request = [
            'TableName' => 'processed_hashes',
            'Key' => [
                'hash' => [
                    'S' => strval($hash)
                ]
            ],
            'ConsistentRead' => true
        ];

        $result = $this->getItemResult($request);

        if ($result == null) {
            return false;
        }

        return true;
    }

}
