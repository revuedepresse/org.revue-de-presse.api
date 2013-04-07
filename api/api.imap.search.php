<?php

$class_api = $class_application::getApiClass();

$class_dumper = $class_application::getDumperClass();

$results = $class_api::getImapSearchResults( 'SUBJECT "slashdot"' );

if ( $verbose_mode )

	echo serialize( $results );