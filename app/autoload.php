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

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

// AWS SDK needs a special autoloader
//require_once __DIR__.'/../vendor/amazonwebservices/aws-sdk-for-php/sdk.class.php';

// Load Amazon credentials config
//include_once __DIR__ . '/config/amazon_config.inc.php';

return $loader;
