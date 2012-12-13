<?php

$class_dumper = $class_application::getDumperClass();

$class_file = $class_application::getFileClass();

$path = '## FILL ABSOLUTE PATH ##';

$class_dumper::log(
	__METHOD__,
	array( $class_file::getFileSize( $path ) ),
	$verbose_mode
);		
