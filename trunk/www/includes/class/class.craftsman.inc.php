<?php

/**
* Lock class
*
* @package  sefi
*/
class Craftsman extends Administrator
{
	/**
	* Do opening
	*
	* @return	nothing
	*/
	public static function &doOpening()
	{
		// Check the current session identifier and the headers already sent
		if ( function_exists( 'sessionStart' ))
		{
			$session = &sessionStart();
			
			return $session;
		}
		else
		
			throw new Exception(
				sprintf(
					EXCEPTION_INVALID_ENTITY,
					ENTITY_ENVIRONMENT
				)
			);	
	}

	/**
	* Get a store
	*
	* @tparam	string	$store_type		type of store
	* @return	mixed	$locksmith
	*/
	public static function &getStore()
	{
		$arguments = func_get_args();

		$store = self::checkContext( $arguments );

		$session = &self::doOpening();

		if (
			! isset( $session[$store] ) ||
			! is_array( $session[$store] )
		)

			$session[$store] = array();

		$store = &$session[$store];
		
		return $store;
	}

	/**
	* Check a context
	*
	* @param	string	$context	context
	* @return	mixed	store
	*/
	public static function checkContext( $context )
	{
		$store = NULL;

		if (
			! is_array( $context ) ||
			! count( $context ) ||
			! isset( $context[0] ) ||
			! is_string( $context[0] ) ||
			empty( $context[0] )
		)
		
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );
		else
		
			$store = $context[0];

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

	/**
	* Close up the shop
	*
	* @tparam	string	$store_type	store type		
	* @return	nothing
	*/
	public static function goOutOfBusiness()
	{
		global $class_application;

		$arguments = func_get_args();

		$store = self::checkContext( $arguments );

		$session = &self::doOpening();

		if (
			! is_null( $session ) &&
			FALSE !== (
				$class_application::key_exists( $session, $store )
			)
		)
		{
			$session[$store] = NULL;

			unset( $session[$store] );
		}
	}
}
