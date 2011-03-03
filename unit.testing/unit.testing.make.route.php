<?php

$class_dumper = $class_application::getDumperClass();

$class_route = $class_application::getRouteClass();

/**
* the implicit list of arguments is
*
* uri
* type
* entity
* parent
* level
* index
*/

$route = $class_route::make('manage-users', ENTITY_ADMINISTRATION);

$class_dumper::log(
	__METHOD__,
	array(
		'$route->sync()',
		$route->sync(),
		'$route',
		$route
	),
	$verbose_mode
);