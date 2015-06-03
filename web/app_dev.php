<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

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
