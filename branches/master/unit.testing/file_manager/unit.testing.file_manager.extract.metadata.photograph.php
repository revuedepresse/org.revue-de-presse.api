<?php

$class_dumper = $class_application::getDumperClass();
$class_feed_reader = $class_application::getFeedReaderClass();
$class_file_manager = $class_application::getFileManagerClass();

$file_path = dirname( __FILE__ ) . '/../../snapshots/02x34rggog4viL1140675.jpg';

$metadata = $class_file_manager::extractMetadata(
	array( PROPERTY_PATH => $file_path ),
	METADATA_TYPE_RDF
);

$class_dumper::log( __METHOD__, array(
	'[metadata extracted from photograph]', $metadata
), $verbose_mode );