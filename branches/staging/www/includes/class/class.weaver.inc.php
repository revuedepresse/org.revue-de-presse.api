<?php

/**
* Weaver class
*
* Class for weaving
* @package  sefi
*/
class Weaver extends Arc
{
    /**
    * Dereference the properties of an object
    *
	* @tparam	object	$object		object
	* @tparam	array	$properties	properties
	* @tparam	integer	$depth		depth of dereferencing
	* @tparam	string	$method		method to be used for dereferencing
	* @return	nothing
	*/
	public static function dereference()
	{
		global $class_application, $verbose_mode;
		
		$class_dumper = $class_application::getDumperClass();

		$arguments = func_get_args();

		if ( isset( $arguments[0] ) )

			$object = $arguments[0];
		else
		
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );
		
		if ( ! isset( $arguments[1] ) )
		
			$properties = NULL;
		else
		
			$properties = $arguments[1];

		if ( ! isset( $depth ) )
		
			$depth = 1;
		else
		
			$depth = $arguments[2];

		if ( ! isset( $arguments[3] ) )
		
			$method = NULL;
		else

			$method = $arguments[3];

		if ( is_null( $method ) )
	
			$method = 'getById'; 

		$class_dumper::log(
			__METHOD__,
			array($properties)
		);

		if ( ! is_null ( $properties ) )

			if ( is_array( $properties ) && count( $properties )  )
			{
				$_object = clone $object;

				while ( list ( $property, $class ) = each( $properties ) )

					if (
						isset( $object->$property ) &&
						$object->$property &&
						class_exists( $class ) &&
						( $class_methods = get_class_methods( $class ) ) &&
						in_array( 'getById', $class_methods )
					)

						$_object->$property = array(
							PROPERTY_ID => $object->$property,
							PROPERTY_OBJECT => call_user_func_array(
								array(
									$class,
									$method
								),
								array(
									$object->$property
								)
							)
						);
	
				$object = $_object;
			}

		return $object;
	}

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
}