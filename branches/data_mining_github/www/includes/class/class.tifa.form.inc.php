<?php
namespace tifa;

require_once('tifa.globals.php');

class form
{
	private $_id;
	private $_fields;	

	/**
    * Construct a form
	*
    * @return	object  representing a form
	*/
    public function __construct($id = null)
    {
		$this->_id = $id;
	}
	
	public function __toString()
	{
		while (list($field_name) = each($_fields))
		{
			echo $_fields[$field_name];
		}
	}
}

class field
{
	private $_name;	
	private $_type;
	private $_value;
	
	/**
    * Construct a field
	*
    * @return	object  representing a field
	*/
    public function __construct($name, $type, $default = false)
    {
		$this->_default = $default;
		$this->_name = $name;
		$this->_type = $type;
	}
	
	public function __toString()
	{
		switch ($this->_type)
		{
			case FIELD_TYPE_TEXT:
				echo HTML_TAG_INPUT_OPENING. HTML_TAG_INPUT_CLOSING;
				
				break;
			case FIELD_TYPE_PASSWORD:
				break;
			case FIELD_TYPE_SUBMIT:
				
				break;
		}
	}
}
