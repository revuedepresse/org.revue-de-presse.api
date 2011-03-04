<?php

$class_dumper = $class_application::getDumperClass();

$class_query = $class_application::getQueryClass();

$query = $class_query::getById( 4 );

$class_dumper::log(
	__METHOD__,
	array(
		'$query = $class_query::getById( 4 )',
		$query
	),
	$verbose_mode
);