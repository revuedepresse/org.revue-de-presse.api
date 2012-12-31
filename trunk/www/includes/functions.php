<?php

if ( ! function_exists( 'assignConstant' ) )
{
    /**
    * Assign safely a constant
    *
    * @return  nothing
    */
    function assignConstant( $constant_name, $value )
    {
        // check the constant existence
        if ( ! defined($constant_name ) ) 
            define($constant_name, $value); // define the constant
    }
}

$cli_mode = defined( 'STDIN' ) ? TRUE : FALSE;

assignConstant( 'CLI_MODE', $cli_mode );
assignConstant( 'USER_SYSTEM', exec( 'whoami' ) );

assignConstant( 'FUNCTION_ARRAY_HAS_VALUE', 'str_key_arr' );
assignConstant( 'FUNCTION_ARRAY_VALID', 'arr_valid' );
assignConstant( 'FUNCTION_ASSIGN_CONSTANT', 'assignConstant' );
assignConstant( 'FUNCTION_AUTOLOAD_JQUERY4PHP', 'YsJQueryAutoloader' );
assignConstant( 'FUNCTION_AUTOLOAD_SEFI', 'autoloadSefi' );
assignConstant( 'FUNCTION_DECLARE_CONSTANT_BATCH', 'declareConstantsBatch' );
assignConstant( 'FUNCTION_DEFINED', 'defined' );
assignConstant( 'FUNCTION_EXTRACT_LANGUAGE_ITEM', 'extractLanguageItem' );
assignConstant( 'FUNCTION_FOR_EACH_ITEM', 'forEachItem' );
assignConstant( 'FUNCTION_FPRINT', 'fprint' );
assignConstant( 'FUNCTION_FUNCTION_EXISTS', 'function_exists' );
assignConstant( 'FUNCTION_GET_VARIABLE_NAME', 'getVariableName' );
assignConstant( 'FUNCTION_IDENTITY', 'identity' );
assignConstant( 'FUNCTION_OBJECT_HAS_NON_EMPTY_STRING_MEMBER', 'str_mmb_obj' );
assignConstant( 'FUNCTION_CONSTANT_REVERSE', 'reverse_constant' );
assignConstant( 'FUNCTION_SPRINTF', 'sprintf' );
assignConstant( 'FUNCTION_START_SESSION', 'sessionStart' );
assignConstant( 'FUNCTION_STRING_VALID', 'str_valid' );

if ( ! function_exists( FUNCTION_ARRAY_HAS_VALUE ) )
{
	/**
	* Check if one of the first-level index of a multidimensional array
	* has one value at least
	*
	* @param	array	$arr
	* @param	string	$str
	* @return	boolean
	*/
	function str_key_arr( $str, $arr )
	{
		if ( ! is_string( $str) || ! is_array( $arr ) )

			$result = NULL;
		else
			$result = isset( $arr[$str] ) && ( count( $arr[$str] ) > 0 );

		return $result;
	}
}

if ( ! function_exists( FUNCTION_ARRAY_VALID ) )
{
	/**
	* Check an array is valid i.e. it is a map containing at least one item
	*
	* @param	array	$arr
	* @return	boolean
	*/
	function arr_valid( $arr )
	{
		if ( ! is_array( $arr ) || is_null( $arr ) )

			$result = NULL;
		else
			$result = count( $arr ) > 0;

		return $result;
	}
}

