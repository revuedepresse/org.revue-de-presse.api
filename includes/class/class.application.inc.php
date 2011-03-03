<?php

/**
* Main class of the package sefi
*
* @package  sefi
*/
class Application extends \sefi\Application
{

    /**
    * Construct a new application 
    *
    * @param	integer	$page	    representing a page
    * @param	integer	$handler_id	representing a field handler
    * @param	string	$block	    containing a block name
    * @return 	object	representing an application
    */	      
    private function __construct(
        $page = PAGE_HOMEPAGE,
        $handler_id = FORM_ARBITRARY,
        $block = BLOCK_HTML
    )
    {
        parent::__construct();
    }
}
?>