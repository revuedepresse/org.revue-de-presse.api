<?php

/**
* Draf Form class
*
* @package  sefi
*/
class Draft_Form
{
    private $_id;
    private $_action;
    private $_fields;

    /**
    * Construct a new form
    * 
    * @param    integer	    $id	    form identifier 
    * @return   object
    */	    
    public function __construct($id)
    {
        if (is_integer($id))
    
            $this->_id = $id;
        else
    
            throw new Exception("Data type error: a form id has to be an integer.");
    }

    /**
    * Get the id 
    * 
    * @return integer
    */	        
    public function getId()
    {
        return $this->_id;
    }    
    
    /**
    * Get the action
    * 
    * @return array
    */	        
    public function getAction()
    {
        return $this->_action;
    }

    /**
    * Get the fields
    * 
    * @return integer
    */	        
    public function getFields()
    {
        return $this->_fields;
    }

    /**
    * Set the action
    * 
    * @param    integer    $action	an action
    * @return nothing
    */	        
    public function setAction($action)
    {
        if (is_string($action))
    
            $this->_action = $action;
        else
    
            throw new Exception("Data type error: an action mode has to be a string.");
    }

    /**
    * Set the fields
    * @param    array    $fields	fields
    * 
    * @return nothing
    */	        
    public function setFields($fields)
    {
        if (is_array($fields))
    
            $this->_fields = $fields;
        else
            throw new Exception("Data type error: fields have to be passed as an array.");
    }    
}
?>