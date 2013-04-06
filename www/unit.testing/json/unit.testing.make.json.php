<?php

$class_dumper = $class_application::getDumperClass();
$class_json= $class_application::getJsonClass();

$json = $class_json::make( '{}' );

$class_dumper::log( __METHOD__, array(
	'$json', $json
), $verbose_mode );
