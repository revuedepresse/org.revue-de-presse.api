<?php

$class_file_manager = $class_application::getFileManagerClass();

$class_feed_reader = $class_application::getFeedReaderClass();

$file_path = '## FILL ABSOLUTE PATH ##';

$metadata = $class_file_manager::extractMetadata(
	array(PROPERTY_PATH => $file_path),
	METADATA_TYPE_RDF
);