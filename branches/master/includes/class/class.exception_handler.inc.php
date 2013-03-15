<?php

/**
* Exception Handler class
* 
* Class for exception handling
* @package sefi
*/
class Exception_Handler extends Event_Manager
{
	/**
	* Handler assertions
	*
	* @return	nothing
	*/	
	public static function assertionHandler( $file, $line, $expression )
	{
		fprint( ENTITY_ASSERTION.': '."\n".$expression, UNIT_TESTING_MODE_STATUS );

		throw new Exception(
			sprintf(
				EXCEPTION_UNIT_TESTING_ASSERTION_INVALID,
				'at line ['.$line.'] '.
				'in program '.$file
			)
		);
	}
	
	/**
	* Save an exception
	* 
	* @param 	array	$context	context
	* @return 	nothing
	*/
	public static function logContext( $context )
	{
		global $class_application;

		$class_event = $class_application::getEventClass();

		$class_event::logEvent( $context );
	}

	/**
	* Log an exception
	* 
	* @param 	integer	$code		code
	* @param	string	$message	message
	* @param	string	$file_name	file name
	* @param	integer	$line		line
	* @return 
	*/
	public static function logException( $code, $message, $file_name, $line )
	{
		global $verbose_mode;

		$class_firephp = CLASS_FIREPHP;

		$mysqli_object_error = FALSE;

		if ( strpos( $message, 'Couldn\'t fetch mysqli' ) === FALSE )
			
			$exception = new Exception( $message, $code );
		else
		
			$mysqli_object_error = TRUE;

		// check if the detected error is not related to the Smarty template engine
		if (
			isset( $exception ) &&
			is_object( $exception ) &&
			(
				( get_class( $exception ) == CLASS_EXCEPTION ) ||
				is_subclass_of( $exception, CLASS_EXCEPTION )
			) &&			
			! preg_match( '/split/', $exception->getTraceAsString() ) &&
			! preg_match( '/rss::parser/', $exception->getTraceAsString () )
			||
			$mysqli_object_error
		)
		{
			$context_http = array(
				PROTOCOL_HTTP_METHOD_GET => $_GET,
				PROTOCOL_HTTP_METHOD_POST => $_POST
			);

			// prevent the strict warning from terminating the error handling
			if (
				is_string( $message ) &&
				(
					$mysqli_object_error ||
					( strpos( strtolower( $message ), 'deprecated' ) !== FALSE ) ||
					( strpos( strtolower( $message ), 'magic' ) !== FALSE ) || 
					( strpos( strtolower( $message ), 'compatible' ) !== FALSE ) ||
					(
						( strpos( strtolower( $message ), 'mb_detect_encoding' ) !== FALSE ) &&
						( FALSE !== strpos( $file_name, 'firephp' ) )
					) 
				)
			)
			{
				$style = 'style="float:left;text-align:left;{}"';

				if ( $verbose_mode )
				{
					$backtrace = debug_backtrace();
 
					if (
						$mysqli_object_error &&
						is_array($backtrace) &&
						isset($backtrace[0]) &&
						is_array($backtrace[0]) &&
						count($backtrace[0]) &&
						isset($backtrace[0]['args']) &&
						is_array($backtrace[0]['args']) &&
						count($backtrace[0]['args']) &&
						isset($backtrace[0]['args'][4]) &&
						is_array($backtrace[0]['args'][4]) &&
						count($backtrace[0]['args'][4])
					)
					
						echo 
							'<pre '.str_replace('{}',
							'font-size:1.2em', $style).'>',
							'faulty arguments: ',
							'<form style="float:left;width:100%">'.
							'<textarea cols="100", rows="30">',
							serialize( $backtrace[0]['args'][4] ),
							'</textarea></form>',
							'</pre>';
						;
				}

				echo
					'<pre '.str_replace('{}', 'font-size:1.2em', $style).'>'.
					'file name: ', $file_name, "<br />\n",
					'line: ', $line, "<br />\n",
					'message: ', $message, "<br />\n",
					(
							$verbose_mode
						?
							'backtrace: <pre '.str_replace('{}', '', $style).'>'.
							print_r($backtrace, TRUE)
						:
							''
					),
					"</pre><br />\n",
					'</pre>'
				;

				exit();
			}

			$context = array(
				PROPERTY_CONTEXT => print_r( $context_http, TRUE ),
				PROPERTY_DESCRIPTION => sprintf(
					EVENT_DESCRIPTION_EXCEPTION_CAUGHT,
					$code,
					$file_name,
					$line,
					$message,
					$exception->getTraceAsString()
				),
				PROPERTY_EXCEPTION => $exception,
				PROPERTY_TYPE => EVENT_TYPE_EXCEPTION_CAUGHT
			);

			if ( DEPLOYMENT_LOG === 1 ) error_log(implode("\n", $context));
			else if ( DEPLOYMENT_LOG === 0 )
			{
				self::logContext( $context );
                if (defined('STDIN')) {
                    error_log(print_r($context, true));
                }
                $firephp = $class_firephp::getInstance( TRUE );
				$firephp->log( $exception );
			}

			exit();
		}
		else 
			exit( EXCEPTION_INVALID_ERROR_HANDLER );

	}

