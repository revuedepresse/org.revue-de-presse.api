<?php

/**
* Interceptor class
*
* Class for intercepting HTTP headers and messages
* @package  sefi
*/
class Interceptor extends Store
{
	/**
	* Get a compass
	*
	* @param	integer		$store_type		store type
	* @return	nothing
    */	
	public static function &getCompass($store_type = STORE_SESSION)
	{
		// check the navigation store
		if (
			!isset($_SESSION[STORE_NAVIGATION]) ||
			!is_array($_SESSION[STORE_NAVIGATION])
		)

			// declare the navigation store as an empty array
			$_SESSION[STORE_NAVIGATION] = array();

		// return the navigation store
		return $_SESSION[STORE_NAVIGATION];
	}

	/**
	* Get a dictionary
	*
	* @param	integer		$store_type		store type
	* @return	nothing
    */	
	public static function &getDictionary($store_type = STORE_SESSION)
	{
		// check the conversation store
		if (
			!isset($_SESSION[STORE_CONVERSATION]) ||
			!is_array($_SESSION[STORE_CONVERSATION])
		)

			// declare the conversation store as an empty array
			$_SESSION[STORE_CONVERSATION] = array();

		// return the conversation store
		return $_SESSION[STORE_CONVERSATION];
	}

	/** 
	* Get the definition of a content type
	* 
	* @param	string		$content_type	content type
	* @param	integer		$store_type		store type
	* @return	nothing
    */	
	public static function &getDefinition($content_type = DEFINITION_PRIMAL, $store_type = STORE_SESSION)
	{
		// get the compass
		$_dictionary= &self::getDictionary();

		// check the navigation store
		if (
			!isset($_dictionary[STORE_DEFINITION]) ||
			!is_object($_dictionary[STORE_DEFINITION]) ||
			!isset($_dictionary[STORE_DEFINITION]->{$content_type})
		)
		{
			// check the scope store
			if (
				!isset($_dictionary[STORE_DEFINITION]) ||
				!is_object($_dictionary[STORE_DEFINITION])
			)

				// construct the scope store as a new instance of the standard class
				$_dictionary[STORE_DEFINITION] = new stdClass();					

			// check the previous position stored in session
			if (empty($_dictionary[STORE_DEFINITION]->{$content_type}))

				$_dictionary[STORE_DEFINITION]->{$content_type} = null;
		}

		// return the scope of the provided border type
		return $_dictionary[STORE_DEFINITION]->{$content_type};
	}

	/** 
	* Get the position
	* 
	* @param	string		$position_type	position type	
	* @param	integer		$store_type		store type
	* @return	nothing
    */	
	public static function &getPosition($position_type = POSITION_CURRENT, $store_type = STORE_SESSION)
	{
		// get the compass
		$_compass = &self::getCompass();

		// check the navigation store
		if (
			! isset( $_compass[STORE_POSITION] ) ||
			! is_object( $_compass[STORE_POSITION] ) ||
			! isset( $_compass[STORE_POSITION]->{$position_type} )
		)
		{
			// check the position store
			if (
				! isset( $_compass[STORE_POSITION] ) ||
				! is_object( $_compass[STORE_POSITION] )
			)

				// construct the position store as a new instance of the standard class
				$_compass[STORE_POSITION] = new stdClass();					

			// check the previous position stored in session
			if (empty($_compass[STORE_POSITION]->{$position_type}))

				// store the provided URL as a position of the provided position type in session
				$_compass[STORE_POSITION]->{$position_type} = null;
		}

		// return the position of the provided type
		return $_compass[STORE_POSITION]->{$position_type};
	}

