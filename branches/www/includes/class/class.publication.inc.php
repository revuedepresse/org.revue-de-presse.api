<?php

/**
* Publication class
*
* @package  sefi
*/
class Publication extends Content
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
* Start implementing the Publication class
*
* (branch 0.1 :: revision :: 662)
*
*/