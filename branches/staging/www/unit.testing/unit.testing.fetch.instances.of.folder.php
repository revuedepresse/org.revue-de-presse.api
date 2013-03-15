<?php

$class_dumper = $class_application::getDumperClass();

$class_folder = $class_application::getFolderClass();

$folder = $class_folder::getById( 1 );

$class_dumper::log(
	__METHOD__,
	array(
		'$folder = $class_route::getById( 1 );',
		$folder
	),
	$verbose_mode
);