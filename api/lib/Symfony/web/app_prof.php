<?php

use Symfony\Component\HttpFoundation\Request;

xhprof_enable();

$loader = require_once __DIR__ . '/../app/autoload.php';

require_once __DIR__ . '/../app/AppKernel.php';

$kernel = new AppKernel('prof', true);
$request  = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);

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

