<?php

namespace Imagepush\DevBundle\Test\Phpunit;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Base class for testing the CLI tools.
 *
 * Got from http://alexandre-salome.fr/blog/Test-your-commands-in-Symfony2
 */
abstract class CommandTestCase extends WebTestCase
{
    /**
     * Runs a command and returns it output
     */
    public function runCommand($command)
    {
        $application = new Application(static::$client->getKernel());
        $application->setAutoExit(false);

        $fp = tmpfile();
        $input = new ArgvInput($this->parseStringCommand($command));
        $output = new StreamOutput($fp);

        $application->run($input, $output);

        fseek($fp, 0);
        $output = '';
        while (!feof($fp)) {
            $output = fread($fp, 4096);
        }
        fclose($fp);

        return $output;
    }

    /**
     * Runs a command and returns it output
     */
    public function runCommandTester($command)
    {
        $application = new Application(static::$client->getKernel());
        $application->setAutoExit(false);

        if (false === strpos($command, '--env')) {
            $command .= ' --env=' . static::$container->getParameter('kernel.environment');
        }

        $fp = tmpfile();
        $input = new ArgvInput($this->parseStringCommand($command));
        $output = new StreamOutput($fp);

        $application->run($input, $output);

        fseek($fp, 0);
        $output = '';
        while (!feof($fp)) {
            $output = fread($fp, 4096);
        }
        fclose($fp);

        return $output;
    }

    /**
     * @param string $string
     *
     * @return array
     */
    protected function parseStringCommand($string)
    {
        $string = str_replace('  ', ' ', $string);
        $commandArgs = explode(' ', $string);
        array_unshift($commandArgs, './app/console');

        return $commandArgs;
    }
}
