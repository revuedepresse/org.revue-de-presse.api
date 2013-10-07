#!/usr/bin/env php
<?php

// if you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
//umask(0000);

set_time_limit(0);

require_once __DIR__.'/bootstrap.php.cache';
require_once __DIR__.'/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;

$input = new ArgvInput();
$env = $input->getParameterOption(array('--env', '-e'), getenv('SYMFONY_ENV') ?: 'dev');
$debug = !$input->hasParameterOption(array('--no-debug', ''));

if ($env === 'prof') {
    xhprof_enable();
}

$kernel = new AppKernel($env, $debug);
$application = new Application($kernel);
$application->setAutoExit(false);
$exitCode = $application->run();

if ($env === 'prof') {
    $xhprofData = xhprof_disable();
    $xhprofRoot = '## FILL ABSOLUTE PATH ##'';

    include_once $xhprofRoot . "/xhprof_lib/utils/xhprof_lib.php";
    include_once $xhprofRoot . "/xhprof_lib/utils/xhprof_runs.php";

    $xhprofHost = 'xhprof.dev';
    $xhprofRuns = new XHProfRuns_Default();

    $namespace = "wtw";
    $runId = $xhprofRuns->save_run($xhprofData, $namespace);
    $profilingUrl = 'http://' . $xhprofHost. '/index.php?' .
    'run=' . $runId .
    '&source=' . $namespace;

    error_log(sprintf('-----------------[ %s %s ]----------------', '[request handling]', $profilingUrl));
}

exit($exitCode);