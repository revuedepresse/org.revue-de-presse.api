<?php

$class_file_manager = $class_application::getFileManagerClass();

$class_feed_reader = $class_application::getFeedReaderClass();

$file_path = '## FILL ABSOLUTE PATH ##';

$rdf_contents = $class_file_manager::extractMetadata(
	array(PROPERTY_PATH => $file_path),
	METADATA_TYPE_RDF
);

file_put_contents(dirname(__FILE__).'/../rdf/dump.rdf', $rdf_contents);

$class_feed_reader::parseRDF(dirname(__FILE__).'/../rdf/dump.rdf');