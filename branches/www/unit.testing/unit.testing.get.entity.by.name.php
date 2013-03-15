<?php

$class_dumper = $class_application::getDumperClass();

$class_entity = $class_application::getEntityClass();

$class_dumper::log(
    __METHOD__,
    array(
        '$class_entity::getByName( ENTITY_SERVICE )',
        $class_entity::getByName( ENTITY_SERVICE )
    ),
    $verbose_mode
);

