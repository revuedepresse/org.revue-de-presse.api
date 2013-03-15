<?php

/**
* Feed class
*
* @package  sefi
*/
class Feed extends Syndication
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

	/**
	* Get a publication from a hash map
	*
	* @param	array	$hash_map	container of key-value properties
	* @return	object	feed
	*/
	public static function getFromHashMap()
	{
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
* Start implementing the Feed class
*
* (branch 0.1 :: revision :: 662)
*
*/