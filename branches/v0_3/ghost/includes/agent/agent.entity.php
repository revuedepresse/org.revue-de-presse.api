<?php

/**
* Entity class
* 
* Class to construct Entity
* @package sefi
*/
class Entity implements Model_Entity
{
    protected $properties;
    protected static $namespace;
    protected static $store = NULL;

	/**
	* Get magically a property featuring an entity
	*
	* @param	string	$name	name
	* @return	mixed	property value
	*/
	public function &__get($name)
	{
		$property = &$this->getProperty($name);
		
		return $property;
	}

	/**
	* Get the properties featuring an entity
	*
	* @return	mixed	properties
	*/
	public function &getProperties()
	{
		return $this->properties;
	}

	/**
	* Get the value of a property featuring an entity
	*
	* @param	string	$name	name
	* @return	mixed	property value
	*/
	public function &getProperty( $name )
	{
		if ( ! isset( $this->properties ) )
		
			throw new Exception( EXCEPTION_CONSISTENCY_ISSUE );
		
		if ( ! isset( $this->properties->$name ) )
		
			$this->properties->$name = NULL;
			
		return $this->properties->$name;
	}

	/**
	* Call magically a non-declared static method 
	*
	* @param	string	$name			name of magic static method
	* @param	array	$arguments		arguments
	* @return	nothing
	*/	
	public static function __callStatic($name, $arguments)
	{
		global $class_application, $verbose_mode;

		if ( empty( $class_application ) )

			fprint(
				debug_backtrace(),
				UNIT_TESTING_MODE_STATUS,
				array(
					'line' => __LINE__,
					'file' => __FILE__
				)
			);

		$class_dumper = $class_application::getDumperClass();

		$class_parser = $class_application::getParserClass();

		$callback_parameters = NULL;

		$exception_details = '';

		self::checkStaticParameters($entity_type);

		$operation_get_by = str_replace('.', '', ACTION_GET_BY);

		$operation_get_type = str_replace('.', '', ACTION_GET_TYPE);

		$pattern_get_instance_type =
			REGEXP_OPEN.
				ACTION_GET.
				'(?!'.CLASS_ENTITY.')'.
					REGEXP_CATCH_START.
					'\S+'.
					REGEXP_CATCH_END.
				ucfirst(PROPERTY_TYPE).
				'(?:'.
				REGEXP_CATCH_START.
					ucfirst(PROPERTY_PROPERTY).
				REGEXP_CATCH_END.
				REGEXP_OR.
				REGEXP_CATCH_START.
					'\S+'.
				REGEXP_CATCH_END.
				')'.
			REGEXP_CLOSE
		;
		
		if (
			isset( $arguments[1] ) &&
			isset( $arguments[2] ) && 
			( $arguments[2] == 1024 )
		)

			$class_dumper::log(
				__METHOD__,
				array($name)
			);

		if (
			FALSE !== strpos(strtolower($name), $operation_get_type) ||
			FALSE !== strpos(strtolower($name), $operation_get_by)
		)
		{
			if ( isset( $arguments[2] ) )
	
				$informant = $arguments[2];

 			if ( FALSE !== strpos(strtolower($name), $operation_get_type) )


				$operation = $operation_get_type;
			else 

				$operation = $operation_get_by;

			$property = strtolower(substr($name, strlen($operation)));

			if (
				is_array($arguments) &&
				isset($arguments[0])
			)
			{
				array_unshift($arguments, $property);

				if ( $operation == $operation_get_type )

					$method_action = ACTION_GET_TYPE.'.'.PROPERTY_PROPERTY;
				else
				{
					$method_action = ACTION_GET_BY.'.'.PROPERTY_PROPERTY;

					if (isset($arguments[1]))

						$value = $arguments[1];
					else
					
						throw new Exception(EXCEPTION_INVALID_ARGUMENT);

					$name = $arguments[0];

					$arguments[0] = $value;
					$arguments[1] = $name;
				}

				$method_name = $class_parser::translate_entity(
					$method_action,
					ENTITY_NAME_METHOD
				);

				if ( isset( $informant ) && $informant == 1024 )

					$class_dumper::log(
						__METHOD__,
						array($method_name, $entity_type, $arguments)
					);

				if ( in_array( $method_name, get_class_methods( $entity_type ) ) )
	
					$callback_parameters = call_user_func_array(
						array(
							$entity_type,
							$method_name
						),
						$arguments
					);
				else

					$exception_details .=
						': '.
						sprintf(
							EXCEPTION_DEVELOPMENT_CLASS_METHOD_REQUIRED,
							$method_name,
							$entity_type
						)
					;
			}
			else
			
				throw new Exception(
					EXCEPTION_INVALID_ARGUMENT.': '.
					EXCEPTION_EXPECTATION_ARRAY
				);
		}
		else if ($match = preg_match($pattern_get_instance_type, $name, $matches))
		{
			$method_name =
				ACTION_GET.
				ucfirst(ENTITY_INSTANCE).
				ucfirst(PROPERTY_TYPE).
				ucfirst(PROPERTY_PROPERTY)
			;

			if (!empty($matches[3]))
			{
				if (!is_array($arguments))

					$arguments = array();

				$arguments[0] = strtolower($matches[3]);
			}

			if (in_array($method_name, get_class_methods(__CLASS__)))

				$callback_parameters = call_user_func_array(
					array(
						static::getSignature(),
						$method_name
					),
					$arguments
				);
		}
	
		if ( ! isset($callback_parameters ) )

			switch ($name)
			{
				default:

					throw new Exception(EXCEPTION_FURTHER_IMPLEMENTATION_REQUIRED.$exception_details);
					
			}

		return $callback_parameters;
	}

