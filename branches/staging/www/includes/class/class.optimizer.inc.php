<?php

/**
* Optimizer class
*
* Class for script optimization
* @package  sefi
*/
class Optimizer extends Test_Case
{
	protected static $timeline = array();

    /**
    * Start a timer
    *
    * @return 	nothing
    */	
	public static function startTimer()
	{
		list($start_msec, $start_sec) = explode(" ", microtime());

		self::$timeline['start'] = (float)$start_msec + (float)$start_sec;
	}

    /**
    * Stop a timer
    *
    * @return 	nothing
    */	
	public static function stopTimer()
	{
		list($end_msec, $end_sec) = explode(" ", microtime());

		self::$timeline['end'] = (float)$end_msec + (float)$end_sec;
	}

    /**
    * Log results
    *
    * @param	boolean	$stop_timer	stop timer flag
    * @param	string	$file_name	log file name
    * @return 	nothing
    */	
	public static function logResults(
		$stop_timer = FALSE,
		$file_name = FILE_NAME_LOG_PERFORMANCE
	)
	{
		if ( $stop_timer )

			self::stopTimer();

		$directory_tmp =
			dirname(__FILE__).'/../../' .
			DIR_API . '/' . DIR_WTW.'/'
		;

		$directory = $directory_tmp . DIR_LOGS.'/';

		$file_name = $directory.$file_name;

		if ( ! file_exists( $file_name ) )
		{
			if ( ! file_exists ($directory_tmp ) )

				mkdir($directory_tmp);

			else if ( ! is_dir( $directory_tmp ) )
			
				throw new Exception(
					EXCEPTION_INVALID_SERVER_CONFIGURATION_TEMPORARY_DIRECTORY
				);

			if (!file_exists($directory))

				mkdir($directory);

			else if ( ! is_dir( $directory ) )
			
				throw new Exception(
					EXCEPTION_INVALID_SERVER_CONFIGURATION_TEMPORARY_DIRECTORY
				);
		}

		$handle = fopen( $file_name, 'a+' );
		fwrite( $handle, self::$timeline['end'] - self::$timeline['start']."\n" );
		fclose( $handle );
	}

    /**
    * Show results
    *
    * @return 	nothing
    */	
	public static function showResults()
	{
 		echo self::$timeline['end'] - self::$timeline['start'];
	}
}


/**
*************
* Changes log
*
*************
* 2011 10 25
*************
* 
* Revise path to benchmark folder
*
* method affected ::
*
* OPTIMIZER :: logResults
* 
* (branch 0.1 :: revision 772)
**
*/