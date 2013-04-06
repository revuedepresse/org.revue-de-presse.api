<?php

/**
* Photograph class
*
* Class for photograph properties edition
* @package  sefi
*/
class Photograph extends Entity
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
