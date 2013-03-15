<?php

$class_dumper = $class_application::getDumperClass();

$class_route = $class_application::getRouteClass();

$route = $class_route::getById( 28 );

$class_dumper::log(
	__METHOD__,
	array(
		'$route = $class_route::getById( 28 );',
		$route
	),
	$verbose_mode
);