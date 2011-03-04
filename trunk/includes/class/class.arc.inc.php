<?php

/**
* Arc class
*
* Class for representing an Arc 
* @package  sefi
*/
class Arc extends Edge
{
	/**
	* Get the source edge of an Arc
	*
	* @return	mixed	source edge
	*/
	public function getSourceEdge()
	{
		return parent::getById(
			$this->{PROPERTY_SOURCE},
			NULL,
			CLASS_EDGE
		);
	}

	/**
	* Get an Arc by the key property of its destination edge
	*
	* @param	integer		$edge		edge
	* @param	mixed		$type 		type of Arc
	* @param	boolean		$instantiation	instantiation flag
	* @return	string		signature
	*/
	public static function getByDestination(
		$edge,
		$type = NULL,
		$instantiation = TRUE
	)
	{
		$callback_parameters = NULL;

		if (is_null($type))

			$type = self::getDefaultType();

		if ( is_object( $edge ) && isset( $edge->{PROPERTY_ID} ) )
		{
			$destination_id = $edge->{PROPERTY_ID};

			if ( $instantiation )
			{
				$properties = self::getByDestinationId(
					$destination_id,
					$type,
					! $instantiation
				);

				/**
				 *
				 * Prepare Arc class instantiation
				 * for the 1-N cardinality case 
				 *
				 * e.g.
				 *
				 * 1 source for N destination
				 * N source for 1 destination
				 *
				 */
				if ( count( $properties ) > 1 )
				{
					$callback_parameters = array();
		
					while ( list( , $properties_arc ) = each( $properties ) )
	
						$callback_parameters[] = new self( (array) $properties_arc );
				}
				else 

					$callback_parameters = new self(
						( array ) $properties
					);
			}
			else
			
				$callback_parameters = self::getByDestinationId(
					$destination_id,
					$type,
					$instantiation
				);
		}

		return $callback_parameters;
	}

	/**
	* Get an Arc by the edge key of its destination
	*
	* @param	integer		$key			key
	* @param	mixed		$entity_type 	type of Entity
	* @param	mixed		$type		Arc type
	* @param	boolean		$instantiation	instantiation flag
	* @return	mixed
	*/
	public static function getByDestinationKey(
		$key,
		$entity_type = NULL,
		$type = NULL,
		$instantiation = TRUE
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();
		
		if ( is_null( $type ) )
		
			$arc_type = self::getDefaultType();
		else
		{
			$arc_type_properties = array(
				PROPERTY_NAME => $type,
				PROPERTY_ENTITY => ENTITY_ARC
			);

			// fetch the arc type
			$arc_type = self::getTypeValue(
				$arc_type_properties
			);					
		}
		
		if ( is_null( $entity_type ) )
		
			$entity_type = ENTITY_INSIGHT_NODE;

		$edge = self::getByKey( $key, $entity_type );

		return self::getByDestination(
			$edge,
			$arc_type,
			$instantiation
		);
	}

	/**
	* Get an Arc by the edge identifier of its destination
	*
	* @param	integer	$edge_id		edge id
	* @param	mixed	$type 			type of Arc	
	* @param	boolean	$instantiation	instantiation flag
	* @return	object	mixed
	*/
	public static function getByDestinationId(
		$edge_id,
		$type = NULL,
		$instantiation = TRUE
	)
	{
		global $class_application, $verbose_mode;
		
		$class_dumper = $class_application::getDumperClass();
		
		if ( is_null( $type ) )

			$type = self::getDefaultType();

		$properties = self::fetchProperties(
			array(
				SQL_SELECT => array(
					PROPERTY_DESTINATION,
					PROPERTY_ID,
					PROPERTY_SOURCE,
					PROPERTY_TYPE
				),
				PROPERTY_DESTINATION => $edge_id,
				PROPERTY_STATUS => ENTITY_TYPE_STATUS_ACTIVE,
				PROPERTY_TYPE => $type
			),
			__CLASS__
		);

		if ( $instantiation )
		{
			$callback_parameters = array();

			if ( count( $properties ) > 1 )
			
				while ( list( , $properties_arc ) = each( $properties ) )

					$callback_parameters[] = new self( (array) $properties_arc );
			else 

				$callback_parameters = new self( ( array ) $properties );
		}
		else
		
			$callback_parameters = $properties;

		return $callback_parameters;
	}

	/**
	* Get an edge type by name
	*
	* @param	string	$name	name
	* @return	object	mixed
	*/
	public static function getEdgeTypeByName($name = NULL)
	{
		return parent::getTypeByName($name);
	}

	/**
	* Get an edge type id by name
	*
	* @param	string	$name	name
	* @return	object	mixed
	*/
	public static function getEdgeTypeIdByName($name = NULL)
	{
		return parent::getTypeIdByName($name);
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
	* Make an instance of the Arc class
	*
	* @return	object	Arc instance
	*/
	public static function make()
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$default_entity = self::getByName( ENTITY_ENTITY )->{PROPERTY_ID};

		$arguments = func_get_args();

		$exception_invalid_argument = EXCEPTION_INVALID_ARGUMENT;

		/**
		*
		* Enables integers used as primary keys or
		* objects with id properties to be passed as arguments
		*
		*/
		if ( isset( $arguments[0] ) )
		{
			if ( is_integer( $arguments[0] ) )

				$edge_destination = $arguments[0];
		
			else if (
				is_object( $arguments[0] ) &&
				isset( $arguments[0]->{PROPERTY_ID} ) &&
				! ( $edge_destination = $arguments[0]->{PROPERTY_ID} )
			)
			
				throw new Exception( $exception_invalid_argument );
		}
		else

			throw new Exception( $exception_invalid_argument );

		if ( isset( $arguments[1] ) )
		{
			if ( is_integer( $arguments[1] ) )

				$edge_source = $arguments[1];
		
			else if (
				is_object( $arguments[1] ) &&
				isset( $arguments[1]->{PROPERTY_ID} ) &&
				! ( $edge_source = $arguments[1]->{PROPERTY_ID} )
			)
			
				throw new Exception( $exception_invalid_argument );
		}
		else

			throw new Exception( $exception_invalid_argument );

		if ( isset( $arguments[2] ) )
		{
			$type = $arguments[2];

			$arc_type_properties = array(
				PROPERTY_NAME => $type,
				PROPERTY_ENTITY => ENTITY_ARC
			);

			// fetch the arc type
			$arc_type = self::getTypeValue(
				$arc_type_properties
			);			
		}
		else

			$arc_type = ARC_TYPE_ENCAPSULATION;

		$properties = array(
			PROPERTY_TYPE => $arc_type,
			PROPERTY_DESTINATION => $edge_destination,
			PROPERTY_SOURCE => $edge_source
		);

		return self::add( $properties );
	}	
}