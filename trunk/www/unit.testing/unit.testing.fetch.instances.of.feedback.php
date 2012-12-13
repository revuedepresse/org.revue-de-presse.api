<?php

$class_dumper = $class_application::getDumperClass();

$class_feedback = $class_application::getFeedbackClass();

$class_dumper::log(
	__METHOD__,
	array(
		'$feedback = $class_feedback::getById( 1 );',
		$feedback = $class_feedback::getById( 1 )
	),
	$verbose_mode
);