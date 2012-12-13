<?php

$autoload_functions = spl_autoload_functions();

if (
	! $autoload_functions ||	
	! in_array(
		FUNCTION_AUTOLOAD_JQUERY4PHP,
		$autoload_functions
	)
)
{
	$class_jquery4PHP = 'JQuery4PHP';
	$class_jquery4PHP::load();
}