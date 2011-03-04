<?php

/**
* Validator class
*
* @package  sefi
*/
class Validator extends File_Manager
{
	private $_dom = null;

	/**
    * Construct a validator
	*
	* @param	string	$filepath  containing a filepath
    * @return	object  representing a validator
	*/
    public function __construct($filepath)
    {
		$this->_dom = new DOMDocument();
		$this->_dom->load($filepath, LIBXML_DTDVALID);		
	}

	/**
    * Get Document Object Model
	*
    * @return	object	representing a DOMDocument
	*/
	public function get_dom()
	{
		return $this->_dom;
	}
}
?>
