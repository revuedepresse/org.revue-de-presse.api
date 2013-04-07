<?php

$class_arc = $class_application::getArcClass();

$class_edge = $class_application::getEdgeClass();

$class_dumper::log(
    __METHOD__,
    array(
        '$class_arc::getByDestination(
            $class_edge::getByConditions(
                array(
                    PROPERTY_KEY => 2,
                    PROPERTY_FOREIGN_KEY => array(
                        PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID =>
                            $class_edge::getByName(ENTITY_INSIGHT_NODE, NULL, CLASS_ENTITY)->{PROPERTY_ID}
                    )
                )
            )
        )',
        $class_arc::getByDestination(
            $class_edge::getByConditions(
                array(
                    PROPERTY_KEY => 2,
                    PROPERTY_FOREIGN_KEY => array(
                        PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID =>
                            $class_edge::getByName(ENTITY_INSIGHT_NODE, NULL, CLASS_ENTITY)->{PROPERTY_ID}
                    )
                )
            )
        )
    ),
    $verbose_mode
);

$class_dumper::log(
    __METHOD__,
    array(
        '$class_arc::getByDestination($class_edge::getByKey(2))',
        $class_arc::getByDestination($class_edge::getByKey(2))
    ),
    $verbose_mode
);

$class_dumper::log(
    __METHOD__,
    array(
        '$class_arc::getByDestinationKey(2)',
        $class_arc::getByDestinationKey(2)
    ),
    $verbose_mode
);