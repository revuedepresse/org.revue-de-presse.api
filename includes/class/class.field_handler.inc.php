<?php

/**
* Field handler class
*
* Class for handling fields
* @package  sefi
*/
class Field_Handler extends Form
{
	protected $_configuration = null;
	protected $_handler_id = null;
	protected $_roadmap = null;
	
    /**
    * Construct a new field handler
    *
    * @param	string		$action			action script
    * @param	array		$coordinates	coordinates
    * @param	array		$roadmap		roadmap
    * @param	integer		$handler_id		field handler identifier
    * @param	string		$method			HTTP protocol method
    * @param	boolean		$abstract_flag	abstract flag
    * @return 	object   	representing a field handler
    */	    
	public function __construct(
		$action = NULL,
		$coordinates = NULL,
		$roadmap = NULL,
		$handler_id = FORM_ORDINARY,		
		$method = PROTOCOL_HTTP_METHOD_POST,
		$abstract_flag = FALSE
	)
	{
		$administration = strpos( $action, PREFIX_ADMINISTRATION ) !== FALSE;

		$edition = strpos( $action, PREFIX_EDITION ) !== FALSE;

		if ( ! $abstract_flag )
		{
			if ( count( func_get_args() ) == 1 && $action != NULL )
			{
				$identifier =
					$administration ?
					substr( $action, strlen( PREFIX_ADMINISTRATION ) ) :
					$action
				;

				// set the form identifier
				$identifier =
					$edition ?
					substr( $identifier, strlen( PREFIX_EDITION ) ) :
					$identifier
				;

				// unset the action argument
				$action = NULL;
			}
			else

				$identifier = NULL;

			// set the field handler properties	
			$this->setProperties(
				array(
					$handler_id,
					$action,
					$coordinates,
					$roadmap,
					$method,
					$identifier,
					$administration
				)
			);

			// construct the parent object
			parent::__construct($method, $action);
		}
		else

			$data =  &$this->getSubmittedData($method);
	}

	/**
	* Clear field errors from the control dashboard
	*
	* @param	string		$field_name		field name
	* @param	array		&$field_values	references to fied values
	* @param	array 		&$errors		references to errors
    * @param	integer		$position		position in the data submission process
    * @param	integer		$handler_id		handler
	* @return 	array		containing fields
	*/
	protected function clear_field_errors(
		$field_name,
		&$field_values,
		&$errors,
		$position = null,
		$handler_id = FORM_ORDINARY
	)
	{
		global $class_application;

		// set the data fetcher class name
		$class_data_fetcher = $class_application::getDataFetcherClass();

		// set the dumper class name
		$class_dumper = $class_application::getDumperClass();

		// set the form manager class name
		$class_form_manager = $class_application::getFormManagerClass();

		// get handlers
		$handlers = $class_form_manager::getHandlers();

		// loop on handlers
		foreach ( $handlers as $handler )
		{
			if ( $handler != $handler_id )
			{
				// get the control dashboard
				$_control_dashboard = &self::getControlDashboard( $handler );

				// loop on the field name of the control dashboard
				while ( list( $_field_name ) = each( $_control_dashboard ) )
	
					unset( $_control_dashboard[$_field_name] );
			}
		}

		// get the link suffix length
		$suffix_start = strpos( $field_name, SUFFIX_LINK );
		$suffix_length = strlen( SUFFIX_LINK );

		// check if the current field is linked to another field
		if ( $suffix_start === FALSE )

			$trimmed_field_name = $field_name;
		else

			$trimmed_field_name = substr(
				$field_name,
				0,
				strlen( $field_name ) - $suffix_length
			);

		// get the control dashboard
		$control_dashboard = &self::get_control_dashboard(
			$position,
			$handler_id
		);

		$data_submitted = $this->getSubmittedData();

		// get the field attributes
		$field_attributes = &self::get_field_attributes(
			$field_name,
			$position,
			$handler_id
		);

		// get the field value
		$field_value = &self::get_field_values(
			$field_name,
			$position,
			NULL,
			$handler_id
		);

		// get the link value
		$link_field_value = &self::get_field_values(
			$trimmed_field_name,
			$position,
			NULL,
			$handler_id
		);

		// get the default value
		if ( isset( $field_attributes[HTML_ATTRIBUTE_VALUE] ) )

			$default_value = $field_attributes[HTML_ATTRIBUTE_VALUE];
		else

			$default_value = null;

		// check if the field is linked to some other field
		if (
			isset( $field_attributes[AFFORDANCE_LINK_TO] ) &&
			!preg_match(
				REGEXP_OPEN.
					REGEXP_START.
					SHORTHAND_DATABASE."\.".
				REGEXP_CLOSE,
				$field_attributes[AFFORDANCE_LINK_TO]
			)
		)

			$link = $field_attributes[AFFORDANCE_LINK_TO];
		else

			$link = NULL;

		// get the field type
		$field_type = $field_attributes[HTML_ATTRIBUTE_TYPE];

		// get the field filters
		if ( isset( $field_attributes[AFFORDANCE_APPLY_FILTERS] ) )
		{
			$filters = explode(
				CHARACTER_PIPE,
				$field_attributes[AFFORDANCE_APPLY_FILTERS]
			);

			if (
				count( $filters ) == 1 &&
				empty( $filters[0] )
			)

				unset( $filters );
		}

		// check the records
		if ( isset( $field_attributes[AFFORDANCE_CHECK_RECORDS] ) )
		{
			// set the record reference
			$record_reference = $field_attributes[AFFORDANCE_CHECK_RECORDS];

			if (
				empty( $field_value ) &&
				! empty( $data_submitted[$field_name] )
			)

				$field_value = $data_submitted[$field_name];

			// check records
			$results = $class_data_fetcher::checkRecords(
				$field_value,
				$field_type,
				$record_reference
			);

			if ( ! $results )

				$errors[$field_name][ERROR_WRONG_VALUE] =
					FORM_DISCLAIMER_WRONG_FIELD_VALUE;

			else if (
				isset(
					$errors[$field_name][ERROR_WRONG_VALUE]
				)
			)

				unset(
					$errors[$field_name][ERROR_WRONG_VALUE]
				);
		}

		// check records 
		if ( isset( $field_attributes[AFFORDANCE_CHECK_PREDICATES] ) )
		{
			// get the field names
			$predicates = $field_attributes[AFFORDANCE_CHECK_PREDICATES];

			// check the field names
			$mismatching_values = $class_application::checkPredicates(
				$predicates,
				$handler_id
			);

			// check the matching values indicator
			if (
				is_array($mismatching_values) &&
				count($mismatching_values) != 0
			)
			{
				// set a Smarty parameter name
				$parameter = self::translate_entity(
					ERROR_MISMATCHING_VALUE,
					ENTITY_SMARTY_VARIABLE
				);

				// set the mismatching values count
				$mismatching_values_count = 0;

				// loop on the values that should match
				while (
					list( $name, $value ) =
						each( $mismatching_values )
				)
				{
					// check if the value is stricly a boolean
					if ( $value === TRUE )
					{
						// set a mismatching value error
						$errors[$name][$parameter] = TRUE;

						// increment the mismatching values counter
						$mismatching_values_count++;
					}

					// check if the value is a non-empty string
					else if ( is_string( $value ) && ! empty( $value ) )
					{
						// set a mismatching value error
						$errors[$name][$parameter] = $value;

						// increment the mismatching values counter
						$mismatching_values_count++;
					}

					// check errors for the current name
					else if ( isset( $errors[$name][$parameter] ) )

						// unset existing errors for the current matching value
						unset( $errors[$name][$parameter] );
				}

				reset( $mismatching_values );

				// case without mismatching values
				if ( ! $mismatching_values_count )
				{
					while ( list( $name ) = each( $mismatching_values ) )
					{
						// check if the errors store is to be unset
						if (
							empty( $errors[$name] ) ||
							is_array( $errors[$name] ) &&
							! count( $errors[$name] ) 
						)
						{
							// check the data submission status in the control dashboard
							if ( isset( $control_dashboard[SESSION_STATUS] ) )
				
								// unset the data submission status in the control dashboard
								unset( $control_dashboard[SESSION_STATUS] );
				
							// unset the errors for the current field in the control dashboard
							unset( $control_dashboard[$name] );
				
							// unset the errors for the current field
							unset( $errors[$name] );
						}
					}					
				}
			}
		}

		// clear errors for non empty fields which are not linked to any other field
		if (
			$field_name == $trimmed_field_name &&
			isset( $errors[$field_name][ERROR_FIELD_MISSING] ) &&
			isset( $field_value ) &&
			(
				! empty( $field_value ) ||
				isset( $data_submitted[$field_name] ) && 
				! empty( $data_submitted[$field_name] ) ||
				isset( $filters ) &&
				in_array( FILTER_NUMERIC, $filters )
			)
		)
		{
			// check if confirmation is correct for two fields which have been linked
			if (
				isset( $link ) &&
				! isset(
					$control_dashboard
						[$field_name.SUFFIX_LINK]
							[ERROR_WRONG_CONFIRMATION]
				) &&
				! empty( $data_submitted[$trimmed_field_name] )
			)

				unset( $errors[$field_name][ERROR_FIELD_MISSING] );

			else if ( ! isset( $link ) )

				unset( $errors[$field_name][ERROR_FIELD_MISSING] );
		}

		// check errors for fields with linked values
		else if (
			$field_name != $trimmed_field_name &&
			(
				!empty( $link_field_value ) ||
				!empty( $data_submitted[$trimmed_field_name] )
			) &&
			isset( $errors[$field_name][ERROR_FIELD_MISSING] ) &&
			! isset( $errors[$trimmed_field_name][ERROR_FIELD_MISSING] )
		)
		{
			if (
				$data_submitted[$trimmed_field_name] ==
					$data_submitted[$field_name]
			)

				unset( $errors[$field_name][ERROR_FIELD_MISSING] );

			if ( ! isset( $errors[$field_name][ERROR_WRONG_CONFIRMATION] ) )

				// restore the link target field value
				$field_values[$field_name] = $field_value = $link_field_value;
		}

		// check if a default value was set to acceptable
		if (
			$field_type == FIELD_TYPE_HIDDEN
			||
			isset( $errors[$field_name][ERROR_DEFAULT_VALUE] ) &&
			isset( $default_value ) &&			
			!empty( $field_value ) &&
			$field_value != $default_value
		)
			unset($errors[$field_name][ERROR_DEFAULT_VALUE]);

		// check if the errors store is to be unset
		if (
			empty( $errors[$field_name] ) ||
			is_array( $errors[$field_name] ) &&
			! count( $errors[$field_name] )
		)
		{
			// check the data submission status in the control dashboard
			if ( isset( $control_dashboard[SESSION_STATUS] ) )

				// unset the data submission status in the control dashboard
				unset( $control_dashboard[SESSION_STATUS] );

			// unset the errors for the current field in the control dashboard
			unset( $control_dashboard[$field_name] );

			// unset the errors for the current field
			unset( $errors[$field_name] );
		}
	}

	/**
	* Clear field values
	*
    * @param	integer		$position			position in the data submission process
    * @param	integer		$position_instance	position instance 		
    * @param	integer		$handler_id			handler
	* @return 	array	containing fields
	*/
	public function clear_field_values(
		$position = null,
		$position_instance = null,
		$handler_id = FORM_ORDINARY
	)
	{
		// set the dumper class
		$class_dumper = self::getDumperClass();

		// set the field handler class
		$class_field_handler = __CLASS__;

		$data = &$this->getSubmittedData();

		// get the store
		$store = &self::getStore( $position, $handler_id );

		// check if multiple instances of the same position
		/// are about to be submitted
		if ( isset( $position_instance ) )
		{
			if (
				isset(
					$store
						[SESSION_STORE_FIELD]
							[SESSION_STORE_VALUE]
								[(int)$position_instance])
			)

				unset(
					$store
						[SESSION_STORE_FIELD]
							[SESSION_STORE_VALUE][(int)$position_instance]
				);


			// unset field values but leave the previous position instances intact
			while (
				list( $field_name, $field_value ) =
					each( $store[SESSION_STORE_FIELD][SESSION_STORE_VALUE])
			)

				if ( ! is_array( $field_value ) )

					unset(
						$store
							[SESSION_STORE_FIELD]
								[SESSION_STORE_VALUE]
									[$field_name]
					);
		}
		else
		{
			if ( empty( $store[SESSION_STORE_FIELD][SESSION_STORE_VALUE] ) )
			
				$field_values = &$data;
			else

				$field_values = &$store[SESSION_STORE_FIELD][SESSION_STORE_VALUE];

			$store
				[SESSION_STORE_FIELD]
					[SESSION_STORE_HALF_LIVING] =
				&$field_values
			;

			unset( $store[SESSION_STORE_FIELD][SESSION_STORE_VALUE] );
 		}
	}

	/**
    * Emulate a form submission
    *
    * @return 	nothing
    */	    
	protected function emulate_submission()
	{		
		$this->submit = TRUE;
	}

    /**
    * Alias to get configuration
    *
    * @return 	array	containing configuration properties
	*/
	public function &get_config()
	{
		// return the configuration member
		$configuration = &self::get_configuration();

		// return a reference to the configuration
		return $configuration;
	}

    /**
    * Get the config member
    *
    * @return 	array	containing configuration properties
	*/
	public function &get_configuration()
	{
		// return the configuration member
		return $this->_configuration;
	}

	/**
    * Alias to the getCoordinates method
    *
    * @param	integer		$handler_id		handler identifier
    * @return 	&array	containing coordinates
    */	    
	public function &get_coordinates($handler_id = FORM_ORDINARY)
	{
		// get coordinates
		$coordinates = $this->getCoordinates($handler_id);

		// return coordinates
		return $coordinates;
	}

	/**
    * Get fields
    *
    * @see	getFields
    */	    
	public function &get_fields()
	{
		return $this->getFields();
	}

	/**
    * Alias to the getPosition method
    *
    * @param	integer		$position_type	position type
    * @param	integer		$handler_id		handler identifier
    * @return 	mixed		position
    */	    
	public function &get_position($position_type = COORDINATES_CURRENT_POSITION, $handler_id = FORM_ORDINARY)
	{
		// get a position
		$position = &$this->getPosition($position_type, $handler_id);
		
		// return a position
		return $position;
	}

    /**
    * Alias to the getProperty method
    *
	* @param	string	$name		containing a property name
    * @return	nothing
	*/
	public function &get_property($name)
	{
		// get a property
		$property = &self::getProperty($name);

		// return a property
		return $property;
	}

	/**
    * Get the compass member
    *
	* @param	integer	$informant		informant
    * @return	array	compass
    */
	public function &getCompass($informant = NULL)
	{
		// get the handler member
		$roadmap = &self::getRoadmap($informant);

		// check the compass
		if (
			!is_array($roadmap[PROPERTY_COMPASS]) ||
			!count($roadmap[PROPERTY_COMPASS]) == 0
		)

			// set the default compass
			$roadmap[PROPERTY_COMPASS] = array();
			
		// get a reference to the compass member
		$compass = &$roadmap[PROPERTY_COMPASS];

		// return the compass member
		return $roadmap[PROPERTY_COMPASS];
	}

    /**
    * Alias to get configuration
    *
    * @return 	array	containing configuration properties
	*/
	public function &getConfig()
	{
		// return the configuration member
		$configuration = &self::get_configuration();

		// return a reference to the configuration
		return $configuration;
	}

    /**
    * Alias to get configuration
    *
    * @return 	array	containing configuration properties
	*/
	public function &getConfiguration()
	{
		// return the configuration member
		$configuration = &self::get_configuration();

		// return a reference to the configuration
		return $configuration;
	}

	/**
    * Get coordinates
    *
    * @param	integer		$handler_id		handler identifier
    * @return 	&array	containing coordinates
    */	    
	public function &getCoordinates($handler_id = FORM_ORDINARY)
	{
		// get the current handler
		$roadmap = &$this->getRoadmap($handler_id);

		// check the coordinates
		if (!isset($roadmap->{PROPERTY_COORDINATES}))

			// set the default coordinates
			$roadmap->{PROPERTY_COORDINATES} = array(
				COORDINATES_CURRENT_POSITION => 0,
				COORDINATES_NEXT_POSITION => 1,
				COORDINATES_PREVIOUS_POSITION => null
			);			

		// set the coordinates
		$coordinates = &$roadmap->{PROPERTY_COORDINATES};
		
		// return the coordinates
		return $coordinates;
	}

	/**
    * Get fields
    * 
    * @return 	array	fields
    */	    
	public function &getFields()
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$class_form_manager = $class_application::getFormManagerClass();

		$attributes = $this->getComponentAttributes();
		
		$children = &self::getAProperty( PROPERTY_CHILDREN );

		$configuration = self::getConfig();

		if ( count( $children ) < count( $attributes ) )
		{
			$sort_fields = FALSE;

			$configuration_fields = $configuration[PROPERTY_FIELDS];

			$search = function ( &$value, $key, $parameters )
			{
				if ( $value[PROPERTY_NAME] == $parameters['needle'] )
				
					$value = $parameters['needle'];
			};

			while ( list( $field_name, $component ) = each( $attributes ) )
			{
				if ( ! isset( $children[$field_name] ) )
				{
					$_fields = array();

					array_walk(
						$configuration_fields,
						$search,
						array( 'needle' => $field_name )
					);

					$key = array_search( $field_name, $configuration_fields );

					$field_configuration = $configuration[PROPERTY_FIELDS][$key];

					$sort_fields = ! $sort_fields;
		
					$index = '#';

					$mandatory_value = (
						substr( $field_configuration['type'], -1, 1 )
							== SUFFIX_MANDATORY
					);

					$name = $component[HTML_ATTRIBUTE_NAME];

					$type = ucfirst($field_configuration['type']);

					// set the form identifier
					if (
						defined(
							LANGUAGE_PREFIX_FORM.
								strtoupper(
									PREFIX_LABEL.
										self::translate_entity(
											$this->getProperty(
												PROPERTY_FORM_IDENTIFIER
											),
											ENTITY_CSS_CLASS
										)."_".$name
								)
						)
					)
					
						$label = constant(
							LANGUAGE_PREFIX_FORM.
								strtoupper(
									PREFIX_LABEL.
										self::translate_entity(
											$this->getProperty(
												PROPERTY_FORM_IDENTIFIER
											),
											ENTITY_CSS_CLASS
										)."_".$name
								)
						);
					else
		
						$label = '';

					$component_class =
						ucfirst( ENTITY_FIELD ).'_'.
						ucfirst(
							strtolower(
								rtrim(
									$type,
									SUFFIX_MANDATORY
								)
							)
						)
					;
		
					$mandatory_value =
						( substr( $type, -1, 1 ) == SUFFIX_MANDATORY )
					;

					$children[$field_name] = $this->addComponent(
						array(
							PROPERTY_INDEX => $index,
							PROPERTY_LABEL => $label,
							PROPERTY_MANDATORY => $mandatory_value,
							PROPERTY_NAME => $name,
							PROPERTY_TYPE => $type
						)
					);
				}
				else
				
					$index = $children[$field_name]->getProperty( PROPERTY_INDEX );

				if ( $sort_fields )
				{
					$ordered_fields = $configuration[PROPERTY_FIELDS];

					$sort_by_name = function ( &$value, $key )
					{
						$value = $value[PROPERTY_NAME];
					};

					array_walk( $ordered_fields, $sort_by_name );

					$ordered_fields = array_merge(
						array( PROPERTY_AFFORDANCE ),
						$ordered_fields
					);

					$fixed_fields = array();
	
					while (
						list( $index, $_field_name ) = each( $ordered_fields )
					)
		
						$fixed_fields[$_field_name] = $children[$_field_name];

					$children = $fixed_fields;
			
					$persistent_field_handler =
						&$class_form_manager::getPersistentFieldHandler(
							$this->getHandlerId()
					);
		
					$persistent_field_handler->setAProperty(
						PROPERTY_CHILDREN,
						$fixed_fields
					);
				}
			}			
		}