	/**
	* Construct an entity
	*
	* @tparam	mixed	$properties		properties values
	* @tparam	mixed	$informant		informant
	* @return	object	representing an entity
	*/
	public function __construct()
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$arguments = func_get_args();

		$exception = NULL;

		if ( isset( $arguments[0] ) )
		{
			$properties = $arguments[0];
			
			if (
				! is_array( $properties ) &&
				! is_object( $properties )
			)

				$exception = EXCEPTION_INVALID_ARGUMENT;
		}
		else

			$exception = EXCEPTION_INVALID_ARGUMENT;

		if ( ! is_null( $exception ) )

			throw new Exception( $exception );

		if ( ! isset( $arguments[1] ) )

			$informant = NULL;
		else

			$informant = $arguments[1];

		$this->{PROPERTY_PROPERTIES} = new stdClass();

		$signature = static::getSignature();

		if ( is_array( $properties ) )

			$properties[PROPERTY_SIGNATURE] = $signature;

		else if (
			is_object( $properties ) &&
			isset( $properties->{PROPERTY_ENTITY_NAME} )
		)
		{
			$_properties = array();
			
			$_properties[PROPERTY_SIGNATURE] = $properties->{PROPERTY_ENTITY_NAME};

			while ( list( $name, $value ) = each( $properties ) )
			
				$_properties[$name] = $value;

			$properties = $_properties;
		}
		else
		
			$properties = array( PROPERTY_SIGNATURE => $signature );

		$this->setProperties($properties);		

		// check if the current entity belongs to a namespace
		if (
			!empty( $this->{PROPERTY_SIGNATURE} ) &&
			( strpos( $this->{PROPERTY_SIGNATURE}, '\\' ) !== FALSE )
		)

			$this->{PROPERTY_NAMESPACE} = self::$namespace;

		else

			static::$namespace = NULL;

