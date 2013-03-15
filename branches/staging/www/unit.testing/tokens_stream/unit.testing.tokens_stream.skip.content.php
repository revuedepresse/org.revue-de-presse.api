<?php

$class_dumper = $class_application::getDumperClass();
$class_tokens_stream = $class_application::getTokensStreamClass( NAMESPACE_CID );

$host = $class_tokens_stream::getHost();
$source_classes_names = '/includes/constants.classes.names.php';

$path = PROTOCOL_TOKEN . '://' . $host . $source_classes_names;

# reading overflow 
$length = 1690;
$offset = 1371;

$tokens = $class_tokens_stream::getToken(
	$path, FILE_ACCESS_MODE_READ_ONLY, $length, $offset
);

fprint( array(
	'[last tokens rendered as constants with overflow]',
	$tokens
), $verbose_mode );

/**
*************
* Changes log
*
*************
* 2011 10 07
*************
* 
* Implement unit test for reading stream by skipping some content
*
* (branch 0.1 :: revision :: 685)
* (branch 0.2 :: revision :: 383)
*
*/