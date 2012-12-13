<?php

/**
* Syndication class
*
* @package  sefi
*/
class Syndication extends Publication
{
	/**
	* Get a signature
	*
	* @param	boolean	$namespace	namespace flag
	* @return	string	signature
	*/
	public static function getSignature( $namespace = TRUE )
	{
		$_class = __CLASS__;

		if ( ! $namespace )

			list(
				$_namespace,
				$_class
			) = explode( '\\', __CLASS__ );

		return $_class;
	}
}

/**
*************
* Changes log
*
*************
* 2011 10 01
*************
* 
* Start implementing the Syndication class
*
* (branch 0.1 :: revision :: 662)
*
*/