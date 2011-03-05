<?php
/**
*************
* Changes log
*
*************
* 2011 03 05
*************
* 
* Prepare loading of latest messages only
*
* (trunk :: revision :: 84)
*
*/

// the Header inherit from the Api class
$class_header = $class_application::getHeaderClass();

$class_message = $class_application::getMessageClass();

$class_dumper = $class_application::getDumperClass();

$results = $class_header::getImapSearchResults( 'SUBJECT "slashdot"' );

$messages = $class_header::getImapMessages( $results );

if ( is_array( $messages )  && count( $messages ) )
{
	end( $properties );
	list( $uid ) = each( $properties );

	$class_dumper::log(
		__METHOD__,
		array(
			'[latest uid]',
			$uid
		),
		TRUE
	);	

	//foreach ( $messages as $uid => $properties )
	//{
	//	$header = $class_header::make(
	//		$properties[PROPERTY_HEADER],
	//		$uid
	//	);
	//
	//	$message = $class_message::make(
	//		$properties[PROPERTY_BODY],
	//		$header->{PROPERTY_ID}
	//	);
	//}
}
