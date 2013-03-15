<?php

$class_arc = $class_application::getArcClass();

$arc = $class_arc::getByDestinationKey(2);

$class_dumper::log(
    __METHOD__,
    array($arc->getSourceEdge()),
    $verbose_mode
);