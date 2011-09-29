<?php

/**
* Tag_Html class
*
* @package  sefi
*/
class Tag_Html extends Toolbox
{
    protected $properties;
    
    /**
    * Construct a new instance of Tag_Html
    * 
    * @param   array   $properties properties
    * @return  object  
    */
    public function __construct( $properties )
    {
        $this->properties = new stdClass();

        if ( is_array( $properties ) )

            while ( list( $name, $value ) = each( $properties ) )

                $this->$name = $value;
        else
        
            throw new Exception( EXCEPTION_INVALID_ARGUMENT );
    }

    /**
    * Get a member property
    * 
    * @param   string   $name   name
    * @return  nothing
    */
    public function __get($name)
    {
        return $this->getProperty($name);
    }

    /**
    * Set a member property
    * 
    * @param   string   $name   name
    * @param   mixed    $value  value
    * @return  nothing
    */
    public function __set($name, $value)
    {
        return $this->setProperty($name, $value);
    }

    /**
    * Get a property
    * 
    * @param   string  $name    name
    * @return  mixed
    */
    public function &getProperty($name)
    {
        if (isset($this->properties->$name))

            return $this->properties->$name;
        else
        {
            $this->properties->$name = '';

            return $this->properties->$name;
        }
    }


    /**
    * Check a file
    * 
    * @param   array   $data    data
    * @return  mixed
    */
    public function checkFile($data)
    {
        $file_properties = &$data[$this->{PROPERTY_NAME}];

        $options = $this->getProperty(PROPERTY_OPTIONS);

        $match = preg_match('/\.([^\.]+)$/', $file_properties['name'], $matches);

        if ($match)

    		$file_properties['ext'] = $matches[1];
        else
        
            $file_properties['ext'] = EXTENSION_NO_EXTENSION;
		
		if (isset($file_properties['error']) && $file_properties['error'] != UPLOAD_ERR_OK)
        
			$this->setProperty(PROPERTY_DATA_VALIDATION_FAILURE, FORM_UPLOAD_ERROR);
		
		if (isset($file_properties['error']) && $file_properties['error'] == UPLOAD_ERR_INI_SIZE) 

			$this->setProperty(PROPERTY_DATA_VALIDATION_FAILURE, FORM_UPLOAD_MAX_SIZE);
		
		if (
            $options != NULL &&
            !in_array($file_properties['ext'], explode(',', $options))
        ) 

			$this->setProperty(PROPERTY_DATA_VALIDATION_FAILURE, FORM_UPLOAD_BAD_EXT);
		
		return $file_properties;    
    }

    /**
    * Check options
    * 
    * @param   array   $data    data
    * @return  mixed
    */
    public function checkOptions($data)
    {
		$options = $this->getProperty(PROPERTY_OPTIONS);

        if (is_array($data))
        {
            // loop on submitted data
            while (list(,$option) = each($data))
    
                // Check if the selected option is in the list of available options
                if (!array_key_exists($option, $options))
    
                    $this->setProperty(PROPERTY_DATA_VALIDATION_FAILURE, FORM_VALUE_BAD);

            $data = implode(',', $data);                    
        }
        else if (!array_key_exists($data, $options))
    
            $this->setProperty(PROPERTY_DATA_VALIDATION_FAILURE, FORM_VALUE_BAD);
    
        return $data;
    }

    public function setProperty( $name, $value )
    {
        if ( is_string( $name ) )
        {
            $_value = &$this->getProperty( $name );
 
            $_value = $value;
        }
        else
        
            throw new Exception(EXCEPTION_INVALID_ARGUMENT);
    }

	/**
    * Clear errors related to the field
    *
    * @return 	nothing
    */	
	public function clear_errors()
	{
		if ( isset( $this->{PROPERTY_DATA_VALIDATION_FAILURE} ) )

			unset( $this->{PROPERTY_DATA_VALIDATION_FAILURE} );
	}

	/**
    * Get type
    *
    * @return 	string	containing a type
    */	  
	public function get_type()
	{
		return $this->type;	
	}	
}