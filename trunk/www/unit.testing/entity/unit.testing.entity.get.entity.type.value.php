<?php

$class_data_fetcher = $class_application::getDataFetcherClass();

$class_dumper = $class_application::getDumperClass();

$class_entity = $class_application::getEntityClass();

$properties =  array(
    PROPERTY_NAME => PROPERTY_PRIMARY_KEY,
    PROPERTY_ENTITY => ENTITY_CONSTRAINT
);

$class_dumper::log(
    __METHOD__,
    array(
		'[default entity type of entity "storage"]',
		$class_entity::getDefaultType( NULL, ENTITY_STORAGE ) ),
    $verbose_mode
);

$class_dumper::log(
    __METHOD__,
    array(
		'[target of type visitor]',
		$target_type_visitor = $class_entity::getTypeValue(
            array(
                PROPERTY_NAME => ENTITY_VISITOR,
                PROPERTY_ENTITY => ENTITY_TARGET
            )
        )
    ),
    $verbose_mode
);
    
$class_dumper::log(
    __METHOD__,
    array(
		'[value of an entity type - entity "constraint" of type "primary key"]',
        $class_data_fetcher::getTypeValue( $properties )
    ),
    $verbose_mode
);

$class_dumper::log(
    __METHOD__,
    array(
		'[value of entity type - entity "event" of type "instantiate entity"]',
        $class_data_fetcher::getEntityTypeValue(
            array(
                PROPERTY_NAME => $class_data_fetcher::getEntityTypeValue(
                    array(
                        PROPERTY_NAME => str_replace('.', '_', ACTION_INSTANTIATE_ENTITY),
                        PROPERTY_ENTITY => ENTITY_OPERATION
                    )
                ),
                PROPERTY_ENTITY => ENTITY_EVENT
            )
        )
    ),
    $verbose_mode
);