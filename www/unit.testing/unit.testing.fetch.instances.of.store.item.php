<?php

$class_dumper = $class_application::getDumperClass();

$class_store_item = $class_application::getStoreItemClass();

$store_item = $class_store_item::getById( 1 );

$class_dumper::log(
	__METHOD__,
	array(
		'$store_item = $class_store_item::getById( 1 )',
		$store_item
	),
	$verbose_mode
);

$class_dumper::log(
	__METHOD__,
	array(
		'$store_item = $class_store_item::getById( 2 )',
		$store_item = $class_store_item::getById( 2 )
	),
	$verbose_mode
);
