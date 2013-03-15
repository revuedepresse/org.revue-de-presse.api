<?php

/**
* Lock class
*
* @package  sefi
*/
class Locksmith extends Craftsman
{
	/**
	* Get a store
	*
	* @return	mixed	store
	*/
	public static function &getStore()
	{
		$store = &parent::getStore( STORE_LOCK );
		
		return $store;
	}

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

	public static function goOutOfBusiness()
	{
		return parent::goOutOfBusiness( STORE_LOCK );
	}
}