		return $children;
	}

	/**
    * Get a reference to handler identifier member
    *
    * @return 	integer		handler identifier
    */
	public function &getHandler()
	{
		// set the handler id to its default value if not initialized already
		if (!isset($this->_handler_id))

			$this->_handler_id = FORM_ORDINARY;

		return $this->_handler_id;
	}

	/**
    * Get position
    *
    * @param	integer		$position_type	representing a type of position in the roadmap
    * @param	integer		$handler_id		representing a handler
    * @return 	mixed	representing a step
    */	    
	public function &getPosition($position_type = COORDINATES_CURRENT_POSITION, $handler_id = FORM_ORDINARY)
	{
		// get a position
		$coordinates = &$this->get_coordinates($handler_id);

		// return a position
		return $coordinates[$position_type];
	}

    /**
    * Get a property
    *
	* @param	string		$name	property name
    * @return	nothing
	*/
	public function &getProperty($name)
	{
		// get the configuration
		$configuration = &$this->get_config();

		// check the configuration property
		if (!isset($configuration[$name]))

			// declare a default configuration property
			$configuration[$name] = array();

		// get a configuration property
		$property = &$configuration[$name];

		// return the configuration property	
		return $property;
	}

	/**
    * Get roadmap
    *
	* @param	mixed		$informant		informant
    * @return 	&array		field handler data
    */
	public function &getRoadmap($informant = NULL)
	{
		// case when the handler is not initialized 
		if (!isset($this->_roadmap) || !is_object($this->_roadmap))

			// declare the default handler
			$this->_roadmap = new stdClass();

		return $this->_roadmap;
	}

	/**
    * Add a field to the field handler
    * 
    * @param	string		$name			name
    * @param	string		$type			type
    * @param	string		$label			label
    * @param	string		$options		options
    * @param	string		$default		default values
    * @param	boolean 	$accept_default	default value acceptance eflag 
    * @param	boolean		$required		mandatory flag
    * @param	string		$filters		filters
    * @param	integer		$position		position in the data submission process
	* @param	integer		$handler_id		field handler
    * @param	mixed		$informant		informant
    * @param	boolean		$assert			assertion flag
    * @return 	object		representing a field handler
    */	  
	public function add(
		$name,
		$type,
		$label = null,
		$options = null,
		$default = null,
		$accept_default = TRUE,
		$required = FALSE,
		$filters = FALSE,
		$position = null,
		$handler_id = FORM_ORDINARY,
		$informant = NULL,
		$assert = FALSE
	)
	{
		// get field name compatible with form class
		$ascendant_name = $name;

		// set the dumper class name
		$class_dumper = self::getDumperClass();

		// set the form manager class name
		$class_form_manager = self::getFormManagerClass();

		// get control dashboard
		$control_dashboard = &self::get_control_dashboard(
			$position,
			$handler_id,
			$informant
		);

		$data_submission = $this->getAProperty( PROPERTY_DATA_SUBMISSION );

		$data_submitted = &$this->getSubmittedData();

		// check the affordances store
		$store_affordances =
			$class_form_manager::getPersistentStore( STORE_AFFORDANCES )
		;

		$test_case = NULL;

		// get filters
		if ( $filters )

			$filter_set = explode( CHARACTER_PIPE, $filters );

		// insert filters into control dashboard
		if (
			isset( $filter_set ) &&
			is_array( $filter_set ) &&
			count( $filter_set ) &&
			! empty( $filter_set[0] )
		)
		{
			// loop on each item of the filter set
			while ( list( $filter_index, $filter ) = each( $filter_set ) )

				$control_dashboard[$name][AFFORDANCE_APPLY_FILTERS][$filter] =
					FALSE
				;

			reset( $filter_set );
		}

		$class_dumper::log(
			__METHOD__,
			array(
				'The current field can be added to '.
				'the pool of existing components '.
				'(current field not yet declared)? ',  
					! $this->hasComponent( $ascendant_name )
				?
					'TRUE'
				:
					'FALSE',
				'id of field handler to which a component might be added to: ',
				$this->getHandlerId()
			),
			$assert &&
			DEBUGGING_FIELD_HANDLING &&
			$data_submitted &&
			(
				$test_case =
					(
						$assert &&
						(
							strpos(
								$ascendant_name,
								TEST_CASE_FIELD_HANDLER_COMPONENT
							) !== FALSE
						)
					)
			)
		);

		// case without existing component carrying the current ascendant name
		if ( ! $this->hasComponent( $ascendant_name ) )
			
			// call to add method of form class
			$field_handler = parent::add(
				$ascendant_name,
				$type,
				$label,
				$options,
				$default,
				$informant,
				$test_case
			);
		else

			// check the current token
			$field_handler = $this->checkToken(
				$ascendant_name,
				$type,
				$label,
				$options,
				$default,
				$informant,
				$test_case				
			);

		// check if the submitted form is registered
		// otherwise force its selection
		if (
			! empty(
				$data_submitted[FIELD_NAME_AFFORDANCE]
			) &&
			! empty(
				$store_affordances
					[$data_submitted[FIELD_NAME_AFFORDANCE]]
			)
		)
		{
			// set the selected handler identifier
			$selected_id =
				$store_affordances
					[$data_submitted[FIELD_NAME_AFFORDANCE]]
			;

			// force the persistent selection of the selected handler
			$class_form_manager::selectHandler( $selected_id, TRUE );

			$class_dumper::log(
				__METHOD__,
				array(
					'selected id after trying to add a component: ',
					$selected_id,
					'current field handler: ',
					$this,
					'field handler: ',
					$field_handler
				),
				$assert &&
				DEBUGGING_FIELD_HANDLING &&
				$data_submitted &&
				(
					$test_case =
						(
							strpos(
								$ascendant_name,
								TEST_CASE_FIELD_HANDLER_COMPONENT
							) !== FALSE
						)
				)
			);
		}

		// collect missing field errors
		if (
			$data_submission &&
			$required &&
			$field_handler->getHandlerId() ==
				$class_form_manager::getSelectedHandler()
		)
		{
			// prevent required field from being passed with empty value
			if (
				! isset( $data_submitted[$ascendant_name] ) ||
				empty( $data_submitted[$ascendant_name] )
			)

				$control_dashboard[$name][ERROR_FIELD_MISSING] =
					FORM_REQUIRED_FIELD;

			// check if a required field value doesn't equal their default value
			// when the default value is not accepted
			// (not applied to hidden fields)
			else if (
				! $accept_default &&
				$data_submitted[$ascendant_name] == $default &&
				$type != FIELD_TYPE_HIDDEN &&
				isset( $data_submitted[$ascendant_name] )
			)

				$control_dashboard[$name][ERROR_DEFAULT_VALUE] = $default;
		}
	}

	/**
    * Alias to the buildForm method
    *
    * @see 	$this->build_form
	*/
	public function build_form(
		$fields,
		$options = NULL,
		$before_submission = TRUE,
		$field_handler = FALSE,
		$values = NULL,
		$coordinates = NULL,
		$action = NULL,
		$roadmap = NULL,
		$handler_id = FORM_ORDINARY,		
		$method = PROTOCOL_HTTP_METHOD_POST,
		$informant = NULL
	)
	{
		return $this->buildForm(
			$fields,
			$options,
			$before_submission,
			$field_handler,
			$values,
			$coordinates,
			$action,
			$roadmap,
			$handler_id,		
			$method,
			$informant
		);
	}

	/**
    * Build a form
    *
    * @param	array 		$fields 			fields
    * @param	array		$options			options
    * @param	boolean		$before_submission	data submission flag
    * @param	object		$field_handler		field handler
    * @param	array		$values				values
    * @param	string		$action				action script
    * @param	array		$coordinates		coordinates
    * @param	array		$roadmap			roadmap
    * @param	integer		$handler_id			handler identifier
    * @param	string		$method				HTTP protocol method
	* @param	mixed		$informant			informant
	* @param	mixed		$assert				assertion flag
    * @return 	mixed
	*/
	public function buildForm(
		$fields,
		$options = NULL,
		$before_submission = TRUE,
		$field_handler = FALSE,
		$values = NULL,
		$coordinates = NULL,
		$action = NULL,
		$roadmap = NULL,
		$handler_id = FORM_ORDINARY,		
		$method = PROTOCOL_HTTP_METHOD_POST,
		$informant = NULL,
		$assert = FALSE
	)
	{
		// set the dumper class name
		$class_dumper = self::getDumperClass();

		// set the form manager class name
		$class_form_manager = self::getFormManagerClass();

		// check the fields argument
		if (
			! is_array( $fields ) ||
			! count( $fields ) ||
			empty( $fields[0] )
		)
		{
			$fields = $class_form_manager::getPersistentProperty(
				PROPERTY_FIELDS,
				$this->getHandlerId(),
				ENTITY_FIELD_HANDLER
			);

			if (
				! is_array( $fields ) ||
				! count( $fields ) ||
				empty( $fields[0] )
			)
			{
				$fields = $this->getProperty( PROPERTY_FIELDS );

				$class_dumper::log(
					__METHOD__,
					array(
						'field handler: ',
						$this,
						'fields: ',
						$fields
					),
					TRUE
				);

				if (
					! is_array( $fields ) ||
					! count( $fields ) ||
					empty( $fields[0] )
				)

					// throw an exception 
					throw new Exception( EXCEPTION_UNDEDINED_FIELDS );
			}
		}

		// check the handler identifier
		if ( $handler_id == FORM_ORDINARY )

			// get the handler identifier
			$handler_id = $this->getHandlerId();

		// get field handler coordinates member
		if ( $coordinates == NULL )

			// get coordinates
			$coordinates = &$this->getCoordinates( $handler_id );

		if ( isset( $coordinates[COORDINATES_POSITION_INSTANCES] ) )
		{
			$position_instances = $coordinates[COORDINATES_POSITION_INSTANCES];

			end( $position_instances );
			list( $position_instance ) = each( $position_instances );
			reset( $position_instances );
		}
		else

			$position_instance = NULL;

		// unshift the array of fields with a special hidden input
		array_unshift(
			$fields,
			array(
				HTML_ATTRIBUTE_NAME => FIELD_NAME_AFFORDANCE,
				HTML_ATTRIBUTE_TYPE => FIELD_TYPE_HIDDEN.SUFFIX_MANDATORY,
			)
		);

		// get current position
		$current_position = $this->getPosition(
			COORDINATES_CURRENT_POSITION,
			$handler_id
		);

		// check if no data have been submitted and no field handler has been provided
		if ( $before_submission && ! $field_handler )
		{
			$field_attributes =
			$field_names = 
			$field_values =
			$field_options =
			$labels = 
			$links = 
			$helpers = array();
		}
		else
		{
			// get the control dashboard
			$control_dashboard = &self::get_control_dashboard(
				$current_position,
				$handler_id
			);

			$errors	=
			$field_values = array();

			// get the affordances store
			$store_affordances = &$class_form_manager::getPersistentStore( STORE_AFFORDANCES );

			$submitted_data = &$this->getSubmittedData();	

			// check the submitted data
			if (
				! empty( $submitted_data[FIELD_NAME_AFFORDANCE] ) &&
				! empty( $store_affordances[$submitted_data[FIELD_NAME_AFFORDANCE]] )
			)
			{
				// set the selected handler identifier
				$selected_id = $store_affordances[$submitted_data[FIELD_NAME_AFFORDANCE]];

				// force the persistent selection of the selected handler
				$class_form_manager::selectHandler(
					$selected_id,
					TRUE,
					HANDLER_STATUS_PRIOR
				);
			}

			// check the control dashboard
			$class_dumper::log(
				__METHOD__,
				array(
					'control dashboard: ',
					$control_dashboard
				)
			);
		}

		// get the latest field index
		end( $fields );
		list( $latest_field_index ) = each( $fields );
		reset( $fields );

		// loop on the fields
		while ( list( $field_index, $field_properties ) = each( $fields ) )
		{
			$ascendant_name =
			$default_value = 
			$field_name =
			$field_type	=
			$filters = 
			$target = null;

			// check the field attributes
			if ( ! isset( $field_attributes ) )
	
				$field_attributes = array();

			$this->prepareToken(
				$ascendant_name,
				$default_value, 
				$field_name,
				$field_type,
				$field_attributes,
				$field_properties
			);

			// check the data submission status
			if ( $before_submission )

				// check process
				$this->processToken(
					$handler_id,
					$current_position,
					$ascendant_name,
					$default_value,
					$field_index,
					$field_name,
					$field_properties,
					$field_type,
					$latest_field_index,
					$options,
					$field_attributes,
					$field_names,
					$field_options,
					$field_values,
					$helpers,
					$labels,
					$links,
					$informant
				);
			else

				// check submitted data 
				$this->checkSubmittedData(
					$handler_id,
					$coordinates,
					$current_position,
					$default_value,
					$field_name,
					$field_type,
					$values,
					$errors,
					$field_values					
				);	
		}

		reset( $fields );

		// check the data submission status
		if ( $before_submission )
		{
			$class_dumper::log(
				__METHOD__,
				array(
					'field handler state after adding components',
					$this
				),
				$assert &&
				DEBUGGING_FIELD_HANDLING,
				$before_submission && ! empty( $_POST )
			);

			// set field attributes
			self::set_field_attributes(
				$field_attributes,
				$current_position,
				$handler_id
			);

			return array(
				$labels,
				$this,
				$field_names,
				$field_options,			
				$field_values,
				$helpers
			);
		}
		else
		{
			// check the draws
			$this->checkDraws(
				$handler_id,
				$current_position,
				$fields,
				$errors				
			);

			$class_dumper::log(
				__METHOD__,
				array(
					'[case after data submission]',
					'field handler argument: ',
					$field_handler,
					'current field handler',
					$this,
					'field values: ',
					$field_values
				),
				DEBUGGING_FIELD_HANDLING
			);

			return array(
				$errors,
				$field_values,
				TRUE
			);
		}
	}

	/**
    * Check fields
    *
    * @param	array		$fields				field attributes
    * @param	array		$options			options
    * @param	integer		$position			position in the data submission process
    * @param	integer		$position_instance	position instance
    * @param	integer		$handler_id			field handler 
	* @param	mixed		$informant			informant
	* @param	mixed		$assert				assertion flag 
    * @return  	mixed		results of field checking
	*/	
	public function check_fields(
		$fields,
		$options,
		$position = NULL,
		$position_instance = NULL,
		$handler_id = FORM_ORDINARY,
		$informant = NULL,
		$assert = FALSE
	)
	{
		global $verbose_mode;

		// set the dumper class name
		$class_dumper = self::getDumperClass();

		// set the lock class name
		$class_lock = self::getLockClass();

		// set the form manager class name
		$class_form_manager = self::getFormManagerClass();

		$method = $this->getAProperty( PROPERTY_METHOD );

		// set the matching handler flag
		$matching_handler = FALSE;

	 	// get the affordances store
		$store_affordances = $class_form_manager::getPersistentStore(
			STORE_AFFORDANCES
		);

		$submitted_data = &$this->getSubmittedData();

		if ( ! empty( $_POST ) )
		{
			$assert = ! empty( $_POST ) && ! $submitted_data;

			if ( DEBUGGING_FIELD_ERROR_HANDLING )

				$class_lock::lockEntity( __CLASS__ );

			$class_dumper::log(
				__METHOD__,
				array(
					'assertion flag:',
					$assert,
					'submission status:',
					$this->get_submission_status()
				)
			);

			$assertions_batch = array(
				array( 
					'! $handler_inactive',			
					'$this->get_status()',
					'$matching_handler',
					'! empty( $control_dashboard[SESSION_STATUS] )',
					'$control_dashboard[SESSION_STATUS] == '.
						'SESSION_STATUS_DATA_SUBMITTED',
					'count( $values )'
				)
			);
		}

		if (
			! empty( $submitted_data[FIELD_NAME_AFFORDANCE] ) &&
			$submitted_data[FIELD_NAME_AFFORDANCE] ==
				$this->getProperty( PROPERTY_FORM_IDENTIFIER )
		)

			$matching_handler = TRUE;		

		// set the default handler status
		$handler_inactive = FALSE;

		// get the current handlers
		$handlers = &self::getHandlerStatus( $informant );

		if ( $matching_handler )

			// get the control dashboard
			$control_dashboard = &self::getControlDashboard(
				$handler_id,
				$position
			);
		else
		
			$control_dashboard = array();

		if (
			isset( $_REQUEST ) &&
			count( $_REQUEST ) &&
			! empty( $_REQUEST[FIELD_NAME_AFFORDANCE] ) &&
			$matching_handler
		)

			$this->setSubmissionStatus();
		else 

			$this->setSubmissionStatus( SESSION_STATUS_NO_SUBMISSION );

		// declare the anti-spam flag
		$spam = FALSE;

		// check the handler status
		if ( ! empty( $handlers[(int)$handler_id] )  )
		{
			if ( $handlers[(int)$handler_id] != HANDLER_STATUS_INACTIVE )

				// clear the field values
				$this->clear_field_values(
					$position,
					$position_instance,
					$handler_id
				);
			
			else if ( $handlers[(int)$handler_id] == HANDLER_STATUS_INACTIVE )

				// set the handler status to inactive
				$handler_inactive = TRUE;
		}

		// get the store
		$store = &self::getStore( $position, $handler_id );

		// get submitted data 
		$values = $this->get_data( $position, $handler_id, $informant );

		$class_dumper::log(
			__METHOD__,
			array(
				'field handler with no values at some level',
				$this,
				'non-empty values at this time?',
				is_array( $values ) &&
				! count( $values )
			),
			$assert
		);

		// check if the anti-spam text area has been filled in
		if (
			isset( $_REQUEST[REQUEST_ANTI_SPAM] ) &&
			$_REQUEST[REQUEST_ANTI_SPAM] != NULL
		) 

			// toggle the anti-spam flag
			$spam = TRUE;

		if ( $assert )

			$class_dumper::assert(
				array(
					PROPERTY_EXPRESSION =>
						"\t".implode(
							ASSERTION_CONJUNCTION_AND,
							$assertions_batch[0]
						),
					PROPERTY_EVALUATION =>
						assert(
							implode(
								ASSERTION_CONJUNCTION_AND,
								$assertions_batch[0]
							)
						)
				),
				array(
					'current handler set to active? ',
					! $handler_inactive,

					'status: ',
					$this->get_status(),

					'matching handler? ',
					$matching_handler ? 'TRUE' : 'FALSE',

					'submitted data',
						! empty( $control_dashboard[SESSION_STATUS] )
					?
						$control_dashboard[SESSION_STATUS] ==
							SESSION_STATUS_DATA_SUBMITTED
					:
						'FALSE'
					,

					'POST superglobal',
					$_POST,
					
					'values count',
					count( $values ),

					$values,
					'values'
				)
			);

		// check if the current handler is not set to inactive
		if (
			! $handler_inactive &&
			$this->get_status() &&
			$matching_handler &&
			! empty( $control_dashboard[SESSION_STATUS] ) &&
			( $control_dashboard[SESSION_STATUS] ==
				SESSION_STATUS_DATA_SUBMITTED ) &&
			count( $values )
		)
		{
			// build a form and detect field errors
			list(
				$errors,
				$field_values,
				$data_submitted
			) = $this->build_form(
				$fields,
				$options,
				FALSE,
				$this,
				$values
			);

			// check the data submission status
			if ( isset( $data_submitted ) )
			
				$class_dumper::log(
					__METHOD__,
					array(
						'submission: ',
						$data_submitted,
						'errors: ',
						$errors,
						'field values: ',
						$field_values,
						'handlers: ',
						$handlers,
						'status value ubiquitous handler: ',
						HANDLER_STATUS_UBIQUITOUS,
						'current handler id: ',
						$handler_id,
						'handler id of arbitrary form: ',
						FORM_ARBITRARY
					),
					DEBUGGING_FIELD_HANDLING
				);

			// check if no more error can be detected
			if ( ! count( $errors ) && $data_submitted )
			{
				// save the field values
				self::save_field_values(
					$field_values,
					$position,
					$position_instance,
					$handler_id
				);

				// check the handler status
				if (
					! empty( $handlers[(int)$handler_id] ) &&
					(
						$handlers[(int)$handler_id] == HANDLER_STATUS_UBIQUITOUS ||
						$handler_id != FORM_ARBITRARY
					)
				)

					// deactivate ubiquitous handler
					$handlers[(int)$handler_id] = HANDLER_STATUS_INACTIVE;
			}

			return array(
				$errors,
				$field_values,
				$data_submitted
			);
		}
		else
		{
			return array(
				array(),
				array(),
				FALSE
			);
		}
	}

	/**
	* Check the draws
	*
	* @param	integer		$handler_id 		handler identifier
	* @param	integer		$current_position	current position
	* @param	array		$fields				fields
	* @param	array		&$errors			reference to errors
	* @return	nothing
	*/
	public function checkDraws(
		$handler_id,
		$current_position,
		$fields,
		&$errors				
	)
	{
		// loop on the fields
		while ( list( $field_index, $field_properties ) = each( $fields ) )
		{
			// get field name
			$field_name = $field_properties[HTML_ATTRIBUTE_NAME];				

			// get the field attributes
			$field_attributes = self::get_field_attributes(
				$field_name,
				$current_position,
				$handler_id
			);

			// get the field type
			$field_type = $field_attributes[HTML_ATTRIBUTE_TYPE];

			// get the default value
			if ( isset( $field_attributes[HTML_ATTRIBUTE_VALUE] ) )

				$default_value = $field_attributes[HTML_ATTRIBUTE_VALUE];
			else

				$default_value = NULL;

			// check if the field value can be accepted as forming part of a draw
			if ( ! empty( $field_attributes[AFFORDANCE_DRAW_FIELDS]) )
			{
				if (
					is_array( $field_attributes[AFFORDANCE_DRAW_FIELDS] ) &&
					count( $field_attributes[AFFORDANCE_DRAW_FIELDS] ) == 1
				)
				{
					// check if some draws are connected
					$check_draw_link = FALSE;

					// initialize the count of items to be drawn
					$drawn_items = 0;
					$drawn_links = 0;
					$draw_count = 0;

					// initialized the links
					$links = null;
					$link_count = 0;

					// initialize the validity of a link
					$valid_link = TRUE;

					// get the count of items to be drawn and the items
					list($draw_count, $items) = each($field_attributes[AFFORDANCE_DRAW_FIELDS]);
					reset($field_attributes[AFFORDANCE_DRAW_FIELDS]);

					if (
						isset( $field_attributes[AFFORDANCE_LINK_DRAW] ) && 
						is_array( $field_attributes[AFFORDANCE_LINK_DRAW] ) &&
						count( $field_attributes[AFFORDANCE_LINK_DRAW] ) == 1
					)
					{
						list( $link_count, $links ) =
							each( $field_attributes[AFFORDANCE_LINK_DRAW] );

						reset( $field_attributes[AFFORDANCE_LINK_DRAW] );

						if ( $link_count == $draw_count )

							$check_draw_link = TRUE;
					}

					while ( list( $index, $item ) = each( $items ) )
					{
						// get the value of an item that belongs to the draw
						$value = self::get_field_values(
							$item,
							$current_position,
							NULL,
							$handler_id
						);

						if ($check_draw_link)
						{
							list( $link_index, $link ) = each( $links );
							
							$link_value = self::get_field_values(
								$link,
								$current_position,
								NULL,
								$handler_id
							);

							// get the link attributes
							$link_attributes = self::get_field_attributes(
								$link,
								$current_position,
								$handler_id
							);
			
							// get the link type
							$link_type = $link_attributes[HTML_ATTRIBUTE_TYPE];

							// get the link default value
							if (isset($link_attributes[HTML_ATTRIBUTE_VALUE]))
								$link_default_value = $link_attributes[HTML_ATTRIBUTE_VALUE];
							else
								$link_default_value = null;

							if (
								!is_array($link_value) &&
								!empty($link_value) &&
								(
									$link_type != ucfirst(HTML_ELEMENT_SELECT) ||
									$link_value != $link_default_value
								)
							)
								$drawn_links++;

							Dumper::log(
								__METHOD__,
								array(
									'link value:',
									$link_value,
									'link type:',
									$link_type,
									'link default value:',
									$link_default_value,
									'drawn links:',
									$drawn_links
								)
							);
						}

						Dumper::log(
							__METHOD__,
							array(
								'item index: ',
								$index,
								'item name: ',
								$item,
								'item value: ',
								$value,
								'valid link: ',
								$valid_link
							)
						);

						if (
							! is_array( $value ) &&
							! empty( $value ) &&
							(
								$field_type != ucfirst( HTML_ELEMENT_SELECT ) ||
								$value != $default_value
							)
						)

							$drawn_items++;
					}

					reset( $items );
					reset( $links );

					if (
						$drawn_items >= $draw_count &&
						$drawn_links >= $draw_count
					)
					{
						// unset required field errors
						while ( list( $index, $item ) = each( $items ) )
							if ( isset( $errors[$item] ) )
								unset( $errors[$item] );

						// unset required field errors
						while ( list( $link_index, $link ) = each( $links ) )
							if ( isset( $errors[$link] ) )
								unset( $errors[$link] );

						reset( $items );
						reset( $links );
					}
				}
			}
		}

		reset( $fields );
	}

	/**
    * Check fields
    *
    * @see 		$this->check_fields
	*/	
	public function checkFields(
		$fields,
		$options,
		$position = NULL,
		$position_instance = NULL,
		$handler_id = FORM_ORDINARY,
		$informant = NULL,
		$assert = FALSE		
	)
	{
		// call the check_fields methods
		return $this->check_fields(
			$fields,
			$options,
			$position,
			$position_instance,
			$handler_id,
			$informant,
			$assert = FALSE			
		);
	}

	/**
    * Check the submitted data
    *
	* @param	integer		$handler_id			handler identifier
	* @param	array		$coordinates		coordinates
	* @param	integer		$current_position	current position
	* @param	string		$default_value		default value
	* @param	string		$field_name			field name
	* @param	string		$field_type			field type
	* @param	array		$values				values
	* @param	array		&$errors			reference to the errors
	* @param	array		&$field_values		reference to the field values
	* @return	nothing
	*/	
	public function checkSubmittedData(
		$handler_id,
		$coordinates,
		$current_position,
		$default_value,
		$field_name,
		$field_type,
		$values,
		&$errors,
		&$field_values		
	)
	{
		// set the dumper class name
		$class_dumper = self::getDumperClass();

		// set the form manager class name
		$class_form_manager = self::getFormManagerClass();

		// get the control dashboard
		$control_dashboard = &self::get_control_dashboard(
			$current_position,
			$handler_id
		);

		// get field handler coordinates member
		if ( $coordinates == NULL )

			// get coordinates
			$coordinates = &$this->getCoordinates( $handler_id );

		if ( isset( $coordinates[COORDINATES_POSITION_INSTANCES] ) )
		{
			$position_instances = $coordinates[COORDINATES_POSITION_INSTANCES];

			end( $position_instances );
			list( $position_instance ) = each( $position_instances );
			reset( $position_instances );
		}
		else

			$position_instance = NULL;

		// set the default filtering report
		$successful_filtering = TRUE;

		// get field filters
		$filters = &self::get_field_filters(
			$field_name,
			$current_position,
			$handler_id
		);

		$suffix_start = strpos( $field_name, SUFFIX_LINK );
		$suffix_length = strlen (SUFFIX_LINK );

		// check if the current field is linked to another field
		if ( $suffix_start === FALSE )

			$trimmed_field_name = $field_name;
		else

			$trimmed_field_name = substr(
				$field_name,
				0,
				strlen( $field_name ) - $suffix_length );

		// check if the current field has been assigned a value
		if ( isset( $values[$field_name] ) )

			$field_values[$field_name] = $values[$field_name];

		else if ( $default_value )

			if ( $trimmed_field_name == $field_name )
			
				$field_values[$field_name] = $default_value;
			else

				$field_values[$field_name] = $default_value;

		// check if an error was detected for the current field
		if ( isset( $control_dashboard[$field_name] ) )
		{
			// check if all filters have been applied to the current field
			if ( isset( $filters ) && is_array( $filters ) )
			{
				while ( list( $filter, $filter_applied ) = each( $filters ) )
				{
					// check the filters
					if ( $field_name == 'field name' )
						
						$class_dumper::log(
							__METHOD__,
							array(
								'filters:',
								$filters
							)
						);

					if ( $filter_applied === FALSE )

						$successful_filtering &= FALSE;
				}

				reset( $filters );

				// remove filters successfully applied
				if ( $successful_filtering )

					$filters_removed = self::remove_field_filters(
						$field_name,
						$current_position,
						$handler_id
					);
			}

			// prepare detected error values to be returned
			$errors[$field_name] = &$control_dashboard[$field_name];

			// check if the current field type is not button, image or submit
			// before saving it in session
			if (
				$field_type != FIELD_TYPE_IMAGE &&
				$field_type != FIELD_TYPE_BUTTON &&
				$field_type != FIELD_TYPE_SUBMIT &&
				isset( $values[$field_name] )
			)

				// save the field value in a persistent way
				self::save_field_values(
					array( $field_name => $values[$field_name] ),
					$current_position,
					$position_instance,
					$handler_id
				);

			// clear the field errors
			$this->clear_field_errors(
				$field_name,
				$field_values,
				$errors,
				$current_position,
				$handler_id
			);

			// get a debugging report about the control dashboard,
			// errors and field values
			Dumper::log(
				__METHOD__,
				array(
					'trimmed field name: '.$trimmed_field_name,
					'control dashboard: ',
					$control_dashboard,
					'errors to be passed to the current form: ',
					$errors,
					'field values: ',
					$values
				),
				DEBUGGING_FIELD_HANDLING && strpos( $field_name, 'email' )
			);
		}
	}

	/**
    * Check a token
    * 
    * @param	string		$name			name
    * @param	string		$type			type
    * @param	string		$label			label
    * @param	string		$options		options
    * @param	string		$default		default value
	* @param	mixed		$informant		informant
	* @param	boolean		$assert			assertion flag
    * @return 	object		field handler
    */
	public function checkToken(
		$name,
		$type,
		$label,
		$options,
		$default,
		$informant = NULL,
		$assert = NULL
	)
	{
		$class_dumper = self::getDumperClass();

		// get fields
		$fields = &$this->getFields();

		$fields[$name] = $this->addComponent(
			array(
				PROPERTY_DEFAULT => $default,
				PROPERTY_INDEX => $fields[$name]->getProperty( PROPERTY_INDEX ),
				PROPERTY_LABEL => $label,
				PROPERTY_NAME => $name,
				PROPERTY_OPTIONS => $options,
				PROPERTY_TYPE => $type
			),
			$informant,
			$assert
		);

		return $this;		
	}

	/**
    * Clear the field values
    *
	* @param	mixed		$informant	informant
    * @return 	array		dashboard
    */
	public function clearFieldValues( $informant = NULL )
	{
		// set the data submission status
		$this->setSubmissionStatus( SESSION_STATUS_NO_SUBMISSION );

		// get the dashboard
		$dashboard = &$this->getDashboard( $informant );

		// get the handler identifier
		$handler_id = $this->getHandlerId();

		// get the coordinates of the field handler
		$coordinates = $this->getCoordinates($handler_id);

		// get the current position
		$current_position = $coordinates[COORDINATES_CURRENT_POSITION];

		// get the store
		$store = &self::getStore($current_position, $handler_id);

		// check if there is some error left
		if ( count( $dashboard )  )
		{
			// loop on dashboard items
			foreach ( $dashboard as $field_name => $errors )
			{
				if ( empty( $errors[ERROR_ALREADY_TAKEN] ) )
				{
					if (
						! empty(
							$store
								[SESSION_STORE_FIELD]
									[SESSION_STORE_HALF_LIVING]
										[$field_name]
						)
					)
		
						// unset the half living field values
						unset(
							$store
								[SESSION_STORE_FIELD]
									[SESSION_STORE_HALF_LIVING]
										[$field_name]
						);
		
					if (
						! empty(
							$store
								[SESSION_STORE_FIELD]
									[SESSION_STORE_VALUE]
										[$field_name]
						)
					)
		
						// unset the half living field values
						unset(
							$store
								[SESSION_STORE_FIELD]
									[SESSION_STORE_VALUE]
										[$field_name]
						);
				}
			}
		
			if ( ! count( $store[SESSION_STORE_FIELD][SESSION_STORE_VALUE] ) )
			
				unset( $store[SESSION_STORE_FIELD][SESSION_STORE_VALUE] );
	
			if ( ! count( $store[SESSION_STORE_FIELD][SESSION_STORE_HALF_LIVING] ) )
	
				unset( $store[SESSION_STORE_FIELD][SESSION_STORE_HALF_LIVING] );
		}
		else
		{
			unset( $store[SESSION_STORE_FIELD][SESSION_STORE_VALUE] );

			unset( $store[SESSION_STORE_FIELD][SESSION_STORE_HALF_LIVING] );
		}			
	}

	/**
	* Extract a target from a raw form identifier
	*
	* @param	string	&$identifier	form identifier
	* @param	integer	&$target		target
	* @return	nothing
	*/
	public static function extractTarget(&$identifier, &$target)
	{
		$target_pattern = '#([^0-9]*)(?<!\.)(?:\.+([0-9]*))?#';

		// look for some target in the form identifier
		if (preg_match($target_pattern, $identifier, $matches))
		{
			$identifier = $matches[1];
			
			if (!empty($matches[2]))
		
				$target = $matches[2];
		}
	}

	/**
    * Get the action script
    *
    * @return 	string	containing an action script
    */	    
	public function get_action()
	{
		return strtolower( $this->action );
	}

	/**
    * Get data submitted through a form
    *
    * @param	integer		$position		position in the data submission process
    * @param	integer		$handler_id		handler identifier
	* @param	mixed		$informant		informant
	* @param	mixed		$assert			assertion flag 
    * @return 	mixed		data
    */	    
	public function get_data(
		$position = NULL,
		$handler_id = FORM_ORDINARY,
		$informant = NULL,
		$assert = FALSE
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		// get the control dashboard
		$control_dashboard = &self::get_control_dashboard(
			$position,
			$handler_id,
			$informant	
		);

		// declare the default handler activity flag
		$handler_inactive = FALSE;

		// get the current handlers
		$handlers = self::getHandlerStatus();

		// check the status of the current handler
		if (
			is_array( $handlers ) &&
			count( $handlers ) &&
			! empty( $handlers[$handler_id] ) &&
			$handlers[$handler_id] == HANDLER_STATUS_INACTIVE
		)

			// toggle the handler activity flag
			$handler_inactive = TRUE;

		// get data from the getData parent class method
		$data = parent::getData( $informant, $assert );

		// check that the current handler is not inactive
		if ( ! $handler_inactive )
		{
			// check the data submission status
			if ( $this->get_status() )
	
				// set the data submission flag
				$control_dashboard[SESSION_STATUS] = SESSION_STATUS_DATA_SUBMITTED;
			else

				// set the data submission flag			
				$control_dashboard[SESSION_STATUS] = SESSION_STATUS_NO_SUBMISSION;
		}
		else

			// unset the data submission flag		
			unset( $control_dashboard[SESSION_STATUS] );

		// return data
		return $data;
	}

	/**
    * Alias to the getMemberId method
    *
    * @return 	integer		handler identifier
    */
	public function get_handler_id()
	{
		// get the handler identifier member
		$handler_id = $this->getHandlerId();

		// return a handler identifier
		return $handler_id;
	}

	/**
    * Get the method HTTP protocol
    *
    * @return 	string	containing a method
    */	    
	public function get_method()
	{
		return strtolower($this->method);
	}

	/**
    * Get status
    *
    * @return 	integer	representing a status
    */	    
	public function get_status()
	{
		$data_submission = &$this->getAProperty( PROPERTY_DATA_SUBMISSION );

		$data_submitted = $this->getSubmittedData();

		if (
			FALSE === $data_submission &&
			is_array( $data_submitted ) &&
			count( $data_submitted ) > 0
		)
		
			$data_submission = TRUE;

		return $data_submission;
	}

	/**
    * Alias to the get_status method
    *
    * @return 	boolean	indicating if data have been submitted
    */	    
	public function get_submission_status()
	{
		return $this->get_status();
	}
 
 	/**
    * Alias to the get_action method
    *
    * @return 	string	containing an action script
    */	    
	public function getAction()
	{
		return $this->get_action();
	}

	/**
    * Get persistent component attributes
    *
    * @return 	array	attributes
    */
	public function getComponentAttributes($persistent = TRUE)
	{
		if ($persistent)
		
			return self::get_field_attributes(null, null, $this->getHandlerId());
	}

	/**
    * Get the handler identifier member
    *
    * @return 	integer		handler identifier
    */
	public function getHandlerId()
	{
		// set the handler id to its default value if not initialized already
		if (!isset($this->_handler_id))

			$this->_handler_id = FORM_ORDINARY;

		return $this->_handler_id;
	}

	/**
    * Get the values of the field handler
    *
    * @return 	array	values
    */
	public function getValues()
	{
		// get the handler identifier
		$handler_id = $this->getHandlerId();

		// get the coordinates of the field handler
		$coordinates = $this->getCoordinates($handler_id);

		// get the current position
		$current_position = $coordinates[COORDINATES_CURRENT_POSITION];

		// get field values
		$field_values = self::getFieldValues($handler_id, $current_position);

		// return the field values
		return $field_values;
	}

	/**
    * Check if the current field handler has a component
    *
    * @param	string		$name
    * @param	boolean		$assert 	assertion flag
    * @return 	boolean		component indicator
    */
	public function hasComponent( $name, $assert = FALSE )
	{
		global $verbose_mode;

		$class_dumper = self::getDumperClass();

		// set the form manager class
		$class_form_manager = self::getFormManagerClass();

		// get the current handler identifier
		$handler_id = $this->getHandlerId();

        // get field values of the current field handler
        $field_values = &self::getPersistentProperty(
			PROPERTY_FIELDS,
			$handler_id,
			ENTITY_FORM_MANAGER
		);

		// check the existence of a component		
		if ( isset( $field_values[$name] ) )

			return TRUE;
		else
		{
			// declare an empty array to contain fields
			$hash_table = array();

			$store_field_handlers = $class_form_manager::getPersistentStore(
				STORE_FIELD_HANDLERS
			);

			if ( isset( $store_field_handlers[$handler_id] ) )
			{
				// get the fields of the current handler 
				$fields =
					$store_field_handlers[$handler_id]
						->getProperty( PROPERTY_FIELDS )
				;

				// append the affordance field to the stack
				array_unshift(
					$fields,
					array(
						HTML_ATTRIBUTE_NAME => FIELD_NAME_AFFORDANCE,
						HTML_ATTRIBUTE_TYPE => FIELD_TYPE_HIDDEN.SUFFIX_MANDATORY
					)
				);

				// loop on the fields
				while (
					list( $field_index, $field_attributes ) = each( $fields )
				)

					if ( ! empty( $field_attributes[HTML_ATTRIBUTE_NAME] ) )

						$hash_table[$field_attributes[HTML_ATTRIBUTE_NAME]] =
							$field_attributes
						; 

				// compare the hash table with the field values
				if (
					$assert && 
					is_array( $field_values ) &&
					( count( $hash_table ) > count( $field_values ) )
				)
				{
					$class_dumper::log(
						__METHOD__,
						array(
							'current handler id: ',
							$handler_id,
							'current field handler: ',
							$this,
							'field values: ',
							$field_values,
							'array of fields aggregated for checking ',
							$hash_table,
							'persistent field handlers store',
							$store_field_handlers
						),
						$assert
					);

					// look for another determining factor
					throw new Exception(
						EXCEPTION_DEVELOPMENT_BEHAVIORAL_DEFINITION_MISSING
					);
				}
			}

			return FALSE;
		}
	}

	/**
    * Link two field values
    *
    * @param	string	$name			containing a field name
    * @param	string	$target			containing the target field name
    * @param	integer	$position		representing a position in the data submission process
	* @param	integer	$handler_id		representing a field handler
	* @return 	object	representing a field handler
    */	 
	public function link(
		$name,
		$target,
		$position = NULL,
		$handler_id = FORM_ORDINARY
	)
	{
		// get the control dashboard
		$control_dashboard = &self::get_control_dashboard( $position, $handler_id );

		$data = &$this->getAProperty( PROPERTY_DATA );
		
		$children = &$this->getAProperty( PROPERTY_CHILDREN );

		$data_submission = $this->getAProperty( PROPERTY_DATA_SUBMISSION );

		$data_validation = $this->getAProperty( PROPERTY_DATA_VALIDATION_FAILURE );

		// declare the default field handler
		$field_handler = NULL;

		// get the fields		
		$fields = $this->getFields();

		// set the default missing values flag
		$missing_values = FALSE;

		$data = &$this->getSubmittedData();

		if (
			! empty( $data[$name] ) &&
			! empty( $data[$target] )
		)

			$field_handler = parent::link( $name, $target );
		else
		{
			// toggle the missing values flag
			$missing_values = TRUE;

			$control_dashboard[$target][ERROR_WRONG_CONFIRMATION] = FORM_LINK_BAD;
		}

		// case when some data validation fail
		if (
			$data_validation === TRUE &&
			isset( $children[$target] ) &&
			$children[$target]->getProperty( PROPERTY_DATA_VALIDATION_FAILURE )
		)
		{
			$field_handler->setProperty( PROPERTY_DATA_VALIDATION_FAILURE, FALSE );
			$control_dashboard[$target][ERROR_WRONG_CONFIRMATION] = FORM_LINK_BAD;
			
			$children[$target]->setProperty( PROPERTY_DATA_VALIDATION_FAILURE, FALSE );
		}
		else if (
			! $missing_values &&
			isset( $control_dashboard[$target][ERROR_WRONG_CONFIRMATION] )
		)

			unset( $control_dashboard[$target][ERROR_WRONG_CONFIRMATION] );

		return $field_handler;
	}

    /**
    * Alias to the load_config method
    *
    * @param	string	$file	containing a file name
    * @return 	nothing
	*/
	public function load_config( $file )
	{
		// load a configuration file
		$this->load_configuration( $file );
	}

    /**
    * load a YAML file
    *
    * @param	string	$file	containing a file name
    * @return 	nothing
	*/
	public function load_configuration( $file )
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();
		
		$class_file_manager = $class_application::getFileManagerClass();

		$output = $class_file_manager::loadSettings( $file );

		// check the loader
		if (
			isset( $output ) &&
			is_array( $output ) &&
			count( $output )
		)

			// set a configuration file
			$this->set_config( $output );	
	}

	/**
	* Prepare a token
	* @param	string	&$ascendant_name 	ascendant name
	* @param	string	&$default_value 	default value
	* @param	string	&$field_name		field name
	* @param	string	&$field_type		field type
	* @param	array	&$field_attributes	field attributes
	* @param	array	&$field_properties	field properties
	*/
	public function prepareToken(
		&$ascendant_name,
		&$default_value, 
		&$field_name,
		&$field_type,
		&$field_attributes,
		&$field_properties
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = self::getDumperClass();

		// get field type
		$field_type = ucfirst(
			rtrim(
				$field_properties[HTML_ATTRIBUTE_TYPE],
				SUFFIX_MANDATORY
			)
		);

		// get field name
		$field_name = $field_properties[HTML_ATTRIBUTE_NAME];

		// set a copy of the field name
		$ascendant_name = $field_name;

		// get field id
		if ( isset( $field_properties[HTML_ATTRIBUTE_ID] ) )

			$field_id = $field_properties[HTML_ATTRIBUTE_ID];

		else

			$field_id = $field_name;

		// get field index
		if ( isset( $field_properties[HTML_ATTRIBUTE_TABINDEX] ) )

			$field_tabindex = $field_properties[HTML_ATTRIBUTE_TABINDEX];

		else

			$field_tabindex = "#";

		// store field attributes
		$field_attributes[$field_name] = array(
			HTML_ATTRIBUTE_ID => $field_id,
			HTML_ATTRIBUTE_NAME => $field_name,
			HTML_ATTRIBUTE_TABINDEX =>
					$field_tabindex == '#'
				?
					count( $field_attributes ) + 1
				:
					$field_tabindex,
			HTML_ATTRIBUTE_TYPE => $field_type
		);

		// check predicates
		if ( isset( $field_properties[AFFORDANCE_CHECK_PREDICATES] ) )
		
			// set the check predicates affordance
			$field_attributes[$field_name] += array(
				AFFORDANCE_CHECK_PREDICATES =>
					$field_properties[AFFORDANCE_CHECK_PREDICATES]
			);

		// check the records
		if ( isset( $field_properties[AFFORDANCE_CHECK_RECORDS] ) )
		
			// set the check records affordance
			$field_attributes[$field_name] += array(
				AFFORDANCE_CHECK_RECORDS =>
					$field_properties[AFFORDANCE_CHECK_RECORDS]
			);

		// check the update flags
		if ( isset( $field_properties[AFFORDANCE_UPDATE] ) )
		
			// set the check records affordance
			$field_attributes[$field_name] += array(
				AFFORDANCE_UPDATE => $field_properties[AFFORDANCE_UPDATE]
			);

		// check if the field value can be accepted as forming part of a draw
		if ( isset( $field_properties[AFFORDANCE_DRAW_FIELDS] ) )

			// set the field draw affordance
			$field_attributes[$field_name] += array(
				AFFORDANCE_DRAW_FIELDS =>
					$field_properties[AFFORDANCE_DRAW_FIELDS]
			);

		// check hash
		if ( isset( $field_properties[AFFORDANCE_HASH] ) )

			// set the hash affordance
			$field_attributes[$field_name] += array(
				AFFORDANCE_HASH => $field_properties[AFFORDANCE_HASH]
			);

		// check if the field value can be accepted as forming part of a draw
		// linked to another draw
		if ( isset( $field_properties[AFFORDANCE_LINK_DRAW] ) )

			// set the link draw affordance
			$field_attributes[$field_name] += array(
				AFFORDANCE_LINK_DRAW => $field_properties[AFFORDANCE_LINK_DRAW]
			);

		// check if the field is linked to some other field
		if ( isset( $field_properties[AFFORDANCE_LINK_TO] ) )

			// set the link to affordance
			$field_attributes[$field_name] += array(
				AFFORDANCE_LINK_TO => $field_properties[AFFORDANCE_LINK_TO]
			);

		// check if the field has some default display value
		if ( isset( $field_properties[AFFORDANCE_DISPLAY_DEFAULT_VALUE] ) )

			$default_value = rtrim(
				$field_properties[AFFORDANCE_DISPLAY_DEFAULT_VALUE],
				SUFFIX_ACCEPT_DEFAULT
			);
	}

	/**
	* Process a field token
	*
	* @param	integer		$handler_id			handler identifier
	* @param	integer		$current_position 	currrent position
	* @param	string		$ascendant_name		ascendant name
	* @param	mixed		$default_value		default value
	* @param	string		$field_index		index
	* @param	string		$field_name			name
	* @param	array		$field_properties	properties
	* @param	string		$field_type			type
	* @param	string		$latest_field_index	latest field index
	* @param	array		$options			$options
	* @param	array		&$field_attributes	field attributes
	* @param	array		&$field_names		field names
	* @param	array		&$field_options		field options
	* @param	array		&$field_values		field values
	* @param	array		&$helpers			helpers
	* @param	array		&$labels				labels
	* @param	array		&$links				links
	* @param	mixed		$informant			informant
	* @param	mixed		$assert				assertion flag

	* @return 	nothing
 	*/
	public function processToken(
		$handler_id,
		$current_position,		
		$ascendant_name,
		$default_value,
		$field_index,
		$field_name,
		$field_properties,
		$field_type,
		$latest_field_index,
		$options,
		&$field_attributes,
		&$field_names,
		&$field_options,
		&$field_values,
		&$helpers,
		&$labels,
		&$links,
		$informant = NULL,
		$assert = FALSE
	)
	{
		$class_data_fetcher = self::getDataFetcherClass();

		$class_dumper = self::getDumperClass();

		$class_form_manager = self::getFormManagerClass();

		$exception_missing_options =
			EXCEPTION_UNDEFINED_OPTIONS . ' (field name: ' . $field_name . ')'
		;
		
		// set the i18n package
		$package = $class_data_fetcher::get_package(
			PACKAGE_I18N,
			NULL,
			$handler_id
		);

		// set the identifier prefix
		$identifier_prefix = $package[I18N_IDENTIFIER_PREFIX];

		// check the identifier prefix
		if ( empty( $identifier_prefix ) )

			// throw an exception
			throw new Exception(EXCEPTION_INVALID_I18N_SCOPE);

		// set the helper name
		$helper_name =
			$identifier_prefix.
			strtoupper( PREFIX_HELPER ).
			strtoupper( $field_name )
		;

		// set the label identifier
		$label_identifier =
			$identifier_prefix.
			strtoupper( PREFIX_LABEL ).
			strtoupper( $field_name )
		;

		// get requirements concerning field type from mandatory suffix appendance
		$required =
			(
				substr( $field_properties[HTML_ATTRIBUTE_TYPE], -1, 1 )
					== SUFFIX_MANDATORY
			)
		;

		// set optional default field value
		if ( isset( $default_value ) )
		{
			$match = preg_match(
				REGEXP_OPEN.SHORTHAND_DATABASE.
					"\."."([^\.]*)\.([^.]*)".
				REGEXP_CLOSE,
				$default_value,
				$matches
			);

			// check the matching values
			if ( $match )

				$default_value = $class_data_fetcher::fetchFieldValue(
					$matches[2],
					$matches[1]
				);

			// assign a default value to the field if one exists
			$field_values[$field_name] = $default_value;

			// check if the default display value of the field is accepted
			$accept_default = (
				substr(
					$field_properties[AFFORDANCE_DISPLAY_DEFAULT_VALUE], -1, 1
				) == SUFFIX_ACCEPT_DEFAULT
			);
		
			// add default value helpers as value attribute to field attributes store
			$field_attributes[$field_name] += array(
				HTML_ATTRIBUTE_VALUE => $default_value
			);
		}
		else

			$accept_default = FALSE;

		// set filters to be passed to a field handler
		if ( isset( $field_properties[AFFORDANCE_APPLY_FILTERS] ) )
		{
			$filters = $field_properties[AFFORDANCE_APPLY_FILTERS];

			// add filtering affordance to field attributes
			$field_attributes[$field_name] += array(
				AFFORDANCE_APPLY_FILTERS => $filters
			);
		}
		else

			$filters = FALSE;

		// check if the field is linked to some other field
		if (
			isset( $field_properties[AFFORDANCE_LINK_TO] ) &&
			! preg_match(
				REGEXP_OPEN.
					REGEXP_START.
					SHORTHAND_DATABASE."\.".
				REGEXP_CLOSE,
				$field_properties[AFFORDANCE_LINK_TO]
			)			
		)

			$target = $field_properties[AFFORDANCE_LINK_TO];

		if ( isset( $field_properties[AFFORDANCE_PROVIDE_WITH_OPTIONS] ) )
		{
			$container_name = $field_properties[AFFORDANCE_PROVIDE_WITH_OPTIONS];

			if ( ! isset( $options[$container_name] ) )

				$options = $class_form_manager::getPersistentProperty(
					PROPERTY_OPTIONS,
					$this->getHandlerId(),
					ENTITY_FIELD_HANDLER
				);

			// check there are such options elements 
			if ( isset( $options[$container_name] ) )

				// set the field options
				$field_options[$container_name] = $options[$container_name];

			else

				// throw a new exception if some field options are missing
				throw new Exception( $exception_missing_options );
		}

		// set the field name as if it was an array
		// for checkbox and multiple select inputs
		if (
			$field_type == ucfirst( INPUT_TYPE_CHECKBOX ) ||
			$field_type == ucfirst( substr( HTML_ATTRIBUTE_MULTIPLE, 0, 5 ) )
		)

			$field_name .= SUFFIX_ARRAY;

		$field_names[$ascendant_name] = $field_name;

		// restore the field name for checkbox and multiple select inputs
		if (
			$field_type == ucfirst( INPUT_TYPE_CHECKBOX ) ||
			$field_type == ucfirst( substr( HTML_ATTRIBUTE_MULTIPLE, 0, 5 ) )
		)

			$field_name = $ascendant_name;

		// add value to helper store
		if ( defined( $helper_name ) )
		{
			// prevent source interpretation breaks
			// by replacing special characters with entities
			$helpers[$field_name] = htmlspecialchars( constant( $helper_name ) );

			// add helpers as title attribute to field attributes store
			$field_attributes[$field_name] += array(
				HTML_ATTRIBUTE_TITLE => $helpers[$field_name]
			);
		}

		// add value to label store
		if ( defined( $label_identifier ) )
		{
			$label_value = constant( $label_identifier );
			$labels[$field_name] = $label_value;
		}

		// pass empty string if no label has been defined 
		else

			$label_value = CHARACTER_EMPTY_STRING;

		// get options for multiple, select, checkbox and radio inputs
		if (
			(
				$field_type == ucfirst( HTML_ATTRIBUTE_MULTIPLE ) ||
				$field_type == ucfirst( HTML_ELEMENT_SELECT ) ||
				$field_type == ucfirst( INPUT_TYPE_CHECKBOX ) ||
				$field_type == ucfirst( INPUT_TYPE_RADIO )
			) 
		)
		{
			if (
				isset( $container_name ) &&
				! empty( $options[$container_name] )
			)
			{
				// get the current form identifier
				$form_identifier = $class_form_manager::get_persistent_property(
					PROPERTY_FORM_IDENTIFIER,
					$handler_id
				);

				// declare field name and identifier substrate
				$substrate_field_name = new stdClass();
				$substrate_form_identifier = new stdClass();
				$substrate_i18n_identifier = new stdClass();

				// set the length propert of the substrates
				$substrate_field_name->{PROPERTY_LENGTH} = 
				$substrate_form_identifier->{PROPERTY_LENGTH} = 
				$substrate_i18n_identifier->{PROPERTY_LENGTH} = count($options[$container_name]);

				// set the pattern properties of the substrate
				$substrate_field_name->{PROPERTY_PATTERN} = $field_name;
				$substrate_form_identifier->{PROPERTY_PATTERN} = $form_identifier;						
				$substrate_i18n_identifier->{PROPERTY_PATTERN} = $identifier_prefix;

				// build spaces from the substrates 
				$prefix_field_name =
					self::buildSpace( $substrate_field_name )
				;
				$prefix_form_identifier = self::buildSpace(
					$substrate_form_identifier
				);
				$prefix_i18n_identifier = self::buildSpace(
					$substrate_i18n_identifier
				);

				try {
					// Apply a callback to the optional elements
					$option_elements = array_map(
						
						/**
						*
						* @see 	/includes/functions.php
						*
						*/

						FUNCTION_EXTRACT_LANGUAGE_ITEM,
						$options[$container_name],
						$prefix_form_identifier,
						$prefix_i18n_identifier,
						$prefix_field_name
					);
				}
				catch ( Exception $exception )
				{
					$class_dumper::log(
						__METHOD__,
						array( $exception ),
						DEBUGGING_DISPLAY_EXCEPTION,
						AFFORDANCE_CATCH_EXCEPTION
					);	
				}
		
				// unshift items in the option elements
				$_count = array_unshift( $option_elements, '' );

				// take a slice of the array of option elements
				$option_elements = array_slice( $option_elements, 1, $_count, TRUE );
			}
			else

				// throw a new exception if some field options are missing
				throw new \Exception( $exception_missing_options );
		}
		else

			$option_elements = NULL;

		// add a field to the field handler
		$this->add(
			$field_name,
			$field_type,
			$label_value,
			$option_elements,
			$default_value,
			$accept_default,
			$required,
			$filters,
			$current_position,
			$handler_id,
			$informant
		);
	
		$class_dumper::log(
			__METHOD__,
			array(
				'field handler status before setting default values: ',
				$this
			),
			$assert &&
			DEBUGGING_FIELD_HANDLING &&
			// ( $field_index == $latest_field_index ) &&
			$field_name == TEST_CASE_FIELD_HANDLER_COMPONENT
		);
	
		// set default field value
		if ( ! empty( $default_value ) )

			$this->set(
				$field_name,
				$default_value,
				$accept_default,
				$current_position,
				$handler_id
			);

		// store the current field link target 
		if ( isset( $target ) )

			$links[$field_name] = $target;

		// link the current fields to its link targets
		if ( $field_index == $latest_field_index )
		{
			while ( list( $master, $slave ) = each( $links ) )

				$this->link(
					$master,
					$slave,
					$current_position,
					$handler_id
				);

			reset( $links );
		}
	}

	/**
    * Restore coordinates
    *
    * @param	integer	$storage_model	representing a model of storage
    * @return 	nothing
    */
	public function restore_coordinates( $storage_model = STORE_SESSION )
	{
		$persistent_coordinates = &$this->get_persistent_coordinates();

		// check if the argument passed to the method is an non-empty array
		if ( is_array( $coordinates ) && count( $coordinates ) )
		{
			$current_coordinates = $coordinates;

			// store coordinates in session			
			self::set_persistent_coordinates( $coordinates );
		}
	}

	/**
    * Serialize the current field handler
    *
    * @param	integer		$storage_model	model of storage
    * @param	mixed		$informant		informant
    * @param	boolean		$assert			assertion flag
    * @return 	nothing
    */
	public function serialize(
		$storage_model = STORE_DATABASE,
		$informant = NULL,
		$assert = FALSE
	)
	{
		// set the application class name
		global $class_application, $verbose_mode;
	
		// set the dumper class name
		$class_dumper = self::getDumperClass();

		// set the form manager class name
		$class_form_manager = self::getFormManagerClass();

		// set the member class name
		$class_member = self::getMemberClass();

		// set the serializer class name
		$class_serializer = self::getSerializerClass();

		// set the user handler class name
		$class_user_handler = self::getUserHandlerClass();

		// get the handler identifier
		$handler_id = $this->getHandlerId();
	
		// get the parent folder of the current field handler
		$parent = self::getPersistentProperty(
			PROPERTY_PARENT,
			$handler_id,
			ENTITY_FIELD_HANDLER
		);

		// The sign up form requires no member authentication
		$action_sign_up = 
			$this->getProperty( PROPERTY_FORM_IDENTIFIER ) ==
				ACTION_SIGN_UP
		;

		$authorization_granted =
			$class_user_handler::authorizedUser(
				$this->getProperty( PROPERTY_FORM_IDENTIFIER )
			)
		;

		$class_dumper::log(
			__METHOD__,
			array(
				'action of type sign up? ',  
				$action_sign_up,
				'granted authorization? ',
				$authorization_granted =
					$class_user_handler::authorizedUser(
						$this->getProperty( PROPERTY_FORM_IDENTIFIER )
					),
				'condition to jump valid?',
				// Users have to be granted rights for performing actions
				// e.g. sign up 
				$authorization_granted ||
	
				// The routes different from the ones leading to overviews
				// require basic member authentication
				(
					$class_user_handler::loggedIn() &&
					$parent != ROUTE_OVERVIEW &&
					$parent != ROUTE_UNDEFINED
				) ||
	
				// The routes leading to overviews
				// require basic administrator authentication
				(
					$class_user_handler::loggedIn( TRUE ) &&
					$parent != ROUTE_ROOT &&
					$parent != ROUTE_UNDEFINED
				)				
			),
			DEBUGGING_FIELD_HANDLING,
			$assert
		);

		// check if a user is logged in or whether a challenge is offered to the visitor
		if (
			// Users have to be granted rights for performing actions
			// e.g. sign up 
			$authorization_granted ||

			// The routes different from the ones leading to overviews
			// require basic member authentication
			(
				$class_user_handler::loggedIn() &&
				$parent != ROUTE_OVERVIEW &&
				$parent != ROUTE_UNDEFINED
			) ||

			// The routes leading to overviews
			// require basic administrator authentication
			(
				$class_user_handler::loggedIn( TRUE ) &&
				$parent != ROUTE_ROOT &&
				$parent != ROUTE_UNDEFINED
			)
		)
		{
			// get the current dashboard
			$dashboard = $this->getDashboard();

			if ( $authorization_granted == FALSE )

				// get the qualities of the logged in member 
				$qualities = $class_member::getQualities();

			list( $first_index ) = each( $dashboard );

			$condition_display_dump =
				$assert &&
				(
					DEBUGGING_FIELD_HANDLING ||
					DEBUGGING_FIELD_ERROR_HANDLING
				)
			;

			if ( $condition_display_dump )
			{

				$persistent_field_handler =
					$class_form_manager::getPersistentFieldHandler(
						$handler_id
					)
				;

				$class_dumper::log(
					__METHOD__,
					array(
						'dashboard: ',
						$dashboard,
						'field handler: ',
						$this,
						'persistent field handler',
						$persistent_field_handler->getStore(
							$persistent_field_handler->getPosition(
								COORDINATES_CURRENT_POSITION,
								$this->getHandlerId()
							),
							$this->getHandlerId()
						)				
					),
					$condition_display_dump,
					count( $dashboard ) && ! empty( $first_index )
				);
			}

			// check if there is an error left
			if ( ! count( $dashboard ) || empty( $first_index ) )
			{
				try
				{
					// save the current field handler
					$class_serializer::save( $this );
				}
				catch ( \Exception $exception )
				{
					$class_dumper::log(						
						__METHOD__,
						array(
							'An exception has been caught while calling '.
							's e r i a l i z e r  : :  s a v e =>',
							$exception
						),
						DEBUGGING_DISPLAY_EXCEPTION,
						AFFORDANCE_CATCH_EXCEPTION
					);
				}

				// clear the field values
				$this->clearFieldValues();
			}

			if (
				$parent != ROUTE_OVERVIEW &&
				FALSE == $authorization_granted
			)

				// login the current member again
				$class_member::login( $qualities->{ROW_MEMBER_IDENTIFIER} );

			// get a persistent property
			$persistent_store = &self::getPersistentProperty(
				PROPERTY_STORE,
				$handler_id,
				ENTITY_FIELD_HANDLER
			);

			$feedback = &self::getPersistentProperty(
				PROPERTY_FEEDBACK,
				$handler_id,
				ENTITY_FIELD_HANDLER
			);

			// set the persistent session data submission status
			$persistent_store[SESSION_STATUS] = SESSION_STATUS_RELOCATION;

			$class_dumper::log(
				__METHOD__,
				array(
					'feedback? ',  
					$feedback,
				),
				$assert &&
				DEBUGGING_FIELD_HANDLING,
				DEBUGGING_FIELD_HANDLING &&
				(
					empty( $feedback[PROPERTY_SUCCESS] ) ||
					$action_sign_up
				)
			);

			if ( ! empty( $feedback[PROPERTY_SUCCESS] ) )

				$_SESSION[STORE_FEEDBACK] = $feedback;

			// prevent non-administration members from access
			// the provide with feeback backend page 
			if (
				strpos( $_SERVER['REQUEST_URI'], URI_ACTION_OVERVIEW ) === 0
			)

				// go the feedback page
				$class_application::jumpTo( URI_ACTION_PROVIDE_WITH_FEEDBACK );

			// reload the page currently being visited
			$class_application::jumpTo( $_SERVER['REQUEST_URI'] );
		}
	}

	/**
    * Set the field value
    *
    * @param	string		$field_name		containing a field name
    * @param	mixed		$field_value	containing a field value				
    * @param	boolean		$accept_default	indicating if the default value is accepted
    * @param	integer		$position		representing a position in the data submission process
	* @param	integer		$handler_id		representing a field handler
    * @return 	object	representing a field handler
    */	    
	public function set(
		$field_name,
		$field_value,
		$accept_default = TRUE,
		$position = null,
		$handler_id = FORM_ORDINARY
	)
	{
		// get control dashboard
		$control_dashboard = &self::get_control_dashboard(
			$position,
			$handler_id
		);

		// set the default handler status
		$handler_inactive = FALSE;

		// get the current handlers
		$handlers = self::getHandlerStatus();

		// check the status of the current handler
		if (
			is_array( $handlers) &&
			count( $handlers ) &&
			! empty( $handlers[$handler_id] ) &&
			$handlers[$handler_id] == HANDLER_STATUS_INACTIVE
		)

			// set the handler status flag
			$handler_inactive = TRUE;

		if (
			! isset( $control_dashboard[$field_name] ) ||
			! is_array( $control_dashboard[$field_name] )
		)
			$control_dashboard[$field_name] = array();

		// prevent non accepted default value from being submitted
		if ( ! $accept_default && ! $handler_inactive )

			$control_dashboard[$field_name][ERROR_DEFAULT_VALUE] = $field_value;

		$field_handler = parent::set( $field_name, $field_value );

		return $field_handler;
	}

	/**
    * Set the action member
    *
    * @param	string	$action	side effect script
    * @return 	nothing
    */	    
	public function setAction( $action )
	{
		// set the action member
		$this->action =
				$action != NULL
			?
				$action
			:
			(
					! empty( $_SERVER['REQUEST_URI'] )
				?
					$_SERVER['REQUEST_URI']
				:
					$_SERVER['PHP_SELF']
			)
		;
	}

	/**
    * Alias to set a configuration method
    * 
	*/
	public function setConfig( $configuration )
	{
		// set the configuration member
		$this->setConfiguration( $configuration );
	}

	/**
    * Set a configuration
    *
    * @param	array	$configuration	configuration properties
    * @return	nothing
	*/
	public function setConfiguration( $configuration )
	{
		// set the configuration member
		$this->set_configuration( $configuration );
	}

    /**
    * Alias to the setConfig method
    *
    * @param	array	$configuration	configuration properties
    * @return	nothing
	*/
	public function set_config( $configuration )
	{
		$this->setConfig( $configuration );
	}

    /**
    * Set a property
    *
	* @param	string	$name 		name 
	* @param	mixed	$value		value
	* @param	boolean	$overwrite	overwrite flag
    * @return	nothing
	*/
	public function set_property( $name, $value, $overwrite = TRUE )
	{
		// get the configuration
		$configuration = $this->get_config();

		// check the name
		if (
			is_string( $name ) &&
			!empty( $name )
		)
		{
			// check the overwrite argument
			if (
				$overwrite ||
				! $overwrite && empty( $configuration[$name] )
			)

				$configuration[$name] = $value;
			else

				return;
		}

		// set the configuration member
		$this->set_configuration( $configuration );
	}

    /**
    * set the configuration member
    *
    * @param	array	$configuration	containing configuration properties
    * @return	nothing
	*/
	public function set_configuration( $configuration )
	{
		// check the configuration argument
		if ( is_array( $configuration ) )

			// set the configuration member
			$this->_configuration = $configuration;
	}

	/**
    * Set coordinates
    *
    * @param	array		$coordinates	containing coordinates
    * @param	integer		$handler_id		representing a field handler
    * @return 	nothing
    */
	public function set_coordinates(
		$coordinates,
		$handler_id = FORM_ORDINARY
	)
	{
		$class_dumper = self::getDumperClass();
		
		// get current coordinates
		$current_coordinates = &$this->get_coordinates($handler_id);

		// check the coordinates
		$class_dumper::log(
			__METHOD__,
			array(
				'coordinates:',
				$coordinates
			)
		);

		// check if the argument passed to the method is an non-empty array
		if ( is_array( $coordinates ) && count( $coordinates ) )
		{
			$current_coordinates = $coordinates;

			// store coordinates in session			
			self::set_persistent_coordinates( $coordinates, $handler_id );
		}
	}

	/**
    * Set the handler identifier
    *
    * @param	integer		$handler_id		handler id
    * @return 	nothing
    */
	public function setHandlerId( $handler_id )
	{
		// get a reference to the handler identifier member
		$handler = &$this->getHandler();

		// check the handler identifier
		if ( is_int( $handler_id ) )
		
			// set the handler identifier
			$handler = $handler_id;
		else

			// throw a new exception
			throw new Exception( EXCEPTION_INVALID_IDENTIFIER );
	}

	/**
    * Set the status of handler
    *
	* @param	string		$status			handler status
    * @param	integer		$handler_id		handler identifier
	* @param	mixed		$informant		informant
    * @return 	nothing
    */
	public function setHandlerStatus(
		$status = HANDLER_STATUS_ACTIVE,
		$handler_id = FORM_ORDINARY,
		$informant = NULL
	)
	{
		// get the handler status
		$handler_status = &self::getHandlerStatus();

		// set the handler status
		$handler_status[$handler_id] = $status;

		// set the persistent handler status		
		self::setPersistentHandlerStatus( $status, $handler_id, $informant );
	}

	/**
    * Set a position
    *
    * @param	mixed 		$position		representing a position
    * @param	integer		$position_type	representing a type of position
    * @param	integer		$handler_id		representing a handler
    * @return 	nothing
    */
	public function set_position(
		$position,
		$position_type = COORDINATES_CURRENT_POSITION,
		$handler_id = FORM_ORDINARY
	)
	{
		// get a position
		$current_position = &$this->get_position( $position_type, $handler_id );

		if ( is_integer( $position ) )
		{
			$current_position = $position;
			
			self::set_persistent_position(
				$position,
				$position_type,
				$handler_id
			);
		}
	}

	/**
    * Set a roadmap
    *
    * @param	array		$compass		compass
    * @param	integer		$handler_id		handler identifier
	* @param	mixed		$informant		informant
    * @return 	nothing
    */
	public function setCompass(
		$compass,
		$handler_id = FORM_ORDINARY,
		$informant = NULL
	)
	{
		// get the active handler if no handler identifier has been passed as an argument		
		if ( $handler_id == FORM_ORDINARY )

			// set the handler identifier
			$handler_id = self::get_active_handler( $informant );

		// get the handler member
		$roadmap = &self::getRoadmap( $handler_id );

		// check if the roadmap is non empty array
		if (
			isset( $compass ) &&
			is_array( $compass ) &&
			count( $compass )
		)
		{
			// set the roadmap member
			$roadmap->{PROPERTY_COMPASS} = $compass;

			// set the persistent roadmap
			self::setPersistentCompass( $compass, $handler_id );
		}
	}

    /**
    * Set the properties of a field handler
    * 
    * @param	array		$properties		properties
	* @param	mixed		$informant		informant
    * @return 	object	field handler
    */	
	public function setProperties( $properties, $informant = NULL )
	{
		global $verbose_mode;

		// set the form manager class name
		$class_form_manager = self::getFormManagerClass();

		$class_dumper = self::getDumperClass();

		// get the affordances store
		$store_affordances =
			&$class_form_manager::getPersistentStore( STORE_AFFORDANCES )
		;

		// get the field handlers store
		$store_field_handlers =
			$class_form_manager::getPersistentStore( STORE_FIELD_HANDLERS );

		// get the handler status store
		$store_handler_status = &$class_form_manager::getHandlerStatus();

		if ( $this->getAProperty( PROPERTY_DATA_SUBMISSION ) )
		{
			$method = $this->getAProperty(PROPERTY_METHOD);

			$this->setAProperty( PROPERTY_DATA,  $this->getSubmittedData( ) );
		}

		if ( count($properties) == 7 )
	
			list(
				$handler_id,
				$action,
				$coordinates,
				$compass,
				$method,
				$identifier,
				$administration
			) = $properties;
		else 

			list(
				$handler_id,
				$action,
				$coordinates,
				$compass,
				$method
			) = $properties;

		// check the action
		if ( ! empty( $action ) )

			// set the coordinates
			$this->setAction( $action );

		// check the handler identifier
		if ( $handler_id != FORM_ORDINARY )

			// set the future former identifier
			$_handler_id = $handler_id;

		// check the provided handler
		self::checkHandler( $handler_id ) ;

		// check the identifier
		if ( ! empty( $identifier ) )
		{
			// set the configuration file property 
			$this->set_property( PROPERTY_FORM_IDENTIFIER, $identifier );

			// check the affordances store
			if (
				count( $store_affordances ) &&
				is_array( $store_affordances ) &&
				isset( $store_affordances[$identifier] )
			)
			{
				// fix edge cases when different form identifiers
				// are provided with the same handler id
				if (
					(
						$keys = array_keys( $store_affordances, $handler_id )
					) && count( $keys ) > 1
				)
				
					while ( list( $index, $form_identifier ) = each( $keys ) )
					{
						if ( $index )

							$store_affordances[$form_identifier] =
								$keys[$index] + 1;
					}

				// set the handler identifier
				$handler_id = $store_affordances[$identifier];
			}
			else if (
				is_array( $store_affordances ) &&
				count( $store_affordances ) == 0 ||
				! isset( $store_affordances[$identifier] )
			)
			{
				// loop on the field handlers
				while (
					list( $handler_index, $field_handler ) =
						each( $store_field_handlers )
				)
				{
					if ( in_array( $handler_index, $store_affordances ) )

						$handler_index++;

					// append an identifier to the affordances
					$store_affordances
						[$field_handler->getProperty(
							PROPERTY_FORM_IDENTIFIER
						)] = $handler_index
					;
				}
	
				// check the affordances store
				if ( ! empty( $store_affordances[$identifier] ) )

					// set the handler identifier
					$handler_id = $store_affordances[$identifier];
			}
		}

		// check the handler identifier
		if ( isset( $handler_id ) && is_numeric( $handler_id ) )
		{
			// set the handler identifier member
			$this->_handler_id = $handler_id;

			// get the persistent handler store
			$persistent_field_handler =
				&$class_form_manager::getPersistentFieldHandler( $handler_id )
			;

			/**
			*
			* FIXME
			*
			*/
			if ( is_array( $this->getProperty( PROPERTY_FORM_IDENTIFIER ) ) )
			{
				if (
					isset( $_SESSION[STORE_BACKUP] ) &&
					isset( $_SESSION[STORE_BACKUP][$this->getHandlerId()] ) &&
					is_object( $_SESSION[STORE_BACKUP][$this->getHandlerId()] ) 
				)
				{
					$form_identifier = $_SESSION[STORE_BACKUP]
						[$this->getHandlerId()]->getProperty(
							PROPERTY_FORM_IDENTIFIER
						)
					;

					$persistent_field_handler = &$_SESSION[STORE_BACKUP]
						[$this->getHandlerId()]
					;
						
					$this->setConfig( $_SESSION[STORE_BACKUP]
						[$this->getHandlerId()]->getConfiguration()
					);

					$this->setProperty(
						PROPERTY_FORM_IDENTIFIER,
						$_SESSION[STORE_BACKUP][$this->getHandlerId()]
							->getProperty( PROPERTY_FORM_IDENTIFIER )
					);

					$persistent_field_handler = $this;

					$class_dumper::log(
						__METHOD__,
						array(
							'restored field handler',
							$this
						)
					);
				}
			}
			else
			{
				$form_identifier = $this->getProperty( PROPERTY_FORM_IDENTIFIER );

				$_SESSION[STORE_BACKUP][$this->getHandlerId()] = $this;
			}

			//$class_dumper::log(
			//	__METHOD__,
			//	array(
			//		'this field handler: ',
			//		$this,
			//		'form identifier: ',
			//		$form_identifier,
			//		'persistent field handler: ',
			//		$persistent_field_handler,
			//		$_SESSION
			//	)
			//);

			// check the handler identifier
			if ( $handler_id == 1 )
			{
				if (
					! isset(
						$store_affordances
							[$form_identifier]
					) &&
					isset( $store_handler_status[$handler_id] )
				)
				{
					// loop on the field handlers store items
					while (
						list( $_identifier, $field_handler ) =
							each( $store_field_handlers )
					)
					{
						// compare the field handler identifiers
						if (
							$field_handler
								->getProperty( PROPERTY_FORM_IDENTIFIER ) ==
							$this->getProperty( PROPERTY_FORM_IDENTIFIER )
						)

							// update the handler identifier
							$handler_id = $_identifier;
					}

					// set the handler identifier				
					$this->setHandlerId( $handler_id );
				}

				// set the handler member
				$this->setHandlerStatus(
					HANDLER_STATUS_ACTIVE,
					$handler_id,
					$informant
				);
			}
			else if ( ! isset( $_handler_id ) )
			{
				// check the handler identifier
				if ( $this->getHandlerId() != $handler_id )

					// set the handler identifier				
					$this->setHandlerId( $handler_id );

				// set the handler member
				$this->setHandlerStatus(
					HANDLER_STATUS_SLEEPING,
					$handler_id,
					$informant
				);
			}
			else
			{
				// get the selected handler
				$selected_handler = $class_form_manager::getSelectedHandler();

				// get the sleeping handlers
				$sleeping_handlers = $class_form_manager::getSleepingHandlers();

				// get the active handlers
				$active_handlers = $class_form_manager::getActiveHandlers();

				// check the sleeping handlers
				if ( count( $sleeping_handlers ) )
				
					// set the toppest sleeper
					$toppest_sleeper = max( $sleeping_handlers );

				// check the sleeping handlers
				if ( count( $active_handlers ) )
				
					// set the toppest active handler
					$toppest_active = max( $active_handlers );

				// check the toppest sleeping handler
				if (
					isset( $toppest_sleeper ) &&
					$selected_handler > $toppest_sleeper
				)
				{
					// check the toppest active handler
					if (
						! isset( $toppest_active ) ||
						isset( $toppest_active ) &&
						$toppest_active < $toppest_sleeper &&
						$toppest_sleeper < $handler_id
					)
						// check the handler status store
						if ( isset( $store_handler_status[$handler_id] ) )

							unset( $store_handler_status[$handler_id] );
				}

				// restore the former identifier
				$this->setHandlerId( $_handler_id );

				// set the selected handler member
				$this->setHandlerStatus(
					HANDLER_STATUS_SELECTED,
					$_handler_id,
					$informant
				);
			}
		}

		// check the coordinates
		if (
			isset( $coordinates ) &&
			is_array( $coordinates ) &&
			count( $coordinates ) != 0
		)
		
			// set the coordinates
			$this->set_coordinates( $coordinates, $handler_id );

		// check the roadmap
		if (
			isset( $compass ) &&
			is_array( $compass ) &&
			count( $compass )
		)

			// set the roadmap
			$this->setCompass( $compass, $handler_id );
	}

	/**
    * Set the submission status
    *
    * @param	string	$status		submission status
    * @param	string	$position	position
    * @return 	nothing
    */
	public function setSubmissionStatus(
		$status = SESSION_STATUS_DATA_SUBMITTED,
		$position = NULL
	)
	{
		$class_form_manager = self::getFormManagerClass();

		// get the control dashboard
		$control_dashboard = &self::getControlDashboard(
			$this->getHandlerId(),
			$position
		);
	
		$data_submission = &$this->getAProperty( PROPERTY_DATA_SUBMISSION );

		// get the submission status
		$data_submission = FALSE;

		// get a persistent property
		$persistent_store = $class_form_manager::getPersistentProperty(
			PROPERTY_STORE,
			$this->getHandlerId()
		);

		// check the provided submission status
		if (
			$status == SESSION_STATUS_DATA_SUBMITTED ||
			(
				! empty( $persistent_store[SESSION_STATUS] ) &&
				$persistent_store[SESSION_STATUS] == SESSION_STATUS_RELOCATION
			)
		)
		{
			// insist on defining the data submission status
			$status = SESSION_STATUS_DATA_SUBMITTED;

			// set the submission status
			$data_submission = TRUE;
		}
		else if ( empty( $persistent_store[SESSION_STATUS] ) )

			$persistent_store[SESSION_STATUS] = $status;

		// set the persistent submission status
		self::setPersistentSubmissionStatus(
			$this->getHandlerId(),
			$status,
			$position
		);
	}

	/**
    * Alias to the getActiveHandler method
    *
	* @param	mixed		$informant		informant
    * @return 	integer		handler
    */
	public static function &get_active_handler( $informant = NULL )
	{
		// get an active handler
		$active_handler = &self::getActiveHandler( $informant );

		// return an active handler
		return $active_handler;
	}

	/**
    * Alias to the getControlDashboard method
    *
    * @param	integer		$position		position in the data submission process
	* @param	integer		$handler_id		handler identifier
    * @param	mixed		$informant		informant
    * @return 	&array		containing indicators
    */	    
	public static function &get_control_dashboard(
		$position = NULL,
		$handler_id = FORM_ORDINARY,
		$informant = NULL
	)
	{
		// get a control dashboard
		$control_dashboard = &self::getControlDashboard(
			$handler_id,
			$position,
			$informant
		);
		
		// return a control dashboard
		return $control_dashboard;
	}

	/**
    * Get field attributes
    *
    * @param	string		$field_identifier	field identifier
    * @param	integer		$position			position in the data submission process
	* @param	integer		$handler_id			field handler
    * @param	mixed		$informant			informant 
    * @return 	&array		containing field attributes
    */
	public static function &get_field_attributes(
		$field_identifier = NULL,
		$position = NULL,
		$handler_id = FORM_ORDINARY,
		$informant = NULL
	)
	{
		$store = &self::getStore( $position, $handler_id );

		// case when a field identifier is not provided
		// or there is no such key in the attribute store 
		if (
			! isset( $field_identifier ) ||
			! is_string( $field_identifier ) ||
			! isset(
				$store[SESSION_STORE_FIELD][SESSION_STORE_ATTRIBUTE]
					[$field_identifier]
			)
		)
		{
			// case when a field identifier is provided
			// and there is no such key in the attribute store
			if (
				isset( $field_identifier ) &&
				! isset(
					$store[SESSION_STORE_FIELD][SESSION_STORE_ATTRIBUTE]
						[$field_identifier]
				)
			)
			{
				$store[SESSION_STORE_FIELD][SESSION_STORE_ATTRIBUTE]
					[$field_identifier] = array(
						HTML_ATTRIBUTE_NAME => '',
						HTML_ATTRIBUTE_ID => $field_identifier,
						HTML_ATTRIBUTE_TABINDEX => ''
					)
				;

				return $store[SESSION_STORE_FIELD][SESSION_STORE_ATTRIBUTE]
					[$field_identifier]
				;
			}

			// case when no field identifier is provided
			return $store[SESSION_STORE_FIELD][SESSION_STORE_ATTRIBUTE];
		}
		// case when a field identifier is provided
		else

			return $store[SESSION_STORE_FIELD][SESSION_STORE_ATTRIBUTE]
				[$field_identifier];
	}

	/**
    * Get field values
    *
    * @param	string	$field_identifier	field identifier
    * @param	integer	$position			position in the data submission process
    * @param	integer	$position_instance	position instance
    * @param	integer	$handler_id			field handler
    * @param	mixed	$informant			informant
    * @return 	&array
    */
	public static function &get_field_values(
		$field_identifier = NULL,
		$position = NULL,
		$position_instance = NULL,
		$handler_id = FORM_ORDINARY,
		$informant = NULL
	)
	{
		$store = &self::getStore( $position, $handler_id );

		// case when no field identifier is provided
		if ( ! isset( $field_identifier ) || ! is_string( $field_identifier ) )
		{			
			// check if multiple instances of the same position are to be submitted
			if ( isset( $position_instance ) )

				return
					$store
						[SESSION_STORE_FIELD]
							[SESSION_STORE_VALUE]
								[(int)$position_instance]
				;
			else 

				return
					$store
						[SESSION_STORE_FIELD]
							[SESSION_STORE_VALUE]
				;
		}
		else
		{
			// get the field atttributes
			$field_attributes = &self::get_field_attributes(
				$field_identifier,
				$position,
				$handler_id
			);

			// get the field name
			$field_name = $field_attributes[HTML_ATTRIBUTE_NAME];

			if (
				isset(
					$store
						[SESSION_STORE_FIELD]
							[SESSION_STORE_VALUE]
								[$field_name]
				)
			)
			{

				// check if multiple instances of the same position to be submitted
				if ( isset( $position_instance ) )

					return
						$store
							[SESSION_STORE_FIELD]
								[SESSION_STORE_VALUE]
									[(int)$position_instance]
										[$field_name]
					;
				else

					return
						$store
							[SESSION_STORE_FIELD]
								[SESSION_STORE_VALUE]
									[$field_name]
					;
			}
			else
			{
				// check if multiple instances of the same position are to be submitted
				if ( isset( $position_instance ) )
				{
					$store
						[SESSION_STORE_FIELD]
							[SESSION_STORE_VALUE]
								[(int)$position_instance][$field_name] =
						''
					;

					return
						$store
							[SESSION_STORE_FIELD]
								[SESSION_STORE_VALUE]
									[(int)$position_instance]
										[$field_name]
					;					
				}
				else
				{
					$store
						[SESSION_STORE_FIELD]
							[SESSION_STORE_VALUE]
								[$field_name] = ''
					;

					return
						$store
							[SESSION_STORE_FIELD]
								[SESSION_STORE_VALUE]
									[$field_name]
					;
				}
			}
		}
	}

	/**
    * Get filters
    *
    * @param	string		$field_name		field name
    * @param	integer		$position		position in the data submission process
    * @param	integer		$handler_id		field handler
	* @param	mixed		$informant		informant
    * @return 	mixed containing filters
    */
	public static function &get_field_filters(
		$field_name = NULL,
		$position = NULL,
		$handler_id = FORM_ORDINARY,
		$informant = NULL
	)
	{
		// get control dashboard
		$control_dashboard = &self::get_control_dashboard($position, $handler_id);

		// check the filters
		if (
			isset($field_name) &&
			isset($control_dashboard[$field_name]) &&
			isset($control_dashboard[$field_name][AFFORDANCE_APPLY_FILTERS])
		)
		{
			$dumper = new dumper(
				'f i e l d H a n d l e r',
				'g e t _ f i e l d _ f i l t e r s ( )',
				array(
					'filters:',
					$control_dashboard[$field_name][AFFORDANCE_APPLY_FILTERS]
				)
			);
		}

		if (!isset($control_dashboard[$field_name][AFFORDANCE_APPLY_FILTERS]))
		{
			// set the default field type
			$field_type = null;

			// get the field attributes
			$field_attributes = &self::get_field_attributes(
				$field_name,
				$position,
				$handler_id
			);

			// check if the field attributes exists 
			if (isset($field_attributes[HTML_ATTRIBUTE_TYPE]))

				// get the field type			
				$field_type = $field_attributes[HTML_ATTRIBUTE_TYPE];

			// check if the current field type is not button, image or submit
			if (
				$field_type != FIELD_TYPE_IMAGE &&
				$field_type != FIELD_TYPE_BUTTON &&
				$field_type != FIELD_TYPE_SUBMIT
			)

				// set the filter without filter
				$control_dashboard[$field_name][AFFORDANCE_APPLY_FILTERS] = array(FILTER_WITHOUT_FILTER => TRUE);
			else 

				// set the filter not relevant
				$control_dashboard[$field_name][AFFORDANCE_APPLY_FILTERS] = array(FILTER_NOT_RELEVANT => TRUE);
		}

		return $control_dashboard[$field_name][AFFORDANCE_APPLY_FILTERS];
	}


	/**
    * Alias to the getHandlerByStatus method
    *
	* @param	string		$status		status
	* @param	mixed		$informant	informant
    * @return 	integer		handler
    */
	public static function &get_handler_by_status(
		$status = HANDLER_STATUS_ACTIVE,
		$informant = NULL
	)
	{
		// get a handler
		$handler = &self::getHandlerByStatus( $status, $informant );

		// return a handler
		return $handler;
	}

	/**
    * Alias to the getHandlerStatus method
    *
	* @param	mixed		$informant		informant
    * @return 	&array		handler status
    */
	public static function &get_handlers( $informant = NULL )
	{
		// get handler status
		$handler_status = &self::getHandlerStatus( $informant );

		// return handler status		
		return $handler_status;
	}

	/**
    * Get coordinates stored in session
    *
    * @param	integer		$handler_id	handler
	* @param	mixed		$informant	informant
    * @return 	&array		containing coordinates
    */	    
	public static function &get_persistent_coordinates(
		$handler_id = FORM_ORDINARY,
		$informant = NULL
	)
	{
		// get a persistent property
		$persistent_roadmap = &self::getPersistentProperty(PROPERTY_ROADMAP, $handler_id);

		// assess the informant value
		if ( $informant == 'informant value' )

			Dumper::log(
				__METHOD__,
				array(
					'persistent roadmap:',
					$persistent_roadmap
				)
			);

		// case when there is no coordinate store in session		
		if (
			!isset($persistent_roadmap->{PROPERTY_COORDINATES}) ||
			!is_array($persistent_roadmap->{PROPERTY_COORDINATES})
		)

			// set the persistent coordinates
			$persistent_roadmap->{PROPERTY_COORDINATES} = array(
				COORDINATES_CURRENT_POSITION => 0,
				COORDINATES_NEXT_POSITION => 1,
				COORDINATES_PREVIOUS_POSITION => null
			);

		// get persistent coordinatess
		$persistent_coordinates = &$persistent_roadmap->{PROPERTY_COORDINATES};

		// return persistent coordinates
		return $persistent_coordinates;
	}

	/**
    * Get inactive handler
    *
	* @param	mixed	$informant	informant
    * @return 	integer	handler
    */
	public static function &get_inactive_handler( $informant = NULL )
	{
		// get an inactive handler
		return self::get_handler_by_status( HANDLER_STATUS_INACTIVE, $informant );
	}

	/**
    * Get position stored in session
    *
    * @param	integer		$position_type	type of position
    * @param	integer		$handler_id		handler
	* @param	mixed		$informant		informant
    * @return 	mixed		representing a position
    */	    
	public static function &get_persistent_position(
		$position_type = COORDINATES_CURRENT_POSITION,
		$handler_id = FORM_ORDINARY,
		$informant = NULL		 
	)
	{
		if (
			$informant == 'informant value' &&
			isset( $_SESSION['fm'][$handler_id] )
		)

			$dumper = new dumper(
				'f ie l d H a n d l e r',
				'g e t _ p e r s i s t e n t _ p o s i t i o n ( )',
				array(
					'handler id',
					$handler_id,
					"control dashboard before retrieving the persistent coordinates",
					$_SESSION['fm'][$handler_id]['str'][0]['e']
				)
			);	

		$coordinates = &self::get_persistent_coordinates($handler_id, $informant);

		// set the coordinates position instances
		if (
			$position_type == COORDINATES_POSITION_INSTANCES &&
			!isset($coordinates[COORDINATES_POSITION_INSTANCES])
		)
			$coordinates[COORDINATES_POSITION_INSTANCES] = array(
				1 => FALSE
			);

		return $coordinates[$position_type];
	}

	/**
    * Alias to the getPersistentProperty method
    *
    * @param	string		$property		property
    * @param	integer		$handler_id		handler identifier
	* @param	mixed		$informant		informant
    * @return 	&array		persistent data of a field handler 
    */
	public static function &get_persistent_property(
		$property,
		$handler_id = FORM_ORDINARY,
		$informant = NULL
	)
	{
		// get a persistent property
		$persistent_property = self::getPersistentProperty(
			$property,
			$handler_id
		);

		// return a persistent property
		return $persistent_property;		
	}

	/**
    * Get ubiquitous handler
    *
	* @param	mixed		$informant	informant
    * @return 	integer		representing a handler
    */
	public static function &get_ubiquitous_handler( $informant = NULL )
	{
		// get an ubiquitous handler
		return self::get_handler_by_status(
			HANDLER_STATUS_UBIQUITOUS,
			$informant
		);
	}

	/**
    * Alias to the getStore method
    *
    * @param	integer		$position		position in the data submission process
    * @param	integer		$handler_id		field handler
    * @param	string		$store_type		store type
	* @param	mixed		$informant		informant
    * @return 	&array		data
    */	    
	public static function &get_store(
		$position = null,
		$handler_id = FORM_ORDINARY,
		$store_type = null,
		$informant = NULL
	)
	{
		// get a persistent store
		$persistent_store = &self::getStore(
			$position,
			$handler_id,
			$store_type,
			$informant
		);
		
		// return a persistent store
		return $persistent_store;
	}

	/**
    * Get abstract field handler
    *
    * @return 	object	field handler
    */
	public static function &getAbstractFieldHandler()
	{
		// construct an abstract field handler
		$field_handler = new self(
			NULL,
			NULL,
			NULL,
			FORM_ORDINARY,
			PROTOCOL_HTTP_METHOD_POST,
			TRUE
		);

		// return an abstract field handler
		return $field_handler;
	}

	/**
    * Get active handler
    *
	* @param	integer	$informant		informant
    * @return 	integer		handler
    */
	public static function &getActiveHandler($informant = NULL)
	{
		$class_dumper = self::getDumperClass();

		// set the form manager class name
		$class_form_manager = self::getFormManagerClass();

		// get the handlers
		$handlers = $class_form_manager::getHandlers($informant);

		// check the handlers
		if ( count( $handlers ) )

			$handler = max( $handlers );

		// get an active handler
		$active_handler = &$class_form_manager::getHandlerByStatus(
			HANDLER_STATUS_ACTIVE,
			$informant
		);

		// check the handler
		if (
			isset($handler) &&
			$handler > $active_handler
		)
		{
			// set the active handler
			$active_handler = $handler;

			// get the persistent context store
			$store_context = $class_form_manager::getPersistentStore(
				STORE_CONTEXT
			);

			// get the persistent affordances store
			$store_affordances = $class_form_manager::getPersistentStore(
				STORE_AFFORDANCES
			);

			if (
				count( $store_affordances ) &&
				isset( $store_context[PROPERTY_FORM_IDENTIFIER] ) &&
				isset( $store_affordances[$store_context[PROPERTY_FORM_IDENTIFIER]] ) &&
				$store_affordances[$store_context[PROPERTY_FORM_IDENTIFIER]] &&
				$store_affordances[$store_context[PROPERTY_FORM_IDENTIFIER]] < $active_handler
			)
			
			$active_handler = $store_affordances[$store_context[PROPERTY_FORM_IDENTIFIER]];	
		}

		// return an active handler
		return $active_handler;
	}

	/**
    * Get control dashboard
    *
	* @param	integer		$handler_id		handler identifier
    * @param	integer		$position		position in the data submission process
    * @param	mixed		$informant		informant
    * @return 	&array		containing indicators
    */	    
	public static function &getControlDashboard(
		$handler_id = FORM_ORDINARY,
		$position = null,		
		$informant = NULL
	)
	{
		// get a session store
		$store = &self::getStore($position, $handler_id);

		// initialize the control dashboard the first time
		if (
			!isset($store[SESSION_CONTROL_DASHBOARD]) ||
			!is_array($store[SESSION_CONTROL_DASHBOARD])
		)
			$store[SESSION_CONTROL_DASHBOARD] = array();

		return $store[SESSION_CONTROL_DASHBOARD];
	}

	/**
    * Get the dashboard of a field handler 
    *
	* @param	mixed		$informant	informant
    * @return 	array		dashboard
    */
	public function &getDashboard($informant = NULL)
	{
		// get the handler identifier
		$handler_id = $this->getHandlerId();

		// get the coordinates of the field handler
		$coordinates = $this->getCoordinates($handler_id);

		// get the current position
		$current_position = $coordinates[COORDINATES_CURRENT_POSITION];

		// return a control dashboard
		return self::getControlDashboard($handler_id, $current_position, $informant);		
	}

	/**
    * Get handler by status
    *
	* @param	string		$status		status
	* @param	mixed		$informant	informant
    * @return 	integer		handler
    */
	public static function &getHandlerByStatus($status = HANDLER_STATUS_ACTIVE, $informant = NULL)
	{
		$class_form_manager = CLASS_FORM_MANAGER;

		// get handler by status
		$handler_id = &$class_form_manager::getHandlerByStatus($status, $informant);

		// return handler identifier
		return $handler_id;
	}

	/**
    * Get handler status
    *
	* @param	integer	$informant		informant
    * @return 	&array		handler status
    */
	public static function &getHandlerStatus($informant = NULL)
	{
		global $class_application, $verbose_mode;

		$class_form_manager = $class_application::getFormManagerClass();;

		// get handlers
		$handler_status = &$class_form_manager::getHandlerStatus( $informant );

		// return handlers
		return $handler_status;
	}

	/**
    * Get a persistent compass
    *
    * @param	integer		$handler_id		handler
    * @param	mixed		$informant		informant
    * @return 	&array	roadmap
    */
	public static function &getPersistentCompass($handler_id = FORM_ORDINARY, $informant = NULL)
	{
		// get a persistent roadmap
		$persistent_roadmap = &self::getPersistentRoadmap($handler_id, $informant);

		// check a persistent compass
		if (
			!isset($persistent_compas->{PROPERTY_COMPASS}) ||			
			!is_array($persistent_roadmap->{PROPERTY_COMPASS})
		)

			// declare the default persistent compass
			$persistent_roadmap->{PROPERTY_COMPASS} = array();

		// return a persistent roadmap
		return $persistent_roadmap->{PROPERTY_COMPASS};
	}

	/**
    * Get persistent handler status
    *
    * @param	integer	$informant		informant
    * @return 	&array		persistent handler status
    */
	public static function &getPersistentHandlerStatus($informant = NULL)
	{
		// get persistent handler status
		$persistent_handler = &self::getHandlerStatus($informant);

		// return a persistent handler
		return $persistent_handler;
	}

	/**
    * Get a persistent roadmap
    *
    * @param	integer		$handler_id		handler
    * @param	mixed		$informant		informant
    * @return 	&array	roadmap
    */
	public static function &getPersistentRoadmap($handler_id = FORM_ORDINARY, $informant = NULL)
	{
		// get the active handler if no handler identifier has been passed as an argument		
		if ($handler_id == FORM_ORDINARY)

			// get the active handler
			$handler_id = self::getActiveHandler($informant);

		// get a persistent roadmap
		$persistent_roadmap = &self::getPersistentProperty(PROPERTY_ROADMAP, $handler_id);

		// return a persistent roadmap
		return $persistent_roadmap;
	}

	/**
    * Get a persistent property
    *
    * @param	string		$property		property
    * @param	integer		$handler_id		handler identifier
	* @param	string		$entity_type	entity type
	* @param	integer		$storage_model	storage model
	* @param	mixed		$informant		informant
    * @return 	mixed 		persistent data of a field handler 
    */
	public static function &getPersistentProperty(
		$property,
		$handler_id = FORM_ORDINARY,
		$entity_type = ENTITY_FORM_MANAGER,
		$storage_model = STORE_SESSION,		
		$informant = NULL
	)
	{
		// set the form manager class
		$class_form_manager = self::getFormManagerClass();

		// get a persistent property
		$persistent_property = &$class_form_manager::getPersistentProperty(
			$property,
			$handler_id,
			$entity_type,
			$storage_model,
			$informant
		);

		// return a persistent property
		return $persistent_property;
	}

	/**
    * Get store
    *
    * @param	integer		$position		position in the data submission process
    * @param	integer		$handler_id		field handler
    * @param	string		$store_type		store type
	* @param	mixed		$informant		informant
    * @return 	&array		data
    */	    
	public static function &getStore(
		$position = null,
		$handler_id = FORM_ORDINARY,
		$store_type = null,
		$informant = NULL
	)
	{
		$class_form_manager = self::getFormManagerClass();

		// get an active handler if no handler identifier
		// has been passed as an argument		
		if ( $handler_id == FORM_ORDINARY )

			$handler_id = self::get_handler_by_status(
				HANDLER_STATUS_ACTIVE,
				$informant
			);

		// get an ubiquitous handler
		// if no active handler identifier has been found
		if ( $handler_id == FORM_ORDINARY )

			$handler_id = self::get_handler_by_status(
				HANDLER_STATUS_UBIQUITOUS,
				$informant
			);

		// get an inactive handler
		// if no ubiquitous handler identifier has been found
		if ( $handler_id == FORM_ORDINARY )
	
			$handler_id = self::get_handler_by_status(
				HANDLER_STATUS_INACTIVE,
				$informant
			);

		// get the current position
		// if no position has been passed as an argument		
		if ( ! isset( $position ) )
	
			$position = self::get_persistent_position(
				COORDINATES_CURRENT_POSITION,
				$handler_id,
				$informant
			);

		// get a persistent property
		$persistent_store = &$class_form_manager::getPersistentProperty(
			PROPERTY_STORE,
			$handler_id
		);

		// case when no position is provided as a argument
		if (!isset($position))

			// retrieve the current position stored in session
			$position = &self::get_persistent_position(
				COORDINATES_CURRENT_POSITION,
				$handler_id,
				$informant
			);

		// case when a store type has not been passed as an argument
		// and there is no store in session
		if (
			! isset( $store_type ) &&
			(
				! isset( $persistent_store ) ||
				! is_array( $persistent_store )
			)
		)
		{
			$persistent_store
				[$position]
					[SESSION_STORE_FIELD]
						[SESSION_STORE_ATTRIBUTE] = 
			$persistent_store
				[$position]
					[SESSION_STORE_FIELD]
						[SESSION_STORE_VALUE] =
			$persistent_store
				[$position]
					[SESSION_STORE_FIELD] = 
			$persistent_store
				[$position] = 
			$persistent_store = array();

			$store = &$persistent_store[$position];
		}

		// case when a session store exists and there is no field store
		else if (
			! isset( $store_type ) &&
			! isset( $persistent_store[$position][SESSION_STORE_FIELD] )
		)
		{
			$store = &$persistent_store[$position];

			$store[SESSION_STORE_FIELD] = array();
			$store[SESSION_STORE_FIELD][SESSION_STORE_VALUE] = array();
			$store[SESSION_STORE_FIELD][SESSION_STORE_ATTRIBUTE] = array();			
		}

		// case when a session store exists and there is a field store
		else if ( ! isset( $store_type ) )
		{
			// set the store
			$store = &$persistent_store[$position];

			// check no value store existence in session
			if ( ! isset( $store[SESSION_STORE_FIELD][SESSION_STORE_VALUE] ) )

				$store[SESSION_STORE_FIELD][SESSION_STORE_VALUE] = array();

			// check attribute store existence in session
			if ( ! isset( $store[SESSION_STORE_FIELD][SESSION_STORE_ATTRIBUTE] ) )

				$store[SESSION_STORE_FIELD][SESSION_STORE_ATTRIBUTE] = array();			
		}
		// check if a store type has been passed as an argument
		else
		{
			switch ( $store_type )
			{
				case SESSION_STORE_VALUE:

					// get the field value store for current position and field handler
					$store = &self::get_field_values(
						NULL,
						$position,
						NULL,
						$handler_id
					);

						break;

				case SESSION_STORE_ATTRIBUTE:

					// get the field attributes store for current position and field handler
					$store = &self::get_field_attributes(
						NULL,
						$position,
						$handler_id
					);

						break;
			}
		}

		return $store;
	}

	/**
    * Alias to the activateHandler method
    *
    * @param	integer		$handler_id		field handler
	* @param	mixed		$informant		informant
    * @return 	nothing
    */	
	public static function activate_handler($handler_id, $informant = NULL)
	{
		// activate a handler
		self::activateHandler($handler_id, $informant);	
	}

	/**
    * Activate a handler
    *
    * @param	integer		$handler_id		field handler
	* @param	mixed		$informant		informant
    * @return 	nothing
    */
	public static function activateHandler($handler_id, $informant = NULL)
	{
		// activate a handler
		Form_Manager::activateHandler($handler_id, $informant);
	}

	/**
    * Check a handler
    *
    * @param	integer		$identifier		handler identifier
    * @return 	nothing
    */
	public static function checkHandler( &$identifier )
	{
		// set the Dumper class name
		$class_dumper = self::getDumperClass();
		
		// set the form manager class name
		$class_form_manager = self::getFormManagerClass();

		// get the active handlers
		$active_handlers = $class_form_manager::getActiveHandlers();

		// set the default inactive handler
		$inactive_handler = 0;

		// get the active handlers
		$handlers = $class_form_manager::getHandlers();

		// get the inactive handlers
		$inactive_handlers = $class_form_manager::getInactiveHandlers();

		// get the selected handler
		$selected_handler = $class_form_manager::getSelectedHandler();

		// get the sleeping handlers
		$sleeping_handlers = $class_form_manager::getSleepingHandlers();

		// get the store of affordances
		$store_affordances =
			$class_form_manager::getPersistentStore( STORE_AFFORDANCES )
		;

		// get the store of field handlers
		$store_field_handlers =
			&$class_form_manager::getPersistentStore( STORE_FIELD_HANDLERS )
		;

		// get the store of handlers
		$store_handlers =
			&$class_form_manager::get_persistent_store( STORE_FIELD_HANDLERS )
		;

		// compare the handlers and field handlers counts
		if (
			count( $store_affordances ) &&
			count( $store_affordances ) < count( $store_field_handlers )
		)
		{
			// loop on the affordances
			while (
				list( $_identifier, $field_handler ) =
					each( $store_field_handlers )
			)
			{
				// check the field handlers store 
				if (
					! in_array( $_identifier, $store_affordances ) &&
					isset(
						$affordances[$field_handler->getProperty(
							PROPERTY_SIGNATURE
						)]
					)
				)
				{
					unset( $store_field_handlers[$_identifier] );

					// check the handlers store
					if ( isset( $store_handlers[$_identifier] ) )
	
						unset( $store_handlers[$_identifier] );
				}
			}

			reset( $store_field_handlers );
		}

		// check the inactive handlers
		if ( count( $active_handlers ) )
		{
			// get the latest active handler
			$active_handler = max( $active_handlers );

			if (
				! isset(
					$store_field_handlers[$active_handler]
				)
			)

				$pop = $class_form_manager::storePop(
					STORE_HANDLER_STATUS,
					HANDLER_STATUS_ACTIVE
				);	
		}

		// check the inactive handlers
		if ( count( $handlers ) )

			// get the latest handler
			$handler = max( $handlers );

		// check the inactive handlers
		if ( count( $inactive_handlers ) )

			// get the latest deactivated handler
			$inactive_handler = max( $inactive_handlers );

		// check the sleeping handlers
		if ( count( $sleeping_handlers ) )
		{		
			// get the latest sleeping handler
			$sleeping_handler = max( $sleeping_handlers );

			if ( ! isset( $store_field_handlers[$sleeping_handler] ) )

				$pop = $class_form_manager::storePop();
		}

		// check the toppest handler
		if (
			! empty($handler) &&
			$handler > $identifier
		)

			$identifier = $handler + 1;

		// check the toppest inactive handler
		if (
			! empty( $inactive_handler ) &&
			$inactive_handler >= $identifier
		)
		{
			if ( ! isset( $active_handler ) )

				$identifier = $inactive_handler + 1;
			else

				$identifier = $active_handler + 1;			
		}

		// check the toppest handler
		if (
			! empty( $active_handler ) &&
			$active_handler > $identifier
		)

			$identifier = $active_handler + 1;

		// check the toppest sleeping handler
		if (
			! empty( $handler ) &&
			! empty( $sleeping_handler ) &&
			$handler < $sleeping_handler
		)

			$identifier = $handler;

		if ( isset( $active_handler ) )

			$class_dumper::log(
				__METHOD__,
				array(
					'provided identifier',
					$identifier,					
					'active',
					$active_handler
				)
			);

		if ( isset( $handler ) )

			$class_dumper::log(
				__METHOD__,
				array(
					'provided identifier',
					$identifier,					
					'handler',
					$handler
				)
			);

		if ( isset( $inactive_handler ) )

			$class_dumper::log(
				__METHOD__,
				array(
					'provided identifier',
					$identifier,
					'inactive',
					$inactive_handler
				)
			);
	}

	/**
    * Clean the control dashboard
    *
    * @param	integer 		$position	representing a position
    * @param	integer		$handler_id	representing a field handler
    * @return 	boolean	indicating if the method ran smoothly
    */
	public static function clean_control_dashboard(
		$position = NULL,
		$handler_id = FORM_ORDINARY
	)
	{
		// get a persistent store
		$store = &self::getStore($position, $handler_id);

		// check the control dashboard
		if (isset($store[SESSION_CONTROL_DASHBOARD]))

			// unset a control dashboard
			unset($store[SESSION_CONTROL_DASHBOARD]);

		// check a control dashboard
		if (!isset($store[SESSION_CONTROL_DASHBOARD]))

			return TRUE;

		else

			return FALSE;
	}

	/**
    * Deactivate a handler
    *
    * @param	integer	$handler_id		field handler
	* @param	integer	$informant		informant
    * @return 	nothing
    */
	public static function deactivate_handler(
		$handler_id,
		$informant = NULL
	)
	{
		// get handlers
		$handlers = &self::getHandlerStatus( $informant );
	
		// check the handlers
		if (
			is_array( $handlers ) &&
			count( $handlers ) &&
			! empty( $handlers[(int) $handler_id])
		)
			// set the current handler to active
			$handlers[(int)$handler_id] = HANDLER_STATUS_INACTIVE;
	}

	/**
    * Destroy the session variables
    *
    * @param	boolean	$administration	administration flag
    * @return 	nothing
    */	    
	public static function destroy_session( $administration = FALSE )
	{
		// set the application class name
		$class_application = self::getApplicationClass();

		// destroy an application session
		$class_application::destroySession( $administration );
	}

	/**
    * Get field values
    *
    * @param	integer		$handler_id		field handler
    * @param	integer		$position		position in the data submission process
    * @return 	&array
    */
	public static function getFieldValues($handler_id, $position = null)
	{
		// return field values
		return self::get_field_values(null, $position, null, $handler_id);
	}

    /**
    * Load a field handler
    *
    * @param	string		$identifier		containing an identifier
    * @param	boolean		$administration	administration flag
    * @param	boolean		$unbound		unbound model
    * @param	boolean		$edition		edition flag
    * @param	mixed		$informant		informant
    * @param	integer		$storage_model	representing a model of storage
    * @return 	object	representing a field handler
	*/
	public static function load(
		$identifier,
		$administration = FALSE,
		$unbound = FALSE,
		$edition = FALSE,
		$informant = NULL,
		$storage_model = STORE_YAML
	)
	{
		// set the data fetcher class name
		$class_data_fetcher = self::getDataFetcherClass();

		// set the dumper class name
		$class_dumper = self::getDumperClass();

		// set the form manager class
		$class_form_manager = self::getFormManagerClass();	

		$target = '';

		self::extractTarget($identifier, $target);
		
		// set the configuration file name
		$file = PREFIX_FORM.$identifier.EXTENSION_YAML;

		// set the default names string as an empty string
		$names = '';

		// get the context store
		$store_context = &self::getPersistentProperty( STORE_CONTEXT );

		// get the signature store
		$store_signature = &self::getPersistentProperty( STORE_SIGNATURES );

		// construct a field handler
		$field_handler = new self(
			( $administration ? PREFIX_ADMINISTRATION : '').
			( $edition ? PREFIX_EDITION : '' ). 
			$identifier
		);

		if ( ! empty( $target ) )

			$field_handler->setProperty( PROPERTY_TARGET, $target );

		// load a configuration file
		$field_handler->load_config( $file );

		if (!$unbound)

			// get the handler identifier of the field handler
			$handler_id = $field_handler->getHandlerId();
		else 

			// set an existing handler id value
			$handler_id = $unbound;

		if ( ! count( $field_handler->getProperty( PROPERTY_FORM_IDENTIFIER ) ) )

			// set the configuration file property 
			$field_handler->set_property( PROPERTY_FORM_IDENTIFIER, $identifier );

		if (
			$edition &&
			! count( $field_handler->getProperty( PROPERTY_EDITION ) )
		)

			// set the configuration file property 
			$field_handler->set_property(PROPERTY_EDITION, $edition);
			
		// set the form identifier context
		$store_context[PROPERTY_FORM_IDENTIFIER] =
			$field_handler->getProperty( PROPERTY_FORM_IDENTIFIER )
		;

		// set the edition context
		$store_context[PROPERTY_EDITION] =
			$field_handler->getProperty( PROPERTY_EDITION )
		;

		// get the configuration
		$config = $field_handler->get_config();

		// set the configuration file property 
		$field_handler->set_property( PROPERTY_CONFIGURATION_FILE, $file );

		$match = preg_match(
			'/([^\/\?]+)(\?)?([^?\/]+-[0-9]*)?$/',
			$_SERVER['REQUEST_URI'],
			$matches
		);

		if ( $match )

			$entity = $matches[1];

		// check the form identifier property of the current field handler
		else if ($field_handler->getProperty(PROPERTY_FORM_IDENTIFIER) != '')
	
			$entity =
				PREFIX_ROOT.
				$field_handler->getProperty(PROPERTY_FORM_IDENTIFIER)
			;
		else 

			$entity = $_SERVER['REQUEST_URI'];

		// get attributes
		$attributes = $class_data_fetcher::getAttributes( $entity );

		if (
			is_object( $attributes ) &&
			! empty( $attributes->{ROW_PARENT_HUB} )
		)

			// set the configuration file property 
			$field_handler->set_property(
				PROPERTY_PARENT,
				$attributes->{ROW_PARENT_HUB}
			);
		else

			// set the configuration file property 
			$field_handler->set_property(PROPERTY_PARENT, ROUTE_UNDEFINED);

		if ( ! empty( $config[PROPERTY_LAYOUT] ) )

			// set the configuration file property 
			$field_handler->set_property(
				PROPERTY_LAYOUT,
				$config[PROPERTY_LAYOUT]
			);

		// loop on fields
		foreach ( $config[PROPERTY_FIELDS] as $index => $field_attributes )

			// set the names
			$names .= $field_attributes[HTML_ATTRIBUTE_NAME];

		$signature = md5($names. ( $edition ? PROPERTY_EDITION : '' ) );

		// set the hash property
		$field_handler->set_property(
			PROPERTY_SIGNATURE,
			$signature
		);

		// set the signature of the field handler
		$store_signature[$handler_id] = $signature;

		// return a field handler
		return $field_handler;
	}

	/**
    * Obfuscate errors on fields
    * 
    * @param	object		$field		representing a field
    * @param	mixed		$data		containing field values
    * @param	string		$notice		containing a notice message
    * @param	string		$default	containing default values
    * @param	integer 	$position	representing a position
    * @param	integer		$handler_id	representing a field handler
    * @return 	FALSE
    */	  
	public static function obfuscate_error(
		$field,
		$data,
		$notice = null,
		$default = null,
		$position = null,
		$handler_id = FORM_ORDINARY
	)
	{
		$informant = NULL;

		$field_name = $field->getProperty(PROPERTY_NAME);

		// check the control dashboard
		if ($field_name == 'international_codes' && isset($_SESSION['fm'][$handler_id]))

			$dumper = new dumper(
				'f ie l d H a n d l e r',
				'o b f u s c a t e _ e r r o r ( )',
				array(
					"handler id",
					$handler_id,
					"field name:",
					$field->id,
					"field:",
					$field,
					"data:",
					$data,
					"control dashboard after obfuscating errors",
					$_SESSION['fm'][$handler_id]['str'][0]['e']
				)
			);

		// get the control dashboard
		$control_dashboard = &self::get_control_dashboard($position, $handler_id, $informant);

		// get the current field type
		$field_type = $field->get_type();

		$dumper = new dumper(
			'f ie l d H a n d l e r',
			'o b f u s c a t e _ e r r o r ( )',
			array(
				"field type:",
				$field_type
			)
		);

		if (
			!isset($control_dashboard[$field_name]) ||
			!is_array($control_dashboard[$field_name])
		)
			$control_dashboard[$field_name] = array();

		switch ($field_type)
		{
			case FIELD_TYPE_CHECKBOX:

				foreach ($data as $i)
					if (!array_key_exists($i, $default)) 
						$control_dashboard[$field_name][ERROR_UNKNOWN_VALUE] = $notice;
					else if (isset($control_dashboard[$field_name][ERROR_UNKNOWN_VALUE]))
						unset($control_dashboard[$field_name][ERROR_UNKNOWN_VALUE]);					

					break;

			case FIELD_TYPE_FILE:

				if ($data['error'] != UPLOAD_ERR_OK) 

					$control_dashboard[$field_name][ERROR_TRANSFER_FAILED] = FORM_UPLOAD_ERROR;

				else if (isset($control_dashboard[$field_name][ERROR_TRANSFER_FAILED]))

					unset($control_dashboard[$field_name][ERROR_TRANSFER_FAILED]);

				if ($data['error'] == UPLOAD_ERR_INI_SIZE)

					$control_dashboard[$field_name][ERROR_MAX_SIZE_REACHED] = FORM_UPLOAD_MAX_SIZE;

				else if (isset($control_dashboard[$field_name][ERROR_MAX_SIZE_REACHED]))

					unset($control_dashboard[$field_name][ERROR_MAX_SIZE_REACHED]);
				
				if ($default != null && !in_array($data['ext'], explode(',', $default))) 

					$control_dashboard[$field_name][ERROR_WRONG_MIME_TYPE] = FORM_UPLOAD_BAD_EXT;

				else if (isset($control_dashboard[$field_name][ERROR_WRONG_MIME_TYPE]))

					unset($control_dashboard[$field_name][ERROR_WRONG_MIME_TYPE]);

					break;

			case FIELD_TYPE_MULTIPLE:

				foreach ($data as $i)
					if (!array_key_exists($i, $default))
						$control_dashboard[$field_name][ERROR_UNKNOWN_VALUE] = $notice;
					else if (isset($control_dashboard[$field_name][ERROR_UNKNOWN_VALUE]))
						unset($control_dashboard[$field_name][ERROR_UNKNOWN_VALUE]);					

					break;

			case FIELD_TYPE_RADIO:

					if (!array_key_exists($data, $default))
						$control_dashboard[$field_name][ERROR_UNKNOWN_VALUE] = $notice;
					else
						unset($control_dashboard[$field_name][ERROR_UNKNOWN_VALUE]);					

					break;

			case FIELD_TYPE_SELECT:

				if (!array_key_exists($data, $default))
					$control_dashboard[$field_name][ERROR_UNKNOWN_VALUE] = $notice;
				else if (isset($control_dashboard[$field_name][ERROR_UNKNOWN_VALUE]))
					unset($control_dashboard[$field_name][ERROR_UNKNOWN_VALUE]);				
		
					break;

			case FIELD_TYPE_TEXT:

			default:

				if ($data === FALSE)
					$control_dashboard[$field_name][ERROR_UNKNOWN_VALUE] = $notice;
				else if (isset($control_dashboard[$field_name][ERROR_UNKNOWN_VALUE]))
					unset($control_dashboard[$field_name][ERROR_UNKNOWN_VALUE]);

					break;
		}

		return FALSE;
	}

	/**
    * Clean the filters of a field
    * 
    * @param	string	$field_name	containing a field name
	* @param	integer	$position	representing a position in the data submission process
    * @param	integer	$handler_id	representing a handler
    * @return 	boolean	indicating if the method ran smoothly
    */	    
	public static function remove_field_filters($field_name, $position = null, $handler_id = FORM_ORDINARY)
	{
		$control_dashboard = &self::get_control_dashboard($position, $handler_id);

		// check field filters removal before unsetting persistent container
		$dumper = new dumper(
			'f i e l d H a n d l e r',
			'r e m o v e _ f i e l d _ f i l t e r s ( )',
			array(
				"the filters before removal for '$field_name' are:",
				$control_dashboard[$field_name][AFFORDANCE_APPLY_FILTERS]
			)
		);

		if (isset($control_dashboard[$field_name][AFFORDANCE_APPLY_FILTERS]))
			unset($control_dashboard[$field_name][AFFORDANCE_APPLY_FILTERS]);

		// check field filters removal after unsetting persistent container
		if (
			isset($field_name) &&
			isset($control_dashboard[$field_name]) &&
			isset($control_dashboard[$field_name][AFFORDANCE_APPLY_FILTERS])
		)
			$dumper = new dumper(
				'f i e l d H a n d l e r',
				'r e m o v e _ f i e l d _ f i l t e r s ( )',
				array(
					"the filters after removal for '$field_name' are:",
					$control_dashboard[$field_name][AFFORDANCE_APPLY_FILTERS]
				)
			);

		if (!isset($control_dashboard[$field_name][AFFORDANCE_APPLY_FILTERS]))
			return TRUE;
		else
			return FALSE;
	}

	/**
    * Remove a handler
    * 
    * @param	integer		$handler_id		handler
    * @param	mixed		$informant		informant
    * @return 	boolean	indicating if the method ran smoothly
    */	    
	public static function remove_handler($handler_id, $informant = NULL)
	{
		// get the current handlers
		$handlers = &self::getHandlerStatus($informant);

		// check the handlers
		if (
			isset($handlers) &&
			is_array($handlers) &&
			count($handlers) != 0 &&
			!empty($handlers[$handler_id]) 
		)
			unset($handlers[$handler_id]);
	}

	/**
    * Save field values
    *
    * @see	self::saveFieldValues
    */
	public static function save_field_values(
		$field_values,
		$position = NULL,
		$position_instance = NULL,
		$handler_id = FORM_ORDINARY,
		$informant = NULL,
		$storage_model = STORE_SESSION
	)
	{
		return self::saveFieldValues(
			$field_values,
			$position,
			$position_instance,
			$handler_id,
			$informant,
			$storage_model
		);
	}

	/**
    * Save field values
    *
    * @param	array 		$field_values		field values
    * @param	integer		$position			position in the data submission process
    * @param	integer		$position_instance	position instance
    * @param	integer		$handler_id			handler
    * @param	mixed		$informant			informant
    * @param	integer		$storage_model		model of storage
    * @return 	object		field handler
    */
	public static function saveFieldValues(
		$field_values,
		$position = NULL,
		$position_instance = NULL,
		$handler_id = FORM_ORDINARY,
		$informant = NULL,
		$storage_model = STORE_SESSION
	)
	{
		$class_dumper = self::getDumperClass();

		$store = &self::getStore( $position, $handler_id );

		// check if the field value argument has been passed as an array
		if ( is_array( $field_values ) )
		{
			// check if the field values argument contains more than one item			
			if ( count( $field_values ) > 1 )
			{
				// check the position instance
				if ( (int) $position_instance == 0 )

					unset( $position_instance );

				// set the field values one by one
				while ( list( $name, $value ) = each( $field_values ) )
				{
					// get the field attributes
					$field_attributes = &self::get_field_attributes(
						$name,
						$position,
						$handler_id
					);

					// check the field type
					if (
						$field_attributes[HTML_ATTRIBUTE_TYPE] ==
							FIELD_TYPE_EMAIL ||
						$field_attributes[HTML_ATTRIBUTE_TYPE] ==
							FIELD_TYPE_TEXT
					)

						$value = self::escape_string( trim( $value ) );

					// check the hash affordance
					if ( isset( $field_attributes[AFFORDANCE_HASH] ) )
					
						// set the value hash
						$store[SESSION_STORE_FIELD][SESSION_STORE_VALUE][$name] =
							call_user_func(
								$field_attributes[AFFORDANCE_HASH],
								$value
							)
						;
					else

						// set the value
						$store[SESSION_STORE_FIELD][SESSION_STORE_VALUE]
							[$name] = $value
						;
				}

				// check if multiple instances of the same position
				// are about to be submitted
				if ( isset( $position_instance ) )

					$store
						[SESSION_STORE_FIELD]
							[SESSION_STORE_VALUE]
								[(int)$position_instance] =
									$field_values
					;
			}
			// check if the field values argument contains one single item
			else if ( count( $field_values ) == 1 )
			{
				// get the field name and value
				list( $field_name, $field_value ) = each( $field_values );

				// get the field attributes
				$field_attributes = &self::get_field_attributes(
					$field_name,
					$position,
					$handler_id
				);

				// check the field type
				if (
					in_array(
						strtolower( $field_attributes[HTML_ATTRIBUTE_TYPE] ),
						array(
							FIELD_TYPE_EMAIL,
							FIELD_TYPE_TEXTAREA,
							FIELD_TYPE_TEXT
						)
					)
				)

					$field_value = self::escape_string( trim( $field_value ) );

				// check the hash affordance
				if (
					isset( $field_attributes[AFFORDANCE_HASH] ) &&
					(
						(
							$field_attributes[HTML_ATTRIBUTE_TYPE]
							!== ucfirst( FIELD_TYPE_PASSWORD )
						) ||
						! empty( $field_value )
					)
				)

					// set the value hash
					$store
						[SESSION_STORE_FIELD]
							[SESSION_STORE_VALUE]
								[$field_name] =
						call_user_func(
							$field_attributes[AFFORDANCE_HASH],
							$field_value
						)
					;
				else

					// set the value
					$store
						[SESSION_STORE_FIELD]
							[SESSION_STORE_VALUE]
								[$field_name] =
						$field_value
					;

				// check if multiple instances of the same position are about to be submitted
				if ( isset( $position_instance ) )

					$store
						[SESSION_STORE_FIELD]
							[SESSION_STORE_VALUE]
								[(int)$position_instance][$field_name] =
						$field_value
					;
			}
		}		
	}

	/**
    * Set field attributes
    *
    * @param	array		$attributes	containing field attributes
    * @param	integer 	$position	representing a position
    * @param	integer		$handler_id	representing a field handler
    * @return 	nothing
    */
	public static function set_field_attributes($attributes, $position = null, $handler_id = FORM_ORDINARY)
	{
		$field_attributes = &self::get_field_attributes(null, $position, $handler_id);

		if (is_array($attributes))
			$field_attributes = $attributes;
	}

	/**
    * Set coordinates in session
    *
    * @param	array		$coordinates	containing coordinates
    * @param	integer		$handler_id		representing a handler
    * @return 	nothing
    */
	public static function set_persistent_coordinates($coordinates, $handler_id)
	{
		$persistent_coordinates = &self::get_persistent_coordinates($handler_id);

		if (
			is_array($coordinates) &&
			count($coordinates) > 0
		)
			$persistent_coordinates = $coordinates;
	}

	/**
    * Set persistent position
    *
    * @param	mixed 		$position		representing a position
    * @param	integer		$position_type	representing a type of position
    * @param	integer		$handler_id		representing a handler
    * @return 	nothing
    */
	public static function set_persistent_position($position, $position_type = COORDINATES_CURRENT_POSITION, $handler_id = FORM_ORDINARY)
	{
		$persistent_position = &self::get_persistent_position($position_type, $handler_id);

		if (is_integer($position))
			$persistent_position = $position;
	}

	/**
    * Set a persistent compass
    *
    * @param	array		$compass	containing a roadmap
    * @param	integer		$handler_id	representing a handler
    * @param	mixed		$informant	representing a informant
    * @return 	nothing
    */
	public static function setPersistentCompass($compass, $handler_id = FORM_ORDINARY, $informant = NULL)
	{
		// get a persistent roadmap
		$persistent_compass = &self::getPersistentCompass($handler_id, $informant);

		// check the roadmap
		if (
			isset($compass) &&
			is_array($compass) &&
			count($compass) != 0
		)

			// set a persistent compass
			$persistent_compass = $compass;
	}

	/**
    * Set the status of a persistent handler
    *
    * @param	string		$status			status
    * @param	integer		$handler_id		handler identifier
    * @param	mixed		$informant		informant
    * @return 	nothing
    */
	public static function setPersistentHandlerStatus(
		$status = HANDLER_STATUS_ACTIVE,
		$handler_id = FORM_ORDINARY,
		$informant = NULL
	)
	{
		// get a persistent handler
		$persistent_handler = &self::getPersistentHandlerStatus($handler_id, $informant);

		// set the persistent handler as active
		$persistent_handler[$handler_id] = $status;
	}

	/**
    * Set the persistent submission status
    *
    * @param	integer		$handler_id		handler identifier
    * @param	string		$status			submission status
    * @param	integer		$position		position
    * @return 	nothing
    */
	public static function setPersistentSubmissionStatus(
		$handler_id,
		$status = SESSION_STATUS_DATA_SUBMITTED,
		$position = NULL
	)
	{	
		// set the form manager class name
		$class_form_manager = self::getFormManagerClass();

		// get the control dashboard
		$control_dashboard = &self::getControlDashboard(
			$handler_id,
			$position
		);

		if ( isset( $control_dashboard[SESSION_STATUS] ) )

			$control_dashboard[SESSION_STATUS] = $status;

		// call the mirror method of the Form Manager class
		$class_form_manager::setPersistentSubmissionStatus(
			$handler_id,
			$status
		);		
	}
}

