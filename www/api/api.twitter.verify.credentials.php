<?php

$class_dumper = $class_application::getDumperClass();

$class_api = $class_application::getApiClass();

// verify credentials validity
$class_api::verifyCredentials();