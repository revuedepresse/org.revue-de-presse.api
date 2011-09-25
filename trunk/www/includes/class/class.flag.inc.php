<?php

/**
* Flag class
*
* Class to handle a flag
* @package  sefi
*/
class Flag extends Toolbox
{
	protected $properties;

	/**
	* Construct a flag
	*
	* @param	mixed	$properties properties
	* @return	object	flag
	*/
	public function __construct( $properties = NULL )
	{
		// check the member properties attribute
		if ( ! isset( $this->properties ) )
		
			// declare the member properties as an instance of the standard class
			$this->properties = new stdClass();

		// check the provided properties
		if ( ! isset( $properties ) )

			// set the identifier property
			$this->setProperties( $properties );
	}

	/**
	* Get properties
	*
	* @return	object	properties
	*/	
	public function &getProperties()
	{
		// return the properties
		return $this->properties;
	}

	/**
	* Get a property
	*
	* @param	string	$property	property
	* @return	mixed
	*/	
	public function &getProperty($property)
	{
		// get properties
		$properties = &$this->getProperties();

		// check the property		
		if (!isset($properties->{$property}))

			// declare the property
			$properties->{$property} = null;

		// return the property
		return $properties->{$property};
	}

	/**
	* Set properties
	*
	* @param	mixed	$properties	properties
	* @return	nothing
	*/
	public function setProperties($properties)
	{
		// check the array of properties
		if (
			is_array($properties) &&
			count($properties) != 0
			||
			is_object($properties) &&
			count(get_object_vars($properties)) != 0
		)

			// loop on properties
			while (list($property, $value) = each($properties))

				// check the current property
				if (!is_int($property) && $value !== $this->getProperty($property))

					// set a property
					$this->setProperty($property, $value);

				else

					// set a property
					$this->setProperty($value, null);				
	}

	/**
	* Set a property
	*
	* @param	string		$property	property
	* @param	string		$value	value
	* @return	nothing
	*/
	public function setProperty($property, $value = null)
	{
		// get a property
		$property = &$this->getProperty($property);
		
		// set the property value
		$property = $value;
	}
}