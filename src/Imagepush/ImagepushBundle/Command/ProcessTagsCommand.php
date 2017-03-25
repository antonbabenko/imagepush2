<?php

namespace Imagepush\ImagepushBundle\Command;

use Imagepush\ImagepushBundle\Services\Processor\ProcessorStatusCode;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessTagsCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('imagepush:process-tags')
            ->setDescription('Process tags for images from SQS queue')
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
            $result = $this->getContainer()->get('imagepush.processor.tag')->processTag();

            $output->writeLn($result['log']);

            // no need to proceed
            if (ProcessorStatusCode::NO_ITEMS_CODE == $result['code']) {
                $output->writeLn('Exiting now. Please try again later.');

                break;
            }

        } while (++$i < $number);
    }

}
