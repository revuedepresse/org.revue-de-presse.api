<?php

/**
* Token class
*
* Class for managing tokens
* @package  sefi
*/
class Token extends Controller 
{
	/**
	* Get a token by providing its id
	*
	* @param	integer	$id	identifier
	* @return	object	Store
	*/
	public static function getById( $id )
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
				PROPERTY_ID,
				PROPERTY_STATUS,
				PROPERTY_TYPE,
				PROPERTY_VALUE
			),
			CLASS_TOKEN,
			TRUE
		);	
	}

	/**
	* Fetch the latest tokens
	*
	* @param	array	$types	types
	* @return	mixed	tokens
	*/
	public static function fetchLatestTokens( $types = NULL )
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$tokens = array();

		if ( is_null( $types ) )

			$property_type = array(
				PROPERTY_TYPE =>
					$token_type_default = self::getDefaultType()
			);

		else if ( ! is_array( $types ) || count( $types ) )
		{
			$condition_types = array();

			while ( list( $index, $type ) = each( $types) )

				if (
					! is_null( $type ) &&
					is_string( $type ) &&
					strlen( trim( $type ) )
				)
				{
					$_properties = array(
						PROPERTY_NAME => $type,
						PROPERTY_ENTITY => ENTITY_TOKEN
					);
		
					// fetch the token type
					$condition_types[] .= self::getTypeValue( $_properties );
				}

			if ( count( $condition_types ) )

				$property_type = array( SQL_IN => $condition_types );
		}
		else
		
			throw new Exception( EXCEPTION_INVALID_ARGUMENT ) ;

		$tokens = self::fetchInstances(
			array(
				PROPERTY_TYPE => $property_type,
				PROPERTY_STATUS => ENTITY_STATUS_ACTIVE,
				SQL_SELECT => array(
					PROPERTY_ID => array(
						PROPERTY_RETURN =>
							SQL_FUNCTION_MAX,
						PROPERTY_PARAMETER =>
							PROPERTY_ID
					),
					PROPERTY_STATUS,
					PROPERTY_TYPE,
					PROPERTY_VALUE
				),
				SQL_GROUP_BY => array( PROPERTY_TYPE )
			)
		);

		return $tokens;
	}

	/**
	* Make an of the Token class
	*
	* @return	object	Token
	*/
	public static function make()
	{
		global $class_application;
		
		$class_dumper = $class_application::getDumperClass();

		$arguments = func_get_args();

		// check if the mandatory token value has been provided
		if ( isset( $arguments[0] ) )

			$value = $arguments[0];
		else

			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		// check if the optional token type has been provided
		if ( ! isset( $arguments[1] ) )

			$type = NULL;
		else
		
			$type = $arguments[1];

		// check if the optional entity name has been provided
		if ( ! isset( $arguments[2] ) )

			$entity = NULL;
		else
		
			$entity = $arguments[2];

		if ( ! is_string( $value ) )

			throw new \Exception(EXCEPTION_INVALID_ARGUMENT);

		if ( is_numeric( $type ) && $type ) 

			$entity_type = $type;

		else if (
			is_string ( $entity ) &&
			strlen( trim ( $entity ) ) > 0
		)

			$entity_id = self::getByName( $entity );

		else if ( ! is_null( $entity ) )

			throw new \Exception(EXCEPTION_INVALID_ARGUMENT);
		else
		
			$entity_id = $entity;

		$token_type = self::getDefaultType();

		if (
			! is_null( $type ) &&
			is_string( $type ) &&
			strlen( trim( $type ) )
		)
		{
			$properties = array(
				PROPERTY_NAME => $type,
				PROPERTY_ENTITY => ENTITY_TOKEN
			);

			// fetch the token type
			$token_type = self::getTypeValue($properties);
		}

		$properties = array(
			PROPERTY_VALUE => $value,
			PROPERTY_STATUS => ENTITY_STATUS_ACTIVE,
			PROPERTY_TYPE => $token_type
		);

		if ( ! is_null( $entity_id ) )

			$properties[PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID] = array(
				PROPERTY_FOREIGN_KEY => $entity_id
			);

		return self::add( $properties );
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

		if (!$namespace)

			list($_namespace, $_class) = explode('\\', __CLASS__);

		return $_class;
	}
}