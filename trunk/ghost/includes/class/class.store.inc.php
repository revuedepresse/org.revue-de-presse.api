<?php

/**
* Store class
*
* @package  sefi
*/
class Store extends Store_Item
{
	/**
	* Get a store by providing its id
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
				PROPERTY_ENTITY_TYPE =>
					array(
						PROPERTY_FOREIGN_KEY =>
							PREFIX_TABLE_COLUMN_ENTITY_TYPE.PROPERTY_ID
					),
				PROPERTY_ID,
				PROPERTY_NAME,
				PROPERTY_STATUS,
				PROPERTY_TYPE
			),
			CLASS_STORE,
			TRUE
		);	
	}
  
	/**
	* Get the class signature
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
	* Make an instance of the Store class
	*
	* @return	object	Store instance
	*/
	public static function make()
	{
		global $class_application;
		
		$class_dumper = $class_application::getDumperClass();

		$arguments = func_get_args();

		if (isset($arguments[0]))

			$name = $arguments[0];
		else
		
			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		if (!isset($arguments[1]))

			$entity = ENTITY_STORE;
		else

			$entity = $arguments[1];

		if (!isset($arguments[2]))

			$type = NULL;
		else

			$type = $arguments[2];

		if (is_null($type))
	
			$store_type = self::getDefaultType();
		else
		{
			$properties = array(
				PROPERTY_NAME => $type,
				PROPERTY_ENTITY => ENTITY_STORE
			);
			
			// fetch the selected store type
			$store_type = self::getTypeValue($properties);
		}
	
		// fetch the store entity
		$entity_store = self::getByName($entity);
		
		$properties = array(
			PROPERTY_NAME => $name,
			PROPERTY_STATUS => ENTITY_STATUS_ACTIVE,
			PROPERTY_TYPE => $store_type,
			PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID => array(
				PROPERTY_FOREIGN_KEY => $entity_store->{PROPERTY_ID}
			)
		);
		
		return self::add($properties);
	}
}
?>