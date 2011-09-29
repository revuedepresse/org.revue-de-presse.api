<?php

$class_dumper = $class_application::getDumperClass();

$class_entity = $class_application::getEntityClass();

$class_dumper::log(
    __METHOD__,
    array(
        '$class_entity::getByName(CLASS_INSIGHT)',
        $class_entity::getByName(CLASS_INSIGHT)
    ),
    $verbose_mode
);

$class_dumper::log(
    __METHOD__,
    array(
        '$class_entity::getByName(CLASS_INSIGHT_NODE)',
        $class_entity::getByName(CLASS_INSIGHT_NODE)
    ),
    $verbose_mode
);

$class_dumper::log(
    __METHOD__,
    array(
        '$class_entity::getByName(CLASS_PHOTOGRAPH)',
        $class_entity::getByName(CLASS_PHOTOGRAPH)
    ),
    $verbose_mode
);

$class_dumper::log(
    __METHOD__,
    array(
        '$class_entity::getById(34)',
        $class_entity::getById(34)
    ),
    $verbose_mode
);
