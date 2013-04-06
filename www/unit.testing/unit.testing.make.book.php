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

$status = BOOK_STATUS_FOUND;

$book = $class_book::make( 2264012978, $status, 'feed contents' );

$class_dumper::log(
	__METHOD__,
	array(
		'$book->sync()',
		$book->sync(),
		'$route',
		$book
	),
	$verbose_mode
);
