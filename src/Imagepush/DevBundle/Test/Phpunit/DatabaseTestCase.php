<?php

namespace Imagepush\DevBundle\Test\Phpunit;

use Doctrine\Common\Tester\Tester;

/**
 * Class DatabaseTestCase
 */
abstract class DatabaseTestCase extends WebTestCase
{
    /**
     * @var \Doctrine\Common\Tester\Tester
     */
    protected static $dbTester;

    /**
     * @var array
     */
    protected static $unserializedReferenceRepository;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        static::$dbTester = new Tester();
        static::$dbTester->useEm($this->getEntityManager());

        if (null == static::$unserializedReferenceRepository) {
            static::$unserializedReferenceRepository = unserialize(file_get_contents(
                    static::$container->getParameter('kernel.cache_dir') . '/../commonReferenceRepository'
                ));
        }

        static::$container->get('snc_redis.default')->flushDB();
        static::$dbTester->setReferenceRepositoryData(static::$unserializedReferenceRepository);

        $this->startTransaction();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        $this->rollbackTransaction();

        parent::tearDown();
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        return static::$container->get('doctrine.orm.entity_manager');
    }

    protected function startTransaction()
    {
        /** @var $em \Doctrine\ORM\EntityManager */
        foreach (static::$container->get('doctrine')->getManagers() as $em) {
            $em->clear();
            $em->getConnection()->beginTransaction();
        }
    }

    protected function rollbackTransaction()
    {
        //the error can be thrown during setUp
        //It would be caught by phpunit and tearDown called.
        //In this case we could not rollback since container may not exist.
        if (false == static::$container) {
            return;
        }

        /** @var $em \Doctrine\ORM\EntityManager */
        foreach (static::$container->get('doctrine')->getManagers() as $em) {
            $connection = $em->getConnection();

            while ($connection->isTransactionActive()) {
                $connection->rollback();
            }
        }
    }

}
