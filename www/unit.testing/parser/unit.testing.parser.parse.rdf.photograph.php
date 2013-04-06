<?php

$class_feed_reader = $class_application::getFeedReaderClass();

$file_path=
	'## FILL ABSOLUTE PATH ##''.
	'examples/rdf/wzbupphvxt7ihimg-0399.jpg.rdf'
;

$class_feed_reader::parseRDF( $file_path );
