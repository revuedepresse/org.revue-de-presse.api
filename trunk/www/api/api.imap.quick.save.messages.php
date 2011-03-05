<?php
/**
*************
* Changes log
*
*************
* 2011 03 05
*************
* 
* Start revising the message saving methods from the IMAP API
* for optimizing performances 
*
* (trunk :: revision :: 89)
*
*/

// the Header inherit from the Api class
$class_header = $class_application::getHeaderClass();

$class_message = $class_application::getMessageClass();

$class_dumper = $class_application::getDumperClass();

$criteria = 'SUBJECT "slashdot"';

$last_uid_recorded = 110213;

$mailbox =

$resource = NULL;

$_messages =

$search_results = array();

if ( is_null( $mailbox ) && is_null( $resource ) )

	$mailbox = $class_header::getImapMailbox();

if ( is_null( $resource ) )

	$resource = $class_header::openImapStream( $mailbox );

if ( is_null( $labels ) )

	$labels = array( $mailbox.'[Gmail]/All Mail' );

while ( list( , $label ) = each( $labels ) )
{
	imap_reopen( $resource, $label );			

	$search_results[$label]  =
		imap_search( $resource, $criteria, SE_UID )
	;
}

reset( $search_results );

//list( , $uids ) = each( $search_results );
//reset( $search_results );
//
//end( $uids );
//list( , $uid ) = each( $uids );
//reset( $uids );
//
//
//if ( $verbose_mode )
//
//	echo 'last uid: ', $uid;
//
//$class_dumper::log(
//	__METHOD__,
//	array(
//		'[last uid]',
//		$uid
//	),
//	$verbose_mode
//);

$last_uid_recorded_index = array_search( $last_uid_recorded, $uids );

while ( list( $label, $uids ) = each( $search_results ) )
{
	while (
		( list( $index, $uid ) = each( $uids ) )
	)
	{
		if ( $index > $last_uid_recorded_index )
		{
			imap_reopen( $resource, $label );
	
			$header = $class_header::make(
				imap_fetchheader( $resource, $uid, FT_UID ),
				$uid
			);

			$message = $class_message::make(
				$body = imap_fetchbody( $resource, $uid, '1', FT_UID ),
				$header->{PROPERTY_ID}
			);

			if ( $verbose_mode )

				echo
					'[current uid] <br /><br />', $uid, '<br />', '<br />',
					'[message body] <br /><br />', $body,
					'<br /><br /><br /><br />'
				;
		}
	}

	reset( $uids );
}

reset( $search_results );
