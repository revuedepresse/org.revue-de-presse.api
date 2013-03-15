<?php

$class_api = $class_application::getApiClass();

$class_data_fetcher = $class_application::getDataFetcherClass();

$class_dumper = $class_application::getDumperClass();

$service_type_amazon = $class_data_fetcher::getEntityTypeValue(
	array(
		PROPERTY_NAME => APPLICATION_AMAZON,
		PROPERTY_ENTITY => ENTITY_SERVICE 
	)
);

$store_parent_type_merchant_platform = $class_data_fetcher::getEntityTypeValue(
	array(
		PROPERTY_NAME => APPLICATION_E_COMMERCE,
		PROPERTY_ENTITY => ENTITY_MERCHANT_PLATFORM
	)
);
		
list( $service_amazon, $service_type_default ) = $class_api::checkService( $service_type_amazon );

$class_dumper::log(
	__METHOD__,
	array(
		'[service amazon]', $service_amazon, '[service type amazon]', $service_type_amazon,
		'[service default type]', $service_type_default,
		'[parent store of merchant platform type]', $store_parent_type_merchant_platform
	),
	$verbose_mode
);

$store = $class_api::initializeStore( $service_amazon, $store_parent_type_merchant_platform );

$class_dumper::log(
    __METHOD__,
    array( '[store]', $store),
    $verbose_mode
);