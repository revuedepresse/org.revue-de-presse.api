<?php

$class_dumper = $class_application::getDumperClass();

$class_form = $class_application::getFormClass(NAMESPACE_SEMANTIC_FIDELITY);

$form = $class_form::make('Manage users', 29, 1);

$class_dumper::log(
	__METHOD__,
	array(
		'$form->sync()',
		$form->sync(),
		'$form',
		$form
	),
	$verbose_mode
);