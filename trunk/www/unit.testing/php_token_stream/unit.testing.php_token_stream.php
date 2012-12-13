<?php

$class_dumper = $class_application::getDumperClass();

include_once( 'PHP/Token/Stream/Autoload.php' );

$souce = dirname( __FILE__ ) . '/../includes/functions.php';

$token_stream = new PHP_Token_Stream( $souce );

$class_dumper::log(
	__METHOD__,
	array(
		'[token stream]',
		$token_stream->getFunctions(),
		'[source]',
		$token_stream->getLinesOfCode()
	),
	$verbose_mode
);