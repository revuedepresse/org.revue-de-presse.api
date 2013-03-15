<?php

/**
* Entity model interface
* 
* Interface to create entities
* @package 	sefi
*/
interface Model_Entity
{	
	/**
	* Get the properties featuring an entity
	*
	* @return	mixed	properties
	*/
	public function &getProperties();

	/**
	* Get the value of property featuring an entity
	*
	* @param	string	$name	name
	* @return	mixed	property value
	*/
	public function &getProperty($name);

	/**
	* Call magically a non-declared static method 
	*
	* @param	string	$name			name of magic static method
	* @param	array	$arguments		arguments
	* @return	nothing
	*/	
	public static function __callStatic($name, $arguments);

	/**
	* Construct an entity
	*
	* @return	object	representing an insight
	*/
	public function __construct();

	/**
	* Get magically property the property of an entity
	*
	* @param	string	$name	name
	* @return	mixed	property value
	*/
	public function &__get($name);

	/**
	* Check if a property is set
	*
	* @param	string	$name	name
	* @return	nothing
	*/
	public function __isset($name);

	/**
	* Set magically the property of an entity
	*
	* @param	string	$name	name
	* @param	string	$value	value
	* @return	nothing
	*/
	public function __set($name, $value);

	/**
	* Set the values of properties featuring an entity
	*
	* @param	mixed	$properties	    properties values
	* @return	nothing
	*/	
	public function setProperties($properties);

	/**
	* Serialize an entity
	*
	* @param	boolean		$storage_model	storage model
	* @param	boolean		$verbose		verbose mode
	* @return	nothing
	*/
	public function serialize($storage_model = STORE_DATABASE, $verbose = FALSE);

	/**
	* Synchronize an entity with a persistency layer
	*
	* @return	mixed
	*/
	public function sync();

	/**
	* Fetch the properties of an entity
	*
	* @param	mixed	$context		context
	* @param	mixed	$entity_type	kind of entity 	
    * @param	mixed	$wrap			wrap flag
    * @param	mixed	$verbose		verbose mode
    * @param	mixed	$informant 		informant
	* @return	mixed	reference to properties
	*/	
	public static function &fetchProperties(
		$context,
		$entity_type = CLASS_ENTITY,
		$wrap = FALSE,
		$verbose = FALSE,
		$informant = NULL
	);

	/**
	* Add an entity
	*
	* @param	mixed	$properties	    properties values
	* @param	boolean	$verbose		verbose mode
	* @return	nothing
	*/	
	public static function add($properties, $verbose = FALSE);

	/**
	* Check credentials
	*
    * @param	array	$proofs				credentials
    * @param	mixed	$credential_type	type of credentials
	* @return	mixed	credentials granting level
	*/	
	public static function checkCredentials($proofs = NULL, $credential_type = NULL);

	/**
	* Check the static parameters
	*
    * @param	array	$entity_type	reference to an entity type
    * @param	mixed	$properties		reference to properties
	* @return	array	static parameters
	*/	
	public static function checkStaticParameters(&$entity_type = CLASS_ENTITY, &$properties = NULL);

	/**
	* Get the default type of an entity
	*
	* @param	string	$entity_type			type of entity
	* @return	mixed	default type
	*/	
	public static function fetchDefaultType($entity_type = CLASS_ENTITY);

	/**
	* Get the id of an entity
	*
	* @param	mixed	$context		context
	* @param	mixed	$entity_type	kind of entity 	
	* @return	mixed
	*/	
	public static function fetchId($context, $entity_type = CLASS_ENTITY);

	/**
	* Fetch instances of an entity
	*
	* @param	mixed	$context		context
	* @param	mixed	$entity_type	kind of entity
	* @param	mixed	$informant		informant
	* @return	mixed
	*/	
	public static function fetchInstances($context, $entity_type = CLASS_ENTITY, $informant = NULL);

	/**
	* Get an entity by conditions
	*
	* @param	string	$conditions		conditions
	* @param	string	$entity_type	type of entity
	* @param	mixed	$informant		informant
	* @return	nothing
	*/	
	public static function getByConditions($conditions, $entity_type = NULL, $informant = NULL);

	/**
	* Get an entity by providing some of its properties
	*
	* @param	mixed	$value			value
	* @param	mixed	$name			name
	* @param	mixed	$properties		properties
	* @param	string	$entity_type	type of entity
	* @param	mixed	$informant		informant
	* @return	mixed
	*/	
	public static function getByProperty(
		$value,
		$name = NULL,
		array $properties = NULL,
		$entity_type = NULL,
		$informant = NULL
	);

	/**
	* Get a configuration
	*
	* @param	string	$configuration_type		type of configuration
	* @param	string	$entity_type			type of entity
	* @return	mixed	configuration
	*/	
	public static function getConfiguration(
		$configuration_type = CONFIGURATION_SERIALIZATION,
		$entity_type = CLASS_ENTITY
	);

	/**
	* Get the default type of an entity
	*
	* @param	boolean	$value	value flag
	* @param	string	$entity_type			type of entity
	* @return	nothing
	*/	
	public static function getDefaultType($value = TRUE, $entity_type = NULL);

	/**
	* Get the static signature of the entity
	*
	* @param	boolean	$namespace	namespace flag
	* @return	string	signature
	*/	
	public static function getSignature($namespace = TRUE);

	/**
    * Get an entity type from its properties
    *
    * @param	string	$properties	properties
    * @param	boolean	$verbose	verbose mode
    * @param	mixed	$informant	informant
    * @return  	object	property
	*/
	public static function getType($properties, $verbose = FALSE, $informant = NULL);

	/**
	* Process a request
	*
	* @param	mixed	$request 	request
	* @param	mixed	$context	context
	* @return 	mixed
	*/
	public static function processRequest($request, $context);

	/**
	* Remove an entity by id
	*
	* @param	integer $id	id
	* @return	mixed
	*/
	public static function removeById($id);
}