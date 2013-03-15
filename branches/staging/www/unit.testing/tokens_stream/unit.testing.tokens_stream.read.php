<?php

$class_dumper = $class_application::getDumperClass();
$class_tokens_stream = $class_application::getTokensStreamClass( NAMESPACE_CID );

$host = $class_tokens_stream::getHost();
$source_classes_names = '/includes/constants.classes.names.php';

$path = PROTOCOL_TOKEN . '://' . $host . $source_classes_names;

# reading first token hash
$offset = 0;
$length = -1;

$subsequence = $class_tokens_stream::getSubsequence(
	$path, FILE_ACCESS_MODE_READ_ONLY, $length, $offset
);

$substream = $class_tokens_stream::getSubstream(
	$path, FILE_ACCESS_MODE_READ_ONLY, $length, $offset
);

$signal = $class_tokens_stream::getSignal(
	$path, FILE_ACCESS_MODE_READ_ONLY, $length, $offset
);

fprint( array(
	'[subsequence]', $subsequence,
	'[substream]', $substream,
	'[signal]', $signal		
), $verbose_mode );

/**
*************
* Changes log
*
*************
* 2011 10 03
*************
* 
* Implement unit test for reading whole Tokens_Stream
*
* (branch 0.1 :: revision :: 674)
* (branch 0.2 :: revision :: 379)
*
*/