	/** 
	* Get the scope of navigation
	* 
	* @param	string		$border_type	border type
	* @param	integer		$store_type		store type
	* @return	nothing
    */	
	public static function &getScope(
		$border_type = BORDER_DEFAULT,
		$store_type = STORE_SESSION
	)
	{
		// get the compass
		$_compass = &self::getCompass();

		// check the navigation store
		if (
			!isset($_compass[STORE_BORDER]) ||
			!is_object($_compass[STORE_BORDER]) ||
			!isset($_compass[STORE_BORDER]->{$border_type})
		)
		{
			// check the scope store
			if (
				!isset($_compass[STORE_BORDER]) ||
				!is_object($_compass[STORE_BORDER])
			)

				// construct the scope store as a new instance of the standard class
				$_compass[STORE_BORDER] = new stdClass();					

			// check the previous position stored in session
			if (empty($_compass[STORE_BORDER]->{$border_type}))

				$_compass[STORE_BORDER]->{$border_type} = null;
		}

		// return the scope of the provided border type
		return $_compass[STORE_BORDER]->{$border_type};
	}

	/**
	* Dereference a symbol
	*
	* @return	string	link
    */	
	public static function dereference()
	{
		global $class_application, $verbose_mode;

		$class_data_fetcher = $class_application::getDataFetcherClass();

		$arguments = func_get_args();

		if ( isset( $arguments[0] ) )
		
			$symbol = $arguments[0];
		else
		
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		if ( is_string( $symbol ) && strlen( $symbol ) )
		{
			list( $prefix, $identifier) = explode( '.', $symbol );
			
			if ( $prefix == substr( PREFIX_FORM, 0, - 1 ) )
			{
				$form_configuration =
					$class_data_fetcher::fetchFormConfiguration( $identifier )
				;

				if (
					is_object( $form_configuration ) &&
					isset( $form_configuration->form_uri )
				)
				
					return $form_configuration->form_uri;
				else
				
					return FALSE;
			}
		}
	}

	/**
	* Erase a border
	*
	* @param	string		$border_type		border type	
	* @param	integer		$store_type			store type
	* @return	nothing
    */	
	public static function eraseBorder($border_type = BORDER_DEFAULT, $store_type = STORE_SESSION)
	{
		// get the scope instruments
		$_navigation_instruments = &self::getCompass($store_type);

		// check the scope store
		if (!isset($_navigation_instruments[STORE_BORDER]))

			// construct the scope store as an instance of the standard class		
			$_navigation_instruments[STORE_BORDER] = new stdClass();

		// nullify the value of a position 
		$_navigation_instruments[STORE_BORDER]->{$border_type} = null;
	}

	/**
	* Forget the default border
	*
	* @param	integer		$store_type		store type
	* @return	nothing
    */	
	public static function forgetDefaultBorder($store_type = STORE_SESSION)
	{
		// erase the default border
		self::eraseBorder(BORDER_DEFAULT, $store_type);
	}

	/**
	* Forget the outer border
	*
	* @param	integer		$store_type		store type
	* @return	nothing
    */	
	public static function forgetOuterBorder($store_type = STORE_SESSION)
	{
		// erase the default border
		self::eraseBorder(BORDER_OUTER, $store_type);
	}

	/**
	* Forget a position
	*
	* @param	string		$position_type		position type	
	* @param	integer		$store_type			store type
	* @return	nothing
    */	
	public static function forgetPosition(
		$position_type = POSITION_CURRENT,
		$store_type = STORE_SESSION
	)
	{
		// get the compass
		$_navigation_instruments = &self::getCompass( $store_type );

		// check the position store
		if ( ! isset( $_navigation_instruments[STORE_POSITION] ) )

			// construct the position store as an instance of the standard class		
			$_navigation_instruments[STORE_POSITION] = new stdClass();

		// nullify the value of a position 
		$_navigation_instruments[STORE_POSITION]->{$position_type} = NULL;
	}

	/**
	* Forget the previous position
	*
	* @param	integer		$store_type		store type
	* @return	nothing
    */	
	public static function forgetPreviousPosition($store_type = STORE_SESSION)
	{
		// forget the previous position
		self::forgetPosition(POSITION_PREVIOUS, $store_type);
	}

	/**
	* Forget the primal definition
	*
	* @param	integer		$store_type		store type
	* @return	nothing
    */	
	public static function forgetPrimalDefinition($store_type = STORE_SESSION)
	{
		// erase the primal definition
		self::removeDefinition(DEFINITION_PRIMAL, $store_type);
	}

