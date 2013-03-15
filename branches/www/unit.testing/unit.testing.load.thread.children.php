<?php

$class_dumper = $class_application::getDumperClass();
$class_insight = $class_application::getInsightClass();

$children = $class_insight::loadThreadChildren( 90 );

$class_dumper::log( __METHOD__, array( $children ), $verbose_mode );