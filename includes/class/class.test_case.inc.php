<?php

/**
* Test_Case class
*
* Class for case testing 
* @package  sefi
*/
class Test_Case extends Application
{
	public static function __callStatic( $name, $arguments )
	{
		global $verbose_mode;

		$callback_parameters = array();
	
		switch ( $name )
		{
			case ACTION_PERFORM:

				// check if the test is active
				if (
					isset( $arguments[1] ) &&
					VERBOSE_MODE_STATUS_ENABLED === $arguments[1]
				)
				{
					$class_dumper = self::getDumperClass();
	
					$methods = get_class_methods( __CLASS__ );
					
					$backtrace = debug_backtrace( TRUE );
			
					$trace = $backtrace[2];

					if ( isset( $trace[PROPERTY_CLASS] ) )
					
						$class_name = $trace[PROPERTY_CLASS];

					if ( isset( $trace[PROPERTY_FUNCTION] ) )
					
						$method_name = $trace[PROPERTY_FUNCTION];

					if (
						isset( $trace[PROPERTY_CLASS] ) &&
						strpos( $trace[PROPERTY_CLASS], '\\' )
					)
					{
						list( $namespace, $class_name ) =
							explode(
								'\\',
								$trace[PROPERTY_CLASS]
							)
						;
					}

					if (
						isset( $class_name ) && 
						isset( $method_name ) 
					)
					{
						$constant_name = reverse_constant(
							$class_name,
							strtoupper( substr( PREFIX_CLASS, 0, -1 ) ).'_'
						);

						// get a method of the current class
						$test_method =
							ENTITY_METHOD.'__'.
							(
								isset( $namespace )
							?
								$namespace.'__'
							:
								''
							).
								strtolower( $constant_name ).'__'.
									$method_name
						;

						if ( in_array( $test_method, $methods )  )
	
							$callback_parameters = call_user_func_array(
								array(
    								__CLASS__,
								    $test_method
								),
								$arguments 
							);
					}
				}

					break;

			default:
			
				$callback_parameters = parent::__callStatic( $name, $arguments );
		}

		return $callback_parameters;
	}

	/**
	* Dump a persistent store
	*
	* @param	string	$from		calling method
	* @param	mixed	$type		store type
	* @return	mixed
	*/
	public static function dump_persistent_store( $from, $type = NULL )
	{
		global $verbose_mode;

		// set the dumper class name
		$class_dumper = self::getDumperClass();

		// set the form manager class name
		$class_form_manager = self::getFormManagerClass();

		$default_type = STORE_DASHBOARD;

		if ( is_null( $type ) )

			$type = $default_type;
		
		switch ( $type )
		{
			case $default_type:

				$class_dumper::log(
					$from,
					array(
						'persistent field handler: ',
						$class_form_manager::getPersistentActiveStore()
					),
					$verbose_mode
				);
	
					break;
		}
	}

	/**
	* Test the Form-Manager class constructor 
	*
	* @param	mixed	$test_case	test case
	* @param	boolean	$active		active
	* @param	mixed	$context	context
	* @return	mixed
	*/
	public static function method__form_manager____construct(
		$test_case = NULL,
		$active = FALSE,
		$context = NULL		
	)
	{
		if ( ! $active || ! DEBUGGING_FIELD_ERROR_HANDLING )
		
			return;

		switch ( $test_case )
		{
			case DEBUGGING_FIELD_ERROR_HANDLING:
	
			if ( empty( $_POST ) )

				self::dump_persistent_store( __METHOD__.' '.$context );

					break;
		}
	}

	/**
	* Test the route method of the Interceptor class 
	*
	* @param	mixed	$test_case	test case
	* @param	boolean	$active		active
	* @param	mixed	$context	context
	* @return	mixed
	*/
	public static function method__interceptor__route(
		$test_case = NULL,
		$active = FALSE,
		$context = NULL
	)
	{
		global $verbose_mode;

		// set the field handler class name
		$class_field_handler = self::getFieldHandlerClass();

        // set the lock class name
        $class_lock = self::getLockClass();

		if ( ! $active || DEBUGGING_FIELD_ERROR_HANDLING )
		
			return;

		switch ( $test_case )
		{
			case DEBUGGING_FIELD_ERROR_HANDLING:
	
				if (
					$verbose_mode &&
					DEBUGGING_FIELD_ERROR_HANDLING &&
					( TRUE === $class_lock::lockedEntity( $class_field_handler ) )
				)

					self::dump_persistent_store( __METHOD__ );
			
					break;
		}
	}

