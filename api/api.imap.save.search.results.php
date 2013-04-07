<?php

// the Header inherit from the Api class
$class_header = $class_application::getHeaderClass();

$class_message = $class_application::getMessageClass();

$class_dumper = $class_application::getDumperClass();

$class_header::saveSearchResults( 'SUBJECT "slashdot"' );
	