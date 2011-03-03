<?php

/**
* Memento class
*
* Class for handling Mementos
* @package  sefi
*/
class Memento extends Serializer
{
	/**
	* Remind a memento
	*
	* @tparam	mixed	$key			key
	* @tparam	mixed	$storage_model	storage model
	* @return	mixed
	*/
	public static function &remind()
	{
		global $class_application;

		$class_paper_maker = $class_application::getPaperMakerClass();

		$arguments = func_get_args();

		$memento = NULL;

		if ( ! isset( $arguments[0] ) )
		
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );
		else
		
			$key = $arguments[0];

		if ( ! isset( $arguments[1] ) )
		
			$storage_model = STORE_SESSION;

		$store_mementos = $class_paper_maker::getMaterial( STORE_MEMENTO );
		
		if ( isset( $store_mementos[$key] ) )
		
			$memento = &$store_mementos[$key];

		return $memento;
	}

	/**
	* Forget memento
	*
	* @tparam	mixed	$key			key
	* @tparam	mixed	$storage_model	storage model
	* @return	mixed
	*/
	public static function forget()
	{
		global $class_application;

		$class_paper_maker = $class_application::getPaperMakerClass();

		$arguments = func_get_args();

		$memento = NULL;

		if ( ! isset( $arguments[0] ) )
		
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );
		else
		
			$key = $arguments[0];

		if ( ! isset( $arguments[1] ) )
		
			$storage_model = STORE_SESSION;

		$store_mementos = &$class_paper_maker::getMaterial( STORE_MEMENTO );

		if ( isset( $store_mementos[$key] ) )
		{
			$store_mementos[$key] = NULL;
			
			unset( $store_mementos[$key] );
		}
	}

	/**
	* Forget all mementos
	*
	* @tparam	mixed	$storage_model	storage model
	* @return	mixed
	*/
	public static function forgetEverything()
	{
		global $class_application;

		$class_paper_maker = $class_application::getPaperMakerClass();

		$arguments = func_get_args();

		$memento = NULL;

		if ( ! isset( $arguments[0] ) )
		
			$storage_model = STORE_SESSION;

		$class_paper_maker::trashMaterial( STORE_MEMENTO );
	}

	/**
	* Write a memento
	*
	* @tparam	mixed	$item			item
	* @tparam	boolean	$active			active
	* @tparam	mixed	$storage_model	storage model
	* @return	mixed
	*/
	public static function &write()
	{
		global $class_application;

		$class_paper_maker = $class_application::getPaperMakerClass();

		$arguments = func_get_args();
		
		$memento = NULL;

		if ( ! isset( $arguments[0] ) )
		
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );
		else
		
			$item = $arguments[0];

		if ( ! isset( $arguments[1] ) )

			$active = FALSE;
		else
		
			$active = $arguments[1];

		if ( ! isset( $arguments[2] ) )
		
			$storage_model = STORE_SESSION;

		$store_mementos = &$class_paper_maker::getMaterial( STORE_MEMENTO );

		// make a backup of the provided item
		$_item = $item;

		// convert possible object argument into array
		if ( is_object( $item ) )

			$_item = ( array) $item;
		
		if ( $active == TRUE )
		{
			if ( isset( $_item[PROPERTY_KEY] ) )
			{
				$store_mementos[$_item[PROPERTY_KEY]] = $item;
		
				$memento = &$store_mementos[$_item[PROPERTY_KEY]];
			}
			else
			{
				$store_mementos[] = $item;
		
				end( $store_mementos );
				list( $index, ) = each( $store_mementos );
				reset( $store_mementos );
		
				$memento = &$store_mementos[$index];
			}
		}

		return $memento;
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

			list( $_namespace, $_class ) = explode( '\\', __CLASS__ );

		return $_class;
	}
}