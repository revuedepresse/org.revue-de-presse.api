<?php

/**
* Feedback class
*
* Class for feedback management
* @package  sefi
*/
class Feedback extends Message
{
	/**
	* Get a Feedback by providing its id
	*
	* @param	integer	$id	identifier
	* @return	object	Form
	*/
	public static function getById( $id )
	{
		if ( ! is_numeric( $id  ) )

			throw new \Exception(EXCEPTION_INVALID_ARGUMENT);

		return self::getByProperty(
			$id,
			PROPERTY_ID,
			array(
				PROPERTY_ID,
				PROPERTY_HASH,
				PROPERTY_STATUS,
				PROPERTY_TITLE,
				PROPERTY_TYPE,
				PROPERTY_BODY
			),
			CLASS_FEEDBACK,
			TRUE
		);	
	}

    /**
    * Get a placeholder
    *
	* @param	boolean	$namespace	namespace flag
	* @return	string	signature
	*/
	public static function getSignature( $namespace = TRUE )
	{
		$_class = __CLASS__;

		if ( ! $namespace )

			list(
				$_namespace,
				$_class
			) = explode( '\\', __CLASS__ );

		return $_class;
	}

	/**
	* Make an instance of the Feedback class
	*
	* @return	object	Feedback instance
	*/
	public static function make()
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$default_type = self::getDefaultType();

		$arguments = func_get_args();

		if ( isset( $arguments[0] ) )

			$title = $arguments[0];
		else

			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		if ( isset( $arguments[1] ) )

			$body = $arguments[1];
		else

			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		if ( isset( $arguments[2] ) )

			$type = $arguments[2];
		else

			$type = $default_type;

		if ( isset( $arguments[3] ) )

			$status = $arguments[3];
		else

			$status = ENTITY_STATUS_INACTIVE;

		$separator = '€$€';

		$hash = md5(
			$type . $separator .
			$title . $separator .
			$body . $separator
		);

		$properties = array(
			PROPERTY_STATUS => $status,
			PROPERTY_TITLE => $title,
			PROPERTY_TYPE => $type,
			PROPERTY_BODY => $body,
			PROPERTY_HASH => $hash
		);

		return self::add( $properties );
	}
}
