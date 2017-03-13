<?php

namespace Imagepush\ImagepushBundle\Command;

use Aws\DynamoDb\Exception\DynamoDbException;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateDataToDynamoDBCommand extends ContainerAwareCommand
{
    /**
     * @var $dm DocumentManager
     */
    protected $dm;

    /**
     * @var $ddb \Aws\DynamoDb\DynamoDbClient
     */
    protected $ddb;

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('imagepush:migrate-data-to-dynamodb')
            ->setDescription('Migrate all documents from mongo to dynamodb')
            ->setHelp('This should be safe to run as often as you want :)');
    }

    /**
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
        $this->ddb = $this->getContainer()->get('aws.dynamodb');

        // Tables
//        $tables = ["images", "latest_tags", "links", "processed_hashes", "tags"];
//        $createdTables = $ddb->listTables()->get('TableNames');
//        $missingTables = array_diff($tables, $createdTables);
//        \D::debug($missingTables);

//        foreach ($ddb->listTables()->get('TableNames') as $item) {
//            \D::dump($item);
//        }

        printf("%s - Started\n", date(DATE_ATOM));

//        $this->importImages();

//        $this->importTags();

//        $this->importLatestTags();

//        $this->importImagesTags();

//        $this->importLinks();

//        $this->importProcessedHashes();

        printf("%s - Finished\n", date(DATE_ATOM));
    }

    public function importProcessedHashes()
    {
        $tableName = 'processed_hashes';
        $items = [];
        $count = 0;
        $imported = [];

        $hashes = $this->dm->createQueryBuilder('ImagepushBundle:ProcessedHash')
            ->sort('hash', 'ASC')
            ->getQuery()->toArray();

        if (!count($hashes)) {
            echo "OMG! No hashes found :)";

            return;
        }

        printf("%s - Results: %d\n", date(DATE_ATOM), count($hashes));

        foreach ($hashes as $hash) {
            if (in_array($hash->getHash(), $imported)) {
                echo "DUPLICATE HASH=".$hash->getLink()."\n";
                continue;
            }
            $imported[] = $hash->getHash();
            if (count($imported) > 2) {
                array_shift($imported);
            }

            if ("" == $hash->getHash()) {
                echo "empty hash".$hash->getMongoId()."\n";
                continue;
            }

            if ($count++ % 100 == 0) {
                echo "NUM=" . $count . " => " . strval($hash->getHash()) . "\n";
            }

            $item = [
                'hash' => [
                    'S' => strval($hash->getHash())
                ],
            ];

            $items[] = [
                'PutRequest' => [
                    'Item' => $item
                ]
            ];

            if (count($items) % 25 == 0) {
                $this->batchWriteItem($tableName, $items, $hash->getHash());
                $items = [];
            }
        }

        // final save
        if (count($items)) {
            $this->batchWriteItem($tableName, $items);
        }
    }

    public function importLinks()
    {
        $tableName = 'links';
        $items = [];
        $count = 0;
        $imported = [];

        $links = $this->dm->createQueryBuilder('ImagepushBundle:Link')
            ->sort('link', 'ASC')
            ->getQuery()->toArray();

        if (!count($links)) {
            echo "OMG! No links found :)";

            return;
        }

        printf("%s - Results: %d\n", date(DATE_ATOM), count($links));

        foreach ($links as $link) {
            if (in_array($link->getLink(), $imported)) {
                echo "DUPLICATE LINK=".$link->getLink()."\n";
                continue;
            }
            $imported[] = $link->getLink();
            if (count($imported) > 2) {
                array_shift($imported);
            }

            if ($count++ % 100 == 0) {
                echo "NUM=" . $count . " => " . strval($link->getLink()) . "\n";
            }

            $item = [
                'link' => [
                    'S' => strval($link->getLink())
                ],
                'status' => [
                    'S' => strval($link->getStatus())
                ],
            ];

            $items[] = [
                'PutRequest' => [
                    'Item' => $item
                ]
            ];

            if (count($items) % 25 == 0) {
                $this->batchWriteItem($tableName, $items, $link->getLink());
                $items = [];
            }
        }

        // final save
        if (count($items)) {
            $this->batchWriteItem($tableName, $items);
        }
    }

    public function importLatestTags()
    {
        $tableName = 'latest_tags';
        $items = [];
//        $imported = [];
        $count = 0;

        $tags = $this->dm->createQueryBuilder('ImagepushBundle:LatestTag')
//            ->field('text')->equals('technology')
            ->sort('timestamp', 'ASC')
            ->getQuery()->toArray();

        if (!count($tags)) {
            echo "OMG! No tags found :)";

            return;
        }

        printf("%s - Results: %d\n", date(DATE_ATOM), count($tags));

        foreach ($tags as $tag) {
            if ($count++ % 100 == 0) {
                echo "NUM=" . $count . " => " . strval($tag->getText()) . "\n";
            }

//            if (in_array(strval($tag->getText()), $imported)) {
//                continue;
//            }
//            $imported[] = strval($tag->getText());

            $item = [
                'id' => [
                    'B' => base64_encode($tag->getId())
                ],
                'timestamp' => [
                    'N' => (string) $tag->getTimestamp()->sec
                ],
                'text' => [
                    'S' => strval($tag->getText())
                ],
            ];

            $items[] = [
                'PutRequest' => [
                    'Item' => $item
                ]
            ];

            if (count($items) % 25 == 0) {
                $this->batchWriteItem($tableName, $items, $tag->getText());
                $items = [];
            }
        }

        // final save
        if (count($items)) {
            $this->batchWriteItem($tableName, $items);
        }

    }

    public function importTags()
    {
        $tableName = 'tags';
        $items = [];
        $imported = [];
        $count = 0;

        $tags = $this->dm->createQueryBuilder('ImagepushBundle:Tag')
//            ->field('text')->equals('rulez')
            ->sort('text', 'ASC')
//            ->skip(13210)
            ->getQuery()->toArray();

        if (!count($tags)) {
            echo "OMG! No tags found :)";

            return;
        }

        printf("%s - Results: %d\n", date(DATE_ATOM), count($tags));

        foreach ($tags as $tag) {
            if ($count++ % 100 == 0) {
                echo "NUM=" . $count . " => " . strval($tag->getText()) . "\n";
            }

            if (in_array(strval($tag->getText()), $imported)) {
                continue;
            }
            $imported[] = strval($tag->getText());

            $item = [
                'text' => [
                    'S' => strval($tag->getText())
                ],
                'usedInAvailable' => [
                    'N' => strval((int) $tag->getUsedInAvailable())
                ],
                'usedInUpcoming' => [
                    'N' => strval((int) $tag->getUsedInUpcoming())
                ],
            ];

            $items[] = [
                'PutRequest' => [
                    'Item' => $item
                ]
            ];

            if (count($items) > 0 && count($items) % 25 == 0) {
                $this->batchWriteItem($tableName, $items, $tag->getText());
                $items = [];
            }
        }

        // final save
        if (count($items)) {
            $this->batchWriteItem($tableName, $items);
        }

    }

    public function importImagesTags()
    {
        $tableName = 'images_tags';
        $importedId = 0;
        $runs = 0;
        $items = [];

        while (true) {
            $images = $this->dm->createQueryBuilder('ImagepushBundle:Image')
                ->field('id')->gt($importedId)
                ->sort('id', 'ASC')
                ->limit(25 * 10)
                ->getQuery()->toArray();

            if (!count($images) || $runs++ >= 100000) {
                break;
            }

            printf("%s - Results: %d. Run: %d\n", date(DATE_ATOM), count($images), $runs);

            foreach ($images as $image) {
                $importedId = $image->getId();

                if (!$image->getTags()) {
                    continue;
                }

                echo "Image ID=" . (string) $image->getId() . "\n";

                $tags = array_unique(array_map('strval', (array) $image->getTags()));
//                \D::debug($tags);

                foreach ($tags as $tag) {
                    $item = [
                        'key' => [
                            'B' => base64_encode($image->getId() . $tag)
                        ],
                        'id' => [
                            'N' => (string) $image->getId()
                        ],
                        'tag' => [
                            'S' => $tag
                        ],
                    ];

                    $items[] = [
                        'PutRequest' => [
                            'Item' => $item
                        ]
                    ];

                    // per tags
                    if (count($items) % 25 == 0) {
                        $this->batchWriteItem($tableName, $items, $image->getId());
                        $items = [];
                    }

                }

                // per images
                if (count($items) > 0 && count($items) % 25 == 0) {
                    $this->batchWriteItem($tableName, $items, $image->getId());
                    $items = [];
                }

            }
        }

        // final save
        if (count($items)) {
            $this->batchWriteItem($tableName, $items);
        }

    }

    public function importImages()
    {
        $importedId = 0;
        $tableName = 'images';
        $runs = 0;
        $items = [];

        while (true) {
            $images = $this->dm->createQueryBuilder('ImagepushBundle:Image')
                ->field('id')->gt($importedId)
//                ->field('id')->equals($importedId)
                ->sort('id', 'ASC')
                ->limit(25 * 10)
//                ->skip(250*92)
                ->getQuery()->toArray();

            if (!count($images) || $runs++ >= 500000) {
                break;
            }

            $thisBatchIds = [];

            printf("%s - Results: %d. Run: %d\n", date(DATE_ATOM), count($images), $runs);

            foreach ($images as $image) {

                if (in_array($image->getId(), $thisBatchIds)) {
                    continue;
                }

                if (!$image->getId() || !$image->getFile() || !$image->getSlug() || !$image->getLink(
                    ) || !$image->getTimestamp() || !$image->getTitle() || !$image->getMimeType(
                    ) || !$image->getSourceType()
                ) {
                    continue;
                }

                echo (string) $image->getId() . "\n";

                $item = [
                    'id' => [
                        'N' => (string) $image->getId()
                    ],
                    'title' => [
                        'S' => strval($image->getTitle())
                    ],
                    'timestamp' => [
                        'N' => (string) $image->getTimestamp()->sec
                    ],
                    'link' => [
                        'S' => strval($image->getLink())
                    ],
                    'slug' => [
                        'S' => strval($image->getSlug())
                    ],
                    'file' => [
                        'S' => strval($image->getFile())
                    ],
                    'mimeType' => [
                        'S' => strval($image->getMimeType())
                    ],
                    'sourceType' => [
                        'S' => strval($image->getSourceType())
                    ],
                    'isAvailable' => [
                        'N' => strval((int) $image->getIsAvailable())
                    ],
                    'isInProcess' => [
                        'N' => strval((int) $image->getIsInProcess())
                    ],
                ];

                if ($image->getSourceTags()) {
                    $item['sourceTags'] = [
                        'SS' => array_values(array_unique(array_map('strval', (array) $image->getSourceTags())))
                    ];
                }

                if ($image->getTags()) {
                    $item['tags'] = [
                        'SS' => array_values(array_unique(array_map('strval', (array) $image->getTags())))
                    ];
                }

                if ($image->getTagsFound()) {
                    $t = [];
                    foreach ($image->getTagsFound() as $tk => $tv) {
                        $t += [$tk => ['SS' => array_values(array_unique(array_map('strval', array_keys($tv))))]];
                    }

                    $item['tagsFound'] = [
                        'M' => $t
                    ];
                }

                if ($image->getThumbs()) {
                    $t = [];
                    foreach ($image->getThumbs() as $tk => $tv) {
                        $tvv = [];
                        foreach ($tv as $tvk => $tvvalue) {
                            $tvv += [$tvk => ['N' => (string) $tvvalue]];
                        }
                        $t += [$tk => ['M' => $tvv]];
                    }

                    $item['thumbs'] = [
                        'M' => $t
                    ];
                }

                $items[] = [
                    'PutRequest' => [
                        'Item' => $item
                    ]
                ];

                if (count($items) % 25 == 0) {
                    $this->batchWriteItem($tableName, $items, $image->getId());
                    $items = [];
                }

                $importedId = $image->getId();
                $thisBatchIds[] = $image->getId();
            }
        }

        // final save
        if (count($items)) {
            $this->batchWriteItem($tableName, $items);
        }

        // Update counter table
        $this->batchWriteItem("counter",
            [
                [
                    'PutRequest' => [
                        'Item' => [
                            'key' => [
                                'S' => 'images_max_id'
                            ],
                            'value' => [
                                'N' => strval($importedId)
                            ],
                            'updatedAt' => [
                                'N' => strval(time())
                            ],
                        ]
                    ]
                ]
            ]
        );

    }

    public function batchWriteItem($tableName, array $items, $lastId = 0)
    {

        $delay = 0;
        $attempts = 0;

        try {
            do {
                if (isset($result) && count($result->get('UnprocessedItems'))) {
                    $requestItems = $result->get('UnprocessedItems');
                    $delay = $delay * 2 + 1;

//                    echo "!!!!!!!!!!!!!!!DELAY=";
//                    \D::debug($delay);
                } else {
                    $requestItems[$tableName] = $items;
                }

                sleep($delay);

                $result = $this->ddb->batchWriteItem(
                    [
                        'RequestItems' => $requestItems,
                        'ReturnConsumedCapacity' => 'TOTAL',
                    ]
                );

                if (count($result->get('UnprocessedItems'))) {
                    echo "\n\n\n\nConsumedCapacity:";
                    print_r($result->get('ConsumedCapacity'));
                    echo "\n\n\n\n";
                }
            } while (count($result->get('UnprocessedItems')) > 0 && $attempts++ < 10);

            if ($attempts > 10) {
                echo "\nCould not complete batchWriteItem after ".$attempts." attempts!\n";
                print_r($requestItems);
                exit();
            }
        } catch (DynamoDbException $e) {
//            echo "Request headers=".print_r($e->getRequest()->getHeaders());
//            echo "Request body=".print_r($e->getRequest()->getBody());
//            echo "\n";
//            echo "Result=".print_r($e->getResult());
            echo "Some DynamoDbException happened, but we don't care so much... " . $e->getAwsErrorCode() . "\n";
            echo "Last ID: " . $lastId . "\n";
            echo $e->getMessage();
            echo "\n";
            print_r($items);
            exit();
        }

        printf("batchWriteItem: %d items\n", count($items));
    }
}