	/**
	* Get the default border
	* 
	* @param	integer		$store_type		store type
	* @return	nothing
    */	
	public static function getDefaultBorder( $store_type = STORE_SESSION )
	{
		// return the default border
		return self::getScope( BORDER_DEFAULT, $store_type );
	}

	/**
	* Get an internal link
	*
	* @param	integer		$target			target
	* @param	integer		$target_type	type of target
	* @return	nothing
    */	
	public static function getInternalLink($target, $target_type = NULL)
	{
		$class_entity = CLASS_ENTITY;

		$internal_link = NULL;

		$entity = $class_entity::getById($target_type);

		$entity_name =
				isset($entity->{PROPERTY_SIGNATURE})
			?
				$entity->{PROPERTY_SIGNATURE}
			:
				$entity->{PROPERTY_NAME}
		;

		switch ($entity_name)
		{
			case strtolower(CLASS_PHOTOGRAPH):

				$internal_link = PREFIX_DOM_IDENTIFIER_IMAGE_PHOTOGRAPH.$target;
		
					break;	
		}
		
		return $internal_link;
	}

	/**
	* Get the outer border
	* 
	* @param	integer		$store_type		store type
	* @return	nothing
    */	
	public static function getOuterBorder($store_type = STORE_SESSION)
	{
		// return the default border
		return self::getScope( BORDER_OUTER, $store_type );
	}

	/**
	* Get the previous position
	* 
	* @param	integer		$store_type		store type
	* @return	nothing
    */	
	public static function getPreviousPosition( $store_type = STORE_SESSION )
	{
		// return the previous position
		return self::getPosition( POSITION_PREVIOUS, $store_type );
	}
	
	/**
	* Get the primal definition
	* 
	* @param	integer		$store_type		store type
	* @return	nothing
    */	
	public static function getPrimalDefinition($store_type = STORE_SESSION)
	{
		// return the previous position
		return self::getDefinition(DEFINITION_PRIMAL, $store_type);
	}

	/**
	* Get the attributes of a route
	* 
	* @param	mixed	$conditions	conditions
	* @return	mixed
    */	
	public static function getRouteAttributes($conditions)
	{
		$class_data_fetcher = CLASS_DATA_FETCHER;
	
		if (is_array($conditions) && count($conditions) > 0)

			return $class_data_fetcher::getAttributes($conditions);
	}

	/**
	* Get a signature
	*
	* @param	boolean	$namespace	namespace flag
	* @return	string	signature
	*/
	public static function getSignature($namespace = TRUE)
	{
		$_class = __CLASS__;

		if (!$namespace)

			list($_namespace, $_class) = explode('\\', __CLASS__);

		return $_class;
	}

	/**
	* Remove a definition
	*
	* @param	string		$content_type	content type	
	* @param	integer		$store_type		store type
	* @return	nothing
    */	
	public static function removeDefinition(
		$content_type = DEFINITION_PRIMAL,
		$store_type = STORE_SESSION
	)
	{
		// get the conversation
		$_conversation = &self::getDictionary($store_type);

		// check the conversation store
		if (!isset($_conversation[STORE_DEFINITION]))

			// construct the definition store as an instance of the standard class		
			$_conversation[STORE_DEFINITION] = new stdClass();

		// nullify the value of a definition
		$_conversation[STORE_DEFINITION]->{$content_type} = null;
	}
	
