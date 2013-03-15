<?php

/**
* Form manager class
*
* Class for form management
* @package  sefi
*/
class Form_Manager extends Toolbox
{
	static protected $control_panel;

	/**
	* Decorate the persistent control panel
	* 
	* @param	mixed		$control_panel		control panel
	* @param	integer		$storage_model		storage model
	* @param	integer		$informant			informant
	* @param	boolean		$assert				assertion flag
	* @return	mixed		persistent control panel
	*/	
	public static function &decoratePersistentControlPanel(
		$control_panel,
		$storage_model = STORE_SESSION,
		$informant = NULL,
		$assert = FALSE
	)
	{
		global $verbose_mode;

		$class_dumper = self::getDumperClass();

		// get a persistent control panel
		$persistent_control_panel = &self::get_persistent_control_panel(
			$storage_model
		);

		$available_field_values = FALSE;

		$store_backup = array();

		$class_dumper::log(
			__METHOD__,
			array(
				'persistent control panel before substitution:',
				$persistent_control_panel
			)
		);

		if (
			isset( $persistent_control_panel[STORE_FIELD_HANDLERS] ) &&
			is_array( $persistent_control_panel[STORE_FIELD_HANDLERS] ) &&
			count( $persistent_control_panel[STORE_FIELD_HANDLERS] ) 
		)
		{
			end( $persistent_control_panel[STORE_FIELD_HANDLERS] );

			list( , $field_handler ) =
				each( $persistent_control_panel[STORE_FIELD_HANDLERS] )
			;

			reset( $persistent_control_panel[STORE_FIELD_HANDLERS] );

			if ( is_object( $field_handler ) )

				$store_backup[PROPERTY_CONFIGURATION] =
					$field_handler->getConfig()
				;

			$class_dumper::log(
				__METHOD__,
				array(
					'field handler configuration from backup store:',
					$store_backup
				)
			);
		}

		// set a persistent store
		$persistent_control_panel = $control_panel;

		$class_dumper::log(
			__METHOD__,
			array(
				'persistent control panel after substitution:',
				$persistent_control_panel,
				'backup store: ',
				$store_backup
			)
		);

		$initialize_store = function ( &$container, $store_type )
		{
			// check the store
			if ( ! isset( $container[$store_type] ) )

				$container[$store_type] = array();
		};

		$store_types = array(
			STORE_AFFORDANCES,
			STORE_CONTEXT,
			STORE_SIGNATURES
		);

		while ( list( , $store_type ) = each( $store_types ) )

			$initialize_store( $persistent_control_panel, $store_type );

		reset( $store_types );

		if (
			isset( $control_panel[STORE_FIELD_HANDLERS] ) &&
			isset( $control_panel[STORE_FIELD_HANDLERS][1] )
		)
		{
			if (
				$control_panel[STORE_FIELD_HANDLERS][1]
					->getProperty(PROPERTY_FORM_IDENTIFIER) != NULL &&
				! isset(
					$persistent_control_panel[STORE_AFFORDANCES]
						[$control_panel[STORE_FIELD_HANDLERS][1]
							->getProperty( PROPERTY_FORM_IDENTIFIER )]
				)
			)
	
				// append the signature store to the persistent control panel
				$persistent_control_panel[STORE_AFFORDANCES] =
	
					array(
						$control_panel[STORE_FIELD_HANDLERS][1]
							->getProperty( PROPERTY_FORM_IDENTIFIER ) => 1
					)
				;
	
			// append the context store to the persistent control panel
			$persistent_control_panel[STORE_CONTEXT][PROPERTY_FORM_IDENTIFIER] =
				$control_panel[STORE_FIELD_HANDLERS][1]
					->getProperty( PROPERTY_FORM_IDENTIFIER )
			;
	
			if ( ! isset( $persistent_control_panel[STORE_SIGNATURES][1] ) )
			
				// append the signature store to the persistent control panel
				$persistent_control_panel[STORE_SIGNATURES] =
					array(
						1 => $control_panel[STORE_FIELD_HANDLERS][1]
							->getProperty(PROPERTY_SIGNATURE)
					)
				;
		}

		if (
			isset( $store_backup[PROPERTY_CONFIGURATION] ) &&
			count( $persistent_control_panel[STORE_FIELD_HANDLERS] ) &&
			(
				$field_handler = &$persistent_control_panel[STORE_FIELD_HANDLERS]
					[count( $persistent_control_panel[STORE_FIELD_HANDLERS] )]
			)
		)
		{
			$configuration_decorated = $field_handler->getConfig();

			$configuration = $store_backup[PROPERTY_CONFIGURATION];

			if (
				isset( $configuration[PROPERTY_STORE] ) && 
				is_array( $configuration[PROPERTY_STORE] ) &&
				isset(
					$configuration[PROPERTY_STORE][0]
				) &&
				isset(
					$configuration[PROPERTY_STORE][0][SESSION_STORE_FIELD]
				) &&
				isset(
					$configuration[PROPERTY_STORE][0][SESSION_STORE_FIELD]
				 				[SESSION_STORE_HALF_LIVING]
				) &&
				is_array(
					$configuration[PROPERTY_STORE][0][SESSION_STORE_FIELD]
				 				[SESSION_STORE_HALF_LIVING]					
				) &&
				count(
					$configuration[PROPERTY_STORE][0][SESSION_STORE_FIELD]
				 				[SESSION_STORE_HALF_LIVING]
				)
			)

				$available_field_values = TRUE;

			if ( $available_field_values )
			{
				$class_dumper::log(
					__METHOD__,
					array(
						'persistent control panel configuration after decoration: ',
						$configuration,
						'backup configuration: ',
						$store_backup[PROPERTY_CONFIGURATION],
						'available field values: ',
						$configuration[PROPERTY_STORE][0][SESSION_STORE_FIELD]
							[SESSION_STORE_HALF_LIVING]
					)
				);

				$configuration_decorated[PROPERTY_STORE][0][SESSION_STORE_FIELD]
					[SESSION_STORE_HALF_LIVING] =
						$configuration[PROPERTY_STORE][0][SESSION_STORE_FIELD]
							[SESSION_STORE_HALF_LIVING]
				;

				$configuration_decorated[PROPERTY_STORE][0][SESSION_STORE_FIELD]
					[SESSION_STORE_VALUE] =
						$configuration[PROPERTY_STORE][0][SESSION_STORE_FIELD]
							[SESSION_STORE_VALUE]
				;

				$configuration_decorated[PROPERTY_STORE][0]
					[SESSION_CONTROL_DASHBOARD] =
						$configuration[PROPERTY_STORE][0]
							[SESSION_CONTROL_DASHBOARD]
				;

				$configuration_decorated[PROPERTY_STORE][0]
					[SESSION_CONTROL_DASHBOARD][SESSION_STATUS] =
						SESSION_STATUS_DATA_SUBMITTED
				;

				$configuration_decorated[PROPERTY_STORE][SESSION_STATUS] =
					SESSION_STATUS_DATA_SUBMITTED
				;

				$class_dumper::log(
					__METHOD__,
					array(
						'configuration decorated after restoration: ',
						$configuration_decorated
					)
				);

				$field_handler->setConfig( $configuration_decorated );
			}
		}

		return $persistent_control_panel;
	}

    /**
    * Get a reference to the control panel
    *
    * @return 	array	containing the control panel
	*/
	public static function &get_control_panel()
	{
		// check the control panel
		if ( ! is_array( self::$control_panel ) )

			// set the control panel
			self::$control_panel = array();

		// return the control panel
		return self::$control_panel;
	}

    /**
    * Get a reference to the persistent control panel
    *
	* @param	integer	$storage_model	representing a storage model
    * @return 	array 	containing references to control panel contents
	*/
	public static function &get_persistent_control_panel(
		$storage_model = STORE_SESSION
	)
	{
		// check the control panel in session
		if (
			! isset( $_SESSION[SESSION_CONTROL_PANEL] ) ||
			! is_array( $_SESSION[SESSION_CONTROL_PANEL] )
		)

			// declare an empty control panel
			$_SESSION[SESSION_CONTROL_PANEL] = array();

		// return the persistent control panel
		return $_SESSION[SESSION_CONTROL_PANEL];
	}

    /**
    * Alias to the getPersistentFieldHandler method
    *
    * @param	integer	$handler_id			handler
	* @param	integer	$storage_model		storage model
    * @return 	mixed
	*/
	public static function &get_persistent_field_handler(
		$handler_id = FORM_ORDINARY,
		$storage_model = STORE_SESSION
	)
	{
		// get a persistent field handler		
		$persistent_field_handler = &self::getPersistentFieldHandler(
			$handler_id,
			$storage_model
		);

		// return a persistent field handler
		return $persistent_field_handler;
	}

