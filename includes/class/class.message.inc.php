<?php

/**
* Message class
*
* Class for handling message 
* @package  sefi
*/
class Message extends Header
{
	/**
	* Get a signature
	*
	* @param	boolean	$namespace	namespace flag
	* @return	string	signature
	*/
	public static function getSignature( $namespace = TRUE )
	{
		$_class = __CLASS__;

		if ( ! $namespace )

			list( $_namespace, $_class ) = explode( '\\', __CLASS__ );

		return $_class;
	}

	/**
	* Make an instance of the Message class
	*
	* @return	object	Message instance
	*/
	public static function make()
	{
		global $class_application;
		
		$class_dumper = $class_application::getDumperClass();

		$arguments = func_get_args();

		if ( isset( $arguments[0] ) )

			$body_html = $arguments[0];
		else

			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		if ( isset( $arguments[1] ) )

			$header_id = $arguments[1];
		else

			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		if ( ! isset($arguments[2] ) )

			$type = NULL;
		else

			$type = $arguments[2];

		if ( is_null( $type ) )
	
			$message_type = self::getDefaultType();
		else
		{
			$properties = array(
				PROPERTY_NAME => $type,
				PROPERTY_ENTITY => ENTITY_MESSAGE
			);
			
			// fetch the selected store type
			$message_type = self::getTypeValue( $properties );
		}

		$properties = array(
			PROPERTY_BODY_HTML => $body_html,
			PROPERTY_TYPE => $message_type,
			PREFIX_TABLE_COLUMN_HEADER.PROPERTY_ID => array(
				PROPERTY_FOREIGN_KEY => $header_id
			)
		);

		return self::add( $properties );
	}
}
?>