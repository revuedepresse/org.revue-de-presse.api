<?php

$class_dumper = $class_application::getDumperClass();

$class_locksmith = $class_application::getLocksmithClass();

$store = &$class_locksmith::getStore();

$class_dumper::log(
	__METHOD__,
	array(
		'$_SESSION[STORE_LOCK]',
		$_SESSION[STORE_LOCK]
	),
	$verbose_mode
);