    /**
    * Alias to the getPersistentProperty method
    *
    * @param	string	$property			property
    * @param	integer	$handler_id			handler identifier
	* @param	string	$entity_type		entity type
	* @param	integer	$storage_model		storage model
    * @return 	array 	containing references to control panel contents
	*/
	public static function &get_persistent_property(
		$property,
		$handler_id = FORM_ORDINARY,
		$entity_type = ENTITY_FIELD_HANDLER,
		$storage_model = STORE_SESSION
	)
	{
		// get a persistent property
		$persistent_property = &self::getPersistentProperty(
			$property,
			$handler_id,
			$entity_type,
			$storage_model
		);
		
		// return a persistent property
		return $persistent_property;
	}

    /**
    * Alias to the get_persistent_store method
    *
	* @param	integer	$storage_model	representing a storage model
    * @return 	array 	references to control panel contents
	*/
	public static function &getPersistentAffordanceStore($storage_model = STORE_SESSION)
	{
		// get a persistent affordance store
		$persistent_affordance_store = &self::getPersistentStore(
			SESSION_STORE_AFFORDANCE,
			$storage_model
		);

		// return an affordance store		
		return $persistent_affordance_store;
	}

    /**
    * Alias to the get_persistent_store method
    *
	* @param	string	$store_type		containing a store type
	* @param	integer	$storage_model	representing a storage model
    * @return 	array 	containing references to control panel contents
	*/
	public static function &getPersistentStore($store_type, $storage_model = STORE_SESSION)
	{
		// return a persistent store
		$persistent_store = &self::get_persistent_store(
			$store_type,
			$storage_model
		);

		// return a reference to a persistent store		
		return $persistent_store;
	}

    /**
    * Get a reference to persistent store
    *
	* @param	string	$store_type			store type
	* @param	integer	$storage_model		storage model
    * @return 	array 	references to control panel contents
	*/
	public static function &get_persistent_store(
		$store_type,
		$storage_model = STORE_SESSION
	)
	{
		$class_dumper = self::getDumperClass();
		
		// get the control panel
		$persistent_control_panel = &self::get_persistent_control_panel(
			$storage_model
		);

		$class_dumper::log(
			__METHOD__,
			array(
				'persistent control panel: ',
				$persistent_control_panel				  
			)
		);

		// check the persistent store
		if (
			! isset( $persistent_control_panel[$store_type] ) ||
			(
				! is_array( $persistent_control_panel[$store_type] ) &&
				! is_object( $persistent_control_panel[$store_type] )
			)
		)
	
			// set the persistent store
			$persistent_control_panel[$store_type] = array();

		// return the persistent store
		return $persistent_control_panel[$store_type];
	}

    /**
    * Deserialize an entity
    *
	* @param	array	$properties		properties
	* @param	string	$entity_type	entity
    * @param	mixed	$informant		informant		
    * @return	mixed
	*/
	public static function &deserialize(
		$properties,
		$entity_type = ENTITY_FIELD_HANDLER,
		$informant = NULL
	)
	{
		global $verbose_mode;

		$class_dumper = self::getDumperClass();

		$entity = NULL;

		// switch from the entity argument
		switch ( $entity_type )
		{
			case ENTITY_FIELD_HANDLER:

				// check the array of properties
				if ( is_array( $properties ) && count( $properties )  )
				{			
					// get the handler identifier
					list( $handler_id, $action ) = $properties;

					// get a persistent instance of field handler
					$entity = &self::getPersistentFieldHandler(
						$handler_id,
						STORE_SESSION,
						__METHOD__
					);

					$entity->setProperties( $properties );

					// set the member action
					$entity->setAction( $action );
				}

					break;			
		}

		return $entity;
	}

	/**
    * Get active handler
    *
	* @param	integer		$informant		informant
    * @return 	integer		handler
    */
	public static function &getActiveHandler($informant = null)
	{
		// get an active handler
		$active_handler = &self::getHandlerByStatus(HANDLER_STATUS_ACTIVE, $informant);

		// return an active handler
		return $active_handler;
	}

	/**
    * Get a handler by status
    *
	* @param	string		$status		status
	* @param	integer		$informant		informant
    * @return 	integer		handler
    */
	public static function &getHandlerByStatus(
		$status = HANDLER_STATUS_ACTIVE,
		$informant = NULL
	)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		// declare the default handler
		$handler = FORM_ORDINARY;

		// declare an array of ubiquitous handlers
		$_handlers = array();

		// get handlers by status
		$handlers = &self::getHandlerStatus( $informant );

		// loop on handlers
		while ( list( $handler_id, $handler_status ) = each( $handlers ) )

			if ( $handler_status == $status )

				$_handlers[] = $handler_id;

		reset( $handlers );

		// check if more than one handler is set to the status argument
		if ( count( $_handlers ) > 1 )

			// set the handler
			$handler = max( $_handlers );

		// check if there is at least one handler set to the status argument
		else if ( count( $_handlers) == 1 )

			// set the ubiquitous handler
			$handler = array_pop($_handlers);
	
		if ( ! is_null( $informant ) )

			$class_dumper::log(
				__METHOD__,
				array(
					'handler identifier:',
					$handler
				)
			);	