		return $this;
	}

	/**
	* Check if a property is set
	*
	* @param	string	$name	name
	* @return	nothing
	*/
	public function __isset($name)
	{
		$isset = FALSE;

		if (isset($this->$name) || $this->__get($name) !== NULL)
		
			$isset = TRUE;

		return $isset;
	}

	/**
	* Set magically a property featuring an entity
	*
	* @param	string	$name	name
	* @param	string	$value	value
	* @return	nothing
	*/
	public function __set($name, $value)
	{
		return $this->setProperty($name, $value);
	}

	/**
	* Unset magically a property
	*
	* @param	string	$name	name
	* @return	nothing
	*/
	public function __unset($name)
	{
		$native_private_property = in_array($name, get_class_vars(static::getSignature()));

		if (isset($this->$name) && !$native_private_property)
		{
			$properties = &$this->getProperties();

			unset($properties->$name);
		}
		else if (!$native_private_property)

			$this->$name = NULL;
	}

	/**
	* Set the value of a property featuring an entity
	*
	* @param	string	$name	name
	* @param	mixed	$value	value
	* @return	nothing
	*/
	public function setProperty($name, $value)
	{
		$_value = &$this->getProperty($name);
		
		$_value = $value;
	}

	/**
	* Set the values of properties
	*
	* @param	mixed	$properties		properties values
	* @return	nothing
	*/	
	public function setProperties($properties)
	{
		if (
			(is_array($properties) &&  count($properties) != 0) ||
			(is_object($properties) && count(get_object_vars($properties) != 0))
		)

			foreach ($properties as $name => $value)

				$this->setProperty($name, $value);
	}

	/**
	* Serialize an entity
	*
	* @param	mixed	$callback		callback
	* @param	integer	$storage_model	storage model
	* @param	boolean	$verbose		verbose mode
	* @param	mixed	$informant		informant
	* @return	nothing
	*/
	public function serialize(
		$callback = NULL,
		$storage_model = STORE_DATABASE,
		$verbose = FALSE,
		$informant = NULL
	)
	{
		global $class_application;
	
		$class_serializer = $class_application::getSerializerClass();

		return $class_serializer::save($this, $this->{PROPERTY_SIGNATURE}, $callback, $storage_model, $verbose, $informant);
	}

	/**
	* Synchronize an entity with a persistency layer
	*
	* @return	mixed
	*/
	public function sync(
		$callback = NULL,
		$storage_model = STORE_DATABASE,
		$verbose = NULL,
		$informant = NULL
	)
	{
		global $class_application;
	
		$class_transfer = $class_application::getTransferClass();

		return $class_transfer::synchronize(
			$this,
			$callback,
			$storage_model,
			$verbose,
			$informant
		);
	}

	/**
	* Fetch the properties of an entity
	*
	* @param	mixed	$context		context
	* @param	mixed	$entity_type	kind of entity 	
	* @param	mixed	$wrap			wrap flag
	* @param	mixed	$verbose		verbose mode
	* @param	mixed	$informant		informant
	* @return	mixed	reference to properties
	*/	
	public static function &fetchProperties(
		$context,
		$entity_type = NULL,
		$wrap = FALSE,
		$verbose = FALSE,
		$informant = NULL
	)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$properties = array();

		self::checkStaticParameters($entity_type);

		$entities = self::fetchInstances(
			$context,
			$entity_type,
			$wrap,
			$verbose,
			$informant
		);

		if ( count( $entities ) )
		{
			if ( ! $wrap )
			{
				if ( count( $entities ) == 1 )
				{
					list(,$entity) = each($entities);
	
					$properties = $entity->getProperties();
				}
				else
	
					while (list($index,$entity) = each($entities))
					
						$properties[$index] = $entity->getProperties();
			}
			else
			{
				if ( count( $entities ) == 1 )

					list(, $properties) = each($entities);
				else 

					// properties are provided wrapped as class member variables
					$properties = $entities;
			}
		}

		return $properties;
	}

	/**
	* Add an entity
	*
	* @param	mixed		$properties		properties values
	* @param	mixed		$callback		callback
	* @param	integer		$storage_model	storage model
	* @param	boolean		$verbose		verbose mode
	* @param	mixed		$informant		informant
	* @return	nothing
	*/	
	public static function add(
		$properties,
		$callback = NULL,
		$storage_model = STORE_DATABASE,
		$verbose = FALSE,
		$informant = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		if (
			(
				is_array( $properties ) &&
				count( $properties) != 0
			) ||
			(
				is_object( $properties ) &&
				count( get_object_vars( $properties ) != 0 )
			)
		)
		{
			$class = static::getSignature( TRUE );

			$entity = new $class( $properties );

			$last_insert_id = $entity->serialize(
				$callback,
				$storage_model,
				$verbose,
				$informant
			);

			return $entity;
		}
	}

	/**
	* Check credentials
	*
    * @param	array	$proofs				credentials
    * @param	mixed	$credential_type	type of credentials
	* @return	mixed	credentials granting level
	*/	
	public static function checkCredentials($proofs = NULL, $credential_type = NULL)
	{
		$credentials_granted = TRUE;

		return $credentials_granted;
	}

	/**
	* Check the static parameters
	*
    * @param	array	$entity_type	reference to an entity type
    * @param	mixed	$properties		reference to properties
	* @return	nothing
	*/	
	public static function checkStaticParameters(
		&$entity_type = NULL,
		&$properties = NULL
	)
	{
		if (is_null($entity_type))
		{
			// a reference of the entity type needs to be passed to the first argument
			$_entity_type = static::getSignature();

			$entity_type = $_entity_type;
		}

		if (is_null($properties))

			$_properties = array();

		else if (is_array($properties))
		{
			if (!isset($properties[SQL_SELECT]))

				$_properties = array(SQL_SELECT => $properties);
			else
			
				$_properties = $properties;
		}
		else

			throw new Exception(EXCEPTION_CONSISTENCY_DATA_ACCESS_QUERY_INVALID);

		$properties = $_properties;
	}

	/**
	* Get the default type of an entity
	*
	* @param	string	$entity_type			type of entity
	* @return	mixed	default type
	*/	
	public static function fetchDefaultType($entity_type = NULL)
	{
		$class_dumper = CLASS_DUMPER;

		$class_data_fetcher = CLASS_DATA_FETCHER;

		self::checkStaticParameters($entity_type);

		return $class_data_fetcher::fetchEntityDefaultType($entity_type);
	}

	/**
	* Get the id of an entity
	*
	* @param	mixed	$context		context
	* @param	mixed	$entity_type	kind of entity 	
	* @return	mixed
	*/	
	public static function fetchId($context, $entity_type = CLASS_ENTITY)
	{
		$class_data_fetcher = CLASS_DATA_FETCHER;

		if ($entity_type == CLASS_ENTITY && static::getSignature() != CLASS_ENTITY)

			$entity_type = static::getSignature();

		return $class_data_fetcher::fetchId($context, $entity_type);
	}

	/**
	* Fetch instances of an entity
	*
	* @param	mixed	$context		context
	* @param	mixed	$entity_type	kind of entity
	* @param	boolean	$wrap			wrap flag
	* @param	boolean	$verbose		verbose mode
	* @param	mixed	$informant		informant
	* @return	mixed
	*/	
	public static function fetchInstances(
		$context,
		$entity_type = NULL,
		$wrap = FALSE,
		$verbose = FALSE,
		$informant = NULL
	)
	{
		global $class_application;

		$class_data_fetcher = $class_application::getDataFetcherClass();

		$class_dumper = $class_application::getDumperClass();

		$class_entity = $class_application::getEntityClass();;

		$entities = array();

		$namespace =

		$_namespace = '';

		self::checkStaticParameters($entity_type);

		$properties = $class_data_fetcher::fetchProperties(
			$context,
			$entity_type,
			$wrap,
			$verbose,
			$informant
		);

		if ( is_object( $properties ) )
		{
			if ( ! empty( self::$namespace ) )
			{
				$_namespace = self::$namespace.'\\';

				$namespace = '\\'.$_namespace;
			}

			if (
				isset( $properties->{PROPERTY_ENTITY_NAME} ) &&
				(
					! isset( $properties->{PROPERTY_NAMESPACE} ) &&
					class_exists( $namespace.$properties->{PROPERTY_ENTITY_NAME} ) ||
					$wrap && class_exists( $properties->{PROPERTY_ENTITY_NAME} )
				)
			)
			{
				if ( class_exists( $namespace.$properties->{PROPERTY_ENTITY_NAME} ) )

					$class_name = $_namespace.$properties->{PROPERTY_ENTITY_NAME};

				else if ( class_exists ( $properties->{PROPERTY_ENTITY_NAME} ) )
				
					$class_name = $properties->{PROPERTY_ENTITY_NAME};

				$entity = new $class_name($properties);
			}
			else 
	
				$entity = new $class_entity($properties, $informant);

			return array($entity);
		}
		else if ( is_array( $properties ) )
		{
			while ( list( $index, $_properties ) = each( $properties ) )

				if (
					isset( $_properties->{PROPERTY_ENTITY_NAME} ) &&
					class_exists( $_properties->{PROPERTY_ENTITY_NAME} )
				)
		
					$entities[$index] =
						new $_properties->{PROPERTY_ENTITY_NAME}( $_properties );
				else 
		
					$entities[$index] =
						new $class_entity( $_properties, $informant );
	
			return $entities;
		}
	}

	/**
	* Get an entity by condition
	*
	* @param	string	$conditions		conditions
	* @param	string	$entity_type	type of entity
	* @param	mixed	$informant		informant
	* @return	nothing
	*/	
	public static function getByConditions($conditions, $entity_type = NULL, $informant = NULL)
	{
		$class_dumper = CLASS_DUMPER;

		self::checkStaticParameters($entity_type);

		$_conditions = array(
			SQL_SELECT => array(
				PROPERTY_ID
			),
			PROPERTY_STATUS => ENTITY_STATUS_ACTIVE
		);

		while (list($name, $value) = each($conditions))
		
			$_conditions[$name] = $value;

		return self::fetchProperties(
			$_conditions,
			$entity_type,
			FALSE,
			FALSE,
			$informant
		);
	}

	/**
	* Get an entity by providing some of its properties
	*
	* @param	mixed	$value			value
	* @param	mixed	$name			name
	* @param	mixed	$properties		properties
	* @param	string	$entity_type	type of entity
	* @param	string	$wrap			wrap flag
	* @param	mixed	$verbose		verbose
	* @param	mixed	$informant		informant
	* @return	mixed
	*/	
	public static function getByProperty(
		$value,
		$name = NULL,
		array $properties = NULL,
		$entity_type = CLASS_ENTITY,
		$wrap = FALSE,
		$verbose = FALSE,
		$informant = NULL
	)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		self::checkStaticParameters($entity_type, $properties);

		// check if the property name argument is valid
		if ( ! is_null( $name ) && ! is_string( $name ) )

			$entity = NULL;	
		else
		{
			// set the default property name to be the id
			if ( is_null( $name ) )

				$name = PROPERTY_ID;

			// remove possible namespace from property value
			if ( strpos( $value, '\\' ) !== FALSE )
			{
				$entity_name_sections = explode('\\', $value);
	
				$value = array_pop( $entity_name_sections );
				
				$namespace = implode('\\', $entity_name_sections);
				
				if ( is_null( $properties ) )

					$properties = array();

				$properties[PROPERTY_NAMESPACE] = array(
					$namespace => $entity_name_sections
				);
			}

			$entity = self::fetchProperties(
				array_merge(
					array($name => $value),
					$properties
				),
				$entity_type,
				$wrap,
				$verbose,
				$informant
			);
		}

		return $entity;
	}

	/**
	* Get a configuration
	*
	* @param	string	$configuration_type		type of configuration
	* @param	string	$entity_type			type of entity
	* @return	mixed	configuration
	*/	
	public static function getConfiguration(
		$configuration_type = CONFIGURATION_SERIALIZATION,
		$entity_type = NULL
	)
	{
		$configuration = array();

		self::checkStaticParameters($entity_type);

		switch ($configuration_type)
		{
			case CONFIGURATION_SERIALIZATION:

				$configuration = array(
					PROPERTY_COLUMN_PREFIX =>
						(
							defined(
								strtoupper(
									PREFIX_PREFIX.
									PREFIX_TABLE.
									PREFIX_COLUMN.
									$entity_type
								)							
							)
						?
							constant(
								strtoupper(
									PREFIX_PREFIX.
									PREFIX_TABLE.
									PREFIX_COLUMN.
									$entity_type
								)
							)
						:
							PREFIX_TABLE_COLUMN_ENTITY
						)
					,
					PROPERTY_TABLE =>
						(
							defined(
								strtoupper(
									PREFIX_TABLE.
									$entity_type
								)
							)
						?
								constant(
									strtoupper(
										PREFIX_TABLE.
										$entity_type
									)
								)
						:
							TABLE_ENTITY
						),
					PROPERTY_TABLE_ALIAS =>
						(
							defined(
								strtoupper(
									PREFIX_TABLE.PREFIX_ALIAS.
									$entity_type
								)
							)
						?
								constant(
									strtoupper(
										PREFIX_TABLE.PREFIX_ALIAS.
										$entity_type
									)
								)
						:
							TABLE_ALIAS_ENTITY
						),
					PROPERTY_DATABASE => DB_SEFI
				);

					break;	
		}

		return $configuration;
	}

	/**
	* Get the default type of an entity
	*
	* @param	boolean	$property		property
	* @param	string	$entity_type	type of entity
	* @return	nothing
	*/	
	public static function getDefaultType($property = PROPERTY_VALUE, $entity_type = NULL)
	{
		$callback_parameters = NULL;

		$default_type = self::fetchDefaultType($entity_type);

		if ( is_object( $default_type ) && isset( $default_type->{PROPERTY_VALUE} ) )
		{
			if ( ! empty( $property ) )
			{
				if (isset($default_type->$property))

					$callback_parameters = $default_type->$property;
				else 

					$exception = EXCEPTION_INVALID_ARGUMENT.': '.EXCEPTION_INVALID_PROPERTY_NAME;
			}
			else
			
				$callback_parameters = $default_type;
		}
		else
		
			$exception = sprintf(
				EXCEPTION_CONSISTENCY_DATA_ACCESS_ENTITY_TYPE_DEFAULT_VALUE_MISSING,
				static::getSignature()
			);

		if ( isset( $exception ) )

			throw new Exception($exception);
		else 

			return $callback_parameters;
	}

	/**
	* Get a type of instance
	*
	* @param	string	$name	name
	* @return	mixed	instance type
	*/
	public static function getInstanceType($name = NULL)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		if (!is_string($name) && !is_null($name))
	
			$type = NULL;
		else
		{
			if (empty($name))
 
				$name = self::getDefaultType(PROPERTY_NAME);

			$type = self::getType(
				array(
					PROPERTY_NAME => $name,
					PROPERTY_ENTITY => static::getSignature()
				)
			);
		}

		$class_dumper::log(
			__METHOD__,
			array($type)
		);

        return $type;
	}

	/**
	* Get the property value of an instance type by providing a Entity Type name
	*
	* @param	string	$property_name	property name
	* @param	string	$name			name
	* @return	object	instance type property
	*/
	public static function getInstanceTypeProperty($property_name, $name = NULL)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$type = self::getInstanceType($name);

		$property = NULL;

		if (is_object($type) && isset($type->$property_name))

			$property = $type->$property_name;

		return $property;
	}

	/**
    * Get an entity type from its properties
    *
    * @param	array	$properties	properties
    * @param	boolean	$verbose	verbose mode
    * @param	mixed	$informant	informant
    * @return  	object	property
	*/
	public static function getType($properties, $verbose = FALSE, $informant = NULL)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		if (
			! is_array($properties) ||
			(
				(
					! isset( $properties[PROPERTY_NAME] ) ||
					! is_string( $properties[PROPERTY_NAME] )
				) && (
					! isset( $properties[PROPERTY_VALUE] ) ||
					(
						! is_string( $properties[PROPERTY_VALUE] ) &&
						! is_integer( $properties[PROPERTY_VALUE] ) 
					)
				) && (
					! isset( $properties[PROPERTY_ID] ) ||
					! is_numeric( $properties[PROPERTY_ID] )
				)
			) || 
			! isset( $properties[PROPERTY_ENTITY] ) ||
			! is_string($properties[PROPERTY_ENTITY])
		)

			$type = NULL;
		else
		{
			$entity_id = self::getByName(
					$properties[PROPERTY_ENTITY],
					NULL,
					CLASS_ENTITY,
					$verbose,
					$informant
				)->{PROPERTY_ID}
			;

			$_properties = array(
				SQL_SELECT => array(
					PROPERTY_DEFAULT,
					PROPERTY_ID,
					PROPERTY_INDEX,
					PROPERTY_NAME,
					PROPERTY_STATUS,
					PROPERTY_VALUE
				),
				PROPERTY_STATUS => ENTITY_TYPE_STATUS_ACTIVE,
				PROPERTY_FOREIGN_KEY => array(
					PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID =>
						$entity_id
				)
			);

			if ( isset( $properties[PROPERTY_NAME] ) )

				$type = self::getByName(
					$properties[PROPERTY_NAME],
					$_properties,
					CLASS_ENTITY_TYPE,
					$verbose,
					$informant
				);

			else if ( isset( $properties[PROPERTY_VALUE] ) )

				$type = self::getByValue(
					$properties[PROPERTY_VALUE],
					$_properties,
					CLASS_ENTITY_TYPE,
					$verbose,
					$informant
				);

			else if ( isset( $properties[PROPERTY_ID] ) )

				$type = self::getById(
					$properties[PROPERTY_ID],
					$_properties,
					CLASS_ENTITY_TYPE,
					$verbose,
					$informant
				);
		}

		return $type;
	}

	/**
    * Get the value of an entity type property from its name and context
    *
    * @param	string	$name		property name
    * @param	array	$context	context
    * @param	boolean	$verbose	verbose mode
    * @param	mixed	$informant	informant
    * @return  	mixed	id
	*/
	public static function getTypeProperty(
		$name = PROPERTY_ID,
		$context = NULL,
		$verbose = FALSE,
		$informant = NULL
	)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$entity_type_id = NULL;

		$entity_type = self::getType($context, $verbose, $informant);

		if (is_object($entity_type) && isset($entity_type->$name))
		
			$entity_type_id = $entity_type->$name;

		return $entity_type_id;
	}

	/**
	* Get the static signature of the entity
	*
	* @param	boolean	$namespace	namespace flag
	* @return	string	signature
	*/
	public static function getSignature($namespace = TRUE)
	{
		$_class = __CLASS__;

		if ( ! $namespace )

			list( $_namespace, $_class ) = explode( '\\', __CLASS__ );

		return $_class;
	}

	/**
	* Process a request
	*
	* @param	mixed	$request 	request
	* @param	mixed	$context	context
	* @return 	mixed
	*/
	public static function processRequest($request, $context)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$class_member = $class_application::getMemberClass();

		$class_user_handler = $class_application::getUserHandlerClass();

		$return_value = NULL;

		// checking if an affordance has been selected
		if ( isset( $context->{PROPERTY_AFFORDANCE} ) && ( $affordance = $context->{PROPERTY_AFFORDANCE} ) )

			// checking if a valid entity is concerned
			if ( isset( $context->{PROPERTY_ENTITY} ) && ( $entity = $context->{PROPERTY_ENTITY} ) )
			{
				$entity_properties = self::fetchProperties($entity, CLASS_ENTITY);

				// checking if the entity has been considered before
				if (
					is_object($entity_properties) &&
					isset( $entity_properties->{PROPERTY_ENTITY_NAME} )
				)
	
					$affordance_entity = str_replace(
						'_',
						'-',
						$entity_properties->{PROPERTY_ENTITY_NAME}
					);
				else

					throw new Exception(
						sprintf(
							EXCEPTION_CONSISTENCY_DATA_ACCESS_ENTITY_MISSING,
							$entity
						)
					);

				// checking if the entity and affordance are compatible
				if (
					(
						$entity_start = strpos(
							$affordance,
							$affordance_entity
						)
					) !== FALSE
				)
				{
					$action = substr($affordance, 0, $entity_start - 1);

					$request_sections = explode('-', $action);

					if (is_array($request_sections) && count($request_sections) > 0)
			
						$entity_type = $request_sections[count($request_sections) - 1]; 
			
					$capitalize = function (&$value, $index)
					{
						if ($index != 0)
			
							$value = ucfirst($value);
					};
			
					if (array_walk($request_sections, $capitalize))
					{
						$method = implode($request_sections);

						$class =
								static::getSignature() != CLASS_ENTITY
							?
								static::getSignature()
							:
								$entity_type
						;

						if (
							$class_user_handler::loggedIn() ||
							$class_user_handler::loggedIn(TRUE)
						)
						{
							// get the qualities of the logged in member 
							$member_id = $class_member::getIdentifier();

							$authorization_granted = call_user_func(
								array(
									$class,
									'checkCredentials'
								),
								$context
							);

							if (
								$member_id === $authorization_granted ||
								$authorization_granted === TRUE
							)
							{
								if (class_exists($class) && in_array($method, get_class_methods($class)))
					
									$return_value = call_user_func(
										array(
											$class,
											$method
										),
										$context
									);
					
								else if (!class_exists($class))
					
									throw new Exception(
										EXCEPTION_FURTHER_IMPLEMENTATION_REQUIRED."\n".
										sprintf(EXCEPTION_DEVELOPMENT_CLASS_REQUIRED, ucfirst($class))
									);
								else
					
									throw new Exception(
										EXCEPTION_FURTHER_IMPLEMENTATION_REQUIRED."\n".
										sprintf(EXCEPTION_DEVELOPMENT_CLASS_METHOD_REQUIRED, $method, ucfirst($class))
									);
							}
							else

								throw new Exception(EXCEPTION_RIGHTS_MANAGEMENT_CREDENTIALS_INSUFFICIENT);
						}
					}
				}
			}
			else 

				throw new Exception(EXCEPTION_DEVELOPMENT_ENTITY_MISSING);
		else 

			throw new Exception(EXCEPTION_DEVELOPMENT_BEHAVIORAL_DEFINITION_MISSING);
							
		return $return_value;
	}

	/**
	* Remove by id
	*
	* @param	integer 	$id		identifier
	* @return	mixed
	*/
	public static function removeById($id)
	{
		$entity = new self(
			array(
				PROPERTY_ID => $id,
				PROPERTY_STATUS => ENTITY_STATUS_INACTIVE
			)
		);
		
		return $entity->serialize();
	}
}

/**
*************
* Changes log
*
*************
* 2011 09 25
*************
* 
* Implement entity type accessor by value
*
* method affected ::
*
* DATA FETCHER :: getType
*
* (branch 0.1 :: revision :: 656)
*
*/