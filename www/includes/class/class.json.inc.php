<?php

/**
* Json class
*
* Class for handling json
* @package  sefi
*/
class Json extends \cid\Store
{
	/**
	* Get a Json store by providing its id
	*
	* @param	integer	$id	identifier
	* @return	object	Json
	*/
	public static function getById( $id )
	{
		if ( ! is_numeric( $id  ) )
			
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		return self::getByProperty(
			$id,
			PROPERTY_ID,
			array(
				PROPERTY_ID,
				PROPERTY_HASH,
				PROPERTY_STATUS,
				PROPERTY_TYPE,
				PROPERTY_VALUE
			),
			CLASS_JSON,
			true
		);	
	}

	/**
	* Get a Json store by providing its hash
	*
	* @param	string	$hash	hash
	* @return	object	Json
	*/
	public static function getByHash( $hash )
	{
		if ( ! is_string( $hash  ) )
			
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		return self::getByProperty(
			$hash,
			PROPERTY_HASH,
			array(
				PROPERTY_ID,
				PROPERTY_HASH,
				PROPERTY_STATUS,
				PROPERTY_TYPE,
				PROPERTY_VALUE
			),
			CLASS_JSON,
			true
		);	
	}

	/**
	* Get a JSON object by its status
	*
	* @param	string	$status status
	* @param	string	$count	number of items to be retrieved
	* @return	object	mixed
	*/
	public static function getByStatus( $status = null, $count = 1 )
	{
		$json_status = $status;
		
		if ( ! is_integer( $status ) )
	
			$json_status = ENTITY_STATUS_ACTIVE;

		$properties = self::fetchProperties(
			array(
				SQL_LIMIT => array(
					PROPERTY_COUNT => $count
				),
				SQL_ORDER_BY => array(
					array(
						PROPERTY_COLUMN => PROPERTY_ID,
						PROPERTY_TYPE => SQL_CLAUSE_ORDER_BY_TYPE_DESC
					)
				),
				SQL_SELECT => array(
					PROPERTY_ID,
					PROPERTY_TYPE,
					PROPERTY_STATUS,
					PROPERTY_VALUE
				),
				PROPERTY_STATUS => $json_status,
			),
			__CLASS__,
			false,
			false // memcache flag
		);

		return $properties;
	}

    /**
   	* Get jsons by their type
   	*
   	* @param	integer	$type   $type
   	* @param	string	$count	number of items to be retrieved
   	* @return	object	mixed
   	*/
   	public static function getByType( $type = null, $count = 1 )
   	{
   		if ( is_null( $type ) )

            $json_type = self::getDefaultType();

        else if (is_string($type))

            $json_type = self::getJsonTypeValue($type);
        else

            throw new \InvalidArgumentException(sprintf('Invalid json type (%)', $type));

   		$properties = self::fetchProperties(
   			array(
   				SQL_LIMIT => array(
   					PROPERTY_COUNT => $count
   				),
   				SQL_SELECT => array(
                    PROPERTY_ID,
                    PROPERTY_HASH,
                    PROPERTY_STATUS,
                    PROPERTY_TYPE,
                    PROPERTY_VALUE
   				),
   				PROPERTY_TYPE => $json_type,
   			),
   			__CLASS__,
   			false,
   			false // memcache flag
   		);

   		return $properties;
   	}

	/**
	* Get a signature
	*
	* @param	boolean	$namespace	namespace flag
	* @return	string	signature
	*/
	public static function getSignature( $namespace = true )
	{
		$_class = __CLASS__;

		if ( ! $namespace )

			list( $_namespace, $_class ) = explode( '\\', __CLASS__ );

		return $_class;
	}

    /**
     * Get json type value
     *
     * @param	string	$type   $type
     * @return mixed
     */
    public static function getJsonTypeValue($type)
    {
        global $class_application;
        $class_entity = $class_application::getEntityClass();

        return $class_entity::getTypeValue(
            array(
                PROPERTY_NAME => $type,
                PROPERTY_ENTITY => ENTITY_JSON
            )
        );
    }

	/**
	* Make an instance of the Json class
	*
	* @return	object	Json instance
	*/
	public static function make()
	{
		global $class_application;
		
		$class_dumper = $class_application::getDumperClass();

		$arguments = func_get_args();

		if ( isset( $arguments[0] ) )

			$value = $arguments[0];
		else

			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		if ( ! isset($arguments[1] ) )

			$type = null;
		else

			$type = $arguments[1];

		if ( is_null( $type ) )

			$json_type = self::getDefaultType();
		else
		{
			$properties = array(
				PROPERTY_NAME => $type,
				PROPERTY_ENTITY => ENTITY_JSON
			);

			// fetch the selected json type
			$json_type = self::getTypeValue( $properties );
		}

		$store = json_decode( $value );
		if ( isset( $store->session ) ) unset( $store->session );

		$properties = array(
			PROPERTY_HASH => md5( json_encode( $store ) ),
			PROPERTY_STATUS => ENTITY_STATUS_ACTIVE,
			PROPERTY_TYPE => $json_type,
			PROPERTY_VALUE => $value
		);

		return self::add( $properties );
	}
}
