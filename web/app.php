<?php

require_once __DIR__.'/access_config.php';

if (!user_has_access()) die();

require_once __DIR__.'/../app/bootstrap.php.cache';
require_once __DIR__.'/../app/AppKernel.php';
//require_once __DIR__.'/../app/bootstrap_cache.php.cache';
//require_once __DIR__.'/../app/AppCache.php';

use Symfony\Component\HttpFoundation\Request;

//$kernel = new AppCache(new AppKernel('prod', false));
$kernel = new AppKernel(SF_ENVIRONMENT, SF_DEBUG);
$kernel->loadClassCache();
$request = Request::createFromGlobals();

Request::trustProxyData();

$response = $kernel->handle($request);
$response->send();
//$kernel->terminate($request, $response);
