<?php

$class_dumper = $class_application::getDumperClass();

$class_content = $class_application::getContentClass();

$content = $class_content::getById( 6 );

$class_dumper::log(
	__METHOD__,
	array(
		'$content = $class_content::getById( 6 );',
		$content
	),
	$verbose_mode
);

