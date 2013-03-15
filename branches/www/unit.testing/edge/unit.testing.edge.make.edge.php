<?php

$class_dumper = $class_application::getDumperClass();
$class_edge = $class_application::getEdgeClass();

$edge_placeholder = $class_edge::make( 1, ENTITY_PLACEHOLDER );
$edge_stylesheet = $class_edge::make( 1, ENTITY_STYLESHEET );

$class_dumper::log(
	__METHOD__,
	array(
		'$edge_placeholder', $edge_placeholder,
		'$edge_stylesheet', $edge_stylesheet		
	),
	$verbose_mode
);

/**
*************
* Changes log
*
*************
* 2011 10 22
*************
*
* documentation :: edge ::
*
* See also arc/unit.testing.arc.make.arc.php
*
* (branch 0.1 :: revision :: 736)
* (branch 0.2 :: revision :: 400)
*
*/