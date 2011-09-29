<?php

$class_dumper = $class_application::getDumperClass();

$class_form = $class_application::getFormClass(NAMESPACE_SEMANTIC_FIDELITY);

$class_weaver = $class_application::getWeaverClass();

$form = $class_form::getById( 5 );

$form_extended = $class_weaver::dereference(
	$form,
	array(
		PROPERTY_STORE => $class_application::getStoreClass(),
		PROPERTY_ROUTE => $class_application::getRouteClass()
	)								   
);

$class_dumper::log(
	__METHOD__,
	array(
		'$form = $class_form::getById( 5 );',
		$form
	),
	$verbose_mode
);

$class_dumper::log(
	__METHOD__,
	array(
		'$class_weaver::dereference(
			$form,
			array(
				PROPERTY_STORE => $class_application::getStoreClass(),
				PROPERTY_ROUTE => $class_application::getRouteClass()
		
			)
		);',
		$form_extended
	),
	$verbose_mode
);