	/**
	* Log a trace
	* 
	* @param 	string	$trace		trace
	* @param	mixed	$informant	informant
	* @param	boolean	$assert		assertion flag
	* @return 	nothing
	*/
	public static function logTrace(
		$trace = NULL,
		$informant = NULL,
		$assert = FALSE
	)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$class_exception = $class_application::getExceptionClass();

		$callback_parameters = $class_dumper::write( $trace );

		list( , $trace ) = each( $callback_parameters );

		if ( is_string( $trace ) && is_null( $informant ) )

			$_informant = $trace;

		$exception = new $class_exception(
				is_string( $_informant ) && ! empty( $_informant )
			?
				$_informant
			:
				LOG_MESSAGE_NO_MESSAGE
		);

		$context_http = array(
			PROTOCOL_HTTP_METHOD_GET => $_GET,
			PROTOCOL_HTTP_METHOD_POST => $_POST
		);

		$context = array(
			PROPERTY_CONTEXT => print_r( $context_http, TRUE ),
			PROPERTY_DESCRIPTION => sprintf(
				EVENT_DESCRIPTION_EXCEPTION_CAUGHT,
				$exception->getCode(),
				$exception->getFile(),
				$exception->getLine(),
				$exception->getMessage(),
				$exception->getTraceAsString()
			),
			PROPERTY_EXCEPTION => $exception,
			PROPERTY_TYPE => EVENT_TYPE_EXCEPTION_CAUGHT
		);

		self::logContext( $context );	
	}

	/**
	* Log an exception
	* 
	* @param 	integer	$code		code
	* @param	string	$message	message
	* @param	string	$file_name	file name
	* @param	integer	$line		line
	* @return 
	*/
	public static function testErrorHandler($code, $message, $file_name, $line)
	{
	}

	/**
	* Set the error handler
	* 
	* @return nothing
	*/
	public static function deploy()
	{
		$class_exception_handler = __CLASS__;

		$error_handling_witness = 'testErrorHandler';

 		set_error_handler(
			array( $class_exception_handler, $error_handling_witness )
		);

		$previous_exception_handler = set_exception_handler(
			array( $class_exception_handler, 'notify' )
		);

		$previous_error_handler = set_error_handler(
			array( $class_exception_handler, 'logException' )
		);

		register_shutdown_function(
			array( $class_exception_handler, 'shutdown' )
		);
		
		if (
			! is_array( $previous_error_handler ) ||
			! isset( $previous_error_handler[1] ) ||
			$previous_error_handler[1] != $error_handling_witness
		)
		
			exit( EXCEPTION_INVALID_ERROR_HANDLER );
	}

	/**
	* Set the exception handler
	*
	* @param	object	$exception	exception
	* @return 	nothing
	*/
	public static function notify( $exception )
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$context_http = array(
			PROTOCOL_HTTP_METHOD_GET => $_GET,
			PROTOCOL_HTTP_METHOD_POST => $_POST
		);

		$method = '';

		if (
			is_object( $exception ) &&
			(
				( get_class( $exception ) == CLASS_EXCEPTION ) ||
				is_subclass_of( $exception, CLASS_EXCEPTION )
			)
		)
		{
			$trace = $exception->getTrace();
			
			if ( isset( $trace[0] ) && isset( $trace[0]['function'] ) )
			
				$function = $trace[0]['function'];

			if ( isset( $trace[0] ) && isset( $trace[0]['class'] ) )

				$class = $trace[0]['class'];

			if ( isset( $class ) && isset( $function ) )

				$method = $class . '::' . $function;
		}

		$context = array(
			PROPERTY_CONTEXT => print_r( $context_http, TRUE ),
			PROPERTY_DESCRIPTION => sprintf(
				EVENT_DESCRIPTION_EXCEPTION_CAUGHT,
				$exception->getCode(),
				$exception->getFile(),
				$exception->getLine(),
				$exception->getMessage(),
				$exception->getTraceAsString()
			),
			PROPERTY_EXCEPTION => $exception,
			PROPERTY_TYPE => EVENT_TYPE_EXCEPTION_CAUGHT
		);

		if ( DEPLOYMENT_LOG === 0 )
		{
			$class_dumper::log(						
				$method,
				array( $exception ),
				DEBUGGING_DISPLAY_EXCEPTION
			);
			self::logContext( $context );
		}
		else if ( DEPLOYMENT_LOG === 1 )

			error_log(implode( '\n', array( 
				$exception->getMessage(),
				$exception->getFile(), 
				$exception->getLine() 
			)));

	}

	/**
	* Function called at shutdown
	*
	* @return  	mixed
	*/
	public static function shutdown()
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();
		
		$error_occurrence = FALSE;
		
		if  ( $error_properties = error_get_last() )
		{
			switch( $error_properties['type'] )
			{
				case E_ERROR:
				case E_CORE_ERROR:
				case E_COMPILE_ERROR:
				case E_USER_ERROR:

					$error_occurrence = TRUE;

						break;
			}
		}

		if ( $error_occurrence )

			$class_dumper::log(
				__METHOD__,
				array(
					'[the current script execution was interrupted '.
					'when an error with following details occurred]',
					$error_properties
				),
				$verbose_mode
			);
	}	
}

/**
*************
* Changes log
*
*************
* 2012 04 30
*************
*
* deployment :: event management ::
* 
* Start implement log deployment facilities 
* 
* (v0.1	:: revision 860)
*
*/
