<?php

$class_api = $class_application::getApiClass();

$class_dumper = $class_application::getDumperClass();

$class_dumper::log(
	__METHOD__,
	array(
		'[rate limit]',
		$class_api::checkRateLimit()
	),
	$verbose_mode
);
