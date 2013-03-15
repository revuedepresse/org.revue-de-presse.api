<?php

$class_api = $class_application::getApiClass();

$class_dumper = $class_application::getDumperClass();

$results = $class_api::getImapSearchResults( 'SUBJECT "slashdot"' );

$class_dumper::log(
	__METHOD__,
	array( $class_api :: getImapMessages( $results ) ),
	TRUE
);