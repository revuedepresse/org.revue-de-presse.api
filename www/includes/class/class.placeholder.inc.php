<?php

/**
* Placeholder class
*
* Class for placeholder management
* @package  sefi
*/
class Placeholder extends Controller
{
	/**
	* Get a Placeholder by providing its id
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
				PROPERTY_STATUS,
				PROPERTY_TYPE,
				PROPERTY_NAME,
				PROPERTY_VALUE,
			),
			CLASS_PLACEHOLDER,
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
	* Make an instance of the Placeholder class
	*
	* @return	object	Placeholder instance
	*/
	public static function make()
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$default_type = self::getDefaultType();

		$arguments = func_get_args();

		if ( isset( $arguments[0] ) )

			$name = $arguments[0];
		else

			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		if ( isset( $arguments[1] ) )

			$value = $arguments[1];
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

		$properties = array(
			PROPERTY_NAME => $name,
			PROPERTY_STATUS => $status,
			PROPERTY_TYPE => $type,
			PROPERTY_VALUE => $value
		);

		return self::add( $properties );
	}
}