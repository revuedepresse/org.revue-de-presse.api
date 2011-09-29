<?php

/**
* Edge class
*
* Class for representing an Edge
* @package  sefi
*/
class Edge extends Controller
{
	/**
	* Get an edge type by name
	*
	* @param	string	$name	name
	* @return	object	mixed
	*/
	public static function getTypeByName($name = NULL)
	{
		if (!is_string($name))
	
			$edge_type = NULL;
		else
		{
			$edge_type = self::getByName(
				$name,
				NULL,
				CLASS_ENTITY
			);
		}

		return $edge_type;
	}

	/**
	* Get an edge type id by name
	*
	* @param	string	$name	name
	* @return	object	mixed
	*/
	public static function getTypeIdByName($name = NULL)
	{
		$edge_type = self::getTypeByName($name);

		$edge_type_id = NULL;

		if (is_object($edge_type) && !empty($edge_type->{PROPERTY_ID}));
			
			$edge_type_id = $edge_type->{PROPERTY_ID};

		return $edge_type_id;
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
	* Get by key
	* 
	* @param	integer			$key		key
	* @param	string			$key_type	entity type of the key
	* @return	string	signature
	*/
	public static function getByKey($key, $key_type = NULL)
	{
		if (is_null($key_type))
		
			$key_type = ENTITY_INSIGHT_NODE;

		return self::getByConditions(
			array(
				PROPERTY_KEY => $key,
				PROPERTY_FOREIGN_KEY => array(
					PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID =>
						self::getByName($key_type, NULL, CLASS_ENTITY)->{PROPERTY_ID}
				)
			),
			CLASS_EDGE
		);
	}

	/**
	* Get the properties of an edge by providing its identifier
	*
	* @param	integer	$id	identifier
	* @return	object	Form
	*/
	public static function getById( $id )
	{
		if ( ! is_numeric( $id  ) )

			throw new \Exception( EXCEPTION_INVALID_ARGUMENT );

		return self::getByProperty(
			$id,
			PROPERTY_ID,
			array(
				PROPERTY_KEY,
				PROPERTY_STATUS
			),
			self::getSignature(),
			TRUE
		);	
	}

	/**
	* Fetch the properties of an entity type edge from its name
	*
	* @param	string	$name	name
	* @return	mixed
	*/	
	public static function fetchEntityTypePropertiesByName($name = NULL)
	{
		if (!is_string($name))
		
			$properties = array();
		else
		{
			$entity_type = self::getTypeIdByName(CLASS_ENTITY_TYPE);
			
			$key = self::getTypeIdByName($name);

			$properties = array(
				PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID => $entity_type,
				PROPERTY_KEY => $key
			);
		}

		return $properties;
	}

	/**
	* Make an instance of the Edge class
	*
	* @return	object	Edge instance
	*/
	public static function make()
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$default_entity = self::getByName( ENTITY_ENTITY )->{PROPERTY_ID};

		$arguments = func_get_args();

		if ( isset( $arguments[0] ) )

			$key = $arguments[0];
		else

			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		if ( isset( $arguments[1] ) )
		{
			$name = $arguments[1];

			// Get the properties of an entity by providing its name
			$entity = self::getByName( $name )->{PROPERTY_ID};
		}
		else

			$entity = $default_entity;

		if ( isset( $arguments[2] ) )

			$status = $arguments[2];
		else

			$status  = ENTITY_STATUS_INACTIVE;

		$properties = array(
			PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID => array(
				PROPERTY_FOREIGN_KEY => $entity
			),
			PROPERTY_KEY => $key,
			PROPERTY_STATUS => $status
		);

		return self::add( $properties );
	}
}