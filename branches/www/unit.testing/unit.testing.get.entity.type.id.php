<?php

$class_data_fetcher = $class_application::getDataFetcherClass();

$class_dumper = $class_application::getDumperClass();

$property_id =  array(
    PROPERTY_ID => 202,
    PROPERTY_ENTITY => ENTITY_SERVICE
);

$class_dumper::log(
    __METHOD__,
    array(
		'[get the id of an entity type by providing an entity and a entity type id]',
        $class_data_fetcher::getTypeId( $property_id )
    ),
    $verbose_mode
);

$property_value =  array(
    PROPERTY_VALUE => 0,
    PROPERTY_ENTITY => ENTITY_SERVICE
);

$class_dumper::log(
    __METHOD__,
    array(
		'[get the id of an entity type by providing an entity and a entity type value]',
        $class_data_fetcher::getTypeId( $property_value )
    ),
    $verbose_mode
);

$property_name =  array(
    PROPERTY_NAME => PROPERTY_PRIMARY_KEY,
    PROPERTY_ENTITY => ENTITY_CONSTRAINT
);

$class_dumper::log(
    __METHOD__,
    array(
		'[get the id of an entity type by providing an entity and a entity type name]',
        $class_data_fetcher::getTypeId( $property_name )
    ),
    $verbose_mode
);

$operation_type_value = $class_data_fetcher::getEntityTypeValue(
    array(
        PROPERTY_NAME => str_replace('.', '_', ACTION_INSTANTIATE_ENTITY),
        PROPERTY_ENTITY => ENTITY_OPERATION
    )
);

$class_dumper::log(
    __METHOD__,
    array(
        $class_data_fetcher::getEntityTypeId(
            array(
                PROPERTY_NAME => $operation_type_value,
                PROPERTY_ENTITY => ENTITY_EVENT
            )
        )
    ),
    $verbose_mode
);

$class_dumper::log(
    __METHOD__,
    array(
		'[extended form of retrieval by entity type value]',
        $class_data_fetcher::getEntityTypeId( $property_value )
    ),
    $verbose_mode
);