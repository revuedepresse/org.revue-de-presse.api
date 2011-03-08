<?php
/**
*************
* Changes log
*
*************
* 2011 03 08
*************
* 
* Add the hash property
*
* method affected ::
*
* MESSAGE :: import
*
* (trunk :: revision :: 141)
* 
*************
* 2011 03 05
*************
* 
* Implement message importation
*
* method affected ::
*
* MESSAGE :: import
* 
* (branch 0.1 :: revision :: 570)
*
*/

/**
* Message class
*
* Class for handling message 
* @package  sefi
*/
class Message extends Header
{
	/**
	* Get a signature
	*
	* @param	boolean	$namespace	namespace flag
	* @return	string	signature
	*/
	public static function getSignature( $namespace = TRUE )
	{
		$_class = __CLASS__;

		if ( ! $namespace )

			list( $_namespace, $_class ) = explode( '\\', __CLASS__ );

		return $_class;
	}

	/**
	* Import messages into the database
	*
	* @param	string		$subject	message subject	
	* @param	array		$labels		restriction labels
	* @param	string		$mailbox	mailbox settings
	* @param	resource	$resource	IMAP resource
	* @return	object	Message 	instance
	*/
	public static function import(
		$subject = NULL,
		$labels = NULL,
		$mailbox = NULL,
		$resource = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$class_header = $class_application::getHeaderClass();

		if ( is_null( $subject ) )
		
			$subject = 'slashdot';

		$_messages =

		$max_uids =

		$search_results = array();

		if ( is_null( $mailbox ) && is_null( $resource ) )

			$mailbox = self::getImapMailbox();

		if ( is_null( $resource ) )
		
			$resource = self::openImapStream( $mailbox );

		/**
		*
		* Initialize the search label to be used
		* if no specific parameter is passed as argument
		*
		*/

		if (
			is_null( $labels ) ||
			! is_array( $labels ) ||
			! count( $labels )
		)
		{
			$label = '[Gmail]/All Mail';

			$keywords =  $label . ' ' .SEPARATOR_LABEL_SUBJECT .' ' . $subject;

			$labels = 

			$_labels = array( $keywords => $label );
		}

		while ( list( $index, $label ) = each( $labels ) )
		{
			$keywords = $label . ' ' .SEPARATOR_LABEL_SUBJECT .' ' . $subject;

			if (
				( ! $index && ! isset( $_labels[0] ) ) ||
				$index
			)

				$_labels[$keywords] = $label;
				
			$max_uids[$keywords] = self::fetchMaxUid( $keywords );

			imap_reopen( $resource, $mailbox.$label );			

			list( , $criteria ) = explode( SEPARATOR_LABEL_SUBJECT,  $keywords );

			$criteria = 'SUBJECT "'.$subject.'"';

			$search_results[$label] =
				imap_search( $resource, $criteria, SE_UID )
			;
		}
	
		reset( $_labels );

		/**
		*
		* Display the uids by passing the $verbose_mode parameter
		* as second argument in preproduction
		*
		*/

		fprint(
			array(
				'[search results]',
				$search_results,
				'[maxima]',
				$max_uids
			)
		);
		
		reset( $search_results );

		list( , $uids ) = each( $search_results );

		reset( $search_results );

		while ( list( $label, $uids ) = each( $search_results ) )
		{
			if ( is_array( $uids ) && count( $uids ) )
			{
				$_keywords = $label . ' ' .SEPARATOR_LABEL_SUBJECT .' ' . $subject;
	
				$max_uid = $max_uids[$_keywords];
	
				if ( is_array( $uids ) && count( $uids ) )
				{
					/**
					*
					* Look up the index of the last recorded UID
					* for provided search criteria
					* 
					*/
			
					$max_uid_index = array_search( $max_uid, $uids, TRUE );
		
					if ( $max_uid_index === FALSE )
					
						$max_uid_index = -1;
		
					fprint( array( '[max uid index]', $max_uid_index ) ) ;
		
					imap_reopen( $resource, $mailbox.$label );			
		
					while ( ( list( $index, $uid ) = each( $uids ) ) )
					{
						if ( $index > $max_uid_index )
						{
							/**
							*
							* Save headers and their corresponding messages
							*
							*/
					
							$header = $class_header::make(
								$header_value = imap_fetchheader( $resource, $uid, FT_UID ),
								$uid,
								NULL,
								NULL,
								$label . ' ' . SEPARATOR_LABEL_SUBJECT . ' ' . $subject 
							);
				
							$message = self::make(
								$body = imap_body( $resource, $uid, FT_UID ),
								$header->{PROPERTY_ID}
							);
						}
					}
				}
	
				reset( $uids );
			}
		} 
		
		reset( $search_results );	
	}

	/**
	* Make an instance of the Message class
	*
	* @return	object	Message instance
	*/
	public static function make()
	{
		global $class_application;
		
		$class_dumper = $class_application::getDumperClass();

		$arguments = func_get_args();

		if ( isset( $arguments[0] ) )

			$body_html = $arguments[0];
		else

			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		if ( isset( $arguments[1] ) )

			$header_id = $arguments[1];
		else

			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		if ( ! isset( $arguments[2] ) )

			$type = NULL;
		else

			$type = $arguments[2];

		if ( is_null( $type ) )

		if ( ! isset( $arguments[3] ) )

			$hash = md5(
				$header_id .
					SEPARATOR_LABEL_SUBJECT .
						$body_html
			);
		else

			$hash = $arguments[3];

		if ( is_null( $type ) )

			$message_type = self::getDefaultType();
		else
		{
			$properties = array(
				PROPERTY_NAME => $type,
				PROPERTY_ENTITY => ENTITY_MESSAGE
			);
			
			// fetch the selected store type
			$message_type = self::getTypeValue( $properties );
		}

		$properties = array(
			PROPERTY_BODY_HTML => $body_html,
			PROPERTY_HASH => $hash,
			PROPERTY_TYPE => $message_type,
			PREFIX_TABLE_COLUMN_HEADER.PROPERTY_ID => array(
				PROPERTY_FOREIGN_KEY => $header_id
			)
		);

		return self::add( $properties );
	}
}