/**
* Field button class
*
* @package  sefi
*/
class Field_Button extends Html_Input
{
    /**
	* Construct a new instance of Field_Button
	* 
	* @param   array   $properties properties
	* @return  object  
	*/
    public function __construct($properties)
    {
        parent::__construct($properties);
    }	
}

/**
* Field checkbox class
*
* @package  sefi
*/
class Field_Checkbox extends Html_Input
{
    /**
	* Construct a new instance of Field_Checkbox
	* 
	* @param   array   $properties properties
	* @return  object  
	*/
    public function __construct($properties)
    {
        parent::__construct($properties);
    }

	/**
    * Check data submitted through the field
    *
    * @param	string 	$data	containing data to be checked
    * @return 	string	containing data
    */
	public function check($data)
	{
		$class_field_handler = CLASS_FIELD_HANDLER;

		$options = $this->getProperty( PROPERTY_OPTIONS );
		
		// set a copy of the data argument
		$ascendant_data = $data;

		$data = $this->checkOptions($data);

		$class_field_handler::obfuscate_error($this, $ascendant_data, FORM_VALUE_BAD, $options);

		$this->clear_errors();

		return $data;
	}
}

/**
* Field email class
*
* @package  sefi
*/
class Field_Email extends Html_Input
{
    /**
	* Construct a new instance of Field_Email
	* 
	* @param   array   $properties properties
	* @return  object  
	*/
    public function __construct($properties)
    {
        parent::__construct($properties);
    }

