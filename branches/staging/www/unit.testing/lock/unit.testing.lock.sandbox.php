<?php

$class_dumper = $class_application::getDumperClass();
$class_lock = $class_application::getLockClass();
$class_locksmith = $class_application::getLocksmithClass();
$class_view_builder = $class_application::getViewBuilderClass();

$store = &$class_locksmith::getStore();

$class_lock::lockEntity( $class_view_builder );

$class_dumper::log(
	__METHOD__,
	array(
		'After locking an entity:',
		'$store',
		$store
	),
	$verbose_mode
);

$class_dumper::log(
	__METHOD__,
	array(
		'Check the lock status:',
		'$class_lock::lockedEntity( $class_view_builder )',
		$class_lock::lockedEntity( $class_view_builder )
	),
	$verbose_mode
);

$class_lock::releaseEntity( $class_view_builder );

$class_dumper::log(
	__METHOD__,
	array(
		'After releasing an entity:',
		'$store',
		$store
	),
	$verbose_mode
);

$class_dumper::log(
	__METHOD__,
	array(
		'Check the lock status:',
		'$class_lock::lockedEntity( $class_view_builder )',
		$class_lock::lockedEntity( $class_view_builder )
	),
	$verbose_mode
);

$class_locksmith::goOutOfBusiness();

$class_dumper::log(
	__METHOD__,
	array(
		'Store after the locksmith went out of business:',
		'$store',
		$store
	),
	$verbose_mode
);

$class_dumper::log(
	__METHOD__,
	array(
		'Check the lock status:',
		'$class_lock::lockedEntity( $class_view_builder )',
		$class_lock::lockedEntity( $class_view_builder )
	),
	$verbose_mode
);
