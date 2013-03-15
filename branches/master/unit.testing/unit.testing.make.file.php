<?php

$class_dumper = $class_application::getDumperClass();

$class_file = $class_application::getFileClass();

$file = $class_file::make( dirname( __FILE__ ). '/selenium', 1, ENTITY_DIRECTORY );

$class_dumper::log(
	__METHOD__,
	array(
		'$file = $class_file::make( dirname( __FILE__ ). "/selenium", 1, ENTITY_DIRECTORY );',
		$file = $class_file::make( dirname( __FILE__ ). '/selenium', 1, ENTITY_DIRECTORY )
	),
	$verbose_mode
);