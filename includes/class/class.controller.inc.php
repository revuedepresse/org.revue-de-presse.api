<?php

/**
* Controller class
*
* Class for controlling request
* @package  sefi
*/
class Controller extends Source
{
	/**
	* Check data
	*
	* @param	string		$data		data
	* @param	mixed		$data_type	data type
	* @param	string		$hash		hash
	* @return 	boolean		data validity
	*/
	public static function checkData(
		$data,
		$data_type = DATA_TYPE_CREDENTIALS,
		$hash = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_data_fetcher = $class_application::getDataFetcherClass();

		$validity = FALSE;

		if ( $hash !== NULL )
		{
			if ( is_array( $data ) )
			
			while ( list( $index, $value ) = each( $data ) )

				$data[$index] = call_user_func( $hash, $value );

			reset( $data );
		}

		// switch from the data type
		switch ( $data_type )
		{
			case DATA_TYPE_CREDENTIALS:

				$validity = $class_data_fetcher::checkCredentials( $data );
		}

		return $validity;
	}

	/**
	* Check data being posted
	*
	* @return	nothing
    */	
	public static function checkPostedData()
	{
		global $class_application;

		$class_data_fetcher = $class_application::getDataFetcherClass();

		$class_dumper = $class_application::getDumperClass();

		$previous_position = $class_data_fetcher::getPosition( POSITION_PREVIOUS );

		$default_border = $class_data_fetcher::getDefaultBorder();

		$border = $default_border != -1 ? '-'.$default_border : '';

		$update = FALSE;

		if (isset($_POST[PROPERTY_ACTION]))
		{	
			$action = $_POST[PROPERTY_ACTION];

			switch ($action)
			{
				case ACTION_EDIT_INSIGHT:
				case ACTION_SHARE_INSIGHT:

					$class_insight = CLASS_INSIGHT;

					if (
						isset($_POST[PROPERTY_TARGET]) &&
						isset($_POST[PROPERTY_TARGET_TYPE]) &&
						isset($_POST[PROPERTY_PARENT]) &&
						isset($_POST[PROPERTY_BODY])
					)

						$properties = array(
							PROPERTY_TARGET => $_POST[PROPERTY_TARGET],
							PROPERTY_TARGET_TYPE => $_POST[PROPERTY_TARGET_TYPE],
							PROPERTY_PARENT => $_POST[PROPERTY_PARENT],
							PROPERTY_BODY => $_POST[PROPERTY_BODY]
						);

					// check the environment request URI
					if (isset($_SERVER['REQUEST_URI']))
		
						// set the current URI	
						$current_URI = $_SERVER['REQUEST_URI'];
		
					// check the redirect URL
					else if (isset($_SERVER['REDIRECT_URL']))
		
						// set the current URI				
						$current_URI = $_SERVER['REDIRECT_URL'];

					// get a route package
					$package = $class_data_fetcher::get_package(PACKAGE_ROUTE, $current_URI);

					if (
						is_array($package) &&
						count($package) &&
						isset($package[0]) &&
						is_object($package[0]) &&

						// check if the identifier of an entity has been passed
						isset($package[0]->{PROPERTY_IDENTIFIER}) &&

						isset($package[0]->{PROPERTY_AFFORDANCE}) &&
						
						// check if some edition is at stake
						FALSE !== strpos($package[0]->{PROPERTY_AFFORDANCE}, ACTION_EDIT)
					)
					{
						$property_id = $package[0]->{PROPERTY_IDENTIFIER};

						unset($properties[PROPERTY_TARGET]);

						unset($properties[PROPERTY_TARGET_TYPE]);

						unset($properties[PROPERTY_PARENT]);

						$update = TRUE;

						$properties[PROPERTY_ID] = $property_id;

						$node = $class_insight::fetchInsightNode($property_id);

						$updated_URI =
							$previous_position.
							$border.
							"#".'_'.
							$class_application::generateUniqueEntityId(
								$node,
								ENTITY_INSIGHT_NODE
							)
						;
					}

					$callback_parameters = $class_insight::add($properties);

					if ($update)

						$class_application::jumpTo($updated_URI);

					else if (is_numeric($callback_parameters))
					{
						$_node = $class_insight::fetchInsightNode($callback_parameters);

						if ( FALSE === strpos( $current_URI, $previous_position ) )

							$class_application::jumpTo(
								$previous_position.
								$border.
								"#".'_'.
								$class_application::generateUniqueEntityId(
									$_node,
									ENTITY_INSIGHT_NODE
								)
							);
						else
						
							$class_application::jumpTo(PREFIX_ROOT);
					}

					break;
			}
		}
	}

