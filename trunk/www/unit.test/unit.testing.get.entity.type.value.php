<?php

$class_data_fetcher = $class_application::getDataFetcherClass();

$class_entity = $class_application::getEntityClass();

$properties =  array(
    PROPERTY_NAME => PROPERTY_PRIMARY_KEY,
    PROPERTY_ENTITY => ENTITY_CONSTRAINT
);

$class_dumper::log(
    __METHOD__,
    array($class_entity::getDefaultType(TRUE, ENTITY_STORAGE)),
    true
);

$class_dumper::log(
    __METHOD__,
    array(
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
        $class_data_fetcher::getTypeValue($properties)
    ),
    $verbose_mode
);

$class_dumper::log(
    __METHOD__,
    array(
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