<?php

$class_dumper = $class_application::getDumperClass();

$class_store = $class_application::getStoreClass();

$store = $class_store::getById( 1 );

$class_dumper::log(
	__METHOD__,
	array(
		'$store = $class_store::getById( 1 )',
		$store
	),
	$verbose_mode
);

$class_dumper::log(
	__METHOD__,
	array(
		'$store = $class_store::getById( 2 )',
		$store = $class_store::getById( 2 )
	),
	$verbose_mode
);
