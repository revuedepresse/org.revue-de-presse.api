<?php

$class_dumper = $class_application::getDumperClass();

$class_memento = $class_application::getMementoClass();

$class_dumper::log(
	__METHOD__,
	array(
		'$class_memento::write( new StdClass() );',
		$class_memento::write( new StdClass() ),
		'$_SESSION[STORE_PAPER]',
		$_SESSION[STORE_PAPER],
		'$class_memento::write(
			array( PROPERTY_KEY => "memento" )
		);',
		$class_memento::write(
			array( PROPERTY_KEY => "memento" )
		),
		'$_SESSION[STORE_PAPER]',
		$_SESSION[STORE_PAPER],
		'$class_memento::forget( \'memento\' )',
		$class_memento::forget( 'memento' ),		
		'$_SESSION[STORE_PAPER]',
		$_SESSION[STORE_PAPER]		
	),
	$verbose_mode
);