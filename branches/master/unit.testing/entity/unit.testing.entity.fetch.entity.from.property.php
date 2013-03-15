<?php

$class_data_fetcher = $class_application::getDataFetcherClass();

$class_dumper = $class_application::getDumperClass();

$entity_form = $class_data_fetcher::getEntityByProperty( 'sefi\form', PROPERTY_NAME );

$class_dumper::log(
    __METHOD__,
    array( $entity_form ),
    $verbose_mode
);