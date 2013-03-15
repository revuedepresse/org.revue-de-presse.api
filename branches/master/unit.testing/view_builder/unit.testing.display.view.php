<?php

$class_jquery4php = $class_application::getJquery4phpClass();
$class_standard_class = $class_application::getStandardClass();
$class_view_builder = $class_application::getViewBuilderClass();

$context = new $class_standard_class();

$context->{PROPERTY_CONTAINER} = array(
	HTML_ELEMENT_DIV => array(
		HTML_ATTRIBUTE_CLASS =>
			STYLE_CLASS_VIEW
	)
);

$jquery_snippet_alert =
	$class_jquery4php::newInstance()
	->onClick()
	->in('#body')
	->execute('alert("Hello World")')
;

$jquery_snippet_load_form_send_feedback =
	$class_jquery4php::newInstance()
		->execute(
		$class_jquery4php::load(
			URI_AFFORDANCE_SEND_FEEDBACK.' '.INTERNAL_ANCHOR_SEND_FEEDBACK,
			array()
		)
		->in('.view')
	)
;

$context->{PROPERTY_BODY} = $jquery_snippet_alert;
$context->{PROPERTY_BODY} = $jquery_snippet_load_form_send_feedback;
$context->{PROPERTY_CACHE_ID} = md5( $jquery_snippet_alert );

$class_view_builder::display( $context, VIEW_TYPE_INJECTION );
