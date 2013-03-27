<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

if (!$loader = @include __DIR__ . '/../vendor/autoload.php') {

    $message = <<< EOF
<p>You must set up the project dependencies by running the following commands:</p>
<pre>
    curl -s http://getcomposer.org/installer | php
    php composer.phar install
</pre>

EOF;

    if (PHP_SAPI === 'cli') {
        $message = strip_tags($message);
    }

    die($message);
}

// intl
if (!function_exists('intl_get_error_code')) {
    require_once __DIR__ . '/../vendor/symfony/symfony/src/Symfony/Component/Locale/Resources/stubs/functions.php';

    $loader->add('', __DIR__ . '/../vendor/symfony/symfony/src/Symfony/Component/Locale/Resources/stubs');
}

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

AnnotationRegistry::registerFile(__DIR__ . '/../vendor/doctrine/mongodb-odm/lib/Doctrine/ODM/MongoDB/Mapping/Annotations/DoctrineAnnotations.php');

// Swiftmailer needs a special autoloader to allow
// the lazy loading of the init file (which is expensive)
//require_once __DIR__ . '/../vendor/swiftmailer/swiftmailer/lib/classes/Swift.php';
//Swift::registerAutoload(__DIR__ . '/../vendor/swiftmailer/swiftmailer/lib/swift_init.php');

// AWS SDK needs a special autoloader
//require_once __DIR__.'/../vendor/amazonwebservices/aws-sdk-for-php/sdk.class.php';
// Load Amazon credentials config
include_once __DIR__ . '/config/amazon_config.inc.php';

return $loader;
