<?php

$class_dumper = $class_application::getDumperClass();
$class_insight = $class_application::getInsightClass();

$parents = $class_insight::loadThreadParents( 90 );

$class_dumper::log( __METHOD__, array( $parents ), $verbose_mode );