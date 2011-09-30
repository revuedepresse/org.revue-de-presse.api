<?php

$class_dumper = $class_application::getDumperClass();

$class_store = $class_application::getStoreClass();

$store = $class_store::make('prepare user management data'); 

$class_dumper::log(
	__METHOD__,
	array($store),
	$verbose_mode
);