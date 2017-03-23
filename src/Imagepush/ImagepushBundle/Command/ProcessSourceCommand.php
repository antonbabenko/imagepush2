<?php

namespace Imagepush\ImagepushBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessSourceCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('imagepush:process-source')
            ->setDescription('Process one source')
            ->setHelp('Find best image in source link, find source tags, make thumbs and save image as upcoming')
            ->setDefinition(
                array(
                    new InputOption(
                        'number', null, InputOption::VALUE_OPTIONAL,
                        'Number of items to process.',
                        1
                    )
                )
            );
    }

    /**
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $i = 0;
        $maxNumber = 100;
        $number = $input->getOption('number');

        if ($number > $maxNumber) {
            $output->writeln('<error>Number (' . $number . ') is too large. Maximum: ' . $maxNumber . '</error>');

            return;
        }

        do {
            $content = $this->getContainer()->get('imagepush.processor.source')->processSource();

            $output->writeLn($content, true);
        } while (++$i < $number);
    }

}
