<?php

$class_dumper = $class_application::getDumperClass();
$class_data_miner = $class_application::getDataMinerClass();

$store = array(
	PROPERTY_URI_REQUEST => '/includes/constants.classes.names.php'
//	PROPERTY_URI_REQUEST => '/includes/class/class.data_fetcher.inc.php'
);

fprint( $class_data_miner::getFunctionCalls( $store, FALSE, TRUE ), $verbose_mode );
