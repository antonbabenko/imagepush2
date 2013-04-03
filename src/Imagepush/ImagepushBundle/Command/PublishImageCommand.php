<?php

namespace Imagepush\ImagepushBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PublishImageCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('imagepush:publish-image')
            ->setDescription('Publish latest upcoming image as available');
    }

    /**
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $content = $this->getContainer()->get('imagepush.publisher')->publishImageWithMostTagsFound();

        // This was used before 30.03.2013:
        // $content = $this->getContainer()->get('imagepush.publisher')->publishLatestUpcomingImage();

        $output->writeLn($content, true);
    }

}
