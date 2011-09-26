<?php

/**
* Paper Maker class
*
* @package  sefi
*/
class Paper_Maker extends Craftsman
{
	/**
	* Get a store
	*
	* @return	mixed	store
	*/
	public static function &getStore()
	{
		$store = &parent::getStore( STORE_PAPER );
		
		return $store;
	}

	/**
	* Get material (before creating new memento)
	* 
	* @param	string	$material	material
	* @return	nothing
	*/	
	public static function &getMaterial( $material )
	{
		global $class_application;

		$class_paper_maker = $class_application::getPaperMakerClass();

		if ( ! is_string( $material ) || empty( $material ) )

			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		$paper_mill = &$class_paper_maker::getStore( STORE_PAPER );

		if ( ! isset( $paper_mill[$material] ))
	
			$paper_mill[$material] = array();

		return $paper_mill[$material];
	}

	/**
	* Trash material
	*
	* @param	string	$material	material
	* @return	nothing
	*/	
	public static function &trashMaterial( $material )
	{
		global $class_application;

		$class_paper_maker = $class_application::getPaperMakerClass();

		if ( ! is_string( $material ) || empty( $material ) )

			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		$paper_mill = &$class_paper_maker::getStore( STORE_PAPER );

		if ( isset( $paper_mill[$material] ))
		{
			$paper_mill[$material] = NULL;

			unset( $paper_mill[$material] );
		}

		return $paper_mill;
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
		return parent::goOutOfBusiness( STORE_PAPER );
	}
}