	/**
	* Route
	* 
	* @param	array		$context	context parameters
	* @param	integer		$page		page
	* @param	mixed		$informant	informant
	* @param	boolean		$assert		assertion flag
	* @return	nothing
    */
    public static function route(
		&$context = NULL,
		$page = PAGE_UNDEFINED,
		$informant = NULL,
		$assert = FALSE
	)
    {
		global $class_application, $verbose_mode;

		// set the environment agent
		$agent_environment = $class_application::getEnvironmentClass();

		// set the controller class name
		$class_controller = $class_application::getControllerClass();
		
		// set the data fetcher class name
		$class_data_fetcher = $class_application::getDataFetcherClass();

		// set the dumper class name
		$class_dumper = $class_application::getDumperClass();

		// set the executor class name
		$class_executor = $class_application::getExecutorClass();

		// set the test case class name
		$class_test_case = $class_application::getTestCaseClass();

		// set the user handler class name
		$class_user_handler = $class_application::getUserHandlerClass();

		$class_controller::checkPostedData();

		$route_type_content = $class_data_fetcher::getTypeValue(
			array(
				PROPERTY_NAME => ENTITY_CONTENT,
				PROPERTY_ENTITY => ENTITY_ROUTE
			)
		);

		$action_sign_up = FALSE;
		
		$class_test_case::perform(
			DEBUGGING_FIELD_ERROR_HANDLING,
			$verbose_mode
		);

		// check the current context
		if ( ! isset( $context ) )
		{
			// declare the default route
			$route = new \stdClass();

			// set the default route type
			$route->type = SCRIPT_ERROR_404;

			// check the environment request URI
			if (isset($_SERVER['REQUEST_URI']))

				// set the current URI	
				$current_URI = $_SERVER['REQUEST_URI'];

			// check the redirect URL
			else if (isset($_SERVER['REDIRECT_URL']))

				// set the current URI				
				$current_URI = $_SERVER['REDIRECT_URL'];

			// get a route package
			$package = $class_data_fetcher::get_package(
				PACKAGE_ROUTE, $current_URI
			);

			if (
				isset( $_SESSION[ENTITY_FEEDBACK] ) &&
				is_array( $_SESSION[ENTITY_FEEDBACK] ) &&
				( $feedback = $_SESSION[ENTITY_FEEDBACK] ) &&
				count( $feedback ) === 1 &&
				( list( $form_identifier, $action ) = each( $feedback ) )
				&& ( $form_identifier == ACTION_SIGN_UP ) &&
				isset(
					$_SESSION
						[ENTITY_FEEDBACK]
							[$form_identifier]
				) &&
				isset(
					$_SESSION
						[ENTITY_FEEDBACK]
							[$form_identifier]
								[AFFORDANCE_DISPLAY]
				) && 
				! is_null(
					$_SESSION
						[ENTITY_FEEDBACK]
							[$form_identifier]
								[AFFORDANCE_DISPLAY]
				)
			)

				$action_sign_up = TRUE;

			// check the package
			if (
				is_array( $package ) &&
				count( $package ) &&
				isset( $package[0] ) &&
				! $action_sign_up
			)

				// set the route
				$route = $package[0];

			// check if the package is a feedback message
			else if (
				is_string( $package ) &&
				isset( $_SESSION[ENTITY_FEEDBACK] ) ||
				$action_sign_up
			)
			{
				$class_application::displayFeedbackView(
					(
						is_string( $package ) &&
						isset( $_SESSION[ENTITY_FEEDBACK] )
					?
						$package
					:
						$_SESSION
							[ENTITY_FEEDBACK]
								[$form_identifier]
									[AFFORDANCE_DISPLAY]
					),
					$form_identifier
				);

				unset( $_SESSION[ENTITY_FEEDBACK] );

				// logout the current logged in member
				$class_application::destroySession();

				return;				
			}

			// replace the action prefix with an empty string in the current URI
			$affordance = substr(
				str_replace(
					array(
						PREFIX_ROOT.PREFIX_ACTION,
						EXTENSION_PHP,
						EXTENSION_HTML
					),
					CHARACTER_EMPTY_STRING,
					$current_URI
				),
				1
			);

			// check the current URI
			switch ( $route->type )
			{
				case ROUTE_TYPE_ACTION:
				case ROUTE_TYPE_ADMINISTRATION:

					// check if the current visitor is already logged in
					// and the request URI
					if (
						! $class_user_handler::anybodyThere( TRUE ) &&
						$_SERVER['REQUEST_URI'] != URI_ACTION_OFFER_CHALLENGE
					)

						$class_application::jumpTo( URI_ACTION_OFFER_CHALLENGE );

					// check if the current visitor is already logged in
					else if (
						! $class_user_handler::anybodyThere( TRUE )
					)

				        // display form
						$class_application::displayForm(
							ACTION_CHALLENGE,
							TRUE
						);

					// check if the route affordance is all about challenging
					// a visitor already connected
					else if ( $route->{PROPERTY_AFFORDANCE} == ACTION_CHALLENGE )

						$class_application::jumpTo( URI_ACTION_OVERVIEW );
					else

						$class_application::displayOverview(
							$route,
							PAGE_OVERVIEW
						);

						break;

				case ROUTE_TYPE_AFFORDANCE:

					$affordance =
						(
							! isset( $route->{PROPERTY_AFFORDANCE} )
						?
							$affordance
						:
							$route->{PROPERTY_AFFORDANCE}
						)
					;

					// escape dots in the pattern
					$affordance = $class_application::translate_entity(
						$affordance,
						$to = ENTITY_AFFORDANCE,
						$from = ENTITY_URI
					);

					// set the configuration form file name
					$form_configuration_file =
						dirname(__FILE__).DIR_PARENT_DIRECTORY.DIR_PARENT_DIRECTORY.
						CHARACTER_SLASH.DIR_CONFIGURATION.CHARACTER_SLASH.PREFIX_FORM.
						$affordance.
						EXTENSION_YAML
					;

					// set the configuration tabs file name
					$tabs_configuration_file =
						dirname(__FILE__).DIR_PARENT_DIRECTORY.DIR_PARENT_DIRECTORY.
						CHARACTER_SLASH.DIR_CONFIGURATION.CHARACTER_SLASH.PREFIX_TABS.
						$affordance.
						EXTENSION_YAML
					;

					// check the configuration files
					if (
						file_exists( $form_configuration_file ) ||
						file_exists( $tabs_configuration_file )
					)
					{
						// send a 200 HTTP response header
						header( 'HTTP/1.1 200 OK' );

						if ( file_exists( $form_configuration_file ) )

							// display form
							$class_application::displayForm( $affordance );
						else 

							// display tabs
							$class_application::displayTabs( $affordance );
					}

					// check if the current route is bound to an identifier
					else if (
						is_object( $route ) &&
						! empty( $route->{PROPERTY_IDENTIFIER} ) &&
						! empty( $route->{PROPERTY_IDENTIFIER} ) ||
						$route->{PROPERTY_CONTENT_TYPE} ==
							CONTENT_TYPE_STYLESHEET
					)
					{
						try
						{
							// perform an action
							$performance_results = $class_executor::perform(
								$route->{PROPERTY_AFFORDANCE},
								$route
							);
						}
						catch ( \Exception $exception )
						{
							$class_dumper::log(
								__METHOD__,
								array(
									'An exception has been caught '.
									'while calling  '.
									$class_executor.' :: perform =>',
									$exception
								),
								DEBUGGING_DISPLAY_EXCEPTION,
								AFFORDANCE_CATCH_EXCEPTION
							);
						}
			
						if ( ! is_null( $performance_results ) )
						{
							$route_return = ROUTE_WONDERING;

							$previous_position = self::getPreviousPosition();
	
							// get the default border
							$default_border = self::getDefaultBorder();
		
							if (
								$previous_position !=
									URI_ACTION_DISPLAY_DOCUMENT &&
								is_string( $performance_results )
							)
							{
								// forget the default border
								self::forgetDefaultBorder();
			
								// forget the previous position
								self::forgetPreviousPosition();
			
								// remove the primal definition
								self::forgetPrimalDefinition();
			
								if ($default_border != 1)
			
									// get the default border
									$previous_position .= '-'.$default_border;

								$route_return =
									$previous_position.
										CHARACTER_SHARP.$performance_results
								;
							}

							if ( ! is_array( $performance_results ) )

								// jump to the destination
								$class_application::jumpTo( $route_return );
						}
					}
					else
					{
						// send a 404 HTTP response header
						header("HTTP/1.0 404 Not Found");

						echo file_get_contents(
							BASE_URL.URI_HTTP_RESPONSE_STATUS_400
						);
					}

						break;

				case $route_type_content:

					// check if a user is logged in
					if ( $class_user_handler::loggedIn() )
					{
						/**
						*
						* Update the previous position value
						* when the current route has action or affordance
						* 
						*/

						if ( ! empty( $route->{PROPERTY_AFFORDANCE} ) )

							// set an origin
							self::updatePreviousPosition(
								PREFIX_ROOT.$route->{PROPERTY_AFFORDANCE}
							);

						else if ( ! empty( $route->{PROPERTY_ACTION} ) )

							// set an origin
							self::updatePreviousPosition(
								PREFIX_ROOT.
									$class_application::translate_entity(
										$route->{PROPERTY_ACTION}
									)
							);


						if (
							isset( $route->{PROPERTY_ID} ) &&
							$route->{PROPERTY_ID} == ROUTE_DOCUMENT
						)

							// set an content type
							self::updateContentType( CONTENT_TYPE_DOCUMENT );

						// check the route action property
						else if ( isset( $route->{PROPERTY_ACTION} ) )

							// set an content type
							self::updateContentType( $route->{PROPERTY_ACTION} );

						else if (
							isset( $route->{PROPERTY_AFFORDANCE} ) && 
							self::getPrimalDefinition() ==
								ACTION_DISPLAY_DOCUMENT								 
						)

							// forget the navigation history
							self::forgetPrimalDefinition();

						if ( ! empty( $route->{PROPERTY_IDENTIFIER} ) )

							// set a border
							self::updateDefaultBorder(
								$route->{PROPERTY_IDENTIFIER}
							);
						else 

							// set a border
							self::updateDefaultBorder( BORDER_DEFAULT_OFFSET );

						// check the route id property 
						if ( ! empty( $route->{PROPERTY_ID} ) )

							// display content
							$class_application::displayContent(
								$route->{PROPERTY_ID}
							);

						// check the route identifier property 
						else if ( ! empty( $route->{PROPERTY_IDENTIFIER} ) )

							// display content
							$class_application::displayContent(
								$route->{PROPERTY_IDENTIFIER}
							);
					}
					else

						// display the sign in form
						$class_application::jumpTo( PREFIX_ROOT );

						break;

				case ROUTE_TYPE_DIALOG:

					// check if the current visitor is already logged in as an administrator
					if (
						$class_user_handler::anybodyThere( TRUE ) &&
						$route->{PROPERTY_AFFORDANCE} != AFFORDANCE_LOGOUT &&
						$route->{PROPERTY_FOLDER} != ROUTE_ROOT
					)

				        // display a dialog
						$class_application::displayDialog(
							$route,
							PAGE_OVERVIEW
						);
					else
					{
						// escape dots in the pattern
						$affordance = $class_application::translate_entity(
							$affordance,
							ENTITY_AFFORDANCE,
							ENTITY_URI
						);

						// display a dialog
						$class_application::displayDialog( $affordance );
					}

						break;

				case ROUTE_TYPE_FOLDER:

					// check if an administrator session has been opened
					if ( $class_user_handler::anybodyThere( TRUE ) )
					{
						if (
							strlen( $_SERVER['REQUEST_URI'] ) - 1
								!== strpos( $_SERVER['REQUEST_URI'], '/', 1 )
						)

							$class_application::jumpTo(
								$_SERVER['REQUEST_URI'] . '/',
								301
							);

						$class_application::displayOverview();
						
					}
					else

						$agent_environment::challengeVisitor();

						break;

				case ROUTE_TYPE_MEDIA:

					// check the package identifier
					if ( isset( $route->{PROPERTY_IDENTIFIER} ) )

						// display a media
						$class_application::displayMedia(
							$route,
							MEDIA_TYPE_IMAGE
						);

						break;

				case ROUTE_TYPE_ROOT:

					require_once(
						dirname(__FILE__).
						DIR_PARENT_DIRECTORY.
						DIR_PARENT_DIRECTORY.
						SEPARATOR_LEVEL.'index'.EXTENSION_PHP
					);

						break;

				default:

					// send a 404 HTTP response header
					header('HTTP/1.0 404 Not Found');

					if ( isset( $_SERVER['HTTP_HOST'] ) )

						echo file_get_contents(
							BASE_URL . URI_HTTP_RESPONSE_STATUS_400
						);
			}

				return;
		}

		// check submitted data
		$class_controller::checkSubmittedData( $context, $page );
    }