	/**
	* Check a request
	*
	* @param	mixed	$request	a request
	* @return	mixed	checking results 
    */	
	public static function checkRequest($request)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();
		
		$class_entity = $class_application::getEntityClass();

		$class_insight = $class_application::getInsightClass();

		$class_interceptor = $class_application::getInterceptorClass();

		$class_member = $class_application::getMemberClass();

		$class_toolbox = $class_application::getToolboxClass();

		$checking_results = $request;

		$last_node =

		$match =
		
		$submatch = FALSE;
		
		$matches =
		
		$submatches = array();

		$pattern_primal =

			REGEXP_OPEN.
			
				// something starting with the / symbol not followed by the / symbol
				
				// followed by characters different from the ? symbol
			
				'^\/'.

				REGEXP_CATCH_START.

					'[^\/][^?]*?'.

				REGEXP_CATCH_END
		
				// non grouping parenthesis

				.'(?:'.
			
					// facultative non grouping parenthesis
					
					// minus followed by a digit of one character at least
					
					// the ? symbol followed by an alpabetic character and the = symbol
					
					// followed by alphanumeric characters
					
					"-?([0-9]+)?(?:\?[a-z]=([a-zA-Z0-9]*))?".

					// or

				REGEXP_OR.
			
					// facultative ? followed by a character and the = symbol
					
					// followed by non equal characters

					'\??(?:'.
					
						REGEXP_CATCH_START.
						
							'.'.
						
						REGEXP_CATCH_END.
						
						'=([^=]*))?'.
			
				')'.

				REGEXP_END.

			REGEXP_CLOSE
		;

		// check if the current URI ends with an identifier
		$match = preg_match( $pattern_primal, $request, $matches );

		$pattern_confirmation =
			REGEXP_OPEN.

				REGEXP_ESCAPE.PREFIX_ROOT.
				
				REGEXP_CATCH_START.
				
					// whatever is not numeric and just follows the slash root
					'[^0-9]*'.
				
				REGEXP_CATCH_END.

				// minus symbol as a separator
				'-'.
				
				REGEXP_CATCH_START.

					// one numeric character at least
					'[0-9]+'.

				REGEXP_CATCH_END.

			REGEXP_CLOSE
		;

		if (
			is_array( $matches ) &&
			count( $matches ) &&
			isset( $matches[0] )
		)
		{
			$submatch = preg_match(
				$pattern_confirmation,
				$matches[0],
				$submatches
			);

			if ($submatch && !empty($submatches[1]))
			{
				$route_attributes = $class_interceptor::getRouteAttributes(
					array(
						PREFIX_TABLE_COLUMN_ROUTE.PROPERTY_URI => $submatches[1]
					)
				);

				$entity_insight_node = $class_entity::fetchProperties(
					array( PROPERTY_NAME => ENTITY_INSIGHT_NODE )
				);

				if (
					isset($route_attributes->{PROPERTY_ENTITY}) &&
					isset($entity_insight_node->{PROPERTY_ID}) &&
					$route_attributes->{PROPERTY_ENTITY} == $entity_insight_node->{PROPERTY_ID} &&
					is_numeric($submatches[2])
				)
				{
					$node = $class_insight::fetchInsightNode($submatches[2]);

					$nodes_children = $class_insight::fetchInsightNodeChildren($submatches[2]);

					$nodes_sibling = $class_insight::fetchInsightNodeSiblings($node);
				
					if (
						!is_array($nodes_children) || count($nodes_children) == 0 &&
						!is_array($nodes_sibling) || count($nodes_sibling) == 0
					)

						$last_node = TRUE;

					try {
						$unique_node_id = '_'.$class_toolbox::generateUniqueEntityId($node, ENTITY_INSIGHT_NODE);
					}
					catch (Exception $exception)
					{
						$class_dumper::log(
							__METHOD__,
							array($exception),
							DEBUGGING_DISPLAY_EXCEPTION,
							AFFORDANCE_CATCH_EXCEPTION
						);						
					}	

					if (!empty($node->{PROPERTY_PARENT}))
					{
						$parent_node = $class_insight::fetchInsightNodes(
							array(PROPERTY_ID => $node->{PROPERTY_PARENT})
						);	

						list(, $parent) = each($parent_node);

						try {
							$parent_unique_id = '_'.$class_toolbox::generateUniqueEntityId(
								$parent,
								ENTITY_INSIGHT_NODE
							);
						}
						catch (Exception $exception)
						{
							$class_dumper::log(
								__METHOD__,
								array($exception),
								DEBUGGING_DISPLAY_EXCEPTION,
								AFFORDANCE_CATCH_EXCEPTION
							);			
						}
					}
					else if (!$last_node)
					{
						try {
							$parent_unique_id = '_'.$class_toolbox::generateUniqueEntityId(
								array(
									PROPERTY_OWNER => $node->{PROPERTY_OWNER},
									PROPERTY_THREAD => $node->{PROPERTY_THREAD},
								),
								ENTITY_INSIGHT
							);
						}
						catch (Exception $exception)
						{
							$class_dumper::log(
								__METHOD__,
								array($exception),
								DEBUGGING_DISPLAY_EXCEPTION,
								AFFORDANCE_CATCH_EXCEPTION
							);			
						}
					}
					else
					{
						$insight = $class_insight::fetchInsight($node);

						if (
							!empty($insight->{PROPERTY_TARGET}) &&
							isset($insight->{PROPERTY_TYPE})
						)

							$parent_unique_id = $class_interceptor::getInternalLink(
								$insight->{PROPERTY_TARGET},
								$insight->{PROPERTY_TYPE}
							);
					}

					$member_id = $class_member::getIdentifier();

					$affordance = str_replace('-', '.', $submatches[1]);

					if (
						$member_id != $node->{PROPERTY_OWNER} &&
						$affordance == AFFORDANCE_REMOVE_INSIGHT_NODE
					)
					{
						$match = 
						$submatch = FALSE;
						
						$matches =
						$submatches = array();
					}
					else
					{
						if (!isset($_SESSION[SESSION_STORE_AFFORDANCE]))

							$_SESSION[SESSION_STORE_AFFORDANCE] = array();

						$_SESSION[SESSION_STORE_AFFORDANCE][$affordance] =
						(
								$affordance == AFFORDANCE_REMOVE_INSIGHT_NODE
							?
								$parent_unique_id
							:
								$unique_node_id
						);
					}
				}	
			}
		}

