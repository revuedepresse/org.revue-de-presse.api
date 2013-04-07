<?php

$class_dumper = $class_application::getDumperClass();

$class_edge = $class_application::getEdgeClass();

$edge_placeholder = $class_edge::make( 1, ENTITY_PLACEHOLDER );

$edge_stylesheet = $class_edge::make( 1, ENTITY_STYLESHEET );

$class_dumper::log(
	__METHOD__,
	array(
		'$edge_placeholder',
		$edge_placeholder,
		'$edge_stylesheet',
		$edge_stylesheet		
	),
	$verbose_mode
);