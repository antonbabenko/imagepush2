<?php

namespace Imagepush\ImagepushBundle\Logger;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OutputLogger implements LoggerInterface
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = array())
    {
        $this->output->writeln(sprintf('<error>%s</error>', $message));
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = array())
    {
        $this->output->writeln(sprintf('<error>%s</error>', $message));
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = array())
    {
        $this->output->writeln(sprintf('<error>%s</error>', $message));
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = array())
    {
        $this->output->writeln(sprintf('<error>%s</error>', $message));
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = array())
    {
        $this->output->writeln(sprintf('<comment>%s</comment>', $message));
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = array())
    {
        $this->output->writeln(sprintf('<comment>%s</comment>', $message));
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = array())
    {
        $this->output->writeln(sprintf('<info>%s</info>', $message));
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = array())
    {
        $this->output->writeln(sprintf('<info>%s</info>', $message));
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = array())
    {
        $this->output->writeln($message);
    }
}
