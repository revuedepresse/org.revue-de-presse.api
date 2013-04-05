<?php

$class_dumper = $class_application::getDumperClass();

$sentence = 'Flipboard submits "HTML5 extensions to W3C that make it easier to use elements such as pull quotes and sub-heads."

http://j.mp/gcHOM9';

$class_dumper::log(
	__METHOD__,
	array(
		'[shortened sentence]',
		$class_application::shorten_sentence( $sentence )
	),
	$verbose_mode
);
