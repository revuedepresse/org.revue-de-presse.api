<?php

$class_dumper = $class_application::getDumperClass();

$class_form = $class_application::getFormClass(NAMESPACE_SEMANTIC_FIDELITY);

$field_values = $class_form::displayRendering( 'confirm' );

$class_dumper::log(
	__METHOD__,
	array( $field_values ),
	TRUE
);