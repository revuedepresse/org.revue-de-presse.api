<?php

$class_dumper = $class_application::getDumperClass();

$class_api = $class_application::getApiClass();

// request a new token to Twitter to be signed after user authentication
$class_api::requestToken( NULL, $verbose_mode );
