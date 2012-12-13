<?php

$class_header = $class_application::getHeaderClass();

$class_dumper = $class_application::getDumperClass();

$header = $class_header::make( '???' );

$class_dumper::log(
	__METHOD__,
	array(
		'$header',
		$header
	),
	$verbose_mode
);