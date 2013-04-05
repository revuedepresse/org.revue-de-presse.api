<?php

/**
* Media manager class
*
* Class for media management
* @package  sefi
*/
class Media_Manager extends Diaporama
{    
    /**
    * Construct a new instance of the Media Manager object
    * 
    * @param	integer		$id		diaporama id 
    * @return 	object
    */	    
    public function __construct($id = -1)
	{
		parent::__construct($id);
	}
}