	/**
    * Check data submitted through the field
    *
    * @param	string 	$data	containing data to be checked
    * @return 	string	containing data
    */	
	public function check( $data )
	{
		$class_field_handler = self::getFieldHandlerClass();

		// check email 
		$data = self::check_email($data);

		// check data
		if ( $data === FALSE )

			// set error
			$this->data_validation_failure = FORM_URL_BAD;

		// obfuscate errors
		$class_field_handler::obfuscate_error(
			$this,
			$data,
			FORM_EMAIL_BAD
		);

		$this->clear_errors();

		return $data;
	}
}


/**
* Field file class
*
* @package  sefi
*/
class Field_File extends Html_Input
{
	/**
	* Construct a new instance of Field_File
	* 
	* @param   array   $properties properties
	* @return  object  
	*/
    public function __construct($properties)
    {
        parent::__construct($properties);
	}

	/**
    * Check data submitted through the field
    *
    * @param	string 	$data	containing data to be checked
    * @return 	string	containing data
    */
	public function check($data)
	{
		$class_field_handler = CLASS_FIELD_HANDLER;

		$options = $this->getProperty( PROPERTY_OPTIONS );

		$data = $this->checkFile($data);

		$class_field_handler::obfuscate_error($this, $data, FORM_EMAIL_BAD, $options);

		$this->clear_errors();

		return $data;
	}
}

