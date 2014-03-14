<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

/**
 * @var $loader \Composer\Autoload\ClassLoader
 */
$loader = require __DIR__.'/../vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

AnnotationRegistry::registerFile(__DIR__ . '/../vendor/doctrine/mongodb-odm/lib/Doctrine/ODM/MongoDB/Mapping/Annotations/DoctrineAnnotations.php');

// AWS SDK needs a special autoloader
//require_once __DIR__.'/../vendor/amazonwebservices/aws-sdk-for-php/sdk.class.php';
// Load Amazon credentials config
include_once __DIR__ . '/config/amazon_config.inc.php';

return $loader;
