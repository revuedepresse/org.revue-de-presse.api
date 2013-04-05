<?php

$class_dumper = $class_application::getDumperClass();
$class_tokens_stream = $class_application::getTokensStreamClass( NAMESPACE_CID );

$host = $class_tokens_stream::getHost();
$source_classes_names = '/includes/constants.classes.names.php';

$path = PROTOCOL_TOKEN . '://' . $host . $source_classes_names;

# reading first token hash
$offset = 0;
$length = 1;

$subsequence = $class_tokens_stream::getSubsequence(
	$path, FILE_ACCESS_MODE_READ_ONLY, $length, $offset
);

$offset = 1;
$length = 5;

$substream_from_1 = $class_tokens_stream::getSubstream(
	$path, FILE_ACCESS_MODE_READ_ONLY, $length, $offset
);

# reading from start position
$offset = 0;
$length = 5;

$substream_from_0 = $class_tokens_stream::getSubstream(
	$path, FILE_ACCESS_MODE_READ_ONLY, $length, $offset
);

fprint( array(
	'[first token hash]', $subsequence,
	'[5 first tokens rendered as arrays]', $substream_from_1,
	'[5 first tokens rendered as arrays by omitting opening PHP tag]',
	$substream_from_0
), $verbose_mode );

/**
*************
* Changes log
*
*************
* 2011 10 07
*************
* 
* Implement unit test for registering Tokens_Stream wrapper
*
* (branch 0.1 :: revision :: 665)
* (branch 0.2 :: revision :: 373)
*
*/