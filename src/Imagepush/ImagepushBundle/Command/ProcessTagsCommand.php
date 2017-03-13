<?php

namespace Imagepush\ImagepushBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessTagsCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('imagepush:process-tags')
            ->setDescription('Process tags for images from SQS queue')
            ->setHelp('Find tags');
    }

    /**
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $content = $this->getContainer()->get('imagepush.processor.tag')->processTag();

        $output->writeLn($content, true);
    }

}
