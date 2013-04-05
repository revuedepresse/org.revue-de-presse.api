<?php

$class_dumper = $class_application::getDumperClass();

$class_memento = $class_application::getMementoClass();

$class_dumper::log(
	__METHOD__,
	array(
		'$class_memento::write(
			array( PROPERTY_KEY => "memento" )
		);',
		$class_memento::write(
			array( PROPERTY_KEY => "memento" )
		),		
		'$class_memento::remind( \'memento\' );',
		$class_memento::remind( 'memento' )
	),
	$verbose_mode
);