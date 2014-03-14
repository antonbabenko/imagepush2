<?php
namespace Imagepush\DevBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as DataFixturesLoader;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand;

use Doctrine\Common\Tester\DataFixture\ReferenceRepositorySerializer;
use Doctrine\Common\Tester\DataFixture\Purger\MysqlORMPurger;

class LoadDataFixturesAndSaveRefRepoDoctrineCommand extends DoctrineCommand
{
    protected function configure()
    {
        $this
            ->setName('doctrine:fixtures:load-data-and-save-ref-repo')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures instead of deleting all data from the database first.')
            ->setDescription('Load data fixtures to your database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager = $this->getEntityManager('default');

        $executor = $this->executeLoadDataFixturesDoctrine($input, $output);

        $serializedReferenceRepositoryFilePath =
            $this->getContainer()->getParameter('kernel.cache_dir') . '/../commonReferenceRepository'
        ;

        $output->writeln(sprintf(
                '  <comment>></comment> <info>serialize reference repository to: %s </info>',
                $serializedReferenceRepositoryFilePath
            ));

        $LoadDataFixturesAndSaveRefRepoDoctrineCommand = new ReferenceRepositorySerializer($entityManager);
        $serializedReferenceRepository = $LoadDataFixturesAndSaveRefRepoDoctrineCommand->serialize($executor->getReferenceRepository());

        file_put_contents($serializedReferenceRepositoryFilePath, $serializedReferenceRepository);
    }

    /**
     * THIS METHOD COPY PASTED FROM \Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesCommand::execute
     *
     * @return \Doctrine\Common\DataFixtures\Executor\ORMExecutor
     */
    protected function executeLoadDataFixturesDoctrine(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getEntityManager('default');

        $paths = array();
        foreach ($this->getApplication()->getKernel()->getBundles() as $bundle) {
            $paths[] = $bundle->getPath().'/DataFixtures/ORM';
        }

        $loader = new DataFixturesLoader($this->getContainer());
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            }
        }
        $fixtures = $loader->getFixtures();
        if (!$fixtures) {
            throw new \InvalidArgumentException(
                sprintf('Could not find any fixtures to load in: %s', "\n\n- ".implode("\n- ", $paths))
            );
        }
        $purger = new MysqlORMPurger($em);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
        $executor = new ORMExecutor($em, $purger);
        $executor->setLogger(function($message) use ($output) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
            });
        $executor->execute($fixtures, $input->getOption('append'));

        // add this to get reference repository
        return $executor;
    }
}
