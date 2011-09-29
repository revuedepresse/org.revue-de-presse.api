<?php

/**
* Event class
*
* Class to handler an event
* @package  sefi
*/
class Event
{
	private $properties;

	/**
	* Construct a new instance of event
	*
	* @param	mixed		$properties		event properties
	* @param	string		$description 	event description
	* @return	object		new event instance
	*/
	public function __construct( $properties, $description = '' )
	{
		$this->properties = new stdClass();
	
		if ( is_numeric( $properties ) )
		
			$this->{PROPERTY_TYPE} = $properties;
		
		else if ( is_object( $properties ) )

			while ( list( $name, $value ) = each( $properties ) )
			
				$this->$name = $value;

		if ( is_string( $description ) && !empty( $description ) )
		
			$this->{PROPERTY_DESCRIPTION} = $description;
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
	* Get a reference to a property
	*
	* @param	string	$name	property name 
	* @return	mixed	value
	*/	
	public function &getProperty($name)
	{
		if (!isset($this->properties->$name))
		
			$this->properties->$name = null;

		$value = &$this->properties->$name;

		return $value;
	}

	/**
	* Get a magic property
	*
	* @param	string	$name	property name 
	* @return	nothing
	*/	
	public function __get($name)
	{
		return $this->getProperty($name);
	}

	/**
	* Set a magic property
	*
	* @param	string	$name	property name 
	* @param	string	$value	property value
	* @return	nothing
	*/	
	public function __set($name, $value)
	{
		return $this->setProperty($name, $value);
	}

	/**
	* Get properties
	*
	* @return	object	properties
	*/	
	public function getProperties()
	{
		return $this->properties;
	}
	
	/**
	* Serialize an event
	*
	* @return	mixed	serialization result
	*/	
	public function serialize()
	{
		$class_messenger = CLASS_MESSENGER;

		$class_entity = CLASS_ENTITY;

		return $class_messenger::log(
			$this->properties,
			$class_entity::getByName(ENTITY_EVENT)->{PROPERTY_ID}
		);
	}
	
	/**
	* Set the value of a property
	*
	* @param	string	$name	property name 
	* @param	mixed 	$value	property value
	* @return	nothing
	*/	
	public function setProperty($name, $value)
	{
		$_value = &$this->getProperty($name);

		$_value = $value;
	}

	/**
	* Log an event
	*
	* @param	mixed	$properties	properties
	* @return	mixed	log result
	*/	
	public static function logEvent( $properties )
	{
		if ( ! empty( $properties[PROPERTY_DESCRIPTION] ) )

			$description = $properties[PROPERTY_DESCRIPTION];
		else

			$description = NULL;

		if (is_numeric($properties[PROPERTY_TYPE]))

			$type = $properties[PROPERTY_TYPE];
		else
		
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		$event = new self(
			$type,
			$description
		);

		if (isset($properties[PROPERTY_EXCEPTION]))

			$event->{PROPERTY_EXCEPTION} = $properties[PROPERTY_EXCEPTION];

		if (isset($properties[PROPERTY_CONTEXT]))

			$event->{PROPERTY_CONTEXT} = $properties[PROPERTY_CONTEXT];

		return $event->serialize();
	}
}
?>