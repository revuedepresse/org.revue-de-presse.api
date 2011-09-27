<?php

if ( defined( 'DEPLOYMENT_MODE' ) && ! DEPLOYMENT_MODE )
{
	global $class_application;

	$class_template_engine = $class_application::getDumperClass();

	$class_dumper::log(
		__METHOD__,
		array( '[deployment level :: ' . DEPLOYMENT_MODE . ']'),
		TRUE
	);
}

/**
*************
* Changes log
*
*************
* 2011 09 27
*************
*
* project :: ghost ::
* 
* deployment :: template engine ::
*
* Append deployment mode to landing script
* 
* (revision 321)
*
*/