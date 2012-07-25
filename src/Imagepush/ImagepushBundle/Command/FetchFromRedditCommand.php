<?php

namespace Imagepush\ImagepushBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FetchFromRedditCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('imagepush:fetch-from-reddit')
            ->setDescription('Fetch new sources from Reddit')
            ->setHelp('Fetch from Reddit and save as unprocessed sources');

    }

    /**
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $content = $this->getContainer()->get('imagepush.fetcher.reddit')->run();

        $output->writeLn($content, true);
    }

}