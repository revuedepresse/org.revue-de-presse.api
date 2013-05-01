<?php

$bowerize = function () {
    $pipes = [];

    $descriptors = array(
        array('pipe', 'r'), // stdin
        array('pipe', 'w'), // stdout
        array('pipe', 'w'), // stderr
    );
    $options = array(
        'suppress_error' => true,
        'binary_pipes' => true
    );

    $env = null;
    $cwd = '## FILL ABSOLUTE PATH ##'';
    $commandLine = '/usr/local/lib/node_modules/bower/bin/bower list --map';
    $process = proc_open($commandLine, $descriptors, $pipes, $cwd, null, $options);

    $status = proc_get_status($process);
//    foreach ($pipes as $pipe) {
//        stream_set_blocking($pipe, false);
//    }

    while ($pipes) {
        foreach ($pipes as $index => $pipe) {
            $info = fread($pipe, 8192);
            echo $info;

            if (feof($pipe)) {
                unset($pipes[$index]);
            }
        }
    }

    proc_close($process);
};

print_r($bowerize());
