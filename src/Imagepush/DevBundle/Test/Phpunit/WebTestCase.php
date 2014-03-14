<?php

namespace Imagepush\DevBundle\Test\Phpunit;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;

/**
 * Class WebTestCase
 */
abstract class WebTestCase extends BaseWebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected static $container;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        static::$client = static::createClient();
        static::$container = static::$kernel->getContainer();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        foreach ($this->getTearDownProperties() as $prop) {
            $prop->setValue($this, null);
        }
    }

    /**
     * Returns an array of ReflectionProperty objects for tear down.
     */
    private function getTearDownProperties()
    {
        static $cache = array();

        $class = get_class($this);
        if (!isset($cache[$class])) {
            $cache[$class] = array();
            $refl = new \ReflectionClass($class);
            $filter = \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE;
            foreach ($refl->getProperties($filter) as $prop) {
                if ($prop->isStatic()) {
                    continue;
                }
                if (0 !== strpos($prop->getDeclaringClass()->getName(), 'PHPUnit_')) {
                    $prop->setAccessible(true);
                    $cache[$class][] = $prop;
                }
            }
        }

        return $cache[$class];
    }

    /**
     * @return string
     */
    protected function getFormCsrfToken()
    {
        $form = self::$container->get('form.factory')->create('form')->createView();

        self::$container->get('session')->save();

        return $form['_token']->vars['value'];
    }

}
