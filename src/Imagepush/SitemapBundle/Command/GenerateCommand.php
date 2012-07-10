<?php

namespace Imagepush\SitemapBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

class GenerateCommand extends ContainerAwareCommand
{

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $c = $this->getContainer();
        $dm = $c->get('doctrine.odm.mongodb.' . $input->getOption('dm') . '_document_manager');

        if (!$c->hasParameter('site_url')) {
            throw new \RuntimeException("Sitemap requires base_url parameter [site_url] to be available, through config or parameters");
        }

        $output->write('<info>Fetching resources..</info>' . PHP_EOL);

        $images = $dm->createQueryBuilder('ImagepushBundle:Image')
            ->field('isAvailable')->equals(true)
            ->sort('timestamp', 'ASC')
            ->getQuery()
            ->toArray();

        $tags = $dm->createQueryBuilder('ImagepushBundle:Tag')
            ->field('usedInAvailable')->gt(0)
            ->sort('text', 'ASC')
            ->getQuery()
            ->toArray();

        $sitemapFile = $c->getParameter('kernel.root_dir') . '/../web/sitemap.xml';
        $output->write('<info>Building sitemap...</info>' . PHP_EOL);
        $spaceless = (bool) $input->getOption('spaceless');
        $tpl = $spaceless ? 'SitemapBundle::sitemap.spaceless.xml.twig' : 'SitemapBundle::sitemap.xml.twig';
        $sitemap = $c->get('templating')->render($tpl, compact('images', 'tags'));

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

}