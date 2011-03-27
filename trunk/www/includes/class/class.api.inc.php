<?php
/**
*************
* Changes log
*
*************
* 2011 03 26
*************
*
* Implement a method totwitter timelines
*
* method affected ::
*
* API :: fetchTimelineStatuses
* 
* (branch 0.1 :: revision 630)
* (trunk :: revision :: 195)
*
*************
* 2011 03 06
*************
*
* Implement a method to get mailboxes
*
* method affected ::
*
* API :: getMailboxes
* API :: getImapMailbox
* API :: getImapStream
*
* (branch 0.1 :: revision :: 576)
* (trunk :: revision :: 125)
*
*************
* 2011 03 05
*************
* 
* Revise the method name used to open imap stream
*
* method affected
*
* API :: openImapStream
* 
* (branch 0.1 :: revision :: 569)
*
*/

/**
* Api class
*
* Class for representing an Api
* @package  sefi
*/
class Api extends Transfer
{
	/**
	* Get an entity store
	*
	* @param	integer	$service	service
	* @param	boolean	$static		static flag
	* @return	&array	store
	*/
	public static function &getEntityStore( $service = NULL, $static = TRUE )
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$class_standard_class = $class_application::getStandardClass();

		list( $service, $service_type_default ) = self::checkService( $service );

		$signature = static::getSignature();

		if ( is_null( self::$store ) || ! is_array( self::$store ) )

			self::$store = array();

		if ( $static && ! isset( self::$store[$signature] ) )			

			self::$store[$signature] = array();

		if ( $static )
		{
			if ( ! isset( self::$store[$signature][$service] ) )
			
				self::$store[$signature][$service] =
					new $class_standard_class();

			if ( $static === $signature )

				$store = &self::$store[$signature];
			else

				$store = &self::$store[$signature][$service];
		}
		else

			$store = &self::$store;

