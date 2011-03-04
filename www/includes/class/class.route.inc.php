<?php

/**
* Route class
*
* @package  sefi
*/
class Route extends Data_Fetcher
{
	/**
	* Get a route by providing its id
	*
	* @param	integer	$id	identifier
	* @return	object	Store
	*/
	public static function getById($id)
	{
		if ( ! is_numeric( $id  ) )
			
			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		return self::getByProperty(
			$id,
			PROPERTY_ID,
			array(
				PROPERTY_ENTITY =>
					array(
						PROPERTY_FOREIGN_KEY =>
							PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID
					),
				PROPERTY_CONTENT_TYPE =>
					array(
						PROPERTY_FOREIGN_KEY =>
							PREFIX_TABLE_COLUMN_CONTENT_TYPE.PROPERTY_ID
					),
				PROPERTY_ID,
				PROPERTY_INDEX,
				PROPERTY_PARENT_HUB,
				PROPERTY_LEVEL,
				PROPERTY_STATUS,
				PROPERTY_TYPE,
				PROPERTY_URI
			),
			CLASS_ROUTE,
			TRUE
		);	
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

	/**
	* Make an instance of the Route class
	*
	* @return	object	Route
	*/
	public static function make()
	{
		global $class_application;
		
		$class_dumper = $class_application::getDumperClass();

		$arguments = func_get_args();

		if ( isset( $arguments[0] ) )

			$uri = $arguments[0];
		else

			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		if ( isset( $arguments[1] ) )

			$type = $arguments[1];
		else

			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		if ( ! isset( $arguments[2] ) )

			$entity = NULL;
		else

			$entity = $arguments[2];

		if ( ! isset( $arguments[3] ) )

			$parent = NULL;
		else
		
			$parent = $arguments[3];

		if ( ! isset( $arguments[4] ) )

			$level = NULL;
		else
		
			$level = $arguments[4];

		if ( ! isset( $arguments[5] ) )

			$index = NULL;
		else
		
			$index = $arguments[5];

		if ( ! is_string( $uri ) )

			throw new \Exception(EXCEPTION_INVALID_ARGUMENT);

		if ( is_numeric( $entity ) && $entity )

			$entity_id = $entity;

		else if (
			is_string ( $entity ) &&
			strlen( trim ( $entity ) ) > 0
		)

			$entity_id = self::getByName( $entity );

		else if ( !is_null( $entity ) )

			throw new \Exception(EXCEPTION_INVALID_ARGUMENT);
		else
		
			$entity_id = $entity;

		if ( ! is_numeric ( $index ) && ! is_null( $index ) )

			throw new \Exception(EXCEPTION_INVALID_ARGUMENT);

		if ( ! is_numeric ( $level ) && ! is_null( $level ) )

			throw new \Exception(EXCEPTION_INVALID_ARGUMENT);

		// 	fetch the default form type: content
		$route_type = 

		$route_default_type = self::getDefaultType();

		if (
			! is_null( $type ) &&
			is_string( $type ) &&
			strlen( trim( $type ) )
		)
		{
			$properties = array(
				PROPERTY_NAME => $type,
				PROPERTY_ENTITY => ENTITY_ROUTE
			);

			// fetch the form type
			$route_type = self::getTypeValue($properties);
		}

		$properties = array(
			PROPERTY_URI => $uri,
			PROPERTY_STATUS => ENTITY_STATUS_ACTIVE,
			PROPERTY_TYPE => $route_type
		);

		if ( ! is_null( $entity_id ) )

			$properties[PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID] = array(
				PROPERTY_FOREIGN_KEY => $entity_id
			);

		if ( ! is_null( $index ) )

			$properties[PROPERTY_INDEX] = $index;

		if ( ! is_null( $level ) )

			$properties[PROPERTY_LEVEL] = $level;

		if ( ! is_null( $parent ) && is_numeric( $parent ) )

			$properties[PROPERTY_PARENT_HUB] = $parent;


		return self::add($properties);
	}
}
?>