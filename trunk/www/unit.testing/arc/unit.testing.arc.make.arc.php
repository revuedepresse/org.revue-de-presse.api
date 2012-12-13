<?php

// Fetch instances of Stylesheet and Placeholder classes
require_once( dirname( __FILE__ ) . '/../edge/unit.testing.edge.make.edge.php' );

$class_arc = $class_application::getArcClass();

$arc = $class_arc::make(
	$edge_stylesheet, $edge_placeholder, PROPERTY_ENCAPSULATION
);

$class_dumper::log(
	__METHOD__,
	array( '$arc', $arc ),
	$verbose_mode
);