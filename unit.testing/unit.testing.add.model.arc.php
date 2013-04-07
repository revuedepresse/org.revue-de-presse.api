<?php

$class_arc = $class_application::getArcClass();

$class_edge = $class_application::getEdgeClass();

$properties_destination = $class_arc::fetchEntityTypePropertiesByName(CLASS_MEMBER);

$properties_source = $class_arc::fetchEntityTypePropertiesByName(CLASS_INSIGHT_NODE);

$class_dumper::log(
    __METHOD__,
    array(
		'$class_arc::getInstanceTypeProperty(PROPERTY_VALUE)',
		$class_arc::getInstanceTypeProperty(PROPERTY_VALUE)
	),
    $verbose_mode
);

// replace magically Arc with Instance
$class_dumper::log(
    __METHOD__,
    array(
		'$class_arc::getArcTypeProperty(PROPERTY_VALUE)',
		$class_arc::getArcTypeProperty(PROPERTY_VALUE)
	),
    $verbose_mode
);

// replace magically Arc and Value respectively with Instance and Property
$class_dumper::log(
    __METHOD__,
    array(
		'$class_arc::getArcTypeName()',
		$class_arc::getArcTypeName()
	),
    $verbose_mode
);

$edge_destination = $class_edge::add($properties_destination);

$edge_source = $class_edge::add($properties_source);

$properties = array(
    PROPERTY_TYPE => $class_arc::getArcTypeValue(),
    PROPERTY_DESTINATION => $edge_destination->{PROPERTY_ID},
    PROPERTY_SOURCE => $edge_source->{PROPERTY_ID}
);

$class_dumper::log(
    __METHOD__,
    array(
		'$class_arc::add($properties)',
		$class_arc::add($properties)
	),
    $verbose_mode
);