<?php

/**
* Event Manager class
*
* Class for event management
* @package  sefi
*/
class Event_Manager extends Controller
{
	/**
	* Log events
	*
	* @param	array	$events	events
	* @return	nothing
	*/
	public static function log( $events )
	{
		if (
			is_array( $events ) &&
			( $events_count = count( $events ) )
		)

			for ( $i = 0 ; $i < $events_count ; $i++ )

				$events[$i]->serialize(); 
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