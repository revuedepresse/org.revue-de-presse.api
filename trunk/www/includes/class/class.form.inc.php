<?php

/**
* Form class
*
* Class for handling a form
* @package  sefi
*/
class Form extends Toolbox
{
	protected $action;
	protected $children = array();
	protected $data = array();
	protected $data_submission = false;
	protected $data_validation_failure = false;
	protected $method;

	/**
	* Construct a new instance of Form
	* 
	* @param	$method	HTTP method used to submit the form
	* @param	$action	URL to be called
	* @return 	object	form
	*/
	public function __construct(
		$method = PROTOCOL_HTTP_METHOD_POST,
		$action = NULL
	)
	{
		$children = &$this->getProperty( PROPERTY_CHILDREN );

		$data_submission = &$this->getProperty( PROPERTY_DATA_SUBMISSION );

		// for backward compatibility
		$this->submit = &$data_submission;

		$this->item = &$children;
		
		if ( ! $action )
		
				isset( $_SERVER['REQUEST_URI'] )
			?
				$this->setAProperty( PROPERTY_ACTION, $_SERVER['REQUEST_URI'] )
			:
				''
			;

		else

			// set the action member attribute		
			$this->setAProperty(
				getVariableName( $action, get_defined_vars() ),
				$action
			);

		// set the method member attribute
		$this->setAProperty(
			getVariableName( $method, get_defined_vars() ),
			$method
		);

		// set the data member attribute
		$this->setAProperty(
			PROPERTY_DATA,
			$this->getSubmittedData( $method )
		);		
	}

	/**
	* Get an ancestor property
	* 
	* @param	$name		name
	* @return	mixed
	*/
	public function &getAProperty( $name )
	{
		$property = &$this->$name;
		
		return $property;
	}

	/**
	* Alias to getDataStatus method
	*
	* @param	mixed	$informant	informant
	* @param	mixed	$assert		assertion flag 
	* @return	mixed
	*/
	public function &getData( $informant = NULL, $assert = FALSE )
	{
		$data = &$this->getDataStatus( $informant , $assert );

		return $data;
	}

	/**
	* Get data
	*
	* @param	mixed	$informant	informant
	* @param	mixed	$assert		assertion flag 
	* @return	mixed
	*/
	public function &getDataStatus( $informant = NULL, $assert = FALSE )
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$data = &$this->getAProperty( PROPERTY_DATA );

		$data_submission = $this->getAProperty(
			PROPERTY_DATA_SUBMISSION
		);

		$data_validation_failure = $this->getAProperty(
			PROPERTY_DATA_VALIDATION_FAILURE
		);

		$data_submitted = &$this->getSubmittedData();

