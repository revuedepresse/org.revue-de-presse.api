<?php

$class_arc = $class_application::getArcClass();

$class_dumper = $class_application::getDumperClass();

$class_dumper::log(
    __METHOD__,
    array($class_arc::fetchDefaultType()),
    true
);