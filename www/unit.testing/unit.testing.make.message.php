<?php

$class_dumper = $class_application::getDumperClass();

$class_header = $class_application::getHeaderClass();

$class_message = $class_application::getMessageClass();

$header = $class_header::make( '???' );

$message = $class_message::make( '???', $header->{PROPERTY_ID} );

$class_dumper::log(
	__METHOD__,
	array(
		'$message',
		$message
	),
	$verbose_mode
);