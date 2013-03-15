<?php

/**
* Query class
*
* Class for representing an Query
* @package  sefi
*/
class Query extends Interceptor
{
	/**
	* Get a query by providing its id
	*
	* @param	integer	$id	identifier
	* @return	object	Store
	*/
	public static function getById( $id )
	{
		global $class_application;

		$agent_entity = $class_application::getEntityAgent();

		if ( ! is_numeric( $id  ) )

			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		if ( static::getSignature() === __CLASS__ )

			$callback_parameters = self::getByProperty(
				$id,
				PROPERTY_ID,
				array(
					PROPERTY_ID,
					PROPERTY_STATUS,
					PROPERTY_TYPE,
					PROPERTY_VALUE
				),
				__CLASS__,
				TRUE
			);
		else
		
			$callback_parameters = $agent_entity::getById( $id );

		return $callback_parameters;
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
	* Parse a query
	*
	* @param	string	$query		query
	* @param	string	$query_type	query type
	* @return	mixed
	*/
	public static function parse($query, $query_type = NULL)
	{
		global $class_application, $verbose_mode;

		$class_data_fetcher = $class_application::getDataFetcherClass();

		$class_db = $class_application::getDbClass();

		$class_dumper = $class_application::getDumperClass();

		$entity_names = 
		
		$objects = array();

		$query_language_type_sql = $class_data_fetcher::getEntityTypeValue(
			array(
				PROPERTY_NAME => ENTITY_SQL,
				PROPERTY_ENTITY => ENTITY_QUERY_LANGUAGE
			)
		);

		$default_query_type = $query_language_type_sql;

		if ( is_null( $query_type ) )

			$query_type = $default_query_type;

		if ( is_string ( $query ) && strlen( trim( $query ) ) )
		{
			$results = $class_db::query( $query );

			if ( $results->num_rows )

				while ( $object = $results->fetch_object() )

					if ( isset( $object->{PROPERTY_ID} ) )

						$objects[$object->{PROPERTY_ID}] = $object;
					else

						throw new Exception(
							sprintf(
								EXCEPTION_INVALID_ENTITY,
								ENTITY_QUERY
							)
						);
		}

		while ( list( $id, $object ) = each( $objects ) )
		{
			while ( list( $index, $property ) = each( $object ) )
			{
				// format class names with namespaces
				$entity_name = str_replace(
					'__',
					'\\',
					$index
				);

				if (
					$index != PROPERTY_ID &&
					strpos( $index, '(object)' ) === FALSE
				)
				{
					// instantiate objects from their getById methods
					$instance = call_user_func_array(
						array(
							$entity_name,
							'getById'
						),
						array( $property )
					);

					$objects[$id]->{
						'(object) '.
						$entity_name
					} = array( $instance );

					unset($objects[$id]->$index);

					// list current entity names
					$entity_names[get_class( $instance )] =
						strtolower( $entity_name );
				}
			}
			
			reset($object);
		}

		reset($objects);

		while ( list( $id, $object ) = each( $objects ) )
		{
			while ( list( $index, $instances ) = each( $object ) )
			{
				if ( $index != PROPERTY_ID )
				{
					$attributes =
						$instances[count($instances) - 1]
							->getProperties();

					// loop on attributes for objects dereferencing 
					while ( list( $attribute, $value ) = each( $attributes ) )
					{
						$instance_type_name = NULL;

						// consider key attributes as special foreign keys
						if (
							$attribute == PROPERTY_KEY &&
							isset( $attributes->{PROPERTY_TYPE} )
						)
						{
							$entity_instance_types =
								$class_data_fetcher::fetchEntityTypes(
									$attributes->{PROPERTY_SIGNATURE},
									PROPERTY_VALUE
								);

							$instance_type = $attributes->{PROPERTY_TYPE};

							if (
								isset( $entity_instance_types[$instance_type] )
							)

								$instance_type_name =
									$entity_instance_types
										[$instance_type]->{PROPERTY_NAME}; 
						}

						if (
							in_array( $attribute, $entity_names ) ||
							! is_null( $instance_type_name ) &&
							in_array( $instance_type_name, $entity_names )
						)
						{
							if ( is_null ( $instance_type_name ) )

								$key = array_search(
									$attribute,
									$entity_names
								);
							else

								$key = array_search(
									$instance_type_name,
									$entity_names
								);

							if ( $key !== FALSE )
							{
								$carrier = &$objects[$id]->$index;

								// bind entities by reference
								$_object = &$objects[$id]->{
									'(object) '.
									$key
								};

								if (
									$value ==
										$_object[count($instances) - 1]
											->{PROPERTY_ID}
								)

									$carrier[count($instances) - 1]->
										$attribute =
											array(
												PROPERTY_ID => $value,
												PROPERTY_REFERENCE =>
													&$_object[count($instances) - 1]
											)
									;
							}
						}
					}

					reset( $attributes );
				}
			}

			reset( $object );
		}

		reset( $objects );

		return $objects;
	}
}
?>