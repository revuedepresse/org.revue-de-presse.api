<?php

$class_dumper = $class_application::getDumperClass();

$class_stylesheet = $class_application::getStylesheetClass();

$class_dumper::log(
	__METHOD__,
	array(
		'$stylesheet = $class_stylesheet::getById( 1 );',
		$stylesheet = $class_stylesheet::getById( 1 )
	),
	$verbose_mode
);