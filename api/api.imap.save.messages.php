<?php

// the Header inherit from the Api class
$class_header = $class_application::getHeaderClass();

$class_message = $class_application::getMessageClass();

$class_dumper = $class_application::getDumperClass();

$results = $class_header::getImapSearchResults( 'SUBJECT "slashdot"' );

$messages = $class_header::getImapMessages( $results );

if ( is_array( $messages )  && count( $messages ) ) 

	foreach ( $messages as $uid => $properties )
	{
		$header = $class_header::make( $properties[PROPERTY_HEADER] );

		$message = $class_message::make(
			$properties[PROPERTY_BODY],
			$header->{PROPERTY_ID}
		);
	}