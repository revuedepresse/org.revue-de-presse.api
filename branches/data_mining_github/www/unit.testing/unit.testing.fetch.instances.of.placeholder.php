<?php

$class_dumper = $class_application::getDumperClass();

$class_placeholder = $class_application::getPlaceholderClass();

$class_dumper::log(
	__METHOD__,
	array(
		'$placeholder = $class_placeholder::getById( 1 );',
		$placeholder = $class_placeholder::getById( 1 )
	),
	$verbose_mode
);