	/**
	* Test the load_view method of the sefi/Application class 
	*
	* @param	mixed	$test_case	test case
	* @param	boolean	$active		active
	* @param	mixed	$context	context
	* @return	mixed
	*/
	public static function method__sefi__application__load_view(
		$test_case = NULL,
		$active = FALSE,
		$context = NULL
	)
	{
		global $verbose_mode;

		// set the dumper class name
		$class_dumper = self::getDumperClass();

		// set the field handler class name
		$class_field_handler = self::getFieldHandlerClass();
		
		// set the lock class name
		$class_lock = self::getLockClass();
	
		if ( ! $active || DEBUGGING_FIELD_ERROR_HANDLING )
		
			return;

		switch ( $test_case )
		{
			case DEBUGGING_FIELD_ERROR_HANDLING:
	
			if (
				empty( $_POST ) &&
				TRUE === $class_lock::lockedEntity( $class_field_handler )
			)
			{
				self::dump_persistent_store( __METHOD__ );

				$class_dumper::log(
					__METHOD__,
					array(
						'field handler: ',  
						$context
					),
					$active,
					$class_lock::releaseEntity( $class_field_handler )
				);	
			}

					break;
		}
	}

	/**
	* Test the saveFieldHandler method o the serializer
	*
	* @param	mixed	$test_case	test case
	* @param	boolean	$active		active
	* @param	mixed	$context	context
	* @return	mixed
	*/
	public static function method__serializer__saveFieldHandler(
		$test_case = NULL,
		$active = FALSE,
		$context = NULL		
	)
	{
		// set the dumper class name
		$class_dumper = self::getDumperClass();

		if ( ! $active || ! DEBUGGING_FIELD_HANDLING_DEFAULT_FIELD_VALUES )
		
			return;

		switch ( $test_case )
		{
			case DEBUGGING_FIELD_HANDLING_DEFAULT_FIELD_VALUES:

				/**
				* extract the following variables
				*
				* $field_attributes	field attributes
				* $field_values		field values
				* $match 			value matching with pattern
				* $pattern			pattern used
				* 
				*/
				extract( $context );

				$class_dumper::log(
					__METHOD__,
					array(
						'matching value: ',
						$match,
						'pattern: ',
						$pattern,
						'matching field value: ',
						$field_values[$match],
						'field value per name attribute: ',
						$field_values[$field_attributes[HTML_ATTRIBUTE_NAME]],
					),
					$active,
					strpos(
						$field_values[$match],
						SHORTHAND_DATABASE
					) !== FALSE
					||
					strpos(
						$field_values[$field_attributes[HTML_ATTRIBUTE_NAME]],
						SHORTHAND_DATABASE
					) !== FALSE			
				);

					break;
		}		
	}

	/**
	* Test the constructor method of the View Builder class 
	*
	* @param	mixed	$test_case	test case
	* @param	boolean	$active		active
	* @param	mixed	$context	context
	* @return	mixed
	*/
	public static function method__view_builder____construct(
		$test_case = NULL,
		$active = FALSE,
		$context = NULL
	)
	{
		global $verbose_mode;

		// set the dumper class name
		$class_dumper = self::getDumperClass();

		// set the field handler class name
		$class_field_handler = self::getFieldHandlerClass();
		
		// set the lock class name
		$class_lock = self::getLockClass();
	
		if ( ! $active || ! DEBUGGING_FIELD_ERROR_HANDLING )
		
			return;

		switch ( $test_case )
		{
			case DEBUGGING_FIELD_ERROR_HANDLING:
	
				if ( TRUE === $class_lock::lockedEntity( $class_field_handler ) )
				{
					$class_lock::releaseEntity( $class_field_handler );
		
					// Dump authentication input
					$class_dumper::log(
						__METHOD__,
						array(
							'field handler store: ',
							$context[0]->{PROPERTY_CHECK},
							'field handler id: ',
							$context[1]->getHandlerId()
						),
						DEBUGGING_FIELD_ERROR_HANDLING && $verbose_mode,
						$context[2]. ':' . $context[3]
					);
				}

					break;
		}
	}

