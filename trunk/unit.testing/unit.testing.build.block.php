<?php

$class_view_builder = $class_application::getViewBuilderClass();

$class_dumper = $class_application::getDumperClass();

$class_dumper::log(
	__METHOD__,
	array(
		'echo $class_view_builder::buildBlock(PAGE_DIALOG, BLOCK_HEADER);',
	),
	$verbose_mode
);

echo $class_view_builder::buildBlock(PAGE_DIALOG, BLOCK_HEADER);