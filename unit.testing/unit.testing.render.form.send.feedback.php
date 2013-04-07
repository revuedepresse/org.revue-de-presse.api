<?php

$class_dumper = $class_application::getDumperClass();

$class_dumper::log(
	__METHOD__,
	array(
		'$class_application::getFormView( AFFORDANCE_SEND_FEEDBACK );',
		$class_application::getFormView( AFFORDANCE_SEND_FEEDBACK )
	),
	$verbose_mode
);
