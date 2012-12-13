<?php

$ns = NAMESPACE_CID;

$class_dumper = $class_application::getDumperClass();
$class_data_miner = $class_application::getDataMinerClass();
$class_tokens_stream = $class_application::getTokensStreamClass( $ns );

$store = array( PROPERTY_URI_REQUEST => '/includes/test.php' );

$accessor = $class_data_miner::getFunctionCallsSubstream( $store, TRUE );

fprint(
	$class_tokens_stream::renderSignal(
		$signal = $class_tokens_stream::buildSignal( $accessor[PROPERTY_STREAM] ),
		//RENDER_TYPE_SIGNAL,
		RENDER_TYPE_BLOCK,
		TRUE
	),
	$verbose_mode
);
