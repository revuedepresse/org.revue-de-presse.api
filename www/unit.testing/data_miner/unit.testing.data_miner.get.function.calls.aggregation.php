<?php

$ns = NAMESPACE_CID;

$class_data_miner = $class_application::getDataMinerClass();
$class_dumper = $class_application::getDumperClass();
$class_standard_class = $class_application::getStandardClass();
$class_tokens_stream = $class_application::getTokensStreamClass( $ns );
$class_view_builder = $class_application::getViewBuilderClass();

$context = new $class_standard_class();

$store = array(
	PROPERTY_FUNCTION => array(
		FUNCTION_ASSIGN_CONSTANT,
		FUNCTION_DEFINED,
		FUNCTION_FUNCTION_EXISTS,
		FUNCTION_SPRINTF
	),
	PROPERTY_URI_REQUEST => '/includes/constants.classes.names.php'
);

$accessor = $class_data_miner::aggregate( $store, NULL );

fprint( array( '[accessor]', $accessor ) );

fprint( array(
	'[signal rendering]',
	$render = $class_tokens_stream::renderSignal(
		$signal = $class_tokens_stream::buildSignal(
			$accessor[PROPERTY_STREAM]
		),
		//RENDER_TYPE_SIGNAL,
		RENDER_TYPE_BLOCK,
		TRUE
	)
) );

$context->{PROPERTY_BODY} = $render;
$context->{PROPERTY_CACHE_ID} = md5( $render );
$context->{PROPERTY_CONTAINER} = array(
	HTML_ELEMENT_DIV => array(
		HTML_ATTRIBUTE_CLASS =>
			STYLE_CLASS_VIEW
	)
);

$class_view_builder::display( $context, VIEW_TYPE_INJECTION );
