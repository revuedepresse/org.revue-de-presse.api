<?php

$class_dumper = $class_application::getDumperClass();

$class_folder = $class_application::getFolderClass();

$folder = $class_folder::make( dirname( __FILE__ ) );

$class_dumper::log(
	__METHOD__,
	array(
		'$folder->sync()',
		$folder->sync(),
		'$folder',
		$folder
	),
	$verbose_mode
);