		return $store;
	}

	/**
	* Initialize a store
	*
	* @param	string	$properties
	* @return	&array	reference to a store
	*/

	public static function &getStore( $properties = NULL)
	{
		global $class_application, $verbose_mode;

		$class_data_fetcher = $class_application::getDataFetcherClass();

		$class_dumper = $class_application::getDumperClass();
	
		$level_default_type = $class_data_fetcher::getEntityTypeValue(
			array(
				PROPERTY_NAME => ENTITY_LEAF,
				PROPERTY_ENTITY => ENTITY_LEVEL
			)
		);

		$api_type_default = $class_data_fetcher::getEntityTypeValue(
			array(
				PROPERTY_NAME => APPLICATION_TWITTER,
				PROPERTY_ENTITY => ENTITY_API
			)
		);

		$social_network_type_default = $class_data_fetcher::getEntityTypeValue(
			array(
				PROPERTY_NAME => APPLICATION_MICROBLOGGING_PLATFORM,
				PROPERTY_ENTITY => ENTITY_SOCIAL_NETWORK
			)
		);

		if (
			is_null( $properties ) ||
			is_array( $properties )
		)
		{
			$parent = $social_network_type_default;
		
			$type = $api_type_default;

			$level = $level_default_type ;

			if ( ! is_array( $properties ) && count( $properties ) )
			{	
				if ( isset( $properties[PROPERTY_PARENT] ) )

					$parent = $properties[PROPERTY_PARENT];
	
				if ( isset( $properties[PROPERTY_TYPE] ) )
				
					$type = $properties[PROPERTY_TYPE];

				if ( isset( $properties[PROPERTY_LEVEL] ) )

					$level = $properties[PROPERTY_LEVEL];
			}
		}
		else

			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		$store_parent = $parent ;
		$api_type = $type;

		if (
			! isset( $_SESSION[STORE_API] ) ||
			! isset( $_SESSION[STORE_API][$store_parent] ) ||
			! isset( $_SESSION[STORE_API][$store_parent][$api_type] )
		)
		{
			if ( ! isset( $_SESSION[STORE_API] )  )

				$_SESSION[STORE_API] = array();
		
			if ( ! isset( $_SESSION[STORE_API][$store_parent] ) )

				$_SESSION[STORE_API][$store_parent] = array();

			if ( ! isset( $_SESSION[STORE_API][$store_parent][$api_type] ) )
				
				$_SESSION[STORE_API][$store_parent][$api_type] = array();
		}

		if ( $level == $level_default_type )

			$store = &$_SESSION[STORE_API][$store_parent][$api_type];

		else if ( is_numeric( $level ) )
		{
			if ( $level == 0)

				$store = &$_SESSION[STORE_API];
				
			else if ( $level == 1 )

				$store = &$_SESSION[STORE_API][$store_parent];
		}

		return $store;
	}

	/**
	* Initialize a store
	*
	* @param	integer	$service		service
	* @param	string	$store_parent	store parent
	* @return	&array	reference to a store
	*/	
	public static function &initializeStore( $service = NULL, $store_parent = NULL )
	{
		global $class_application, $verbose_mode;

		$class_data_fetcher = $class_application::getDataFetcherClass();

		$class_dumper = $class_application::getDumperClass();

		list( $service, $service_type_default ) = self::checkService( $service );

		$service_store = &self::getEntityStore( $service );

		if ( ! isset( $service_store->{PROPERTY_API_CONSUMER_KEY} ) )
		
			$service_store->{PROPERTY_API_CONSUMER_KEY} =
				API_TWITTER_CONSUMER_KEY;

		if ( ! isset( $service_store->{PROPERTY_API_CONSUMER_SECRET} ) )

			$service_store->{PROPERTY_API_CONSUMER_SECRET} =
				API_TWITTER_CONSUMER_SECRET
			;

		if ( ! isset( $service_store->{PROPERTY_API_CONSUMER_CALLBACK} ) )

			$service_store->{PROPERTY_API_CONSUMER_CALLBACK} =
				API_TWITTER_CALLBACK
			;

		$api_type_default = $class_data_fetcher::getEntityTypeValue(
			array(
				PROPERTY_NAME => APPLICATION_TWITTER,
				PROPERTY_ENTITY => ENTITY_API
			)
		);

		$social_network_type_default = $class_data_fetcher::getEntityTypeValue(
			array(
				PROPERTY_NAME => APPLICATION_MICROBLOGGING_PLATFORM,
				PROPERTY_ENTITY => ENTITY_SOCIAL_NETWORK
			)
		);

		if ( is_null( $store_parent ) )

			$store_parent = $social_network_type_default;

		if ( strlen( session_id() ) === 0)
		
			session_start();

		switch ( $service )
		{
			case $service_type_default;
			default:

				$api_type = $api_type_default;
		}

		$store = &self::getStore(
			array(
				PROPERTY_TYPE => $api_type,
				PROPERTY_PARENT => $store_parent
			)
		);

		return $store;		
	}

	/**
	* Check the rate limit
	*
	* @param	mixed	$service	service
	* @return	nothing
	*/
	public static function checkRateLimit( $service = NULL )
	{
		return self::contactEndpoint(
			API_TWITTER_RATE_LIMIT,
			array(
				PROPERTY_PROTOCOL => PROTOCOL_HTTP_METHOD_GET
			),
			$service
		);
	}

	/**
	* Check a service
	*
	* @param	mixed	$service
	* @return	mixed	service
	*/
	public static function checkService( $service = NULL )
	{
		global $class_application, $verbose_mode;
		
		$class_data_fetcher = $class_application::getDataFetcherClass();

		$service_type_default = $class_data_fetcher::getEntityTypeValue(
			array(
				PROPERTY_NAME => APPLICATION_TWITTER,
				PROPERTY_ENTITY => ENTITY_SERVICE
			)
		);

		if ( is_null( $service ) )
		
			$service = $service_type_default;

		return array( $service, $service_type_default );
	}

	public static function contactEndpoint(
		$endpoint = NULL,
		$parameters = NULL,
		$service = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$class_twitteroauth = $class_application::getTwitteroauthClass();

		$library_directory_oauth =
			dirname(__FILE__).'/..'.
			DIR_LIBRARY.'/'.
			DIR_LIBRARY_TWITTEROAUTH.'/'
		;

		$response = NULL;

		list( $service, $service_type_default ) = self::checkService( $service );

		if ( is_null(  $endpoint ) )
		
			$endpoint = API_TWITTER_VERIFY_CREDENTIALS;

		if ( is_null(  $parameters ) )

			$parameters = array();
		
		$store = &self::initializeStore( $service );

		$service_store = &self::getEntityStore( $service );

		if (
			! isset( $service_store->{PROPERTY_API_CONSUMER_CALLBACK} ) ||
			! isset( $service_store->{PROPERTY_API_CONSUMER_SECRET} )
		)

			throw new Exception( EXCEPTION_INVALID_CONFIGURATION );

		$api_consumer_key = $service_store->{PROPERTY_API_CONSUMER_KEY};

		$api_consumer_secret = $service_store->{PROPERTY_API_CONSUMER_SECRET};

		switch ( $service )
		{
			case $service_type_default:

				if (
					isset( $store[PROPERTY_TOKEN_ACCESS] ) &&
					count( $store[PROPERTY_TOKEN_ACCESS] )
				)
				{
					$connection = new $class_twitteroauth(
						$api_consumer_key,
						$api_consumer_secret,
						$store[PROPERTY_TOKEN_ACCESS][PROPERTY_TOKEN_OAUTH],
						$store[PROPERTY_TOKEN_ACCESS][PROPERTY_TOKEN_OAUTH_SECRET]
					);

					$method = PROTOCOL_HTTP_METHOD_GET;

					if ( isset( $parameters[PROPERTY_PROTOCOL] ) )

						$method = $parameters[PROPERTY_PROTOCOL];

					$response = $connection->$method( $endpoint, $parameters );
				
					$class_dumper::log(
						__METHOD__,
						array(
							'[connection]',
							$connection,
							'[endpoint]',
							$endpoint,
							'[method]',
							$method,
							'[parameters]',
							$parameters,
							'[response]',
							$response,
						),
						DEBUGGING_DISPLAY_API_CONTACT_ENPOINT_RESPONSE
					);
				}				
					
					break;
		}

		return $response;
	}

	/**
	* Fetch favorite statuses
	*
	* @param	string	$user_name		user name
	* @param	mixed	$service		service 
	* @param	mixed	$options 		options
	* @return	mixed
	*/	
	public static function fetchFavorite(
		$user_name = NULL,
		$service = NULL,
		$options = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$class_user = $class_application::getUserClass();

		$page_index = 1;

		$user_name_default = $class_user::getUserName();

		if ( is_null( $user_name ) )
		
			$user_name = $user_name_default;

		if (
			! is_null( $options ) &&
			is_object( $options ) &&
			isset( $options->{PROPERTY_PAGE} )
		)

			$page_index = $options->{PROPERTY_PAGE};

		list( $service, $service_type_default ) = self::checkService( $service );

		$endpoint = str_replace(
			'{user}',
			$user_name,
			API_TWITTER_FAVORITES.
			( $page_index > 1 ? '&'.PROPERTY_PAGE.'='.$page_index : '' )
		);

		$response = self::contactEndpoint(
			$endpoint,
			NULL,
			$service
		);

		return $response;
	}

	/**
	* Fetch timeline statuses
	*
	* @param	string	$kind		kind
	* @param	mixed	$service	service 
	* @param	mixed	$options 	options
	* @return	mixed
	*/	
	public static function fetchTimelineStatuses(
		$kind = NULL,
		$service = NULL,
		$options = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$class_user = $class_application::getUserClass();

		$default_page_index = 1;

		$default_results_count = 5;

		$page_index = $default_page_index;

		$results_count = $default_results_count;

		if ( is_null( $kind ) )

			$kind = API_TWITTER_TIMELINE_PUBLIC;

		if (
			! is_null( $options ) &&
			is_object( $options ) 
		)
		{
			if ( isset( $options->{PROPERTY_COUNT} ) )

				$results_count = $options->{PROPERTY_COUNT};

			if ( isset( $options->{PROPERTY_PAGE} ) )

				$page_index = $options->{PROPERTY_PAGE};
		}

		list( $service, $service_type_default ) = self::checkService( $service );

		$endpoint = str_replace(
			'{' . PROPERTY_KIND . '}',
			$kind,
			API_TWITTER_TIMELINE.
			(
				$page_index >= $default_page_index ?
					'&'. PROPERTY_PAGE . '=' . $page_index : ''
			) .
			(
				$results_count > $default_results_count ?
					'&'. PROPERTY_COUNT . '=' . $results_count: ''
			)			
		);

		$response = self::contactEndpoint(
			$endpoint,
			NULL,
			$service
		);

		return $response;
	}

	/**
	* Fetch a token tuple
	*
	* @param	mixed	$context	context
	* @return	mixed
	*/	
	public static function fetchTokensTuples( $context = NULL )
	{
		global $class_application, $verbose_mode;

		$class_token = $class_application::getTokenClass();

		$tokens_tuples = array();
	
		if ( is_null( $context ) )

			$tokens_tuples = $class_token::fetchLatestTokens(
				array(
					ENTITY_OAUTH,
					ENTITY_OAUTH_SECRET
				)
			);

		return $tokens_tuples;
	}

	/**
	* Forget an access token
	*
	* @param	mixed	$service	service 
	* @return	nothing
	*/	
	public static function forgetAccessToken( $service = NULL )
	{
		global $class_application, $verbose_mode;
		
		$class_dumper = $class_application::getDumperClass();

		list( $service, $service_type_default ) = self::checkService( $service );

		$store = &self::initializeStore( $service );

		if ( strlen( session_id() ) === 0 )

			session_start();

		if (
			isset( $store[PROPERTY_STATUS] ) &&
			$store[PROPERTY_STATUS] == ENTITY_STATUS_ACTIVE
		)

			$store[PROPERTY_STATUS] = ENTITY_STATUS_INACTIVE;

		$class_dumper::log(
			__METHOD__,
			array('A new token request can be made'),
			$verbose_mode
		);		
	}

	/**
	* Dump IMAP labels on disk 
	*
	* @param   	string		$mailbox 	mailbox settings
	* @param	resource	$resource	IMAP resource
	* @return  	resource 	IMAP stream
	*/
	public static function dumpImapLabels( $mailbox = NULL, $resource = NULL )
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$file_name = 'imap_labels_'.date('Y-m-d_Hi').EXTENSION_TXT;
		
		$mailboxes = '';

		if ( is_null( $mailbox ) && is_null( $resource ) )
	
			$mailbox = self::getImapMailbox();

		if ( is_null( $resource ) )

			$resource = self::openImapStream( $mailbox );

		$list = imap_list( $resource, $mailbox, '*' );

		self::openImapStream( $mailbox );

		while ( list( $index, $item ) = each( $list ) )
		{
			preg_match(
				'#'.$mailbox.'(.*)#',
				$item,
				$sub_patterns
			);

			$class_dumper::log(
				__METHOD__,
				array( $sub_patterns)
			);
		
			$mailboxes .= imap_utf7_decode( $sub_patterns[1] )."\n";
		}
		
		file_put_contents(
			dirname( __FILE__ ).'/../..'.
				DIR_IMAP.CHARACTER_SLASH.
					$file_name,
			$mailboxes
		);

		return $mailboxes;
	}

	/**
	* Get search results from an IMAP mailbox
	*
	* @param	string	$criteria	search criteria
	* @param	string	$labels		labels
	* @param	string	$mailbox	mailbox
	* @param	string	$resource	resource
	* @return  	array	results
	*/
	public static function getImapSearchResults(
		$criteria,
		$labels = NULL,
		$mailbox = NULL,
		$resource = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();		

		$search_results = array();

		if ( is_null( $mailbox ) && is_null( $resource ) )
	
			$mailbox = self::getImapMailbox();

		if ( is_null( $resource ) )

			$resource = self::openImapStream( $mailbox );

		if ( is_null( $labels ) )
		
			$labels = array( $mailbox.'[Gmail]/All Mail' );

		while ( list( , $label ) = each( $labels ) )
		{
			imap_reopen( $resource, $label );			

			$search_results[$label]  =
				imap_search( $resource, $criteria, SE_UID )
			;
		}

		reset( $labels );

		return $search_results;
	}

	/**
	* Alias to getImapStream
	*
	* @return  string	IMAP mailbox settings
	*/
	public static function getImapMailbox()
	{
		return self::getImapStream();
	}

	/**
	* Get IMAP messages
	*
	* @param	mixed		$messages	messages
	* @param	string		$stream		IMAP stream
	* @param	resource	$resource	IMAP resource
	* @return  	integer		message sequence
	*/
	public static function getImapMessages(
		$messages,
		$stream = NULL,
		$resource = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$_messages = array();

		if ( is_null( $stream ) )
		
			$stream  = self::getImapStream();
		
		if ( is_null( $resource ) )

			$resource = self::openImapStream( $stream );

		while ( list( $label, $uids ) = each( $messages ) )
		{
			while (
				( list( , $uid ) = each( $uids ))
			)
			{
				imap_reopen( $resource, $label );

				$_messages[ $uid ] = array(
					PROPERTY_BODY =>
						imap_body( $resource, $uid, FT_UID ),
					PROPERTY_HEADER =>
						imap_fetchheader( $resource, $uid, FT_UID ),
					PROPERTY_STRUCTURE =>
						imap_fetchstructure( $resource, $uid, FT_UID )
				);
			}

			reset( $uids );
		}

		reset( $messages );	

		return $_messages;
	}

	/**
	* Get an IMAP stream
	*
	* @return  string	IMAP mailbox settings
	*/
	public static function getImapStream()
	{
		return '{'.IMAP_HOST.':'.IMAP_PORT.IMAP_FLAGS.'}';		
	}

	/**
	* Get mailboxes
	*
	* @param	string		$pattern	pattern
	* @param	string		$stream		IMAP stream
	* @param	resource	$resource	IMAP resource
	* @return  	integer		message sequence
	*/
	public static function getMailboxes(
		$pattern,
		$stream = NULL,
		$resource = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$mailboxes = array();

		if ( is_null( $stream ) )
		
			$stream = self::getImapStream();
			
		if ( is_null( $resource ) )
		
			$resource = self::openImapStream( $stream );

		$imap_mailboxes = imap_getmailboxes( $resource, $stream, REGEXP_ANY );

		fprint( $imap_mailboxes );

		while ( list( , $_mailbox ) = each( $imap_mailboxes ) )
		{
			$mailbox_name =
				str_replace(
					$stream,
					'',
					$_mailbox->{PROPERTY_NAME}
				)
			;

			if (
				$match = preg_match( $pattern, $mailbox_name, $matches ) 
			)

				$mailboxes[] = $mailbox_name;
		}

		reset( $mailboxes );
		
		return $mailboxes;
	}

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
	* Open an IMAP stream
	*
	* @param   string  	$stream		IMAP stream
	* @return  resource IMAP stream
	*/
	public static function openImapStream( $stream = NULL )
	{
		if ( is_null( $stream ) )
	
			$stream = self::getImapStream();
	
		if ( $_stream = imap_open( $stream, IMAP_USERNAME, IMAP_PASSWORD ) )
		
			$stream = $_stream;

		return $stream;
	}

	/**
	* Request an access tokenn
	*
	* @param	boolean	$service	service
	* @param	boolean	$verbose 	verbose mode 
	* @return	nothing
	*/	
	public static function requestToken( $service = NULL, $verbose = FALSE )
	{
		global $class_application, $verbose_mode;

		$class_data_fetcher = $class_application::getDataFetcherClass();
		
		$class_dumper = $class_application::getDumperClass();

		$class_token = $class_application::getTokenClass();

		$class_twitteroauth = $class_application::getTwitteroauthClass();

		if ( ! $verbose )

			$verbose = $verbose_mode;

		$api_type_default = $class_data_fetcher::getEntityTypeValue(
			array(
				PROPERTY_NAME => APPLICATION_TWITTER,
				PROPERTY_ENTITY => ENTITY_API
			)
		);

		$service_type_default = $class_data_fetcher::getEntityTypeValue(
			array(
				PROPERTY_NAME => APPLICATION_TWITTER,
				PROPERTY_ENTITY => ENTITY_SERVICE
			)
		);
		
		if ( is_null( $service ) )
		
			$service = $service_type_default;

		switch ( $service )
		{
			case $service_type_default;

				$api_type = $api_type_default;
		}

		$store = &self::initializeStore( $service );

		$store_parent = &self::getStore( array( PROPERTY_LEVEL => 1 ) );

		$service_store = &self::getEntityStore( $service );

		$api_consumer_key = $service_store->{PROPERTY_API_CONSUMER_KEY};

		$api_consumer_secret = $service_store->{PROPERTY_API_CONSUMER_SECRET};

		if (
			! isset( $store[PROPERTY_TOKEN_ACCESS] ) ||
			! isset( $store[PROPERTY_STATUS] ) ||
			$store[PROPERTY_STATUS] != ENTITY_STATUS_INACTIVE
		)
		{
			if (
				isset( $store[PROPERTY_TOKEN_ACCESS] ) &&
				isset( $store[PROPERTY_STATUS] ) &&
				$store[PROPERTY_STATUS] == ENTITY_STATUS_INACTIVE
			)
			{
				if ( isset( $store_parent[$service][PROPERTY_TOKEN_ACCESS] ) ) 
			
					unset( $store_parent[$service][PROPERTY_TOKEN_ACCESS] );
			
				if ( isset( $store_parent[$service][PROPERTY_TOKEN_OAUTH] ) ) 
			
					unset( $store_parent[$service][PROPERTY_TOKEN_OAUTH] );
			
				if ( isset( $store_parent[$service][PROPERTY_TOKEN_OAUTH_SECRET] ) ) 
			
					unset( $store_parent[$service][PROPERTY_TOKEN_OAUTH_SECRET] );	
			}
		
			if ( isset( $store ) && isset( $store[PROPERTY_TOKEN_ACCESS] ) )
		
				$class_dumper::log(
					__METHOD__,
					array(
						'access token in session?: ',
						$store[PROPERTY_TOKEN_ACCESS]
					),
					$verbose_mode
				);	
	
			if ( isset( $store ) && isset( $store[PROPERTY_TOKEN_OAUTH] ) )
		
				$class_dumper::log(
					__METHOD__,
					array(
						'OAuth token in session?: ',
						$store[PROPERTY_TOKEN_OAUTH]
					),
					$verbose_mode
				);
		
			if ( isset( $store ) && isset( $store[PROPERTY_TOKEN_OAUTH_SECRET] ) )
		
				$class_dumper::log(
					__METHOD__,
					array(
						'OAuth token secret in session?: ',
						$store[PROPERTY_TOKEN_OAUTH_SECRET]
					),
					$verbose_mode
				);	
			
			$url_callback = $service_store->{PROPERTY_API_CONSUMER_CALLBACK};

			if (
				isset( $store[PROPERTY_TOKEN_OAUTH] ) &&
				isset( $store[PROPERTY_TOKEN_OAUTH_SECRET] )
			)
			{
				$arguments[2] = $store[PROPERTY_TOKEN_OAUTH];
				$arguments[3] = $store[PROPERTY_TOKEN_OAUTH_SECRET];
			
				$connection = new $class_twitteroauth(
					$api_consumer_key,
					$api_consumer_secret,
					$arguments[2],
					$arguments[3]
				);	
			}
			else
			
				$connection = new $class_twitteroauth(
					$api_consumer_key,
					$api_consumer_secret
				);
			
			if (
				! isset( $store ) ||
				(
					! isset( $store[PROPERTY_TOKEN_OAUTH] ) &&
					! isset( $store[PROPERTY_TOKEN_ACCESS] )
				)
			)
			{	
				$request_token = $connection->getRequestToken( $url_callback );
				
				$redirect_url = $connection->getAuthorizeURL( $request_token[PROPERTY_TOKEN_OAUTH] ); 
			
				$store[PROPERTY_TOKEN_OAUTH] = $request_token[PROPERTY_TOKEN_OAUTH];
				$store[PROPERTY_TOKEN_OAUTH_SECRET] = $request_token[PROPERTY_TOKEN_OAUTH_SECRET];
			
				$class_dumper::log(
					__METHOD__,
					array(
						'redirecting to ',
						$redirect_url
					),
					$verbose_mode
				);
			
				header('Location: '.$redirect_url);

				exit();
			}
			
			if (
				isset( $_REQUEST[GET_OAUTH_VERIFIER] ) &&
				(
					! isset( $store[PROPERTY_TOKEN_ACCESS] ) ||
					! is_array( $store[PROPERTY_TOKEN_ACCESS] ) ||
					count( $store[PROPERTY_TOKEN_ACCESS] ) < 2
				)
			)
			{
				$class_dumper::log(
					__METHOD__,
					array(
						'OAuth verifier ',
						$_REQUEST[GET_OAUTH_VERIFIER]
					),
					$verbose_mode
				);				
			
				$store[PROPERTY_TOKEN_ACCESS] =
					$connection->getAccessToken(
						$_REQUEST[GET_OAUTH_VERIFIER]
					);
			}
			else if (
				! isset( $store[PROPERTY_STATUS] ) ||
				$store[PROPERTY_STATUS] === ENTITY_STATUS_INACTIVE
			)
			{
				$class_dumper::log(
					__METHOD__,
					array(
						'invalid verifier',
						$_REQUEST
					),
					$verbose_mode
				);						

				exit();
			}

			$access_denied =
				! isset( $store[PROPERTY_STATUS] ) ||
				$store[PROPERTY_STATUS] != ENTITY_STATUS_ACTIVE
			;

			if (
				( $connection->http_code == HTML_RESPONSE_CODE_200 ) ||
				! $access_denied
			)
			{
				$store[PROPERTY_STATUS] = ENTITY_STATUS_ACTIVE;

				if (
					isset( $store[PROPERTY_TOKEN_ACCESS] ) &&
					is_array( $store[PROPERTY_TOKEN_ACCESS] ) &&
					count( $store[PROPERTY_TOKEN_ACCESS] ) >= 2	
				)
				{
					// prepare a couple of tokens
					$tuple = array(
						array(
							PROPERTY_VALUE =>
								$store[PROPERTY_TOKEN_ACCESS]
									[PROPERTY_TOKEN_OAUTH],
						),
						array(
							PROPERTY_TYPE => PROPERTY_SECRET_OAUTH,
							PROPERTY_VALUE =>
								$store[PROPERTY_TOKEN_ACCESS]
									[PROPERTY_TOKEN_OAUTH_SECRET],
						)
					);

					// serialize the couple of tokens
					self::serializeTokenTuple( $tuple );
				}
			}
			else if ( $access_denied )
			{
				echo
					'<br />','http code: ','<br />',
					$connection->http_code, '<br />'
				;
			
				if ( strlen( session_id() ) > 0 )
				{
					if ( isset( $store[PROPERTY_TOKEN_ACCESS] ) ) 
			
						unset( $store[PROPERTY_TOKEN_ACCESS] );
			
					if ( isset( $store[PROPERTY_TOKEN_OAUTH] ) ) 
			
						unset( $store[PROPERTY_TOKEN_OAUTH] );
			
					if ( isset( $store[PROPERTY_TOKEN_OAUTH_SECRET] ) ) 
			
						unset( $store[PROPERTY_TOKEN_OAUTH_SECRET] );
				}
			
				/* Build TwitterOAuth object with client credentials. */
				$connection = new $class_twitteroauth(
					$api_consumer_key,
					$api_consumer_secret
				);
				 
				/* Get temporary credentials. */
				$request_token = $connection->getRequestToken( $url_callback );
				
				/* Save temporary credentials to session. */
				$store[PROPERTY_TOKEN_OAUTH] = $token = $request_token[PROPERTY_TOKEN_OAUTH];
				
				$store[PROPERTY_TOKEN_OAUTH_SECRET] = $request_token[PROPERTY_TOKEN_OAUTH_SECRET];
				
				$class_dumper::log(
					__METHOD__,
					array(
						'OAuth token',
						$store[PROPERTY_TOKEN_OAUTH],
						'OAuth token secret',
						$store[PROPERTY_TOKEN_OAUTH_SECRET]	  
					),
					$verbose_mode
				);		
				
				/* If last connection failed don't display authorization link. */
				switch ( $connection->http_code )
				{
					case HTML_RESPONSE_CODE_200:
				
						/* Build authorize URL and redirect user to Twitter. */
						$url = $connection->getAuthorizeURL($token);
				
						header('Location: ' . $url);
					
						break;
			
					default:
		
						throw new Exception(
							sprintf(
								EXCEPTION_IMPOSSIBLE_CONNECTION,
								'Twitter'
							)
						);
				}
			
				exit();
			}

			if ( isset( $store[PROPERTY_TOKEN_ACCESS] ) )

				$class_dumper::log(
					__METHOD__,
					array(
						'The current session contains a valid access token: ',
						$store[PROPERTY_TOKEN_ACCESS],
					),
					$verbose_mode
				);		
		}		
	}

	/**
	* Save search results
	*
	* @param	string		$criteria	search criteria
	* @param	mixed		$labels		labels
	* @param	mixed		$mailbox	mailbox	
	* @param	mixed		$resource	resource
	* @return  	array		results
	*/
	public static function saveSearchResults(
		$criteria,
		$labels = NULL,
		$mailbox = NULL,
		$resource = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$class_header = $class_application::getHeaderClass();
		
		$class_message = $class_application::getMessageClass();

		$_messages =

		$search_results = array();

		if ( is_null( $mailbox ) && is_null( $resource ) )
	
			$mailbox = self::getImapMailbox();

		if ( is_null( $resource ) )

			$resource = self::openImapStream( $mailbox );

		if ( is_null( $labels ) )
		
			$labels = array( $mailbox.'[Gmail]/All Mail' );

		while ( list( , $label ) = each( $labels ) )
		{
			imap_reopen( $resource, $label );			

			$search_results[$label] =
				imap_search( $resource, $criteria, SE_UID )
			;

			while (
				( list( , $uid ) = each( $search_results[$label] ))
			)
			{
				$_messages[$uid] = array(
					PROPERTY_BODY =>
						imap_body( $resource, $uid, FT_UID ),
					PROPERTY_HEADER =>
						imap_fetchheader( $resource, $uid, FT_UID ),
					PROPERTY_STRUCTURE =>
						imap_fetchstructure( $resource, $uid, FT_UID )
				);
	
				$header = $class_header::make(
					$_messages[$uid][PROPERTY_HEADER],
					$uid
				);
		
				$message = $class_message::make(
					$_messages[$uid][PROPERTY_BODY],
					$header->{PROPERTY_ID}
				);
			}
		}

		reset( $labels );
	}
	
	/**
	* Serialize a tuple of tokens
	*
	* @param	array	$tokens		tokens
	* @param	mixed	$service	service
	* @return	mixed	tokens
	*/
	public static function serializeTokenTuple( $tokens, $service = NULL )
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$class_token = $class_application::getTokenClass();

		list( $service, $service_type_default ) = self::checkService( $service );

		$tokens_instances = array();

		if ( ! is_array( $tokens ) || ! count( $tokens ) )
		
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );
		else
		{
			while ( list( , $properties ) = each( $tokens) )
			{
				$entity = 

				$type = NULL;

				if ( isset( $properties[PROPERTY_ENTITY] ) )
				
					$entity = $properties[PROPERTY_ENTITY];

				if ( isset( $properties[PROPERTY_TYPE] ) )

					$type = $properties[PROPERTY_TYPE];

				if ( isset( $properties[PROPERTY_VALUE] ) )

					$tokens_instances[] = $class_token::make(
						$properties[PROPERTY_VALUE],
						$type,
						$entity
					);
			}
		}

		return $tokens_instances;
	}

	/**
	* Unserialize access tokens
	*
	* @param	mixed	$service	service
	* @return	nothing
	*/
	public static function unserializeAccessTokens( $service = NULL )
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$class_token = $class_application::getTokenClass();

		list( $service, $service_type_default ) = self::checkService( $service );

		$store = &self::initializeStore( $service );
		
		$tuple = self::fetchTokensTuples();

		$class_dumper::log(
			__METHOD__,
			array(
				'[tokens tuple]',
				$tuple
			),
			DEBUGGING_DISPLAY_API_CONTACT_ENPOINT_RESPONSE
		);

		if ( is_array( $tuple ) && count( $tuple ) === 2 )
		{
			$token_type_default = $class_token::getDefaultType();

			if ( ! isset( $store[PROPERTY_TOKEN_ACCESS] ) )
			
				$store[PROPERTY_TOKEN_ACCESS] = array();

			while ( list( , $token ) = each( $tuple ) )
			{
				if ( $token->{PROPERTY_TYPE} == $token_type_default )
				
					$store[PROPERTY_TOKEN_ACCESS]
						[PROPERTY_TOKEN_OAUTH] =
							$token->{PROPERTY_VALUE};
				else

					$store[PROPERTY_TOKEN_ACCESS]
						[PROPERTY_TOKEN_OAUTH_SECRET] =
							$token->{PROPERTY_VALUE};
			}
		}
	}

	/**
	* Update a service status
	*
	* @param	string	$status		status
	* @param	mixed	$service	service
	* @return	nothing
	*/
	public static function updateStatus( $status = NULL, $service = NULL )
	{
		if ( is_null( $status ) )
		
			throw new Exception(
				sprintf(
					EXCEPTION_INVALID_ENTITY,
					PROPERTY_STATUS
				)
			);

		return self::contactEndpoint(
			API_TWITTER_UPDATE_STATUS,
			array(
				PROPERTY_STATUS => $status,
				PROPERTY_PROTOCOL => PROTOCOL_HTTP_METHOD_POST  
			),
			$service
		);
	}

	/**
	* Verify credentials for accessing a service
	*
	* @param	integer	$service	service
	* @return	nothing
	*/
	public static function verifyCredentials( $service = NULL )
	{
		return self::contactEndpoint( NULL, NULL, $service );
	}
}
