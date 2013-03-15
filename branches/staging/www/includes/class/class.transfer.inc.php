<?php

/**
* Transfer class
*
* Class representing data transfer 
* @package  sefi
*/
class Transfer extends Serializer
{
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

	/**
	* Synchronize an entity with a persistence layer
	*
	*
	* @param	mixed	&$context		context
	* @param	mixed	$callback		callback
	* @param	integer	$storage_model	storage model
	* @param	boolean	$verbose		verbose mode
	* @param	mixed	$checksum		checksum
	* @return	nothing
	*/
	public static function synchronize(
		&$context,
		$callback = NULL,
		$storage_model = STORE_DATABASE,
		$verbose = FALSE,
		$checksum = NULL
	)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();
		
		$default_transfer_type = self::getDefaultType();

		$syncing_results = NULL;

		if (is_null($callback))

			$transfer_type = $default_transfer_type;

		else if (
			is_array($callback) &&
			count($callback) &&
			isset($callback[PROPERTY_TYPE])
		)
		{
			$properties = array(
				PROPERTY_NAME => $callback[PROPERTY_TYPE],
				PROPERTY_ENTITY => ENTITY_TRANSFER
			);

			$transfer_type = self::getTypeValue($properties);
		}

		if ( $transfer_type == $default_transfer_type )
		{
			$arguments = func_get_args();

			if (!isset($arguments[1]) || is_null($arguments[1]))

				$arguments[1] = array(
					ENTITY_SYNCHRONIZATION => $default_transfer_type
				);

			$arguments[0] = &$context;

			$syncing_results = call_user_func_array(
				array('self', 'saveEntity'),
				$arguments
			);
		}

		$class_dumper::log(
			__METHOD__,
			array($syncing_results)
		);	

		return $syncing_results;
	}
}
?>