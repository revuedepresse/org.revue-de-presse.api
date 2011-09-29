<?php

$class_dumper = $class_application::getDumperClass();

$class_memento = $class_application::getMementoClass();

$class_dumper::log(
	__METHOD__,
	array(
		'[memcached server statistics]',
			$class_memento::getStatistics()
	),
	$verbose_mode
);

