<?php

$class_dumper = $class_application::getDumperClass();
$class_entity = $class_application::getEntityClass();
$class_tokens_stream = $class_application::getTokensStreamClass( NAMESPACE_CID );

$host = $class_tokens_stream::getHost();
$source_classes_names = '/includes/test.php';

$format = $class_entity::getDefaultType( NULL, ENTITY_FORMAT )->{PROPERTY_VALUE};
$path = PROTOCOL_TOKEN . '://' . $host . $source_classes_names;
$source = '<?php echo "hello world";';

$properties = array(
	PROPERTY_MODE_ACCESS => FILE_ACCESS_MODE_WRITE_ONLY,
	PROPERTY_PATH => $path,
	PROPERTY_SIGNAL => $source
);

$bytes = $class_tokens_stream::setSubsequence( $properties );

fprint( array( '[number of bytes written]', $bytes ), $verbose_mode );

/**
*************
* Changes log
*
*************
* 2011 10 09
*************
* 
* Implement unit test to set a subsequence in an existing stream
*
* (branch 0.1 :: revision :: 699)
* (branch 0.2 :: revision :: 384)
*
*/