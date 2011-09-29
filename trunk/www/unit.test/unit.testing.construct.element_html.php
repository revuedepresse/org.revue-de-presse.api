<?php

$class_dom_document = $class_application::getDomDocumentClass();

$class_dumper = $class_application::getDumperClass();

$class_element_html = $class_application::getElementHtmlClass();

$dom_document = new $class_dom_document();

$element_div = $dom_document->createElement( HTML_ELEMENT_DIV );

$element_div->setAttribute( HTML_ATTRIBUTE_CLASS, 'class_name' );

$properties = array(
	PROPERTY_DOM_DOCUMENT => $dom_document,
	PROPERTY_DOM_ELEMENT => $element_div
);

$element_html = new $class_element_html( $properties );

$element_html->wrap();

if ( $verbose_mode )

	echo $element_html->export( FORMAT_TYPE_XML, FALSE );

$class_dumper::log(
	__METHOD__,
	array(
		'$element_html',
		$element_html,
		'$element_html->{PROPERTY_ATTRIBUTES}',
		$element_html->{PROPERTY_ATTRIBUTES},
		'$element_html->{PROPERTY_DOM_DOCUMENT}',
		$element_html->{PROPERTY_DOM_DOCUMENT},		
		'$element_html->{PROPERTY_DOM_ELEMENT}',
		$element_html->{PROPERTY_DOM_ELEMENT},
		'( string ) $element_html;',
		( string ) $element_html,
		'$element_html->export( FORMAT_TYPE_XML, FALSE );',
		$element_html->export( FORMAT_TYPE_XML, FALSE )
	),
	$verbose_mode
);