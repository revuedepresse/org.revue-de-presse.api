<?php

$class_dumper = $class_application::getDumperClass();

$class_placeholder = $class_application::getPlaceholderClass();

$placeholder = $class_placeholder::make(
	'{selector}',
	'truc'
);

$class_dumper::log(
	__METHOD__,
	array(
		'$placerholder',
		$placeholder,
		'$placerholder->sync()',
		$placeholder->sync()
	),
	$verbose_mode
);