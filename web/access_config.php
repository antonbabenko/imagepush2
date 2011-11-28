<?php

function user_has_access()
{
  if (preg_match("/^dev/", $_SERVER['HTTP_HOST']))
  {
    define('SF_ENVIRONMENT', 'dev');
    define('SF_DEBUG', true);
    $allowed_ips = array('127.0.0.1', '::1',
      '10.0.0.2', '10.0.0.38', '80.203.227.145', // Anton
      );
  } else
  {
    define('SF_ENVIRONMENT', 'prod');
    define('SF_DEBUG', false);
    $allowed_ips = array();
  }

  if ( SF_ENVIRONMENT == "dev" && !in_array(@$_SERVER['REMOTE_ADDR'], $allowed_ips) )
  {
    echo 'You are not allowed to access this site. Ask somone for more information.';
    return false;
  } else
  {
    return true;
  }
}
