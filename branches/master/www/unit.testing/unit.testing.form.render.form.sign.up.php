<?php

$ns = NAMESPACE_SEMANTIC_FIDELITY;

$class_dumper = $class_application::getDumperClass();
$class_form = $class_application::getFormClass( $ns );

$field_values = $class_form::displayRendering( 'sign.up' );

$class_dumper::log( __METHOD__, array( $field_values ), TRUE );