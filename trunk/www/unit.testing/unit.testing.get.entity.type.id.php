<?php

$class_data_fetcher = $class_application::getDataFetcherClass();

$class_dumper = $class_application::getDumperClass();

$properties =  array(
    PROPERTY_NAME => PROPERTY_PRIMARY_KEY,
    PROPERTY_ENTITY => ENTITY_CONSTRAINT
);

$class_dumper::log(
    __METHOD__,
    array(
        $class_data_fetcher::getTypeId($properties)
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