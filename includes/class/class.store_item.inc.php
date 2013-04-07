<?php

/**
* Store Item class
*
* @package  sefi
*/
class Store_Item extends Entity
{
	/**
	* Get a store item by providing its id
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
				PROPERTY_KEY,
				PROPERTY_ID,
				PROPERTY_INDEX,
				PROPERTY_STATUS,
				PROPERTY_STORE =>
					array(
						PROPERTY_FOREIGN_KEY =>
							PREFIX_TABLE_COLUMN_STORE.PROPERTY_ID
					),
				PROPERTY_TYPE
			),
			CLASS_STORE_ITEM,
			TRUE
		);	
	}

	/**
	* Get the class signature
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
	* Make an instance of the Store Item class
	*
	* @return	object	Store Item instance
	*/
	public static function make()
	{
		$arguments = func_get_args();

		if (isset($arguments[0]))

			$key = $arguments[0];
		else
		
			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		if (isset($arguments[1]))

			$parent = $arguments[1];
		else
		
			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		if (!isset($arguments[2]))

			$entity = ENTITY_STORE;
		else

			$entity = $arguments[2];

		if (!isset($arguments[3]))

			$type = NULL;
		else

			$type = $arguments[3];

		if (is_null($type) && $entity == ENTITY_STORE)

			// fetch the default store item type: query
			$store_item_type = self::fetchDefaultType()->value;

		else 
		{
			if ( is_null($type) && $entity != ENTITY_STORE )

				$type = $entity;

			$properties = array(
				PROPERTY_NAME => $type,
				PROPERTY_ENTITY => ENTITY_STORE
			);
			
			// fetch the selected store item type
			$store_item_type = self::getTypeValue($properties);
		}

		// fetch the store entity
		$entity_store = self::getByName($entity);
		
		$properties = array(
			PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID => array(
				PROPERTY_FOREIGN_KEY => $entity_store->{PROPERTY_ID}
			),
			PREFIX_TABLE_COLUMN_STORE.PROPERTY_ID => array(
				PROPERTY_FOREIGN_KEY => $parent
			),
			PROPERTY_KEY => $key,
			PROPERTY_STATUS => ENTITY_STATUS_ACTIVE,
			PROPERTY_TYPE => $store_item_type,
		);
	
		return self::add($properties);
	}
}
?>