<?php

$class_data_fetcher = $class_application::getDataFetcherClass();
$class_dumper = $class_application::getDumperClass();

$database = DB_SEFI;
$table = TABLE_FORM;

$constraint_type_unique = $class_data_fetcher::getEntityTypeValue( array(
	PROPERTY_NAME => PROPERTY_UNIQUE,
	PROPERTY_ENTITY => ENTITY_CONSTRAINT
) );

$constraints_unique = $class_data_fetcher::getTableConstraints(
	$table,
	$database,
	$constraint_type_unique,
	$verbose_mode
);

$class_dumper::log(
	__METHOD__,
	array( $constraints_unique ),
	$verbose_mode
);