/**
* Field hidden class
*
* @package  sefi
*/
class Field_Hidden extends Html_Input
{
	/**
	* Construct a new instance of Field_Hidden
	* 
	* @param   array   $properties properties
	* @return  object  
	*/
    public function __construct($properties)
    {
        parent::__construct($properties);
	}
}

/**
* Field radio class
*
* @package  sefi
*/
class Field_Radio extends Html_Input
{
    /**
    * Construct a new instance of Field_Radio
    * 
    * @param   array   $properties properties
    * @return  object  
    */
    public function __construct($properties)
    {
        return parent::__construct($properties);
    }

	/**
    * Check data submitted through the field
    *
    * @param	string 	$data	containing data to be checked
    * @return 	string	containing data
    */
	public function check( $data )
	{
		$class_field_handler = CLASS_FIELD_HANDLER;

		$options = $this->getProperty( PROPERTY_OPTIONS );
	
		$data = $this->checkOptions($data);

		$class_field_handler::obfuscate_error(
			$this,
			$data,
			FORM_VALUE_BAD,
			$options
		);

		$this->clear_errors();

		return $data;
	}
}

/**
* Field password class
*
* @package  sefi
*/
class Field_Password extends Html_Input
{
    /**
    * Construct a new instance of Field_Password
    * 
    * @param   array   $properties properties
    * @return  object  
    */
    public function __construct($properties)
    {
        return parent::__construct($properties);
    }	
}

