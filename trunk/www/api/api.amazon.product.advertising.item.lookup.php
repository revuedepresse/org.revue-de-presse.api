<?php 

$class_api = $class_application::getApiClass();

$class_dumper = $class_application::getDumperClass();

$isbn = 2264012978;

// Get a book by providing its ISBN
$response = $class_api::lookUpBookByISBN( $isbn );

$class_dumper::log(
    __METHOD__,
    array(
        '[book look up by ISBN]',
        $response
    ),
    $verbose_mode
);