<?php

/**
* Router class
*
* @package  sefi
*/
class Router extends Route
{
	/** Get a route
	* 
	* @param	array		&$context	context parameters
	* @param	integer		$page		page
	* @return	nothing
    */
    public static function getRoute(&$context = null, $page = PAGE_UNDEFINED)
    {
		global $class_application;

		$class_interceptor = $class_application::getInterceptorClass();

		// return a route
		return $class_interceptor::route($context, $page);
	}

	/**
	* Get a signature
	*
	* @param	boolean	$namespace	namespace flag
	* @return	string	signature
	*/
	public static function getSignature($namespace = TRUE)
	{
		$_class = __CLASS__;

		if (!$namespace)

			list($_namespace, $_class) = explode('\\', __CLASS__);

		return $_class;
	}
}
?>