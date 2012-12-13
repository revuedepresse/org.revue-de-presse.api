<?php

/**
* Dumper class
*
* Class for dumping mixed data
* @package  sefi
*/
class Dumper extends \Toolbox
{
	private $_firephp = null;

    /**
    * Construct a new dumper
    *
    * @param	string	$class_name		containing a class name
    * @param	string	$method_name	containing a method name
    * @param	array	$messages		containing messages
    * @param	boolean	$force_display	indicating if display should be forced
    * @param	mixed	$exit			indicating if the interpretation should be terminated
    * @param	string	$uri			containing a URI
    * @return  	nothing
    */
    public function __construct(
		$class_name,
		$method_name,
		$messages,
		$force_display = FALSE,
		$exit = FALSE,
		$uri = NULL
	)
    {
		if ( ! defined( 'DEBUGGING_FORM_BUILDING' ) )
			define( 'DEBUGGING_FORM_BUILDING', TRUE );

		if ( ! defined( 'AFFORDANCE_CATCH_EXCEPTION' ) )
			define( 'AFFORDANCE_CATCH_EXCEPTION', TRUE );

		if (
			$force_display &&
			defined( 'CURRENT_DEPLOYMENT_STAGE' ) &&
			constant( 'CURRENT_DEPLOYMENT_STAGE' ) === 0
 		)
		{
			$this->start_log( $class_name, $method_name, $force_display );
	
			while ( list( $message_index, $message ) = each( $messages ) )

				$this->append_message( $message );
	
			$this->end_log();
		}

		// check if display and exit should be forced
		if (
			(
				$force_display && $exit ||
				$exit === AFFORDANCE_CATCH_EXCEPTION
			) &&
			empty( $uri )
		)
		{
			if ( is_string( $exit ) && ! empty( $exit ) )

				self::log(
					LOG_MESSAGE_PROGRAM_TERMINATION,
					array( LOG_MESSAGE_CAUGHT_EXCEPTION ),
					TRUE
				);

			exit();
		}

		// check if the URI argument is empty		
		else if ( ! empty( $uri ) )
		{
			$class_application = CLASS_APPLICATION;

			// go to the URI
			$class_application::jumpTo($uri);			
		}
    }

    /**
    * Start logging some activities
    * 
    * @param	string	$class_name		containing a class name
    * @param	string	$method_name	containing a method name	
    * @param	string	$display		containing a display mode
    * @return 	nothing
	*/	
	private function start_log(
		$class_name,
		$method_name = NULL,
		$display = FALSE
	)
	{
		// set the default collapsed mode
		$collapsed = FALSE;
		$group_title =
		$title_start = '';

		ob_start();

		$this->_firephp = firephp::getInstance( TRUE );

		// check the display
		if ($display === DUMPER_DISPLAY_COLLAPSED)

			// set the collapsed mode to true
			$collapsed = TRUE;

		if ( FALSE === strpos( $method_name, ':' ) )

			$title_start = 'method: ';

		// check the class and method names
		if ( ! empty( $class_name ) && ! empty( $method_name ) )

			// set the group title
			$group_title = $title_start . $class_name.' :: '.$method_name;

		// check the class and method names
		if ( empty( $class_name ) && ! empty( $method_name ) )

			// set the group title
			$group_title = $title_start . $method_name;

		else
		{
			$trace = debug_backtrace( TRUE );

			if ( isset( $trace[count($trace) - 1]['file'] ) )

				// set an alternate group title
				$group_title = 'logged from: '.$trace[count($trace) - 1]['file'];

			else if ( isset( $trace[count($trace) - 1]['args'] ) )
			{
				if (
					is_array( $trace[count($trace) - 1]['args'] ) &&
					count( $trace[count($trace) - 1]['args'] ) &&
					isset( $trace[count($trace) - 1]['args'][0] ) &&
					is_object( $trace[count($trace) - 1]['args'][0] ) &&
					(
						CLASS_EXCEPTION
							=== get_class( $trace[count($trace) - 1]['args'][0] )
					)
				)
					$group_title = $trace[count($trace) - 1]['args'][0]->getFile();
			}
		}

		$this->_firephp->group(
			$group_title,
			array(
				'Collapsed' => $collapsed
			)
		);

		$this->_firephp->log( '===== DEBUG MESSAGE START');
	}

    /**
    * Call system error logger
    *
    * @param	information
    * @return	nothing
	*/	
	public static function error_log( $information )
	{
		error_log( $information );
	}
    
	/**
    * Trace the execution of a program
    *
    * @return	boolean	$verbose	verbosity flag
    * @return	mixed	$exit		terminate the current program execution
    * @return	mixed	$informant	informant 
    * @return	boolean	$assert		assertion flag
    * @return	nothing  
	*/	
	public static function trace(
		$verbose = FALSE,
		$exit = FALSE,
		$informant = NULL,
		$assert = FALSE
	)
	{
		global $verbose_mode;

		$_verbose_mode = $verbose_mode;

		if ( ! is_null( $verbose ) )

			$_verbose_mode = $verbose_mode;
			
		$backtrace = debug_backtrace( TRUE );

		// get rid of the call to this method
		if ( isset( $backtrace[0] ) )

			unset( $backtrace[0] );

		self::log(
			__METHOD__,
			array(
				'[trace of program execution]',
				$backtrace
			),
			$_verbose_mode,
			$exit
		);		
	}