if ( ! function_exists( FUNCTION_AUTOLOAD_SEFI ) )
{
	/**
	* Load automatically a class definition
	*
	* @param	string	$class	class name
	* @return	nothing
	*/
	function autoloadSefi( $class )
	{
		$verbose_mode = FALSE;
	
		$base_directory = dirname(__FILE__);
	
		// save the original class name
		$class_name = $class;
	
		// lower the case of the class name
		$class = strtolower($class);
	
		// set the default value of a namespace
		$namespace =
		
		$_namespace = '';
		
		// loop for namespaces
		if ( strpos($class, "\\") !== FALSE )
		{
			// get the namespace
			list($namespace, $class) = explode("\\", $class);
	
			$_namespace = $namespace;
	
			// append a separator to the namespace
			$namespace = strtolower($namespace.'.');
		}
	
		if ($verbose_mode)
		{
			echo 'namespace: ', $_namespace, '<br />';
			echo 'class: ', $class, '<br /><br />';
		}
	
		// check if the class has been declared already
		if (class_exists($class) && class_exists($_namespace.'/'.$class))
	
			return;
	
		// set the class file name
		$file =
			dirname( __FILE__ ) .
				'/class/class.' .
					$namespace .
						strtolower( $class ) .
							'.inc.php'
		;
	
		// check if a class definition file exists
		if (
			! file_exists( $base_directory . $file ) &&
			! file_exists(
				$base_directory .
					'/agent/agent.' .
						strtolower( $class_name ).'.php'
			) &&
			! file_exists(
				$base_directory .
					'/interface/interface.' .
						strtolower( $class_name ) .
							'.inc.php'
			)
		)
		{
			// set the path to the class definition file on disk
			$path2class = '/class/class.'.$namespace.$class.'.inc.php';

			if ( ! file_exists( $base_directory . $path2class ) )
			{
				$directory_smarty_libs =
					'/../lib/Smarty/libs/'
				;

				$directory_smarty_system_plugins = 'sysplugins/';

				$path_class =
					$base_directory .
						$directory_smarty_libs .
							$directory_smarty_system_plugins .
								$class . '.php'
				;

				if ( ! file_exists( $path_class ) )

					return;

				else if (
					substr( $class, 0, 16 ) === 'smarty_internal_' ||
					$class == 'smarty_security'
				)
				
					$file =
						$directory_smarty_libs .
							$directory_smarty_system_plugins
								. $class . '.php'
					;
			}
			else
	
				$file = $path2class;
		}
	
		// check if a class definition file exists
		if ( file_exists( $base_directory.$file ) )

			require( $base_directory.$file );
	
		// check if a class definition file exists elsewhere
		else if (
			file_exists( $base_directory .
				'/interface/interface.' .
					strtolower( $class_name ) . '.inc.php'
			)
		)
	
			require(
				$base_directory .
					'/interface/interface.' .
						strtolower( $class_name ) . '.inc.php'
			);

		// check if an agent definition file exists elsewhere
		else if (
			file_exists(
				$base_directory .
					'/agent/agent.' .
						strtolower( $class_name ) . '.php'
			)
		)
	
			require(
				$base_directory .
					'/agent/agent.' .
						strtolower( $class_name ) . '.php'
			);
	}
}

if ( ! function_exists( FUNCTION_DECLARE_CONSTANT_BATCH ) )
{
    /**
    * Declare batch of constants
    *
    * @param	array	$batch			batch
    * @param	array	$reverse_order	flag to declare the constants
    * 									in a reverse order
    * @return  	nothing
    */
    function declareConstantsBatch( $batch, $reverse_order = TRUE )
    {
		if ( is_array( $batch ) && count( $batch ) )
		{
			$collection = $batch;

			if ( $reverse_order ) $collection = array_reverse( $batch );

			while ( list( $name, $value ) = each( $collection ) )
			{
				$constant_value = $value;

				if (
					is_array( $value ) &&
					(
						(
							isset( $value[PROPERTY_CONSTANT] ) &&
							isset( $value[PROPERTY_NAME] )
						) ||
						(
							isset( $value[0] ) &&
							isset( $value[1] ) 
						)
					)
				)
				{
					if (
						isset( $value[PROPERTY_NAME] ) &&
						defined( $value[PROPERTY_NAME] )
					)
						$constant_value = constant( $value[PROPERTY_NAME] );

					else if ( defined( $value[1] ) )

						$constant_value = constant( $value[1] );
					else
						throw new Exception(
							sprintf(
								EXCEPTION_INVALID_OPERATION,
								ENTITY_DECLATION_CLASSES
							)
						);
				}

				assignConstant( $name, $constant_value );
			}

		}
    }
}

if ( ! function_exists( FUNCTION_EXTRACT_LANGUAGE_ITEM ) )
{
    /**
    * Retrieve a language item value from a key
    *
    * @param	string	$key					key
    * @param	string	$prefix_form_identifier	form identifier prefix 
    * @param	string	$prefix_i18n_identifier	i18n identifier prefix 
    * @param	string	$prefix_field_name		field name prefix
    * @return  	nothing
    */	
	function extractLanguageItem(
		$key,
		$prefix_form_identifier,
		$prefix_i18n_identifier,
		$prefix_field_name
	)
	{
		$callback_parameters = $key;
		
		$match = preg_match(
			REGEXP_I18N_LANGUAGE_ITEM,
			$key,
			$matches
		);

		// check the matching values
		if ( $match )
		{
			$i18n_identifier =
				$prefix_i18n_identifier.
				PREFIX_OPTION.
				str_replace(
					'.',
					'_',
					$prefix_form_identifier
				).'_'.
				$prefix_field_name.'_'.
				rtrim( $matches[1] , SUFFIX_ACCEPT_DEFAULT )
			;

			// check if a constant is defined
			if ( defined( strtoupper( $i18n_identifier ) ) )

				// return an i18n constant
				$callback_parameters = constant( strtoupper( $i18n_identifier ) );

			else

				// throw a new exception
				throw new Exception(
					EXCEPTION_LOST_IN_TRANSLATION .
						' (' . strtoupper( $i18n_identifier ) . ')'
				);
		}

		return $callback_parameters;
	}
}