/**
* Field select class
*
* @package  sefi
*/
class Field_Select extends Html_Select
{
    /**
    * Construct a new instance of Field_Select
    * 
    * @param   array   $properties properties
    * @return  object  
    */
    public function __construct($properties)
    {
        return parent::__construct($properties);
    }

	/**
    * Check data submitted through the field
    *
    * @param	string 	$data	containing data to be checked
    * @return 	string	containing data
    */
	public function check( $data )
	{
		$class_field_handler = CLASS_FIELD_HANDLER;

		$options = $this->getProperty( PROPERTY_OPTIONS );

		if ($this->getProperty(PROPERTY_NAME) == 'field name')

			// check the control dashboard
			$dumper = new dumper(
				'f o r m _ f i e l d S e l e c t',
				'c h e c k ( )',
				array(
					"field name:",
					$this->getProperty(PROPERTY_NAME),
					"control dashboard before obfuscating errors",
					$_SESSION['fm'][2]['str'][0]['e']
				)
			);

		$data = $this->checkOptions($data);

		$class_field_handler::obfuscate_error($this, $data, FORM_VALUE_BAD, $options);

		$this->clear_errors();

		if ($this->getProperty(PROPERTY_NAME) == 'field name')
		
			// check the control dashboard
			$dumper = new dumper(
				'f o r m _ f i e l d S e l e c t',
				'c h e c k ( )',
				array(
					"field name:",
					$this->getProperty(PROPERTY_NAME),
					"control dashboard after obfuscating errors",
					$_SESSION['fm'][2]['str'][0]['e']
				)
			);

		return $data;
	}
}

