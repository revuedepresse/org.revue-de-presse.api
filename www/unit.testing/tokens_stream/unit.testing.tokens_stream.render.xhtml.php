<?php

$class_dumper = $class_application::getDumperClass();
$class_entity = $class_application::getEntityClass();
$class_tokens_stream = $class_application::getTokensStreamClass( NAMESPACE_CID );

$host = $class_tokens_stream::getHost();
$source_classes_names = '/includes/constants.classes.names.php';

$format = $class_entity::getDefaultType( NULL, ENTITY_FORMAT )->{PROPERTY_VALUE};
$render_type_default = $class_entity::getDefaultType( NULL, ENTITY_RENDER )->{PROPERTY_VALUE};

$offset = 0;
$length = -1;
$path = PROTOCOL_TOKEN . '://' . $host . $source_classes_names;
//$render = $render_type_default;
//$render = RENDER_TYPE_SIGNAL;
$render = RENDER_TYPE_TOKEN;

$properties = array(
	PROPERTY_FORMAT => $format,
	PROPERTY_MODE_ACCESS => FILE_ACCESS_MODE_READ_ONLY,
	PROPERTY_LENGTH => $length,
	PROPERTY_OFFSET => $offset,
	PROPERTY_PATH => $path,
	PROPERTY_RENDER => $render
);

$render = $class_tokens_stream::render( $properties );

fprint( $render, $verbose_mode );

/**
*************
* Changes log
*
*************
* 2011 10 03
*************
* 
* Implement unit test for rendering Tokens_Stream as XHTML document
*
* (branch 0.1 :: revision :: 674)
* (branch 0.2 :: revision :: 379)
*
*/