if ( ! function_exists( FUNCTION_FOR_EACH_ITEM ) )
{
    /**
    * Loop on a container and pass each type to callback function
    *
    * @return  nothing
    */
    function forEachItem( $container, $callback )
    {
		if (
			is_array( $container ) &&
			count( $container ) 
		)
		{	
			while ( list( , $item ) = each( $container ) )
	
				$results[] = call_user_func_array(
					$callback,
					array( $item )
				);

			reset( $container );
		}
		else
			
			// return input if the callback can not by applied
			$results = $container ;

		return $results;
    }
}

if ( ! function_exists( FUNCTION_FPRINT ) )
{
	/**
	* Print an array in a formated way
	*
	* @param	$var		variable
	* @param	$display	display flag	
	* @param	$exit		termination flag
	* @param	$tag		surrounding tag 
	* @return  	nothing
	*/	
	function fprint(
		$var,
		$display = FALSE,
		$exit = FALSE,
		$return = FALSE,
		$tag = 'pre'
	)
	{
		$no_tag = FALSE;

		if ( is_null( $tag ) )
		{
			$line_feed = "\n\n";

			$no_tag = TRUE;
		}
		else 

			$line_feed = '<br /><br />';

		$_message =
			( $no_tag ? '' : '<'.$tag.'>' ).
			(
					$display || $return
				?
					(
							is_object( $var ) || is_array( $var )
						?
							print_r( $var, TRUE)
						:
							(
									is_string( $var )
								?
									$var
								:
									''
							)
					)
				:
					''
			).
			( $no_tag ? '' : '</'.$tag.'>' )
		;

		$condition_display = $display && ! $return;
		$condition_return = $return && ! $exit;

		if ( $condition_display ) echo $_message;
		else if ( $condition_return ) return $_message;
		
		if ( $exit !== FALSE )
		{
			if (
				is_array( $exit ) &&
				count( $exit ) == 2 &&
				isset( $exit['line'] ) &&
				isset( $exit['file'] )
			)

				$message =
					'Termination at line ['.$exit['line'].'] '.
					'in file ['.$exit['file'].']'
				;

			else if ( ! $return ) $message = $exit;
			else $message = $_message;

			exit( $message );
		}
	}
}

if ( ! function_exists( FUNCTION_GET_VARIABLE_NAME ) )
{
	/**
	* Get the name of a variable
	*
	* @param	&$var	variable
	* @param	$scope	scope of search
	* @param	$prefix	prefix
	* @param	$suffix	suffix
	* @return  	nothing
	*/	
	function getVariableName(
		&$var,
		$scope = FALSE,
		$prefix = 'unique',
		$suffix = 'value'
	)
	{
		if ( $scope )
		  
		  $vals = $scope;
		else
		  
		  $vals = $GLOBALS;
	
		$old = $var;
	
		$var = $new = $prefix.rand().$suffix;
		
		$vname = FALSE;
	
		if ( is_array( $vals ) && count( $vals ) > 0 )
	
			foreach ( $vals as $key => $val )
			{
		  
				if ( $val === $new )
				
					$vname = $key;
			}
		else

			return NULL;

		$var = $old;
		
		return $vname;
	}
}

if ( ! function_exists( FUNCTION_CONSTANT_REVERSE ) )
{
	/**
	* Reverse a constant
	*
	* @param	mixed	$value	value
	* @param	string	$prefix	prefix
	* @param	boolean	$strict	strict reverse
	* @return  	string  reversed constant
	*/	
	function reverse_constant( $value, $prefix, $strict = FALSE )
	{
		// get the user defined constants
		$constants = get_defined_constants( TRUE );
	
		// get the matching keys
		$keys = array_keys( $constants['user'], $value, $strict );
	
		// set the pattern
		$pattern =
			REGEXP_OPEN.
				REGEXP_START.
				$prefix.
				REGEXP_CATCH_START.
					REGEXP_WILDCARD.REGEXP_ANY.
				REGEXP_CATCH_END.
			REGEXP_CLOSE
		;
	
		// loop on keys
		foreach ( $keys as $index => $key )
		{
			$match = preg_match( $pattern, $key, $matches );
	
			if ( $match )
			
				$reversed_constant = $matches[1];	
		}

		if ( isset( $reversed_constant ) )
	
			// return the reversed constant
			return $reversed_constant;
	}
}

if ( ! function_exists( FUNCTION_IDENTITY ) )
{
	/*
    * Return identically the input
    *
    * @return  nothing
    */
    function identity()
    {
		// get the arguments
		$arguments = func_get_args();

		// check the arguments
		if (count($arguments) == 1)

			// return the only argument passed to the function
			return $arguments[0];

		// return the arguments
		return $arguments;
    }
}

