<?php

$class_dumper = $class_application::getDumperClass();

$class_tag_form = $class_application::getTagFormClass();

$form_rendering = $class_tag_form::render( 'post' );

echo $form_rendering;

$class_dumper::log(
	__METHOD__,
	array( $form_rendering ),
	$verbose_mode
);