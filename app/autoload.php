<?php

use Symfony\Component\ClassLoader\UniversalClassLoader;
use Doctrine\Common\Annotations\AnnotationRegistry;

// Goutte - web browser (should be included before Zend namespace is defined)
require_once __DIR__.'/../src/goutte.phar';

$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
    'Symfony'          => array(__DIR__.'/../vendor/symfony/src', __DIR__.'/../vendor/bundles'),
    'Sensio'           => __DIR__.'/../vendor/bundles',
    'JMS'              => __DIR__.'/../vendor/bundles',
    'Doctrine\\ODM\\MongoDB'    => __DIR__.'/../vendor/doctrine-mongodb-odm/lib',
    'Doctrine\\MongoDB'         => __DIR__.'/../vendor/doctrine-mongodb/lib',
    'Doctrine\\Common' => __DIR__.'/../vendor/doctrine-common/lib',
    'Doctrine\\DBAL'   => __DIR__.'/../vendor/doctrine-dbal/lib',
    'Doctrine'         => __DIR__.'/../vendor/doctrine/lib',
    'Monolog'          => __DIR__.'/../vendor/monolog/src',
    'Assetic'          => __DIR__.'/../vendor/assetic/src',
    'Metadata'         => __DIR__.'/../vendor/metadata/src',

    'Snc'              => __DIR__.'/../vendor/bundles',
    'Imagine'          => __DIR__.'/../vendor/imagine/lib',
    'Liip'             => __DIR__.'/../vendor/bundles',
    'Predis'           => __DIR__.'/../vendor/predis/lib',
    'Zend'             => __DIR__.'/../vendor', // zend components (feed)
    'Knp\Bundle'       => __DIR__.'/../vendor/bundles',
    'Gaufrette'        => __DIR__.'/../vendor/gaufrette/src',
    'Stof'             => __DIR__.'/../vendor/bundles',
    'Gedmo'            => __DIR__.'/../vendor/gedmo-doctrine-extensions/lib',

    'Imagepush'        => __DIR__.'/../src',

));
$loader->registerPrefixes(array(
    'Twig_Extensions_' => __DIR__.'/../vendor/twig-extensions/lib',
    'Twig_'            => __DIR__.'/../vendor/twig/lib',
));

// intl
if (!function_exists('intl_get_error_code')) {
    require_once __DIR__.'/../vendor/symfony/src/Symfony/Component/Locale/Resources/stubs/functions.php';

    $loader->registerPrefixFallbacks(array(__DIR__.'/../vendor/symfony/src/Symfony/Component/Locale/Resources/stubs'));
}

$loader->registerNamespaceFallbacks(array(
    __DIR__.'/../src',
));
$loader->register();

AnnotationRegistry::registerLoader(function($class) use ($loader) {
    $loader->loadClass($class);

    return class_exists($class, false);
});
AnnotationRegistry::registerFile(__DIR__.'/../vendor/doctrine/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');
AnnotationRegistry::registerFile(__DIR__.'/../vendor/doctrine-mongodb-odm/lib/Doctrine/ODM/MongoDB/Mapping/Annotations/DoctrineAnnotations.php');

// Swiftmailer needs a special autoloader to allow
// the lazy loading of the init file (which is expensive)
require_once __DIR__.'/../vendor/swiftmailer/lib/classes/Swift.php';
Swift::registerAutoload(__DIR__.'/../vendor/swiftmailer/lib/swift_init.php');

// AWS SDK needs a special autoloader
require_once __DIR__.'/../vendor/aws-sdk/sdk.class.php';

// Load Amazon credentials config
include_once  __DIR__.'/config/amazon_config.inc.php';

/*
// Load the custom PEAR vendors as well...
set_include_path(
    get_include_path() . PATH_SEPARATOR .
    dirname(__DIR__) . '/vendor/pear/'
);

require_once 'Services/Amazon/S3.php';
require_once 'Services/Amazon/S3/Stream.php';

Services_Amazon_S3_Stream::register('s3',
    array('access_key_id'     => 'xxx',
          'secret_access_key' => 'yyy',
          'acl' => 'public-read',
    )
);
*/

//define("AWS_CERTIFICATE_AUTHORITY", true);