	/**
	* Set a content type
	* 
	* @param	string		$content_type	content type
	* @param	integer		$store_type		store type
	* @return	nothing
    */
	public static function updateContentType(
		$content_type,
		$store_type = STORE_SESSION
	)
	{
		// update the default content definition
		self::updateDefinition(
			$content_type,
			DEFINITION_PRIMAL,
			$store_type
		);
	}

	/**
	* Set a default border 
	* 
	* @param	string		$border		border
	* @param	integer		$store_type	store type
	* @return	nothing
    */	
	public static function updateDefaultBorder($border, $store_type = STORE_SESSION)
	{
		// update the default border position
		self::updateScope($border, BORDER_DEFAULT, $store_type);
	}

	/**
	* Update a definition
	* 
	* @param	string		$content_type		content type
	* @param	integer		$definition_level 	definition level
	* @param	integer		$store_type			store type
	* @return	nothing
    */		
	public static function updateDefinition(
		$content_type,
		$definition_level = DEFINITION_PRIMAL,
		$store_type = STORE_SESSION
	)
	{
		// switch from a store type
		switch ($store_type)
		{
			case STORE_SESSION:

				// get the current definition
				$_definition= &self::getDefinition($definition_level, $store_type);

				// compare the definition in session with the one provided
				if ($content_type != $_definition)

					// store the provided content type as a definition of the provided definition type in session
					$_definition = $content_type;

					break;
		}
	}

