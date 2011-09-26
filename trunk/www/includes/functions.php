<?php

if ( ! function_exists( 'assignConstant' ) )
{
    /**
    * Assign safely a constant
    *
    * @return  nothing
    */
    function assignConstant($constant_name, $value)
    {

        // check the constant existence
        if (!defined($constant_name))

            // define the constant
            define($constant_name, $value);
    }
}

assignConstant( 'FUNCTION_AUTOLOAD_JQUERY4PHP', 'YsJQueryAutoloader' );

assignConstant( 'FUNCTION_AUTOLOAD_SEFI', 'autoloadSefi' );

assignConstant( 'FUNCTION_DECLARE_CONSTANT_BATCH', 'declareConstantsBatch' );

assignConstant( 'FUNCTION_EXTRACT_LANGUAGE_ITEM', 'extractLanguageItem' );

assignConstant( 'FUNCTION_FOR_EACH_ITEM', 'forEachItem' );

assignConstant( 'FUNCTION_FPRINT', 'fprint' );

assignConstant( 'FUNCTION_GET_VARIABLE_NAME', 'getVariableName' );

assignConstant( 'FUNCTION_IDENTITY', 'identity' );

assignConstant( 'FUNCTION_START_SESSION', 'sessionStart' );


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
    * @param	array	$batch	batch
    * @return  	nothing
    */
    function declareConstantsBatch($batch)
    {
		if ( is_array( $batch ) && count( $batch ) )
		{
			$batch_reversed = array_reverse($batch);

			while ( list( $name, $value ) = each( $batch_reversed ) )

				assignConstant( $name, $value );
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

		//echo
		//	'message: ',
		//	$_message,
		//	$line_feed
		//;
		//
		//echo
		//	'display condition: ',
		//			$condition_display
		//		?
		//			'TRUE'
		//		:
		//			'FALSE'
		//	,
		//	$line_feed
		//;
		//
		//echo
		//	'return condition: ',
		//		$condition_return
		//	?
		//		'TRUE'
		//	:
		//		'FALSE'
		//	,
		//	$line_feed
		//;

		if ( $condition_display )

			echo $_message;

		else if ( $condition_return )
		
			return $_message;
		
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
			else if ( ! $return )
			
				$message = $exit;
			else
			
				$message = $_message;

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
		global $session_id;

		if ( headers_sent() && $bootstrap )

			throw new Exception(
				sprintf(
					EXCEPTION_INVALID_ENTITY,
					ENTITY_CONTEXT
				)
			);

		else if ( ( $session_id = session_id() ) === '' && ! defined( STDIN ) )
		{
			session_cache_limiter( 'using private_no_expire' );
		
			// start a session
			$session_id = session_start();
		}

		$session = &$_SESSION;			

		return $session;
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

if (
	! $autoload_functions ||	
	! in_array(
		FUNCTION_AUTOLOAD_JQUERY4PHP,
		$autoload_functions
	)
)
{
	$class_jquery4PHP = 'JQuery4PHP';

	$class_jquery4PHP::load();
}