	/**
	* Test the BuildDOMNode method of the View Builder class 
	*
	* @param	mixed	$test_case	test case
	* @param	boolean	$active		active
	* @param	mixed	$context	context
	* @return	mixed
	*/
	public static function method__view_builder__buildDOMNode(
		$test_case = NULL,
		$active = FALSE,
		$context = NULL		
	)
	{
		global $verbose_mode;

		// set the dumper class name
		$class_dumper = self::getDumperClass();

		if ( ! $active || ! DEBUGGING_FIELD_ERROR_HANDLING )
		
			return;

		switch ( $test_case )
		{
			case DEBUGGING_FIELD_ERROR_HANDLING:

				$_dashboard = $context[ENTITY_DASHBOARD];				
				$_check = $context[PROPERTY_CHECK];
				$_store = $context[ENTITY_STORE];
				$field_name = $context[PROPERTY_NAME];				

				if ( 'email_again' == $field_name )
		
					$class_dumper::log(
						__METHOD__,
						array(
							'POST superglobale: ',
							$_POST,

							'dashboard: ',
							$_dashboard,
							
							'session status: ',
							$_store[SESSION_STATUS],
		
							'data submitted: ',
							$_store[SESSION_STATUS] ==
								SESSION_STATUS_DATA_SUBMITTED,
								
							'error condition: ',
							isset( $_dashboard ) && is_array( $_dashboard ) &&
							! empty( $_dashboard[$field_name] ) &&
							is_array( $_dashboard[$field_name] ) &&
							count( $_dashboard[$field_name] ) != 0 &&
							(
								! empty(
									$_dashboard
										[$field_name]
											[ERROR_FIELD_MISSING]
								) ||
								! empty(
									$_dashboard
										[$field_name]
											[ERROR_ALREADY_TAKEN]
								) ||
								! empty(
									$_dashboard
										[$field_name]
											[ERROR_WRONG_VALUE]
								)
							),									
		
							'warning raising condition: ',
							isset( $_store ) && is_array( $_store ) &&
							! empty( $_store[SESSION_STATUS] ) &&									
							$_store[SESSION_STATUS] ==
								SESSION_STATUS_DATA_SUBMITTED &&									
							isset( $_dashboard ) && is_array( $_dashboard ) &&
							! empty( $_dashboard[$field_name] ) &&
							is_array( $_dashboard[$field_name] ) &&
							count( $_dashboard[$field_name] ) != 0 &&
							(
								! empty(
									$_dashboard
										[$field_name]
											[ERROR_FIELD_MISSING]
								) ||
								! empty(
									$_dashboard
										[$field_name]
											[ERROR_ALREADY_TAKEN]
								) ||
								! empty(
									$_dashboard
										[$field_name]
											[ERROR_WRONG_VALUE]
								)
							),
							'alternate condition: ',
							isset( $_store ) && is_array( $_store ) &&
							! empty( $_store[SESSION_STATUS] ) &&
							
							(
								(
									$_store[SESSION_STATUS] ==
										SESSION_STATUS_DATA_SUBMITTED
								) ||
								count(
									$_check
										[SESSION_STORE_FIELD]
											[SESSION_STORE_HALF_LIVING]
								)
							) &&
							
							isset( $_dashboard ) && is_array( $_dashboard ) &&
							! empty( $_dashboard[$field_name] ) &&
							is_array( $_dashboard[$field_name] ) &&
							count( $_dashboard[$field_name] ) != 0 &&
							(
								! empty(
									$_dashboard
										[$field_name]
											[ERROR_FIELD_MISSING]
								) ||
								! empty(
									$_dashboard
										[$field_name]
											[ERROR_ALREADY_TAKEN]
								) ||
								! empty(
									$_dashboard
										[$field_name]
											[ERROR_WRONG_VALUE]
								)
							)							
						),
						$verbose_mode
					);
					
					break;
		}
	}

	/**
	* Test the setAttribute method of the View Builder class 
	*
	* @param	mixed	$test_case	test case
	* @param	boolean	$active		active
	* @param	mixed	$context	context
	* @return	mixed
	*/	
	public static function method__view_builder__setAttribute(
		$test_case = NULL,
		$active = FALSE,
		$context = NULL		
	)
	{
		global $verbose_mode;

		// set the dumper class name
		$class_dumper = self::getDumperClass();

		if ( ! $active || ! DEBUGGING_FIELD_HANDLING_DEFAULT_PASSWORD )
		
			return;

		switch ( $test_case )
		{
			case DEBUGGING_FIELD_HANDLING_DEFAULT_PASSWORD:

				$class_dumper::log(
					__METHOD__,
					array( $context ),
					$verbose_mode
				);

					break;
		}
	}
}
?>