		return array( $match, $submatch, $matches, $submatches );
	}

	/**
	* Check submitted data
	*
	* @param	array	$context	context
	* @param	array	$page		page
	* @return	nothing
    */	
	public static function checkSubmittedData( &$context, $page )
	{
		global $class_application, $verbose_mode;

		// set the dumper class name
		$class_dumper = $class_application::getDumperClass();

		// set the field handler class name
		$class_field_handler = $class_application::getFieldHandlerClass();

		// set the form manager class name
		$class_form_manager = $class_application::getFormManagerClass();

		// set the user handler class name
		$class_user_handler = $class_application::getUserHandlerClass();

		// get the active handler identifer and the coordinates
		list(
			$handler_id,
			$coordinates
		) = $context;

		// set the current position
		$current_position = $coordinates[COORDINATES_CURRENT_POSITION];

		// set the default errors
		$errors = NULL;

		// check the field handler context parameter
		if (
			isset( $context[CONTEXT_INDEX_FIELD_HANDLER] ) &&
			is_object( $context[CONTEXT_INDEX_FIELD_HANDLER] ) &&
			get_class( $context[CONTEXT_INDEX_FIELD_HANDLER] ) ==
				CLASS_FIELD_HANDLER
		)

			// set the field handler
			$field_handler = $context[CONTEXT_INDEX_FIELD_HANDLER];

		// check the error context parameter
		if (
			isset( $context[CONTEXT_INDEX_ERRORS] ) &&
			is_array( $context[CONTEXT_INDEX_ERRORS] )
		)

			// set the errors 
			$errors = $context[CONTEXT_INDEX_ERRORS];

		// check the data submission context parameter
		if (
			isset( $context[CONTEXT_INDEX_DATA_SUBMISSION] ) &&
			is_bool( $context[CONTEXT_INDEX_DATA_SUBMISSION] )
		)

			// set the data submission
			$data_submitted = $context[CONTEXT_INDEX_DATA_SUBMISSION];

		// build the next form if no error is detected
		if (
			isset( $errors ) &&
			is_array( $errors ) &&
			! count( $errors ) &&
			$data_submitted
		)
		{
			// set the scripts
			list(
				$current_script,
				$default_script,
				$next_script
			) = $class_application::fetch_scripts( $context, NULL, $page );

			// set the next anchor
			$next_anchor = $class_application::fetch_anchor(
				$context,
				$page,
				TRUE
			);

			// check the field handler context parameter 
			if ( isset( $context[CONTEXT_INDEX_FIELD_HANDLER] ) )

				// get the context field handler
				$field_handler = $context[CONTEXT_INDEX_FIELD_HANDLER];

			if (
				isset(
					$context[CONTEXT_INDEX_FIELD_VALUES][FIELD_NAME_OFFER]
				) &&
				isset(
					$context[CONTEXT_INDEX_FIELD_VALUES][FIELD_NAME_IDENTITY]
				) && 				
				(
					$challenge_response =
						$context[CONTEXT_INDEX_FIELD_VALUES][FIELD_NAME_OFFER]
				) &&
				(
					$player_identity =
						$context[CONTEXT_INDEX_FIELD_VALUES][FIELD_NAME_IDENTITY]
				) &&
				$_SERVER['REQUEST_URI'] == URI_ACTION_OFFER_CHALLENGE
			)
			{
				$data_validity = self::checkData(
					array(
						'identity' => $player_identity,
						'response' => $challenge_response
					),
					DATA_TYPE_CREDENTIALS,
					HASH_SHA1
				);

				// check the data validity
				if ( $data_validity )

					// login a administrator
					$class_user_handler::logMemberIn(
						$data_validity->{ROW_MEMBER_IDENTIFIER},
						TRUE
					);

				$class_application::jumpTo( URI_ACTION_OVERVIEW );
			}

			// check the current affordance
			if (
				$field_handler->getProperty(PROPERTY_FORM_IDENTIFIER) ==
					AFFORDANCE_CONFIRM &&
				isset( $context[CONTEXT_INDEX_FIELD_VALUES] ) &&
				is_array( $context[CONTEXT_INDEX_FIELD_VALUES] )
			)
			{
				// destroy the current field handler
				$class_form_manager::destroyHandler( $handler_id );

				// check the confirm field name
				if (
					isset(
						$context[CONTEXT_INDEX_FIELD_VALUES][FIELD_NAME_CONFIRM]
					) &&
					$context[CONTEXT_INDEX_FIELD_VALUES][FIELD_NAME_CONFIRM] ==
						CONFIRMATION_VALUE_POSITIVE
				)
				{
					if (
						!isset($_SESSION[SESSION_STORE_AFFORDANCE]) ||
						!is_array($_SESSION[SESSION_STORE_AFFORDANCE])
					)

						$_SESSION[SESSION_STORE_AFFORDANCE] = array();						

					// decode a URL encoded query parameter
					// and save it in the current session
					$_SESSION[SESSION_STORE_AFFORDANCE][AFFORDANCE_CONFIRM] =
						rawurldecode( $_GET[GET_REFERER] );

					// jump to the referer
					$class_application::jumpTo(
						rawurldecode( $_GET[GET_REFERER] )
					);
				}

				// check the previous position
				else if ( $previous_position = self::getPreviousPosition() )
				{
					// get the default border
					$default_border = self::getDefaultBorder();

					if ( $previous_position != URI_ACTION_DISPLAY_DOCUMENT )
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

					}
					else if (
						isset( $_SESSION[STORE_SEARCH_RESULTS] ) && 
						isset(
							$_SESSION[STORE_SEARCH_RESULTS]
								->items[$default_border]
						)
					)

						$previous_position =
							PREFIX_ROOT.
							str::rewrite(
								$_SESSION[STORE_SEARCH_RESULTS]
									->items[$default_border]
										->title
							).
							'-'.GET_DOCUMENT_IDENTIFIER_REWRITTEN.
							$default_border
						;

					// jump to the destination
					$class_application::jumpTo( $previous_position );
				}
				else

					// jump to the root
					$class_application::jumpTo( PREFIX_ROOT );
			}

			// check a roadmap to get the next step in the data submission process
			$class_application::get_next_position(
				$context,
				$next_anchor,
				$field_handler
			);

			// set the next script after checking if updates on the next position 
			$next_script = $class_application::fetch_scripts(
				$context,
				SCRIPT_INDEX_NEXT,
				$page
			);

			// set the next position
			$next_position = $coordinates[COORDINATES_NEXT_POSITION];

			// redirect to the next form step if submitted data are correct
			$class_application::jumpTo(

				// set the next action script
				$next_script.

				// set the internal anchor
				(
						! empty( $next_anchor )
					?
						QUERY_STRING_ANCHOR
					:
						CHARACTER_EMPTY_STRING
				).
				
				$next_anchor
			);

			// terminate the script interpretation
			exit();
		}

		// check the field values 
		if (
			isset( $context[CONTEXT_INDEX_FIELD_VALUES] ) &&
			is_array( $context[CONTEXT_INDEX_FIELD_VALUES] ) &&
			! count( $context[CONTEXT_INDEX_FIELD_VALUES] )
		)
	
			// set the field values
			$context[CONTEXT_INDEX_FIELD_VALUES] =
				$context[CONTEXT_INDEX_DEFAULT_FIELD_VALUES];

		// check the stored field field values
		if (
			isset( $stored_field_values ) &&
			$stored_field_values !== FALSE
		)

			// set the field values
			$context[CONTEXT_INDEX_FIELD_VALUES] = $stored_field_values;	
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
}
?>