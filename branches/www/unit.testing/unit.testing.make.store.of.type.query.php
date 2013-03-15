<?php

$class_dumper = $class_application::getDumperClass();

$class_store = $class_application::getStoreClass();

$store = $class_store::make('fetch user data', ENTITY_QUERY); 

$class_dumper::log(
	__METHOD__,
	array($store),
	$verbose_mode
);