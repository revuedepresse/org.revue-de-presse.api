<?php

$class_dumper = $class_application::getDumperClass();
$class_tokens_stream = $class_application::getTokensStreamClass( NAMESPACE_CID );

$host = $class_tokens_stream::getHost();
$source_classes_names = '/includes/constants.classes.names.php';

$path = PROTOCOL_TOKEN . '://' . $host . $source_classes_names;

$substream = $class_tokens_stream::getSubstream(
	$path, FILE_ACCESS_MODE_READ_ONLY, 5, 0
);

$token = $class_tokens_stream::getToken(
	$path, FILE_ACCESS_MODE_READ_ONLY, 2
);

fprint(  array(
	'[array containing the first 5 tokens]', $substream,
	'[2 next tokens constants - reading resumed from last position]',
	$token
), $verbose_mode ) ;

/**
*************
* Changes log
*
*************
* 2011 10 07
*************
* 
* Implement unit test for resuming stream read
*
* (branch 0.1 :: revision :: 685)
* (branch 0.2 :: revision :: 384)
*
*/