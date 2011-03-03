<?php

$class_dumper = $class_application::getDumperClass();

$class_locksmith = $class_application::getLocksmithClass();

$store = &$class_locksmith::getStore();

$class_dumper::log(
	__METHOD__,
	array(
		'$store = $class_dumper',
		$store = $class_dumper,
		'$_SESSION[STORE_LOCK]',
		$_SESSION[STORE_LOCK]
	),
	$verbose_mode
);

$class_locksmith::goOutOfBusiness();

$class_dumper::log(
	__METHOD__,
	array(
		'Is the locksmith store still opened?',
		'isset( $_SESSION[STORE_LOCK] )',
		isset( $_SESSION[STORE_LOCK] )
	),
	$verbose_mode
);