/**
* Field submit class
*
* @package  sefi
*/
class Field_Submit extends Html_Input
{
    /**
    * Construct a new instance of Field_Submit
    * 
    * @param   array   $properties properties
    * @return  object  
    */
    public function __construct($properties)
    {
        return parent::__construct($properties);
    }	
}

/**
* Field text class
*
* @package  sefi
*/
class Field_Text extends Html_Input
{
    /**
    * Construct a new instance of Field_Text
    * 
    * @param   array   $properties properties
    * @return  object  
    */
    public function __construct($properties)
    {
        return parent::__construct($properties);
    }

	/**
    * Check data submitted through the field
    *
    * @param	string 	$data	containing data to be checked
    * @return 	string	containing data
    */
	public function check($data)
	{
		$class_field_handler = CLASS_FIELD_HANDLER;

		$data_validation_failure = &$this->getProperty(
			PROPERTY_DATA_VALIDATION_FAILURE
		);

		// get field filters
		$filters = &$class_field_handler::get_field_filters($this->id);		
		$valid_input = TRUE;

		if (isset($filters) && is_array($filters) && count($filters) > 0)

			while (list($filter, $filter_applied) = each($filters))
			{
				switch ($filter)
				{
					case FILTER_NUMERIC:

						if (!is_numeric($data))
							$data_validation_failure = ERROR_WRONG_INPUT_TYPE;
						else
							$filters[$filter] = TRUE; 

							break;
				}
			}

		reset($filters);

		$class_field_handler::obfuscate_error($this, $data, ERROR_WRONG_INPUT_TYPE);

		$this->clear_errors();

		return $data;
	}
}

/**
* Field textarea class
*
* @package  sefi
*/
class Field_Textarea extends Html_Textarea
{
    /**
    * Construct a new instance of Field_Textarea
    * 
    * @param   array   $properties properties
    * @return  object  
    */
    public function __construct($properties)
    {
        return parent::__construct($properties);
    }	
}
?>