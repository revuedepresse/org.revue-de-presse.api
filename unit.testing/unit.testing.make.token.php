<?php

$class_dumper = $class_application::getDumperClass();
$class_token = $class_application::getTokenClass();

$token = $class_token::make( 'test', PROPERTY_SECRET_OAUTH ); 

$class_dumper::log(
	__METHOD__,
	array( $token ),
	$verbose_mode
);