<?php

/**
* Tag_Span class
*
* Class for handling span tag 
* @package  sefi
*/
class Tag_Span extends Html_Tag
{
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