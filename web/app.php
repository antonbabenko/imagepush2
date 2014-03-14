<?php

// Check whether user IP has access to the requested sub-domain
require_once __DIR__.'/access_control.php';

umask(0000);

use Symfony\Component\HttpFoundation\Request;

require_once __DIR__.'/../app/bootstrap.php.cache';
require_once __DIR__.'/../app/AppKernel.php';
//require_once __DIR__.'/../app/AppCache.php';

$kernel = new AppKernel(SF_ENVIRONMENT, SF_DEBUG);
$kernel->loadClassCache();
//$kernel = new AppCache($kernel);

Request::enableHttpMethodParameterOverride();

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
