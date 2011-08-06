<?php

namespace Imagepush\ImagepushBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImagepushFetchFromDiggCommand extends ContainerAwareCommand
{
  const NEWLINE = true;

  protected function configure()
  {
    parent::configure();

    $this
      ->setName('imagepush:fetch-from-digg')
      ->setDescription('Fetch new sources from Digg')
    ;
    
  }

  /**
   * Executes the current command.
   *
   * @param InputInterface  $input  An InputInterface instance
   * @param OutputInterface $output An OutputInterface instance
   *
   * @return integer 0 if everything went fine, or an error code
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $content = $this->getContainer()->get('imagepush.fetcher.digg')->run();
    
    $output->writeLn($content, true);
  }

}