if ( ! function_exists( FUNCTION_OBJECT_HAS_NON_EMPTY_STRING_MEMBER ) )
{
	/**
	* Check if an object has a member which is an non-empty string
	* 
	* @param	string	$str		string
	* @param	string	$obj		object
	* @param	boolean	$string		member type	
	* @return 	boolean	TRUE if $str is a non empty string declared as member
	* 					variable of the object $obj
	* 					FALSE otherwise
	*/
    function str_mmb_obj( $str, $obj, $string = TRUE )
    {
		if (
			! is_object( $obj ) ||
			( ( $vars = get_object_vars( $obj ) ) && ( count( $vars ) === 0 ) ) || 
			! is_string( $str ) ||
			( strlen( $str ) === 0 )
		)

			$result = NULL;
		else 
			$result = 
				is_object( $obj ) && isset( $obj->$str ) &&
				(
					(
						$string && is_string( $obj->$str ) &&
						( strlen( trim( $obj->$str ) ) > 0 )
					) ||
					is_int( $obj->$str )
				)
			;

		return $result;
    }
}

if ( ! function_exists( FUNCTION_START_SESSION ) )
{
	/**
	* Start a new session
	*
	* @param	boolean	$bootstrap	boostrap flag
	* @return	nothing
	*/
	function &sessionStart( $bootstrap = FALSE )
	{
		global $session_id, $jenkins_workspace;

		if ( headers_sent() && $bootstrap && ! $jenkins_workspace )

			throw new Exception(
				sprintf(
					EXCEPTION_INVALID_ENTITY,
					ENTITY_CONTEXT
				)
			);

		else if ( ( $session_id = session_id() ) === '' )
		{
			session_cache_limiter( 'using private_no_expire' );

			if ( CLI_MODE ) // if cli mode is enabled
			{
				$close = function() { return TRUE; };

				$destroy =
					function( $session_id )
					{
						global $class_application;

						$class_template_engine = $class_application::getTemplateEngineClass();
								
						// construct a new Smarty object
						$template_engine = new $class_template_engine();

						$template_engine->clear_all_cache();
					}
				;

				$gc = function() { return TRUE; };

				$open = function( $save_path, $session_id ) { return TRUE; };

				$read = function() { return FALSE; };

				$write = function( $key, $value ) { return TRUE; };
				
				session_set_save_handler( $open, $close, $read, $write, $destroy, $gc );

				$_SERVER['REQUEST_URI'] = '/'; // emulate interactive mode
			}

            if ( ! headers_sent() ) 
            {
                $session_id = session_start();
            }
		}

		$session = &$_SESSION;			

		return $session;
	}
}

if ( ! function_exists( FUNCTION_STRING_VALID ) )
{
	/**
	* Check if a string is valid
	*
	* @param	array	$str
	* @return	boolean
	*/
	function str_valid( $str )
	{
		if ( is_null( $str ) || ! is_string( $str ) )

			$result = NULL;
		else
			$result = ( strlen( $str ) > 0 ) ;

		return $result;
	}
}

$autoload_functions = spl_autoload_functions();

if (
	! $autoload_functions ||
	! in_array(
		FUNCTION_AUTOLOAD_SEFI,
		$autoload_functions
	)
)
	spl_autoload_register( FUNCTION_AUTOLOAD_SEFI );

/**
*************
* Changes log
*
*************
* 2011 09 27
*************
*
* project :: wtw ::
* 
* deployment :: template engine ::
*
* Declare execution mode and system user
* Implement session handler for CLI mode
* Clear cache in CLI mode when destroying session
*
* (branch 0.2 :: revision :: 314)
*
*************
* 2011 10 11
*************
*
* project :: wtw ::
* 
* deployment :: robustness ::
*
* Implement functions to check data structures against
*
* functions affected ::
*
* 	arr_valid
* 	str_valid
* 	str_key_val
* 	str_mmb_obj
*
* (branch 0.1 :: revision :: 705)
*
*************
* 2011 10 18
*************
*
* project :: wtw ::
* 
* deployment :: introspection ::
*
* Declare functions
*
* functions affected ::
*
* 	assign_constant
* 	defined
* 	function_exists
* 	sprintf
*
* (branch 0.1 :: revision :: 722)
* (branch 0.1 :: revision :: 383)
*
*************
* 2011 10 21
*************
*
* project :: wtw ::
* 
* deployment :: performance ::
*
* Declare function used to retrieve constant name from value and prefix
*
* functions affected ::
*
* 	reverse_constant
*
* (branch 0.1 :: revision :: 727)
* (branch 0.1 :: revision :: 393)
*
*/
