<?php

$class_book = $class_application::getBookClass();

$class_dumper = $class_application::getDumperClass();

$book = $class_book::getById( 329838 );

$class_dumper::log(
	__METHOD__,
	array(
		'$book  = $class_book::getById( 329838 )',
		$book
	),
	$verbose_mode
);