		if ( $assert )
		{
			$assertions_batch = array(
				array(
					'! count($data)',
					'$data_submission',
					'! $data_validation_failure'
				)
			);			

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
					'field handler: ',
					$this,
	
					'submitted data: ',
					$data_submitted,
	
					'data submission status ',		
					$data_submission ? 'TRUE' : 'FALSE',
	
					'data validation failure: ',		
					$data_validation_failure ? 'TRUE' : 'FALSE',
				)
			);
		}

		if (
			
			(
				(
					! ( $submitted_data_count_prior = count( $data ) ) ||
					( $submitted_data_count = count( $data_submitted ) ) && 
					( $submitted_data_count > $submitted_data_count_prior )
				) &&
				$data_submission &&
				! $data_validation_failure
			)
		)
		
			$data = $data_submitted;

		return $data;
	}

	/**
	* Alias to getAProperty method
	* 
	* @param	$name		name
	* @return	mixed
	*/
	public function &getProperty($name)
	{
		$property = &$this->getAProperty($name);
	
		return $property;
	}

	/**
	* Get submitted data
	*
	* @param	string	$method		method
	* @param	mixed	$informant	informant
	* @param	boolean	$assert		assertion flag
	* @return	mixed	data
	*/
	public function &getSubmittedData(
		$method = NULL,
		$informant = NULL,
		$assert = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		if ( is_null( $method ) )
		
			$method = $this->getAProperty( PROPERTY_METHOD );

		if (
			$method != PROTOCOL_HTTP_METHOD_POST &&
			$method != PROTOCOL_HTTP_METHOD_GET
		)
		
			$method = PROTOCOL_HTTP_METHOD_POST;

		$data_submitted = &$GLOBALS['_'.strtoupper( $method )];

		$class_dumper::log(
			__METHOD__,
			array(
				'submitted data: ',
				$data_submitted
			),
			DEBUGGING_FIELD_HANDLING && $assert
		);

		return $data_submitted;
	}

	/**
	* Add a new element to the form
	* 
	* @param	string		$name		name
	* @param	string		$type		type
	* @param	string		$label		label
	* @param	mixed		$options	options
	* @param	string		$default	default value
	* @param	mixed		$informant	informant
	* @param	boolean		$assert		assertion flag
	* @return	object		form
	*/
	public function add(
		$name,
		$type,
		$label = NULL,
		$options = NULL,
		$default = NULL,
		$informant = NULL,
		$assert = NULL
	)
	{
		$class_dumper = self::getDumperClass();

		$child_already_born = FALSE;

		$children = &$this->getAProperty( PROPERTY_CHILDREN );

		$index =
				count( $children ) === 1 &&
				$name == PROPERTY_AFFORDANCE
			?
				1
			:
				count( $children ) + 1
		;

		$child = $this->addComponent(
			array(
				PROPERTY_DEFAULT => $default,
				PROPERTY_INDEX => $index,
				PROPERTY_LABEL => $label,
				PROPERTY_NAME => $name,
				PROPERTY_OPTIONS => $options,
				PROPERTY_TYPE => $type,
			),
			$informant,
			$assert
		);

		$child_signature = md5( serialize( $child ) );

		// check there is no component already registered for this name
		// or do nothing for hidden input declared twice
		if (
			! isset( $children[$name] ) ||
			(
				$child_already_born =
					( $child_signature == md5( serialize( $children[$name] ) ) )
			)
		)
		{
			if ( ! $child_already_born )
	
				$children[$name] = $child;

			return $this;
		}
		else
		
			throw new Exception( EXCEPTION_INVALID_FIELD_TYPE );
	}

	/**
	* Add a component
	* 
	* @param	mixed		$properties		properties
	* @param	mixed		$informant		informant
	* @param	boolean		$assert			assertion flag
	* @return	object	component
	*/
	public function addComponent(
		$properties,
		$informant = NULL,
		$assert = NULL		
	)
	{
		$class_dumper = self::getDumperClass();

		$data = &$this->getSubmittedData( NULL, $informant, $assert );

		$field_handler_data = &$this->getAProperty( PROPERTY_DATA );

		$data_submission = &$this->getAProperty( PROPERTY_DATA_SUBMISSION );

		$name = $properties[PROPERTY_NAME];

		$type = $properties[PROPERTY_TYPE];

		if ( ! is_array( $field_handler_data ) )

			$field_handler_data	= array();	 

		if ( ! empty( $type ) )
		{
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

			$mandatory_value = ( substr( $type, -1, 1 ) == SUFFIX_MANDATORY );
		}
		else if ( ! empty( $name ) )

			throw new Exception( EXCEPTION_INVALID_FILE_TYPE.': '.$name );

		$class_dumper::log(
			__METHOD__,
			array(
				'component class: ',
				$component_class,
				'constructor exists for this component? ',
				method_exists( $component_class, '__construct' )
			),
			$assert &&
			DEBUGGING_FIELD_HANDLING
		);

		if ( empty( $name ) || ! class_exists( $component_class ) )

			throw new Exception (
				EXCEPTION_INVALID_HTML_TAG.': '.
				print_r( func_get_args() ), TRUE
			);

		else if ( method_exists( $component_class, '__construct' ) )
		{
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

				$field_label = constant(
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
			
				$field_label = '';

			$properties[PROPERTY_LABEL] = $field_label;
			$properties[PROPERTY_MANDATORY] = $mandatory_value;
			$properties[PROPERTY_TYPE] = $component_class;

			$component = new $component_class( $properties );

			// check the current field value
			if (
				isset( $data[$name] ) ||
				
				// file field case
				strtolower( $type ) == FIELD_TYPE_FILE &&
				isset( $_FILES[$name] ) &&
				is_array( $_FILES[$name] ) &&
				! empty( $_FILES[$name]['name'] )
			)
			{
				$this->setAProperty( PROPERTY_DATA_SUBMISSION, TRUE );
	
				if ( isset( $data[$name] ) && ! is_array( $data[$name] ) )
				
					$data[$name] = trim( $data[$name] );

				$class_dumper::log(
					__METHOD__,
					array(
						'Does some checking method exists for this component? ',
						method_exists( $component_class, 'check' ) &&
						(
							isset( $data[$name] ) &&
							$data[$name] != NULL
						) ||
						! empty( $_FILES[$name]['name'] )
					),
					$assert &&
					DEBUGGING_FIELD_HANDLING
				);

				// check the submitted data
				if (
					method_exists( $component_class, 'check' ) &&
					(
						isset( $data[$name] ) &&
						$data[$name] != NULL
					) ||
					! empty( $_FILES[$name]['name'] )
				)
				{
					if ( isset( $data[$name] ) )

						$field_data = $component->check( $data[$name] );
					else 

						$field_data = $component->check( $_FILES );

					if (
						$component->getProperty(
							PROPERTY_DATA_VALIDATION_FAILURE
						)
					)
	
						$this->setAProperty(
							PROPERTY_DATA_VALIDATION_FAILURE,
							TRUE
						);
					else
					{
	
						$data[$name] = $field_data;

						if ( ! isset( $field_handler_data[$name] ) )
					
							$field_handler_data[$name] = $field_data;
					}

					$class_dumper::log(
						__METHOD__,
						array(
							'component: ',
							$component,
							'component data validation failure: ',
							$component->getProperty(
								PROPERTY_DATA_VALIDATION_FAILURE
							),
							'field data: ',
							$field_data,
							'superglobal data: ',
							$data,
							'should an error be raised because of '.
							'some value missing for the current component?',
							$mandatory_value &&
							( ! isset( $data[$name] ) || $data[$name] == NULL ),
							'current field handler: ',
							$this,
							'field handler data:',
							$field_handler_data
						),
						$assert &&
						DEBUGGING_FIELD_HANDLING
						&& FALSE !==
						strpos( $name, TEST_CASE_FIELD_HANDLER_COMPONENT )
					);
				}
			}
		}

		$class_dumper::log(
			__METHOD__,
			array(
				'field handler data: ',
				$data,
				'current component name: ',
				$name,
				'error raised for current field: ',
				$mandatory_value &&
				( ! isset( $data[$name] ) || $data[$name] == NULL )				
			),
			$assert &&
			DEBUGGING_FIELD_HANDLING
			&& ( FALSE === strpos( $name, TEST_CASE_FIELD_HANDLER_COMPONENT ) )
		);

		// check whether a value is mandatory for the current component
		// and no value was provided
		if (
			$mandatory_value &&
			( ! isset( $data[$name] ) || $data[$name] == NULL )
		)
		{
			$component->setProperty(
				PROPERTY_DATA_VALIDATION_FAILURE,
				FORM_REQUIRED_FIELD
			);

			$this->setProperty(
				PROPERTY_DATA_VALIDATION_FAILURE,
				TRUE
			);
		}

		return $component;
	}

	/**
	* Link two fields
	* 
	* @param	string	$name		name
	* @param	string	$target		target
	* @param	boolean	$assert		assertion flag
	* @return	object	form
	*/
	public function link( $name, $target, $assert = FALSE )
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$class_test_case = $class_application::getTestCaseClass();

		// initialize the context of a test case 
		$context = array();

		$children = &$this->getAProperty( PROPERTY_CHILDREN );

		$data = $this->getAProperty( PROPERTY_DATA );

		$data_submission = $this->getAProperty( PROPERTY_DATA_SUBMISSION );

		if ( $assert )
		{
			$assertions_batch = array(
				array(
					'isset( $data[$name] )',
					'isset( $data[$target] )',
					'$data[$name] == $data[$target]'
				),
				array(
					'empty( $_POST )',
					'! isset( $_POST[$target] )',
					'! isset( $_POST[$name] )',
					'$_POST[$target] != $_POST[$name]'
				)			
			);

			$assertion =
				'('."\n\t".
					implode(
						ASSERTION_CONJUNCTION_AND,
						$assertions_batch[0]
					)."\n".
				')'."\n".
				trim( ASSERTION_CONJUNCTION_OR )."\n".
				'('."\n\t".
					implode(
						ASSERTION_CONJUNCTION_OR,
						$assertions_batch[1]
					)."\n".
				')'
			;
		}

		/**
		*
		* context for test case of revision 561
		*
		* Revise field links controller
		*
		*/

		$context[PROPERTY_DATA_SUBMISSION] = $data_submission;

		$class_dumper::log(
			__METHOD__,
			array(
				'[data submission]',
				$data_submission
			)
		);

		if ( $data_submission )
		{
			if ( $assert )

				$class_dumper::assert(
					array(
						PROPERTY_EXPRESSION => $assertion,
						PROPERTY_EVALUATION => assert( $assertion )
					),
					array(
						
						'field handler: ',
						$this,
						
						'field name: ',
						$name,
						
						'target: ',
						$target,
						
						'data: ',
						$data,
						
						'target field value: ',
							isset( $data[$target] )
						?
							$data[$target]
						:
							PROPERTY_UNDEFINED,
						
						'linked field value: ',
							isset( $data[$name] )
						?
							$data[$name]
						:
							PROPERTY_UNDEFINED,

						'POST superglobal: ',
						$_POST
					)
				);

			// Set the data validation failure of the context
			$context[PROPERTY_DATA_VALIDATION_FAILURE] =
				! isset( $data[$name] ) ||
				! isset( $data[$target] ) ||
				$data[$name] != $data[$target]
			;

			if (
				! isset( $data[$name] ) ||
				! isset( $data[$target] ) ||
				$data[$name] != $data[$target]
			)
			{
				$children[$target]->setProperty(
					PROPERTY_DATA_VALIDATION_FAILURE,
					FORM_LINK_BAD
				);

				$this->setAProperty(
					PROPERTY_DATA_VALIDATION_FAILURE,
					TRUE
				);
			}
			else

				$children[$target]->setProperty(
					PROPERTY_DATA_VALIDATION_FAILURE,
					FALSE
				);

			unset( $children );
		}

		// Set the component property of the context 

		$context[PROPERTY_COMPONENT] = $this;

		$class_test_case::perform(
			DEBUGGING_FIELD_HANDLING_LINK_FIELDS_AT_DATA_SUBMISSION,
			$verbose_mode,
			$context
		);
		
		return $this;
	}

	/**
	* Set the value of an element
	* 
	* @param	$name	name
	* @param	$value	value
	* @return	object	form
	*/
	public function set( $name, $value )
	{
		$children = &$this->getProperty( PROPERTY_CHILDREN );

		if ( isset( $children[$name] ) )

			$children[$name]->{PROPERTY_DEFAULT} = $value;

		return $this;
	}	

	/**
	* Set the ancestor property
	* 
	* @param	mixed	$name	name or value
	* @param	mixed	$value	value
	* @return	nothing
	*/
	public function setAProperty( $name, $value )
	{
		$_value = &$this->getAProperty($name);

		$_value = $value;
	}
	
	/**
	* Alias to setAProperty method
	* 
	* @param	mixed	$name	name or value
	* @param	mixed	$value	value
	* @return	nothing
	*/
	public function setProperty($name, $value)
	{
		return $this->setAProperty($name, $value);
	}
}
