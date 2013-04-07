<?php

$class_dumper = $class_application::getDumperClass();

$class_entity = $class_application::getEntityClass();

$class_dumper::log(
    __METHOD__,
    array(
        '$class_entity::getType(...)',
        $class_entity::getType(
			array(
				PROPERTY_NAME => 'amazon',
				PROPERTY_ENTITY => ENTITY_SERVICE
			)
		)
    ),
    $verbose_mode
);

