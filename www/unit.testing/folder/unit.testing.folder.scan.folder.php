<?php

$class_dumper = $class_application::getDumperClass();
$class_folder = $class_application::getFolderClass();

$path = '## FILL ABSOLUTE PATH ##'';

//$path = '## FILL ABSOLUTE PATH ##'';

$class_dumper::log(
    __METHOD__,
    array(
        '$class_folder( $path );',
        $folder = $class_folder::scan_folder( $path, TRUE, 2 )
    ),
    $verbose_mode
);