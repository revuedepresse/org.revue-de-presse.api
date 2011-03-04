<?php

$class_api = $class_application::getApiClass();

$class_dumper = $class_application::getDumperClass();

$class_dumper::log(
	__METHOD__,
	array($class_api::updateStatus('test')),
	$verbose_mode
);