    /**
    * End logging some activities
    * 
    * @return	nothing  
	*/	
	private function end_log()
	{
		$this->_firephp->log("===== DEBUG MESSAGE END");
		$this->_firephp->groupEnd();
	}

    /**
    * Append argument to log 
    *
    * @param	mixed	$argument	containing an argument
    * @return 	nothing
	*/
	private function append_message( $argument )
	{
		// check the argument type
		if (
			is_object( $argument ) &&
			(
				( get_class( $argument ) == CLASS_EXCEPTION ) ||
				is_subclass_of( $argument, CLASS_EXCEPTION )
			)
		)

			// log the argument as an error
			$this->_firephp->error( $argument );

		else if (
			is_object( $argument ) &&
			(
				get_class( $argument ) === CLASS_MYSQLI_RESULT ||
				get_class( $argument ) === CLASS_MYSQLI_STATEMENT
			)
		)

			$this->_firephp->log( print_r( $argument, TRUE ) );
		else 

			$this->_firephp->log( $argument );
	}

    /**
    * check an assertion
    * 
    * @param	array	$assertion
    * @param	array	$message
    * @return 	nothing
	*/	
	public static function assert( $assertion, $message )
	{
		$trace = array();

		$backtrace = debug_backtrace( TRUE );
		
		if ( isset( $backtrace[0] ) )

			$trace = $backtrace[0];

		$_message = array_merge(
			array(
				ENTITY_ASSERTION => array(
					PROPERTY_EXPRESSION => $assertion[PROPERTY_EXPRESSION],
					PROPERTY_EVALUATION => $assertion[PROPERTY_EVALUATION],
					PROPERTY_FILE =>
							isset( $trace['file'] )
						?
							$trace['file']
						:
							PROPERTY_UNDEFINED,
					PROPERTY_LINE =>
							isset( $trace['line'] )
						?
							$trace['line']
						:
							PROPERTY_UNDEFINED
				)
			),
			$message
		);
		
		self::log(
				isset( $backtrace[1] ) &&
				isset( $backtrace[1]['function'] )
			?
				(
					isset( $backtrace[1] ) &&
					isset( $backtrace[1]['class'] )
				?
					$backtrace[1]['class'].'::'
				:
					''
				).
				$backtrace[1]['function']
			:
				NULL,
			$_message,
			UNIT_TESTING_ASSERTIVE_MODE_STATUS
		);
	}

    /**
    * Dump log messages
    *
    * @param	string	$method_name	containing a method name
    * @param	array	$messages		containing messages
    * @param	boolean	$force_display	indicating if display should be forced
    * @param	mixed	$exit			indicating if the interpretation should be terminated
    * @param	string	$uri			containing a URI
    * @return  	nothing
	*/
	public static function log(
		$method_name,
		$messages,
		$force_display = FALSE,
		$exit = FALSE,
		$uri = NULL
	)
	{
		global $class_application, $verbose_mode;

		$condition_display_exception_cli_mode =
			CLI_MODE &&
			is_array( $messages ) && isset( $messages[0] ) &&
			is_object( $messages[0] ) &&
			( get_class( $messages[0] ) === CLASS_EXCEPTION ) &&
			( $force_display === TRUE )
		;

		if (
			isset( $_SERVER ) &&
			isset( $_SERVER['HTTP_HOST'] )
		)
		{
			if ( is_array( $force_display ) && count( $force_display ) == 0 )

				echo '<br /><pre>', print_r($messages, TRUE), '</pre><br />' ;		
			else
			{
				if (
					is_array( $messages ) &&
					array_key_exists( ENTITY_ASSERTION, $messages )
				)
				{
					$assertion = $messages[ENTITY_ASSERTION];
					$class_exception_handler =
						$class_application::getExceptionHandlerClass()
					;
					unset( $messages[ENTITY_ASSERTION] );
				}

				if (
					! isset( $assertion ) ||
					UNIT_TESTING_ASSERTIVE_MODE_STATUS &&
					! $assertion[PROPERTY_EVALUATION]
				)
					$dumper = new self(
						'',
						$method_name,
						$messages,
						$force_display,
						$exit,
						$uri
					);

				if ( isset( $assertion ) && ! $assertion[PROPERTY_EVALUATION] )
					$class_exception_handler::assertionHandler(
						$assertion[PROPERTY_FILE],
						$assertion[PROPERTY_LINE],
						'<br />'.$assertion[PROPERTY_EXPRESSION].'<br />'
					);
			}
		}
		else if ( $condition_display_exception_cli_mode )

			fprint( $messages[0]->getMessage(), $verbose_mode, TRUE, FALSE, NULL );
		
		else 

			fprint( $messages, $force_display, $exit );
	}

	/**
	* Write a message to file
	*
	* @param	mixed	$message	message
	* @return	boolean	success indicator
	*/	
	public static function write( $message = NULL )
	{
		$class_file_manager = self::getFileManagerClass();

		return $class_file_manager::dump( $message );
	}
}

/**
*************
* Changes log
*
*************
* 2012 04 29
*************
* 
* deployment :: exception management ::
*
* Revise error management in CLI mode
* 
* (revision 858)
*
*************
* 2012 05 06
*************
* 
* deployment :: exception management ::
*
* Add method wrapping PHP native function error_log
*
* methods affected ::
*
* 	DUMPER::error_log
* 
* (revision 902)
*
*/