	/**
	* Review the map
	* 
	* @param	object		$coordinates		coordinates
	* @param	integer		$coordinates_type	coordinates type
	* @param	integer		$store_type			store type
	* @return	nothing
    */	
	public static function updateMap(
		$coordinates,
		$coordinates_type = COORDINATES_CURRENT_POSITION,
		$store_type = STORE_SESSION
	)
	{
		// update the previous position
		self::updatePreviousPosition($coordinates->{POSITION_PREVIOUS}, $store_type);
	}

	/**
	* Set the outer border 
	* 
	* @param	string		$border		border
	* @param	integer		$store_type	store type
	* @return	nothing
    */	
	public static function updateOuterBorder($border, $store_type = STORE_SESSION)
	{
		// update the default border position
		self::updateScope($border, BORDER_OUTER, $store_type);
	}

	/**
	* Update a position
	* 
	* @param	string		$url				url
	* @param	integer		$position_type		position type
	* @param	integer		$store_type			store type
	* @return	nothing
    */		
	public static function updatePosition(
		$url,
		$position_type = POSITION_CURRENT,
		$store_type = STORE_SESSION
	)
	{
		// switch from a store type
		switch ($store_type)
		{
			case STORE_SESSION:

				// get the current position
				$_position = &self::getPosition($position_type, $store_type);

				// compare the url in session with the one provided
				if ($url != $_position)

					// store the provided URL as a position of the provided position type in session
					$_position = $url;

					break;
		}		
	}

	/**
	* Update a scope
	* 
	* @param	string		$border				border
	* @param	integer		$border_type		border type
	* @param	integer		$store_type			store type
	* @return	nothing
    */		
	public static function updateScope(
		$border,
		$border_type = BORDER_DEFAULT,
		$store_type = STORE_SESSION
	)
	{
		// switch from a store type
		switch ($store_type)
		{
			case STORE_SESSION:

				// get the current scope
				$_scope = &self::getScope($border_type, $store_type);

				// compare the border in session with the one provided
				if ($border != $_scope)

					// store the provided border as a scope of the provided scope type in session
					$_scope = $border;

					break;
		}
	}

	/**
	* Set an origin
	* 
	* @param	string		$url		URL
	* @param	integer		$store_type	store type
	* @return	nothing
    */	
	public static function updatePreviousPosition($url, $store_type = STORE_SESSION)
	{
		// update the previous position
		self::updatePosition($url, POSITION_PREVIOUS, $store_type);
	}
}
?>