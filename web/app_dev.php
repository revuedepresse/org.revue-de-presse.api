<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
//umask(0000);

// This check prevents access to debug front controllers that are deployed by accident to production servers.
// Feel free to remove this, extend it, or make something more sophisticated.
if (
    (
        isset($_SERVER['HTTP_CLIENT_IP']) ||
        !in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '## FILL WITH IP ADDRESS ##', 'fe80::1', '::1'])
    ) && (
        ! isset($_SERVER['HTTP_X_FORWARDED_FOR']) ||
        ($_SERVER['HTTP_X_FORWARDED_FOR'] !== '## FILL IP ADDRESS ##')
    )
) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}

require_once __DIR__.'/../app/autoload.php';
Debug::enable();

require_once __DIR__.'/../app/AppKernel.php';

$kernel = new AppKernel('dev', true);
$kernel->loadClassCache();

$request = Request::createFromGlobals();

Request::setTrustedProxies(['dashboard.dev']);

$request->setTrustedHeaderName(Request::HEADER_CLIENT_PROTO, 'X_FORWARDED_PROTO');
$request->setTrustedHeaderName(Request::HEADER_CLIENT_IP, 'X_FORWARDED_FOR');
$request->setTrustedHeaderName(Request::HEADER_CLIENT_HOST, 'X_FORWARDED_HOST');
$request->setTrustedHeaderName(Request::HEADER_CLIENT_PORT, 'X_FORWARDED_PORT');

$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
