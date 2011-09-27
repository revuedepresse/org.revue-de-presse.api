<?php

if ( defined( 'CLASS_TEMPLATE_ENGINE' ) && defined( 'STDIN' ) )
{
	global $class_application;

	$class_template_engine = $class_application::getTemplateEngineClass();
			
	// construct a new Smarty object
	$template_engine = new $class_template_engine();

	echo print( array( '[cache cleared]', $template_engine->clear_all_cache() ) );
}

/**
*************
* Changes log
*
*************
* 2011 09 27
*************
* 
* development :: deployment ::
*
* Implement cache cleaning in CLI mode
* 
* (revision 321)
*
*/