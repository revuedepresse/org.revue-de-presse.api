<?php

$class_dumper = $class_application::getDumperClass();

$class_stylesheet = $class_application::getStylesheetClass();

$stylesheet = $class_stylesheet::make( 'main', constant( 'STYLESHEET_TYPE_SCREEN*' ) );

$class_dumper::log(
	__METHOD__,
	array(
		'$stylesheet',
		$stylesheet
	),
	$verbose_mode
);

$stylesheet->{PROPERTY_STATUS} = ENTITY_STATUS_ACTIVE;

$class_dumper::log(
	__METHOD__,
	array(
		'$stylesheet->sync()',
		$stylesheet->sync()
	),
	$verbose_mode	
);