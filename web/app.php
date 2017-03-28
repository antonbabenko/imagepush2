<?php

umask(0000);

use Symfony\Component\HttpFoundation\Request;

require_once __DIR__.'/access_config.php';

if (!user_has_access()) die();

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__.'/../app/autoload.php';

if (defined('SF_DEBUG') && SF_DEBUG == true) {
    \Symfony\Component\Debug\Debug::enable();
} else {
    include_once __DIR__.'/../app/bootstrap.php.cache';
}

$kernel = new AppKernel(SF_ENVIRONMENT, SF_DEBUG);
$kernel->loadClassCache();
//$kernel = new AppCache($kernel);

Request::enableHttpMethodParameterOverride();

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
