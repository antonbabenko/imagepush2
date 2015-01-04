<?php

namespace Imagepush\ImagepushBundle\Command;

use Imagepush\ImagepushBundle\Logger\OutputLogger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
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
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fetcher = $this->getContainer()->get('imagepush.fetcher.reddit');
        $fetcher->setLogger(new OutputLogger($output));
        $fetcher->run();
    }

}
