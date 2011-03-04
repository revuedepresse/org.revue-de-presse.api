<?php

$class_dumper = $class_application::getDumperClass();

$class_content = $class_application::getContentClass();

$content = $class_content::make('User Management', 28, ENTITY_STREAM);

$class_dumper::log(
	__METHOD__,
	array($content->sync(), $content),
	$verbose_mode
);