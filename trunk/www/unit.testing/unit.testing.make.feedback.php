<?php

$class_dumper = $class_application::getDumperClass();

$class_feedback = $class_application::getFeedbackClass();

$feedback = $class_feedback::make( 'body', 'title', 1 );

$class_dumper::log(
	__METHOD__,
	array(
		'$feedback->sync()',
		$feedback->sync(),
		'$feedback',
		$feedback
	),
	$verbose_mode
);