		// return the handler
		return $handler;
	}

	/**
    * Get handlers
    *
	* @param	integer		$informant		informant
	* @param	integer	$storage_model		storage model
    * @return 	&array		handler status
    */
	public static function &getHandlerStatus(
		$informant = NULL,
		$storage_model = STORE_SESSION
	)
	{
		// get the persistent_control_panel
		$persistent_control_panel = &self::get_persistent_control_panel();

		// get a persistent store
		$store_field_handlers = self::get_persistent_store(
			STORE_FIELD_HANDLERS,
			$storage_model
		);

		// case when there is no active handler
		if (
			! isset($persistent_control_panel[PROPERTY_HANDLER_STATUS] ) ||
			! is_array($persistent_control_panel[PROPERTY_HANDLER_STATUS] ) ||
			! count( $persistent_control_panel[PROPERTY_HANDLER_STATUS] ) 
		)
		{			
			if (
				! isset( $persistent_control_panel[PROPERTY_HANDLER_STATUS] ) ||
				count( $store_field_handlers ) ==
					count( $persistent_control_panel[PROPERTY_HANDLER_STATUS] )
			)

				// declare the handler status store as an empty array
				$persistent_control_panel[PROPERTY_HANDLER_STATUS] = array();
			else
			{
				list( $handler ) = each($store_field_handlers );

				$persistent_control_panel[PROPERTY_HANDLER_STATUS][$handler] =
					HANDLER_STATUS_ACTIVE;
			}
		}

		// return handler status
		return $persistent_control_panel[PROPERTY_HANDLER_STATUS];
	}

    /**
    * Construct a formManager object
    *
    * @param	string	$form_id		form identifier
    * @param	boolean	$administration	administration flag
    * @param	boolean	$target			target
    * @param	boolean	$edition		edition flag
    * @param	mixed	$informant		informant
    * @return 	object	formManager
	*/
	public function __construct(
		$form_id,
		$administration = FALSE,
		$target = NULL,
		$edition = FALSE,
		$informant = NULL
	)
	{
		global $class_application, $verbose_mode;

		// set the dumper class
		$class_dumper = $class_application::getDumperClass();

		// set the field handler class
		$class_field_handler = $class_application::getFieldHandlerClass();

		// set the test case class
		$class_test_case = self::getTestCaseClass();

		// load a config file
		$field_handler = $class_field_handler::load(
			$form_id,
			$administration,
			FALSE,
			$edition,
			$informant
		);

		if ( ! is_null( $target ) )
		{
			$field_handler->setProperty(PROPERTY_TARGET, $target);

			$field_handler->setProperty(
				PROPERTY_FORM_IDENTIFIER,
				( $edition ? ACTION_EDIT.'.' : '' ).
				$field_handler->getProperty( PROPERTY_FORM_IDENTIFIER ).
				'.'.$target
			);
		}

		$class_test_case::perform(
			DEBUGGING_FIELD_ERROR_HANDLING,
			$verbose_mode,
			'before adding a new field handler'			
		);

		// add a field handler to the persistent control panel
		self::add_field_handler( $field_handler, $informant );

		$class_test_case::perform(
			DEBUGGING_FIELD_ERROR_HANDLING,
			$verbose_mode,
			'after adding a new field handler'
		);
	}

	/**
    * Deactivate a persistent field handler
    *
    * @param	integer		$form_identifier		form identifier
	* @param	integer		$storage_model		storage model
    * @return 	nothing
    */	    
	public static function deactivateHandler(
		$form_identifier = null,
		$storage_model = STORE_SESSION
	)
	{
		// check the handler identifier
		if (!isset($handler_id))

			// get the active handler identifier
			$handler_id = self::getActiveHandler();

		// get the persistent form manager
		$form_manager = &self::getPersistentFieldHandler($handler_id);

		// get a persistent store
		$store_field_handlers = &self::get_persistent_store(STORE_FIELD_HANDLERS, $storage_model);

		// check if the persistent form identifier matches with the provided one
		$match = $form_identifier == self::getPersistentProperty(PROPERTY_FORM_IDENTIFIER, $handler_id);

		// check if the field handler matching with the provided identifier
		if (isset($store_field_handlers[$handler_id]) && $match)
		{
			$class_field_handler = CLASS_FIELD_HANDLER;

			// set the persistent handler status inactive
			$class_field_handler::setPersistentHandlerStatus(HANDLER_STATUS_INACTIVE, $handler_id);

			// disabled the field handler by toggling its identifier
			$store_field_handlers[-1 * $handler_id] = $store_field_handlers[$handler_id];

			// unset the matching field handler
			unset($store_field_handlers[$handler_id]);
		}
	}

	/**
    * Destroy a persistent field handler
    *
    * @param	integer	$handler_id		field handler
	* @param	integer	$protection		protection flag
	* @param	integer	$storage_model	storage model
    * @return 	nothing
    */
	public static function destroyHandler(
		$handler_id = null,
		$protection = FALSE,
		$storage_model = STORE_SESSION
	)
	{
		// set the field handler class name
		$class_field_handler = CLASS_FIELD_HANDLER;

		// get the persistent form manager
		$form_manager = &self::getPersistentFieldHandler($handler_id);

		// get the persistent affordances 
		$store_affordances = &self::get_persistent_store(STORE_AFFORDANCES, $storage_model);

		// get the persistent field handlers
		$store_field_handlers = &self::get_persistent_store(STORE_FIELD_HANDLERS, $storage_model);

		// get the persistent signatures
		$store_signatures = &self::get_persistent_store(STORE_SIGNATURES, $storage_model);

		// check if the field handler matching with the provided identifier
		if (isset($store_field_handlers[$handler_id]))
		{
			if (
				!$protection ||
				$store_field_handlers[$handler_id]->getProperty(PROPERTY_FORM_IDENTIFIER) != $protection
			)
			{
				// remove a handler identifier
				$class_field_handler::remove_handler($handler_id);
	
				// unset the matching field handler
				unset($store_field_handlers[$handler_id]);

				// unset the matching field handler
				unset($store_signatures[$handler_id]);
				
				$keys = array_search($handler_id, $store_affordances);

				if (count($keys) == 1)

					unset($store_affordances[$keys]);
			}
		}
	}

	/**
	* Extract a target from a raw form identifier
	*
	* @param	string	$identifier		form identifier
	* @param	integer	$target			target
	* @return	nothing
	*/
	public static function extractTarget(&$identifier, &$target)
	{
		$class_field_handler = CLASS_FIELD_HANDLER;
		
		$class_field_handler::extractTarget($identifier, $target);
	}

	/**
    * Check handlers
    *
    * @param	array	$context		deficient context
    * @param	mixed	$corrections	corrected data
    * @param	boolean	$edition		edition flag
    * @param	mixed	$informant		informant
    * @return 	nothing
    */	    
	public static function fixOnCheck(
		$context,
		&$corrections,
		$edition = FALSE,
		$informant = NULL
	)
	{
		// set the Dumper class name
		$class_dumper = self::getDumperClass();

		// set the field handler class name
		$class_field_handler = self::getFieldHandlerClass();

		// get handlers status
		$handlers_status = &self::getHandlerStatus();

		// get the persistent affordances store
		$store_affordances = &self::get_persistent_store( STORE_AFFORDANCES );

		// get the persistent field handlers store
		$store_field_handlers = &self::get_persistent_store( STORE_FIELD_HANDLERS );

		while (
			list( $_handler_id, $_field_handler ) =
				each( $store_field_handlers )
		)

			if (
				is_object( $_field_handler ) &&
				$_field_handler->getDataStatus()
			)
			{
				$form_identifier =
					$_field_handler->getProperty( PROPERTY_FORM_IDENTIFIER );
				
				$children = $_field_handler->getAProperty( PROPERTY_CHILDREN );

				$submitted_data = $_field_handler->getSubmittedData();

				if (
					(
						! is_array( $children ) ||
						! isset( $children[PROPERTY_AFFORDANCE] )
					) &&
					is_array( $submitted_data ) &&
					count( $submitted_data ) &&
					! empty( $submitted_data[PROPERTY_AFFORDANCE] )
				)
				{
					$saved_field_handler = $class_field_handler::load(
						$submitted_data[PROPERTY_AFFORDANCE],
						TRUE,
						$_handler_id,
						$edition,
						$informant
					);
					
					$store_field_handlers[$_handler_id] = $saved_field_handler;

					break;	
				}
			}

		reset( $store_field_handlers );

		if ( isset( $context[PROPERTY_HANDLERS] ) )
		{
			$duplicated_forms =
			$form_identifiers =
			$handlers =
			$intruders =
			$removal_list = array();

			// check affordances
			while  (
				list( $form_identifier, $handler_id) =
					each( $store_affordances )
			)

				if ( ! in_array($handler_id, $handlers ) )
				{
					if (
						isset( $store_field_handlers[$handler_id] ) &&
						$store_field_handlers[$handler_id]
							->getProperty( PROPERTY_FORM_IDENTIFIER ) ==
								$form_identifier
					)
	
						$handlers[$form_identifier] = $handler_id;
					else
					
						$intruders[] = $form_identifier;
				}
				else
				{
					$master_identifier =
						$store_field_handlers[$handler_id]
							->getProperty( PROPERTY_FORM_IDENTIFIER );

					if ( $form_identifier != $master_identifier )

						$intruders[] = $form_identifier; 
				}

			reset( $store_affordances );
			
			while (
				list( $handler_id, $field_handler) =
					each( $store_field_handlers )
			)
			{
				if ( is_object( $field_handler ) )
				{
					$form_identifier =
						$field_handler->getProperty(
							PROPERTY_FORM_IDENTIFIER
						);
	
					if ( in_array( $form_identifier, $intruders ) )
					
						$store_affordances[$form_identifier] = $handler_id;
				}
			}

			reset( $store_field_handlers );

			while ( list( $index, $handler_id ) = each( $intruders ) )
			{
				unset( $store_affordances[$handler_id] );

				if ( isset( $handlers_status[$handler_id] ) )

					unset( $handlers_status[$handler_id] );
			}

			while (
				$null_key = array_search( NULL, $context[PROPERTY_HANDLERS] ) )
			{
				unset( $context[PROPERTY_HANDLERS][$null_key] );

				if ( $store_field_handlers[$null_key] === NULL )

					unset( $store_field_handlers[$null_key] );
			}

			// get a solid ground of form identifiers
			while (
				list( $handler_id, $field_handler )
					= each( $context[PROPERTY_HANDLERS] )
			)

				if (
					! in_array(
						$field_handler->getProperty( PROPERTY_FORM_IDENTIFIER ),
						$form_identifiers
					)
				)

					$form_identifiers[$handler_id] =
						$field_handler->getProperty( PROPERTY_FORM_IDENTIFIER );
				else

					$duplicated_forms[$handler_id] =
						$field_handler->getProperty( PROPERTY_FORM_IDENTIFIER );

			if ( count( $duplicated_forms ) )
			{
				// create a list of identifiers to be removed for duplication
				while (
					list( $handler_id, $form_identifier )
						= each( $duplicated_forms )
				)
				{
					if ( ! in_array( $handler_id, $store_affordances ) )
					{
						$removal_list[] = $handler_id;

						unset( $duplicated_forms[$handler_id] );
					}
				}

				reset($duplicated_forms);

				if (count($duplicated_forms) > 0)

					// skim the list of duplicated forms again
					while (
						list( $handler_id, $form_identifier ) =
							each( $duplicated_forms )
					)
					{
						$duplicated_form_id = array_search(
							$form_identifier,
							$form_identifiers
						);

						$removal_list[] = $duplicated_form_id;

						$form_identifiers[$handler_id] = $form_identifier;

						unset( $form_identifiers[$duplicated_form_id] );
					}
			}

			// go through the removal list
			while ( list( $index, $handler_id ) = each( $removal_list ) )
			{
				unset($handlers_status[$handler_id]);

				unset($store_field_handlers[$handler_id]);
			}
		}
	}

    /**
    * Get a handler identifier
    *
    * @param	string		$affordance		affordance
    * @param	boolean		$administration	administration flag	
    * @param	boolean		$edition		edition flag	
    * @param	mixed		$informant		informant		
    * @return 	integer		handler identifier
	*/
	public static function getHandlerId(
		$affordance,
		$administration = FALSE,
		$edition = FALSE,
		$informant = NULL
	)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$target = NULL;

		// extract a possible target entity from an affordance or form identifier
		self::extractTarget( $affordance, $target );

		// construct a new Form_Manager object
		$form_manager = new self(
			$affordance,
			$administration,
			$target,
			$edition,
			$informant
		);

		// get the persistent field handlers
		$store_field_handlers = &self::get_persistent_store( STORE_FIELD_HANDLERS );

		// check the field handlers
		self::fixOnCheck(
			array(
				PROPERTY_HANDLERS => $store_field_handlers
			),
			$store_field_handlers,
			$edition,
			$informant
		);

		// check if the identifier of the newly created form manager
		// matches with the provided affordance
		if (
			isset($store_field_handlers[$form_manager->get_handler_id()]) && 
			$store_field_handlers
				[$form_manager->get_handler_id()]
					->getProperty(PROPERTY_FORM_IDENTIFIER)	!= $affordance		
		)
		{
			list($index, $handlers) = each($form_manager::get_control_panel());			

			if ( isset( $handlers[$form_manager->get_handler_id()] ) )

				$store_field_handlers[$form_manager->get_handler_id()] =
					$handlers[$form_manager->get_handler_id()];
		}

		// return the identifier of a form manager instance
		return $form_manager->get_handler_id( $informant );
	}

	/**
    * Get inactive handlers
    *
	* @param	integer		$informant		informant
	* @param	integer	$storage_model		storage model	
    * @return 	array			handlers
    */
	public static function getInactiveHandlers($informant = null, $storage_model = STORE_SESSION)
	{
		// declare a default array of inactive handlers
		$inactive_handlers = array();

		// get a persistent store
		$store_handler_status = self::getHandlerStatus();

		// loop on handlers
		while (list($handler, $handler_status) = each($store_handler_status))
		{
			// check if the current handler is inactive
			if ($handler_status == HANDLER_STATUS_INACTIVE)

				$inactive_handlers[] = $handler;
		}

		// return the inactive handlers
		return $inactive_handlers;
	}

    /**
    * Alias to the get_persistent_control_panel method
    *
	* @param	integer	$storage_model		storage model
    * @return 	array 			references to control panel contents
	*/
	public static function &getPersistentControlPanel($storage_model = STORE_SESSION)
	{
		// get a reference to the persistent control panel
		$persistent_control_panel = &self::get_persistent_control_panel($storage_model);

		// return a persistent control panel		
		return $persistent_control_panel;
	}

    /**
    * Get a reference to a persistent field handler
    *
    * @param	integer		$handler_id		handler
	* @param	integer	$storage_model		storage model
	* @param	integer		$informant		informant
    * @return 	mixed
	*/
	public static function &getPersistentFieldHandler(
		$handler_id = FORM_ORDINARY,
		$storage_model = STORE_SESSION,
		$informant = NULL
	)
	{
		global $verbose_mode;

		$class_dumper = self::getDumperClass();

		$class_field_handler = self::getFieldHandlerClass();

		// get the active handler
		$active_handler_id = self::getActiveHandler( $informant );

		// set an existence flag
		$already_existed = TRUE;

		// get a reference to the persistent control panel
		$store_field_handlers = self::get_persistent_store(
			STORE_FIELD_HANDLERS,
			$storage_model
		);

		$class_dumper::log(
			__METHOD__,
			array(
				'persistent field handlers: ',
				$store_field_handlers				  
			)
		);
		
		// construct a field handler if the provided id can not be found 
		if (
			! isset( $store_field_handlers[$handler_id] ) ||
			! is_object( $store_field_handlers[$handler_id] ) ||
			get_class( $store_field_handlers[$handler_id] ) !=
				$class_field_handler
		)
		{
			// check the persistent handler
			if ( ! isset( $store_field_handlers[$handler_id] ) )
			{
				// construct a field handler
				$store_field_handlers[$handler_id] =
					$class_field_handler::getAbstractFieldHandler();

				// toggle the existence flag				
				$already_existed = FALSE;
			}
		}
		else
		{
			// get a reference to the persistent control panel
			$store_field_handlers = &self::get_persistent_store(
				STORE_FIELD_HANDLERS,
				$storage_model
			);

			if ( isset( $store_field_handlers[$handler_id] ) )
			
				$field_handler = &$store_field_handlers[$handler_id];

			if ( $informant == __CLASS__.'::deserialize' )
			
				$class_dumper::log(
					__METHOD__,
					array(
						'field handler id at deserialization: ',
						$field_handler->get_handler_id()
					)
				);

			return $field_handler;
		}

		// compare the handler identifier and the active handler
		if ( $active_handler_id != $handler_id )
		{
			// activate a handler		
			self::activateHandler( $handler_id );

			// check the field handler identifier
			if ( $already_existed )

				// select a handler		
				self::selectHandler( $handler_id, TRUE );

			else if ( ! $already_existed )
			{
				// get a persistent store
				$store_handler_status = &self::getHandlerStatus();

				unset( $store_handler_status[$handler_id] );
			}
		}
		else

			// select a handler		
			self::selectHandler( $handler_id, TRUE );

		$class_dumper::log(
			__METHOD__,
			array(
				'selected field handler: ',
				$store_field_handlers[$handler_id]				  
			)
		);

		// return a persistent handler
		return $store_field_handlers[$handler_id];
	}

    /**
    * Get a reference to a persistent property
    *
    * @param	string		$property		property
    * @param	integer		$handler_id		handler identifier
	* @param	string		$entity_type	entity type
	* @param	integer		$storage_model	storage model
	* @param	integer		$informant		informant
    * @return 	mixed		references to control panel contents
	*/
	public static function &getPersistentProperty(
		$property,
		$handler_id = FORM_ORDINARY,
		$entity_type = ENTITY_FIELD_HANDLER,
		$storage_model = STORE_SESSION,
		$informant = null
	)
	{
		$class_dumper = self::getDumperClass();
		
		$_property = null;

		switch ( $entity_type )
		{
			case ENTITY_FIELD_HANDLER:

				// get a persistent handler
				$field_handler = &self::getPersistentFieldHandler(
					$handler_id,
					$storage_model
				);

				try
				{
					// set a reference to a the property
					$persistent_property = &$field_handler->getProperty(
						$property
					);
				}
				catch ( Exception $exception )
				{
					$class_dumper::log(
						__METHOD__,
						array(
							'An exception has been caught while calling '.
							'f i e l d H a n d l e r  : :  g e t P r o p e r t y =>',
							$exception,
							CHARACTER_BLANK.CHARACTER_PARENTHESIS_START.
							$property.CHARACTER_PARENTHESIS_END
						),
						DEBUGGING_DISPLAY_EXCEPTION,
						AFFORDANCE_CATCH_EXCEPTION
					);
				}

					break;				

			case ENTITY_CONTROL_PANEL:

				// get the persistent_control_panel
				$persistent_control_panel =
					&self::get_persistent_control_panel()
				;

				// check the provided property
				if ( is_string( $property ) && ! empty( $property ) )
				{
					if (
						! isset( $persistent_control_panel[$property] ) ||
						! is_array( $persistent_control_panel[$property] ) ||
						! is_object( $persistent_control_panel[$property] )
					)

						// declare the handler status store as an empty array
						$persistent_control_panel[$property] = array();

					$persistent_property = &$persistent_control_panel[$property];
				}
				else

					return;
	
					break;
	
			case ENTITY_FORM_MANAGER:
			default:

				// get a persistent handler
				$persistent_handler = &self::getPersistentFieldHandler(
					$handler_id,
					$storage_model,
					$informant
				);

				// check the persistent property
				if (
					! isset( $persistent_handler->$property ) ||
					! is_object( $persistent_handler ) &&
					! is_array( $persistent_handler )
				)
				{
					// switch from the property name
					switch ( $property )
					{
						case PROPERTY_FIELD_VALUES:

							// get a store
							$store = &self::getPersistentProperty(
								PROPERTY_STORE,
								$handler_id
							);

							// get a roadmap
							$position = $persistent_handler->getPosition(
								COORDINATES_CURRENT_POSITION,
								$handler_id
							);

							if (
								isset(
									$store[$position]
								) &&
								is_array(
									$store[$position]
								) &&
								count(
									$store[$position]
								) &&
								isset(
									$store[$position][SESSION_STORE_FIELD]
								) &&
								is_array(
									$store[$position][SESSION_STORE_FIELD]
								) &&
								count(
									$store
										[$position]
											[SESSION_STORE_FIELD]
								) &&
								isset(
									$store
										[$position]
											[SESSION_STORE_FIELD]
												[SESSION_STORE_VALUE]
								) &&
								is_array(
									$store
										[$position]
											[SESSION_STORE_FIELD]
												[SESSION_STORE_VALUE]
								) &&
								count(
									$store
										[$position]
											[SESSION_STORE_FIELD]
												[SESSION_STORE_VALUE]
								)
							)

								$_property =
									&$store[$position]
										[SESSION_STORE_FIELD]
											[SESSION_STORE_VALUE]
								;

								break;

						case PROPERTY_FIELDS:

							// get a store
							$store = &self::getPersistentProperty(
								PROPERTY_STORE,
								$handler_id
							);

							// get a roadmap
							$position = $persistent_handler->getPosition(
								COORDINATES_CURRENT_POSITION,
								$handler_id
							);

							if (
								isset( $store[$position] ) &&
								is_array( $store[$position] ) &&
								count( $store[$position] ) &&
								isset(
									$store
										[$position]
											[SESSION_STORE_FIELD]
								) &&
								is_array(
									$store
										[$position]
											[SESSION_STORE_FIELD]
								) &&
								count(
									$store
										[$position]
											[SESSION_STORE_FIELD]
								) != 0 &&
								isset(
									$store
										[$position]
											[SESSION_STORE_FIELD]
												[SESSION_STORE_ATTRIBUTE]
								) &&
								is_array(
									$store
										[$position]
											[SESSION_STORE_FIELD]
												[SESSION_STORE_ATTRIBUTE]
								) &&
								count(
									$store
										[$position]
											[SESSION_STORE_FIELD]
												[SESSION_STORE_ATTRIBUTE]
								)
							)

								$_property =
									&$store
										[$position]
											[SESSION_STORE_FIELD]
												[SESSION_STORE_ATTRIBUTE]
								;

								break;
							
						case PROPERTY_COORDINATES:
						case PROPERTY_HANDLER:

							// check the persistent property		
							if (
								! isset( $persistent_handler->$property ) ||
								! is_array( $persistent_handler->$property )
							)

									// set the default persistent property
								$persistent_handler->$property = array();
		
								break;

						case PROPERTY_ROADMAP:

							// set the persistent roadmap property
							$_property = &$persistent_handler
								->{AFFORDANCE_GET.ucfirst($property)}()
							;

								break;

						default:
		
							// set the persistent property
							$persistent_handler->$property = new stdClass();
					}
				}

				// check if the persistent property is protected or private
				if ( ! isset( $property ) )

					// get a persistent property
					$persistent_property = &$persistent_handler->$property ;

				else
				
					// get a persistent property
					$persistent_property = $_property;

					break;
		}

		// return a property
		return $persistent_property;
	}

	/**
    * Activate a handler
    *
    * @param	integer		$handler_id		field handler
	* @param	integer		$informant		informant
    * @return 	nothing
    */
	public static function activateHandler( $handler_id, $informant = NULL )
	{
		// get handler status
		$handlers = &self::getHandlerStatus( $informant );

		// get the persistent field handler store
		$store_field_handlers = self::getPersistentStore(
			STORE_FIELD_HANDLERS
		);

		// switch from the handler identifier
		switch ( $handler_id )
		{
			default:

				if ( isset( $store_field_handlers[$handler_id] ) )
				{
					if (
						! isset( $handlers[(int)$handler_id] ) ||
						$handlers[(int)$handler_id] != HANDLER_STATUS_SLEEPING
					)
	
						// set the current handler to active
						$handlers[(int)$handler_id] = HANDLER_STATUS_ACTIVE;
				}

					break;
		}
	}

    /**
    * Check if a field handler presents multiple steps
    *
    * @param	object		&$field_handler		representing a field handler
    * @param	integer		$informant			informant
    * @return 	nothing
	*/
	public static function add_field_handler(
		&$field_handler,
		$informant = NULL
	)
	{
		global $verbose_mode;

		$class_dumper = self::getDumperClass();
		
		// get the control panel
		$control_panel = &self::get_control_panel();

		// check the field handler store of the control panel
		if (
			! isset( $control_panel[STORE_FIELD_HANDLERS] ) ||
			! is_array( $control_panel[STORE_FIELD_HANDLERS] )
		)

			// set the field handler store
			$control_panel[STORE_FIELD_HANDLERS] = array();

		// check the field handler argument
		if (
			is_object( $field_handler ) &&
			get_class( $field_handler )
				== CLASS_FIELD_HANDLER
		)	
	
			// append a field handler to the field handler store
			// of the control panel
			$control_panel
				[STORE_FIELD_HANDLERS]
					[(int)$field_handler->get_handler_id()] =
				$field_handler
			;

		$class_dumper::log(
			__METHOD__,
			array(
				'field handler id at construction: ',
				$field_handler->get_handler_id()
			)
		);

		// set the persistent control panel
		self::set_persistent_control_panel( $control_panel, STORE_SESSION ) ;
	}

    /**
    * Get the current handler identifier
    *
    * @param	integer		$informant		informant
    * @return 	integer		representing a handler
	*/
	public static function get_handler_id($informant = null)
	{
		// get the control panel
		$control_panel = self::get_control_panel();

		// get the current field handler
		$field_handler = array_pop( $control_panel[STORE_FIELD_HANDLERS] );

		// get the current field handler identifier
		$handler_id = $field_handler->get_handler_id();

		// get the active handlers
		$active_handlers = self::getActiveHandlers();

		// return the handler identifier		
		return $handler_id;
	}	

	/**
    * Get active handler
    *
	* @param	integer		$informant		informant
	* @param	integer		$storage_model	storage model
    * @return 	array		handlers
    */
	public static function getActiveHandlers(
		$informant = NULL,
		$storage_model = STORE_SESSION
	)
	{
		// declare a default array of inactive handlers
		$inactive_handlers = array();

		// get a persistent store
		$store_handler_status = self::getHandlerStatus();

		// loop on handlers
		while (list($handler, $handler_status) = each($store_handler_status))
		{
			// check if the current handler is inactive
			if ($handler_status == HANDLER_STATUS_ACTIVE)

				$inactive_handlers[] = $handler;
		}

		// return the inactive handlers
		return $inactive_handlers;
	}

	/**
    * Get handlers
    *
	* @param	integer		$informant		informant
	* @param	integer		$storage_model	storage model
    * @return 	array		handlers
    */
	public static function getHandlers($informant = null, $storage_model = STORE_SESSION)
	{
		// declare an empty array of active handlers
		$handlers = array();

		// get a persistent store
		$store_field_handlers = &self::get_persistent_store(STORE_FIELD_HANDLERS, $storage_model);

		// loop on handlers
		while (list($handler) = each($store_field_handlers))

			// check if the current handler is inactive
			if ($handler > 0)

				$handlers[] = $handler;

		// reset the field handlers store 
		reset($store_field_handlers);

		// return the active handlers
		return $handlers;	
	}

	/**
    * Get a persistent store from the active field handler
    *
    * @return 	mixed	persistent store of active field handler
	*/
	public static function getPersistentActiveStore()
	{
		global $verbose_mode;

		// set the dumper class name
		$class_dumper = self::getDumperClass();

		// set the field handler class name
		$class_field_handler = self::getFieldHandlerClass();

		// get the current handler identifier
		$handler_id = $class_field_handler::get_active_handler();

		// get the persistent handler store
		$persistent_field_handler =
			self::getPersistentFieldHandler( $handler_id )
		;

		return $persistent_field_handler->getStore(
			$persistent_field_handler->getPosition(
				COORDINATES_CURRENT_POSITION,
				$handler_id
			),
			$handler_id
		);
	}

	/**
    * Get a property extractor
    *
	* @param	mixed		$control_panel	container
	* @param	integer		$storage_model	storage model 		
    * @return 	function	reference to extractor closure
    */
	public static function getPropertyExtractor(
		$control_panel,
		$storage_model = STORE_SESSION
	)
	{
		global $verbose_mode;

		// set the Application class name
		$class_application = self::getApplicationClass();

		// set the Dumper class name
		$class_dumper = self::getDumperClass();

		// set the Memento class name
		$class_memento = self::getMementoClass();

		// set the Form_Manager class name
		$class_form_manager = __CLASS__;
		
		// get a persistent control panel
		$persistent_control_panel = &self::get_persistent_control_panel(
			$storage_model
		);

		$preselected_index = 1;

		$collection_ephemeral[$preselected_index] =
		
		$collection_persistent[$preselected_index] =
		
		$collection_ephemeral =
		
		$collection_persistent = array();

		$backup_control_panel =

		$decorated_control_panel = NULL;

		/**
		* Extract a property
		*
		* @tparam	mixed		$container		container
		* @tparam	mixed		$index			index
		* @tparam	string		$name			property name
		* @tparam	mixed		&$destination	destination
		* @return	nothing
		*/
		$extract_property = function(
			$container,
			$index,
			$name,
			&$destination
		) use ( $class_form_manager )
		{
			if (
				FALSE !==
				(
					$key_exists = $class_form_manager::keys_exists(
						$container,
						array(
							STORE_FIELD_HANDLERS,
							$index,
							array(
								PROPERTY_METHOD =>
									array(
										'getProperty',
										array( $name )
									)
							)
						)
					)
				)
			)

				list( $destination[$name] ) = $key_exists;
		};

		$extract_property(
			$persistent_control_panel,
			$preselected_index,
			PROPERTY_SIGNATURE,
			$collection_persistent[$preselected_index]
		);

		$extract_property(
			$control_panel,
			$preselected_index,
			PROPERTY_SIGNATURE,
			$collection_ephemeral[$preselected_index]
		);

		$condition_mirroring = $collection_persistent === $collection_ephemeral;

		$extract_property(
			$persistent_control_panel,
			$preselected_index,
			PROPERTY_HANDLER_STATUS,
			$collection_persistent[$preselected_index]
		);

		$class_dumper::log(
			__METHOD__,
			array(
				'ephemeral data collection: ',
				$collection_ephemeral,
				'persistent data collection: ',
				$collection_persistent,
				'mirroring condition: ',
				$condition_mirroring
			)
		);		

		if ( $condition_mirroring )
		{
			$control_panel_decorated = self::decoratePersistentControlPanel(
				$control_panel,
				$storage_model
			);

			if (
				isset(
					$collection_persistent[$preselected_index]
						[PROPERTY_SIGNATURE]
				)
			)
				$class_memento::write(
					array(
						PROPERTY_KEY => $collection_persistent[$preselected_index]
							[PROPERTY_SIGNATURE],
						ENTITY_PANEL => $control_panel_decorated
					)
				);

			$persistent_control_panel = &$control_panel_decorated;
		}

		$callback_parameters = array(
			'collection_ephemeral' => $collection_ephemeral,
			'collection_persistent' => $collection_persistent,
			'condition_mirroring' => $condition_mirroring,
			'extract_property' => $extract_property,
			'persistent_control_panel' => &$persistent_control_panel,
			'preselected_index' => $preselected_index
		);

		return $callback_parameters;
	}

	/**
    * Get the selected handler
    *
	* @param	integer		$informant		informant
	* @param	integer		$storage_model	storage model
    * @return 	array 		handlers
    */
	public static function getSelectedHandler($informant = null, $storage_model = STORE_SESSION)
	{
		// declare the default selected handler
		$selected_handler = null;

		// get a persistent store
		$store_handler_status = self::getHandlerStatus();

		// loop on handlers
		while (list($handler, $handler_status) = each($store_handler_status))

			// check if the current handler has been selected
			if ($handler_status == HANDLER_STATUS_SELECTED)

				$selected_handler = $handler;

		reset($store_handler_status);

		// return the selected handler
		return $selected_handler;
	}

	/**
    * Get sleeping handlers
    *
	* @param	integer		$informant		informant
	* @param	integer		$storage_model	storage model
    * @return 	array		handlers
    */
	public static function getSleepingHandlers($informant = null, $storage_model = STORE_SESSION)
	{
		// declare a default array of inactive handlers
		$sleeping_handlers = array();

		// get a persistent store
		$store_handler_status = self::getHandlerStatus();

		// loop on handlers
		while (list($handler, $handler_status) = each($store_handler_status))
		{
			// check if the current handler is inactive
			if ($handler_status == HANDLER_STATUS_SLEEPING)

				$sleeping_handlers[] = $handler;
		}

		// return the inactive handlers
		return $sleeping_handlers;
	}

	/**
	* Check if a field handler is already registered
	*
	* @param	array		$collection_ephemeral	ephemeral data collection
	* @param	array		$collection_persistent	persistent data collection
	* @param	integer		$preselected_index		preselected index
	* @param	mixed		$informant				informant
	* @param	boolean		$assert					assertion flag
	* @return 	array		handler registration indicator
	* 						active handlers count
	*/
	public static function handlerExistsAlready(
		&$collection_ephemeral,
		&$collection_persistent,
		$preselected_index,
		$informant = NULL,
		$assert = FALSE
	)
	{
		$active_handlers = self::getHandlers();

		// set a default existence assumption
		$handler_already_exists = FALSE;

		$store_field_handlers = self::getPersistentStore( STORE_FIELD_HANDLERS );

		/**
		* 
		* Checks if the control panel argument contains
		* a field handler registered before
		*
		*/
		while (
			list( $handler_identifier, $field_handler )
				= each( $store_field_handlers )
		)
		{
			/**
			*
			* Prepare the collection of persistent data
			* to receive additional information
			*
			*/
			if (
				! isset( $collection_persistent[$handler_identifier] ) ||
				! is_array( $collection_persistent[$handler_identifier] )
			)

				$collection_persistent[$handler_identifier] = array();

			/**
			*
			* Check if a signature property exists for the current
			* field handler registered at the persistent layer level
			* 
			*/
			list(
				$collection_persistent
					[$handler_identifier]
						[PROPERTY_SIGNATURE]
			) = self::key_exists(
				$field_handler,
				array(
					PROPERTY_METHOD =>
						array(
							'getProperty',
							array( PROPERTY_SIGNATURE )
						)
				)
			);

			if (
				isset(
					$collection_ephemeral
						[$preselected_index]
							[PROPERTY_SIGNATURE]
				) &&
				$collection_ephemeral
					[$preselected_index]
						[PROPERTY_SIGNATURE] ===
					$collection_persistent
						[$handler_identifier]
							[PROPERTY_SIGNATURE]
			)

				$handler_already_exists = TRUE;
		}

		$callback_parameters = array(
			'active_handlers' => $active_handlers,
			'handler_already_exists' => $handler_already_exists
		);
		
		return $callback_parameters;
	}

    /**
    * Check if a field handler presents multiple steps
    *
    * @param	integer	$page		representing a page
    * @param	integer	$handler_id	representing a field handler	
    * @return 	boolean	indicating if the field presents multiple steps
	*/
	public static function is_multi_step(
		$page = PAGE_UNDEFINED,
		$handler_id = FORM_ORDINARY
	)
	{
		// check the page identifier
		if (
			$page >= PAGE_SIGN_UP_STEP_0 &&
			$page <= PAGE_SIGN_UP_STEP_7
		)
			return TRUE;
		else
			return FALSE;
	}

	/**
	* Check if a persistent field handler is registered
	* for the active field handler
	* by comparing provided control panel with
	* persistent control panel
	* 
	* @param	array	$collections	collections
	* @return	array	collections
	*/				
	public static function matchingFieldHanders( &$collections )
	{
		$class_dumper = self::getDumperClass();

		// set the application class name
		$class_form_manager = __CLASS__;

		$callback_parameters = array();

		/**
		* Prepare a store by initializing it as a store
		*
		* @tparam	array	&$container 	data collection
		* @tparam	array	&$referal		referal container	
		* @tparam	integer	$handler		handler id
		* @tparam	string	$store_type		store type
		*/
		$prepare_store = function (
			&$container,
			$referal,
			$handler,
			$store_type
		) use ( $class_form_manager )
		{
			if ( ! isset($container[$handler] ) )

				$container[$handler] = array();

			else if ( ! isset( $container[$handler][$store_type] ) )

				$container[$handler][$store_type] = array();

			/**
			*
			* Check if a field handler is registered for the provided handler
			* 
			*/
			list(
				$container
					[$handler]
						[$store_type]
			) = $class_form_manager::keys_exists(
				$referal,
				array(
					$store_type,
					$handler
				)
			);
		};

		if ( is_array( $collections ) && count( $collections ) )

			while ( list( $index, $properties ) = each( $collections ) )
			{
				if (
					isset( $properties[PROPERTY_CONTAINER] ) && 
					isset( $properties[PROPERTY_REFERRAL] ) &&
					isset( $properties[PROPERTY_HANDLER] )
				)

					$prepare_store(
						$collections[$index][PROPERTY_CONTAINER],
						$properties[PROPERTY_REFERRAL],
						$properties[PROPERTY_HANDLER] ,
						STORE_FIELD_HANDLERS
					);

				$callback_parameters[$properties[PROPERTY_NAME]] =
					$collections[$index][PROPERTY_CONTAINER];
			}

		return $callback_parameters;
	}

	/**
    * Select a handler
    *
	* @param	integer		$identifier		identifier
	* @param	boolean		$force			force flag
	* @param	string		$status			status
    * @return 	nothing
    */
	public static function selectHandler(
		$identifier,
		$force = FALSE,
		$status = NULL
	)
	{
		// get a persistent store
		$store_handler_status = &self::getHandlerStatus();

		// loop on the handler status store 
		while (list($handler) = each($store_handler_status))
		{
			if (
				!isset($store_handler_status[$handler]) ||
				isset($store_handler_status[$handler]) &&
				$store_handler_status[$handler] != HANDLER_STATUS_PRIOR
			)

				// set the current handler status
				$store_handler_status[$handler] = HANDLER_STATUS_SLEEPING;
		}

		// check the handler status store 
		if (
			!isset($store_handler_status[$identifier]) &&
			(
				isset($store_handler_status[$identifier]) &&
				$store_handler_status[$identifier] != HANDLER_STATUS_SLEEPING
			) ||
			$force === true
		)
		{
			if (empty($status))

				// select a handler
				$store_handler_status[$identifier] = HANDLER_STATUS_SELECTED;

			else 
				// select a handler
				$store_handler_status[$identifier] = $status;
		}
	}

    /**
    * Serialize a context
    *
	* @param	array		$context	context parameters
	* @param	integer		$page		page
    * @param	integer		$informant	informant
	* @return	nothing
	*/
	public static function serialize( &$context, &$page, $informant = NULL )
	{
		// get the active handler identifer and the coordinates
		list(
			$handler_id,
			$coordinates
		) = $context;

		// get the current persistent field handler 
		$persistent_field_handler = self::getPersistentFieldHandler( $handler_id );

		// serialize the persistent field handler
		$persistent_field_handler->serialize();
	}

    /**
    * Set a persistent control panel
    *
    * @param	array		$control_panel	control panel
	* @param	integer		$storage_model	storage model
    * @param	integer		$informant		informant
    * @param	boolean		$assert			assertion flag
    * @return 	nothing
	*/
	public static function set_persistent_control_panel(
		$control_panel,
		$storage_model = STORE_SESSION,
		$informant = NULL,
		$assert = FALSE
	)
	{
		global $verbose_mode;

		// Set the application class name
		$class_application = self::getApplicationClass();

		// Set the application class name
		$class_form_manager = __CLASS__;

		// Set the dumper class name
		$class_dumper = self::getDumperClass();

		// set the Memento class name
		$class_memento = self::getMementoClass();
		
		// Let us assume there is no registered handler
		// similar to the required one by default
		$handler_already_exists = FALSE;

		/**
		* Inject in the local scope the following variables
		*
		* @return 	boolean		$condition_mirroring		mirroring condition
		* @return 	array		$collection_ephemeral		ephemeral data collection
		* @return 	array		$collection_persistent		persistent data collection
		* @return 	closure		$extract_property			property extractor
		* @return 	array		$persistent_control_panel	persistent control panel
		* @return 	integer		$preselected_index			preselected index
		* 
		*/
		extract(
			self::getPropertyExtractor(
				$control_panel,
				$storage_model
			),
			EXTR_REFS
		); 

		if ( ! $condition_mirroring || 1 ) 
		{
			if (
				FALSE !== (
					$key_exists = self::key_exists(
						$persistent_control_panel,
						PROPERTY_HANDLER_STATUS
					)
				) &&
				count( $key_exists[0] ) 
			)
			{
				list( $statuses ) = $key_exists;

				/**
				*
				* add the field handler statuses
				* to the collection of persistent data
				*
				*/

				while ( list( $_handler_id, $status ) = each( $statuses ) )
				{
					/**
					*
					* Prepare the collection of persistent data
					* to receive additional information
					*
					*/
					if (
						! isset( $collection_persistent[$_handler_id] ) ||
						! is_array( $collection_persistent[$_handler_id] )
					)
		
						$collection_persistent[$_handler_id] = array();

					$collection_persistent[$_handler_id]
						[PROPERTY_HANDLER_STATUS] = $status
					;
				}

				/**
				*
				* Inject the following variable in the symbol table
				*
				* @return	array	$active_handlers		active handlers
				* @return	boolean	$handler_already_exists registration indicator
				* 
				*/
 
				extract (
					self::handlerExistsAlready(
						$collection_ephemeral,
						$collection_persistent,
						$informant,
						$assert
					)
				);

				$active_handler = self::syncPersistentStore(
					$persistent_control_panel,
					$control_panel,
					$active_handlers
				);
			}

			if (
				! $handler_already_exists &&
				isset( $active_handler )
			)
			{
				$collections = array(
					array(
						PROPERTY_CONTAINER => &$collection_persistent,
						PROPERTY_HANDLER => $active_handler,
						PROPERTY_NAME => 'collection_persistent',
						PROPERTY_REFERRAL => &$persistent_control_panel
					),
					array(
						PROPERTY_CONTAINER => &$collection_ephemeral,
						PROPERTY_HANDLER => $preselected_index,
						PROPERTY_NAME => 'collection_ephemeral',
						PROPERTY_REFERRAL => &$control_panel
					)							
				);				

				/**
				*
				* Check if a persistent field handler is registered
				* for the active field handler
				* by comparing provided control panel with
				* persistent control panel
				*
				* @return	collection_ephemeral
				* @return	collection_persistent
				* 
				*/
				extract( self::matchingFieldHanders( $collections ), EXTR_REFS );

				if (
					is_object(
						$collection_ephemeral[$preselected_index]
							[STORE_FIELD_HANDLERS]						
					) &&
					is_object(
						$collection_persistent[$active_handler]
							[STORE_FIELD_HANDLERS]
					) 
				)
				{
					// sleep the active handler
					$persistent_control_panel[PROPERTY_HANDLER_STATUS]
						[$active_handler] = HANDLER_STATUS_SLEEPING
					;
		
					// set the handler to be set as active
					$persistent_control_panel[PROPERTY_HANDLER_STATUS]
						[++$active_handler] = HANDLER_STATUS_ACTIVE
					;
	
					// check the control panel
					if ( is_array( $control_panel ) )
					{
						// loop on the control panel			
						while (
							list( $store_type, $store ) =
								each( $control_panel )
						)
						{
							list( $identifier, $matter ) =  each( $store );
	
							// check the handler identifier
							if (
								$store_type == STORE_FIELD_HANDLERS &&
								$matter->getHandlerId() != $active_handler
							)
	
								// update the handler identifier
								$matter->setHandlerId( $active_handler );
								
							// append the current matter
							// to the persistent control panel
							$persistent_control_panel[$store_type]
								[$active_handler] = $matter
							;
						}
	
						// reset the control panel
						reset( $control_panel );
									
						// set the form identifier
						$form_identifier =
							$persistent_control_panel
								[STORE_FIELD_HANDLERS]
									[$active_handler]
										->getProperty( PROPERTY_FORM_IDENTIFIER )
						;
	
						// check the persistent control panel
						if (
							! isset(
								$persistent_control_panel[STORE_AFFORDANCES]
									[$form_identifier]
							)
						)
	
							$persistent_control_panel[STORE_AFFORDANCES]
								[$form_identifier] = $active_handler
							;
	
						// check the persistent control panel
						if (
							isset(
								$persistent_control_panel[STORE_CONTEXT]
									[PROPERTY_FORM_IDENTIFIER]
							) &&
							$persistent_control_panel[STORE_CONTEXT]
								[PROPERTY_FORM_IDENTIFIER] != $form_identifier
						)
	
							$persistent_control_panel[STORE_CONTEXT]
								[PROPERTY_FORM_IDENTIFIER] = $form_identifier;
	
						// check the persistent control panel
						if (
							isset(
								$persistent_control_panel[STORE_SIGNATURES]
							) &&
							! isset(
								$persistent_control_panel[STORE_SIGNATURES]
									[$active_handler]
							) &&
							! in_array(
								$persistent_control_panel
									[STORE_FIELD_HANDLERS]
										[$active_handler]
											->getProperty( PROPERTY_SIGNATURE ),
								$persistent_control_panel[STORE_SIGNATURES]
							)
						)
	
							// append the active handler to the signatures store
							$persistent_control_panel
								[STORE_SIGNATURES]
									[$active_handler] =
								$persistent_control_panel
									[STORE_FIELD_HANDLERS]
										[$active_handler]
											->getProperty( PROPERTY_SIGNATURE )
							;
					}
				}
				else if (
					isset( $control_panel[STORE_FIELD_HANDLERS] ) &&
					is_array( $control_panel[STORE_FIELD_HANDLERS] ) &&
					count( $control_panel[STORE_FIELD_HANDLERS] ) 
				)
				{
					// get the sleeping handlers
					$sleeping_handlers = self::getSleepingHandlers();
				
					if ( count( $sleeping_handlers ) != 0 )
	
						$sleeping_handler = max( $sleeping_handlers );
					else
	
						$class_application::jumpTo( PREFIX_ROOT );
	
					// check the sleeping handlers
					if (
						isset(
							$control_panel[STORE_FIELD_HANDLERS]
								[$sleeping_handler]
						) &&
						! isset(
							$persistent_control_panel[$sleeping_handler]
						) &&
						isset( $persistent_control_panel[STORE_SIGNATURES] ) &&
						is_array(
							$persistent_control_panel[STORE_SIGNATURES]
						) &&
						! in_array(
							$control_panel
								[STORE_FIELD_HANDLERS]
									[$sleeping_handler]
										->getProperty( PROPERTY_SIGNATURE ),
							$persistent_control_panel[STORE_SIGNATURES]
						)
					)
					{
						$persistent_control_panel
							[STORE_FIELD_HANDLERS]
								[$sleeping_handler] =
							$control_panel[STORE_FIELD_HANDLERS]
								[$sleeping_handler]
						;
	
						// get the form identifier of the current field handler 
						$form_identifier =
							$control_panel
								[STORE_FIELD_HANDLERS]
									[$sleeping_handler]
										->getProperty( PROPERTY_FORM_IDENTIFIER )
						;
							
						// get the signature of the current field handler 
						$signature =
							$control_panel
								[STORE_FIELD_HANDLERS]
									[$sleeping_handler]
										->getProperty( PROPERTY_SIGNATURE )
						;
	
						// check the current persistent
						// sleeping handler affordance
						if (
							! isset(
								$persistent_control_panel
									[STORE_AFFORDANCES]
										[$form_identifier]
										
							)
						)
	
							$persistent_control_panel
								[STORE_AFFORDANCES]
									[$form_identifier] = $sleeping_handler
							;
	
						// check the current persistent
						// sleeping handler signature
						if (
							! isset(
								$persistent_control_panel[STORE_SIGNATURES]
									[$sleeping_handler]
							)
						)

							$persistent_control_panel[STORE_SIGNATURES]
								[$sleeping_handler] = $signature
							;
	
						$persistent_control_panel[STORE_CONTEXT]
							[PROPERTY_FORM_IDENTIFIER] = $form_identifier
						;
					}
					else 
					{
						// set the control panel field handlers cursor
						// to the last item
						end( $control_panel[STORE_FIELD_HANDLERS] );
	
						// get the current handler
						list( $_handler, $_field_handler ) =
							each( $control_panel[STORE_FIELD_HANDLERS] );
	
						// reset the control panel field handlers
						reset( $control_panel[STORE_FIELD_HANDLERS] );
	
						// get the form identifier of the current field handler 
						$form_identifier =
							$control_panel
								[STORE_FIELD_HANDLERS]
									[$_handler]
										->getProperty( PROPERTY_FORM_IDENTIFIER )
						;
	
						// set the current form identifier
						$persistent_control_panel[STORE_CONTEXT]
							[PROPERTY_FORM_IDENTIFIER] = $form_identifier
						;
	
						// check the field handlers store
						if (
							! isset(
								$persistent_control_panel[STORE_FIELD_HANDLERS]
									[$_handler]
							)
						)
	
							// set the field handler
							$persistent_control_panel[STORE_FIELD_HANDLERS]
								[$_handler] = $_field_handler
							;
	
						// check the affordances store
						if (
							! isset(
								$persistent_control_panel[STORE_AFFORDANCES]
									[$form_identifier]
							) ||
							$persistent_control_panel[STORE_AFFORDANCES]
								[$form_identifier]
							!= $_handler
						)
	
							// append the current affordance
							// to the affordances store
							$persistent_control_panel[STORE_AFFORDANCES]
								[$form_identifier] = $_handler
							;
					}
				}
			}
			else if (
				$handler_already_exists &&
				isset( $active_handler ) &&
				isset( $control_panel[STORE_FIELD_HANDLERS] ) &&
				is_array( $control_panel[STORE_FIELD_HANDLERS] ) &&
				count( $control_panel[STORE_FIELD_HANDLERS] )
			)
			{
				// set the control panel field handlers cursor to the last item
				end( $control_panel[STORE_FIELD_HANDLERS] );

				// get the current handler
				list( $_handler, $_field_handler ) =
					each( $control_panel[STORE_FIELD_HANDLERS] );

				// reset the control panel field handlers
				reset( $control_panel[STORE_FIELD_HANDLERS] );

				// get the form identifier of the current field handler 
				$form_identifier =
					$control_panel
						[STORE_FIELD_HANDLERS]
							[$_handler]->getProperty( PROPERTY_FORM_IDENTIFIER );

				// set the current form identifier
				$persistent_control_panel[STORE_CONTEXT]
					[PROPERTY_FORM_IDENTIFIER] = $form_identifier
				;

				// check the field handlers store
				if (
					! isset(
						$persistent_control_panel
							[STORE_FIELD_HANDLERS]
								[$_handler]
					)
				)

					// set the field handler
					$persistent_control_panel
						[STORE_FIELD_HANDLERS]
							[$_handler] =
								$_field_handler
					;

				// check the affordances store
				if (
					! isset(
						$persistent_control_panel
							[STORE_AFFORDANCES]
								[$form_identifier]
					) ||
					$persistent_control_panel
						[STORE_AFFORDANCES]
							[$form_identifier] !=
								$_handler
				)

					// append the current affordance to the affordances store
					$persistent_control_panel
						[STORE_AFFORDANCES]
							[$form_identifier] =
						$_handler
					;			
			}
		}

		$class_dumper::log(
			__METHOD__,
			array(
				'persistent control panel decorated after restoration: ',
				$persistent_control_panel
			)
		);

		$class_memento::write(
			array(
				PROPERTY_KEY => '_',
				ENTITY_PANEL => $persistent_control_panel
			)
		);
	}

    /**
    * Set a persistent store
    *
    * @param	array		$store			containing a store
	* @param	string		$store_type		containing a store type
	* @param	integer		$storage_model	representing a storage model
    * @return 	nothing
	*/
	public static function set_persistent_store($store, $store_type, $storage_model = STORE_SESSION)
	{
		// get a persistent store type
		$persistent_store = &self::get_persistent_store($store_type, $storage_model);

		// check the store
		if (
			is_array($store) &&
			count($store) != 0
		)

			// set a persistent store
			$persistent_store = $store;
	}

	/**
    * Set a persistent affordance
    *
	* @param	array	$persistent_affordance	persistent affordance
    * @return 	nothing
	*/
	public static function setPersistentAffordance($persistent_affordance)
	{
		// get the persistent affordance store
		$persistent_affordance_store = &self::getPersistentAffordanceStore();

		list($handler_id, $affordance)  = each($persistent_affordance);

		// check if the provided affordance is not already in the store
		if (
			!isset($persistent_affordance_store[$handler_id]) ||
			$persistent_affordance_store[$handler_id] != $affordance
		)
		
			// append an affordance to the store
			$persistent_affordance_store[$handler_id] = $affordance;
	}


	/**
    * Set a persistent submission status
    *
    * @param	integer	$handler_id		handler id
    * @param	string	$status			submission status
    * @return 	nothing
    */
	public static function setPersistentSubmissionStatus(
		$handler_id,
		$status = SESSION_STATUS_DATA_SUBMITTED
	)
	{
		// set the form manager class name
		$class_form_manager = CLASS_FORM_MANAGER;

		// get the persistent control dashboard of a field handler
		$control_dashboard = &$class_form_manager::getPersistentProperty(PROPERTY_STORE, $handler_id);

		// set the data submission flag
		$control_dashboard[SESSION_STATUS] = $status;
	}

	/**
    * Pop a store item
    *
	* @param	string	$store_type		store type
	* @param	string	$entity_type	entity type
    * @return 	mixed
	*/
	public static function storePop(
		$store_type = STORE_HANDLER_STATUS,
		$entity_type = HANDLER_STATUS_SLEEPING
	)
	{
		// set the default item to be popped
		$pop = NULL;

		// switch from the store type
		switch ( $store_type )
		{
			case STORE_HANDLER_STATUS: 

				$items = array();

				// get the persistent_control_panel
				$persistent_control_panel =
					&self::get_persistent_control_panel()
				;

				// loop on the store items
				while (
					list( $index, $status ) =
						each( $persistent_control_panel[$store_type] )
				)
				{
					if ( $status == $entity_type )
					
						$items[] = $index;
				}

				// reset the persistent control panel
				reset( $persistent_control_panel[$store_type] );

				// check the items count
				if ( count( $items ) )

					$toppest_item = max( $items );

				// pop the latest item
				// out of the current persistent control panel store 
				$pop = array_pop( $persistent_control_panel[$store_type] );

					break;
		}
		
		return $pop;
	}

	/**
    * Sync a persistent and ephemeral stores
    *
	* @param	array	$persistent_control_panel	persistent control panel
	* @param	array	$control_panel				control panel
	* @param	array	$active_handlers			active handlers
    * @return 	mixed	active handler
	*/
	public static function syncPersistentStore(
		&$persistent_control_panel,
		$control_panel,
		$active_handlers
	)
	{
		$active_handler = NULL;

		if ( count( $active_handlers ) )

			$active_handler = max( $active_handlers );
		else
		{
			/**
			*
			* Make backup of persistent stores:
			* 
			* Affordances
			* Context
			* Signatures
			*
			* Import the ephemeral control panel
			*
			* Restore the missing persistent stores from the backup
			*
			*/

			// check the affordances
			if ( isset( $persistent_control_panel[STORE_AFFORDANCES] ) )

				// set the affordance store
				$store_affordances =
					$persistent_control_panel[STORE_AFFORDANCES]
				;

			// check the context
			if ( isset( $persistent_control_panel[STORE_CONTEXT] ) )

				// set the signature store
				$store_context = $persistent_control_panel[STORE_CONTEXT];

			// check the signatures
			if ( isset( $persistent_control_panel[STORE_SIGNATURES] ) )

				// set the signature store
				$store_signatures =
					$persistent_control_panel[STORE_SIGNATURES]
				;

			// set a persistent store
			$persistent_control_panel = $control_panel;

			// check the affordance store
			if ( isset( $store_affordances ) )			
			
				// append the affordance store
				// to the persistent control panel
				$persistent_control_panel[STORE_AFFORDANCES] =
					$store_affordances
				;

			// check the context store
			if ( isset( $store_context ) )			
			
				// append the context store
				// to the persistent control panel
				$persistent_control_panel[STORE_CONTEXT] =
					$store_context
				;

			// check the signature store
			if ( isset( $store_signatures ) )			
			
				// append the signature store
				// to the persistent control panel
				$persistent_control_panel[STORE_SIGNATURES] =
					$store_signatures
				;
		}

		return $active_handler;
	}	
}