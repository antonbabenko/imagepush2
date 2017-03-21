<?php

namespace Imagepush\SitemapBundle\Command;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Imagepush\ImagepushBundle\Repository\ImageRepository;
use Imagepush\ImagepushBundle\Repository\TagRepository;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

class GenerateCommand extends ContainerAwareCommand
{

    /**
     * @var DynamoDbClient
     */
    protected $ddb;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ImageRepository
     */
    protected $imageRepo;

    /**
     * @var TagRepository
     */
    protected $tagRepo;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('imagepush:generate:sitemap')
            ->setDescription('Generate sitemap')
            ->setDefinition(array(
                new InputOption(
                    'dm', null, InputOption::VALUE_OPTIONAL,
                    'Used document manager.',
                    'default'
                ),
                new InputOption(
                    'spaceless', 'spls', InputOption::VALUE_OPTIONAL,
                    'Output spaceless sitemap.',
                    0
                )
            ));
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $this->ddb = $container->get('aws.dynamodb');
        $this->logger = $container->get('logger');
        $this->imageRepo = $container->get('imagepush.repository.image');
        $this->tagRepo = $container->get('imagepush.repository.tag');

        if (!$container->hasParameter('site_url')) {
            throw new \RuntimeException("Sitemap requires base_url parameter [site_url] to be available, through config or parameters");
        }

        $output->write('<info>Fetching resources..</info>' . PHP_EOL);

        // Currently there is no need to increase capacity before these queries,
        // because these command is executed few times a day.

        $images = $this->findImages(9999999);
        $output->writeln(sprintf('<info>Found %d images</info>', count($images)));

        $tags = $this->findTags(999999);
        $output->writeln(sprintf('<info>Found %d tags</info>', count($tags)));

        $sitemapFile = $container->getParameter('kernel.root_dir') . '/../web/sitemap.xml';
        $output->write('<info>Building sitemap...</info>' . PHP_EOL);
        $spaceless = (bool) $input->getOption('spaceless');
        $tpl = $spaceless ? 'SitemapBundle::sitemap.spaceless.xml.twig' : 'SitemapBundle::sitemap.xml.twig';
        $sitemap = $container->get('templating')->render($tpl, compact('images', 'tags'));

        $output->write("<info>Saving sitemap in [{$sitemapFile}]..</info>" . PHP_EOL);
        file_put_contents($sitemapFile, $sitemap);

        // gzip the sitemap
        if (function_exists('gzopen')) {
            $output->write("<info>Gzipping the generated sitemap [{$sitemapFile}.gz]..</info>" . PHP_EOL);
            $gz = gzopen($sitemapFile . '.gz', 'w9');
            gzwrite($gz, $sitemap);
            gzclose($gz);
        } else {
            $output->write('<info>Error - Gzip is not enabled!!!</info>' . PHP_EOL);
        }

        $output->write('<info>Done</info>' . PHP_EOL);
    }

    protected function findImages($limit)
    {
        $request = [
            'TableName' => 'images',
            'IndexName' => 'isAvailable-timestamp-index',
            'ExpressionAttributeNames' => [
                '#t' => 'timestamp',
            ],
            'ExpressionAttributeValues' => [
                ':isAvailable' => ['N' => '1'],
            ],
            'KeyConditionExpression' => 'isAvailable = :isAvailable',
            'ProjectionExpression' => 'id, slug, #t',
            'ScanIndexForward' => false,
            'Limit' => $limit
        ];

        $results = $this->getResults('query', $request, $limit, 30); // set max page to larger value to perform more attempts

        return $results;
    }

    protected function findTags($limit)
    {
        $request = [
            'TableName' => 'tags',
            'ExpressionAttributeNames' => [
                '#text' => 'text'
            ],
            'ExpressionAttributeValues' => [
                ':usedInAvailable' => ['N' => '0'],
            ],
            'FilterExpression' => 'usedInAvailable > :usedInAvailable',
            'ProjectionExpression' => '#text',
            'Limit' => $limit
        ];

        $results = $this->getResults('scan', $request, $limit, 20);

        return $results;
    }

    /**
     * @param $command  string  'query' or 'scan'
     * @param $request  array   Associative array with 'request' for query command
     * @param $limit    integer Desired number of records
     * @param $maxPages int     Max number of pages to try to scan. Set to small number to avoid large scan.
     *
     * @return array|null
     */
    public function getResults($command, array $request, $limit, $maxPages = 3)
    {

        $count = 0;
        $page = 0;
        $results = [];

        # The Query operation is paginated. Issue the Query request multiple times.
        try {
            do {
                # Add the ExclusiveStartKey if we got one back in the previous response
                if (isset($response) && isset($response['LastEvaluatedKey'])) {
                    $request['ExclusiveStartKey'] = $response['LastEvaluatedKey'];
                }

                // Add some sleep to prevent ProvisionedThroughputExceededException
                sleep(5);

                if ('query' == $command) {
                    $response = $this->ddb->query($request);
                } else {
                    $response = $this->ddb->scan($request);
                }

                $count += $response['Count'];

                $results = array_merge($results, $response['Items']);
            } # If there is no LastEvaluatedKey in the response, there are no more items matching this request
            while (isset($response['LastEvaluatedKey']) and $count < $limit and $page++ < $maxPages);
        } catch (DynamoDbException $e) {
            $this->logger->error($e->__toString());
        }

        $this->logger->warn(sprintf('Sitemap generator got %d results from DynamoDB and performed %d paged requests of %d (maxPages). Increase maxPages to allow more requests when these numbers get closer.', $count, $page, $maxPages));

        if (count($results) > $limit) {
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }

}
