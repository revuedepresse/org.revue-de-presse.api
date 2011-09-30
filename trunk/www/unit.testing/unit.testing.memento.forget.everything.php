<?php

$class_dumper = $class_application::getDumperClass();

$class_memento = $class_application::getMementoClass();

$class_dumper::log(
	__METHOD__,
	array(
		'$class_memento::forgetEverything();',
		$class_memento::forgetEverything(),
		'$_SESSION[STORE_PAPER]',
		$_SESSION[STORE_PAPER]		
	),
	$verbose_mode
);