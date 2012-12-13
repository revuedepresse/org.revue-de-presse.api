<?php

$class_dumper = $class_application::getDumperClass();

$class_store_item = $class_application::getStoreItemClass();

$store_item = $class_store_item::make(2, 1);

$class_dumper::log(
	__METHOD__,
	array($store_item),
	$verbose_mode
);