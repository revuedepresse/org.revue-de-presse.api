<?php

$class_dumper = $class_application::getDumperClass();

$class_dumper::log(
	__METHOD__,
	array(
		'photograph of id #87 can be loaded from: ',
		'http://## FILL HOSTNAME ##/affordance.load.photograph.php?i=87'),
	$verbose_mode
);

// Fetch contents
$class_application::displayResource(
	HOSTNAME_TIFA.
	AFFORDANCE_LOAD."-".
	ENTITY_PHOTOGRAPH."-".
	$_GET[GET_IDENTIFIER]
);
