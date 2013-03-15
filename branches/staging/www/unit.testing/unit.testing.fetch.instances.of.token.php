<?php

$class_dumper = $class_application::getDumperClass();

$class_token = $class_application::getTokenClass();

$token = $class_token::getById( 1 );

$class_dumper::log(
	__METHOD__,
	array(
		'$token = $class_token::getById( 1 )',
		$token
	),
	$verbose_mode
);