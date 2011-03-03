<?php

/**
* Lock class
*
* @package  sefi
*/
class Lock extends Token
{
	/**
	* Get a lock
	*
	* @param	mixed	$entity	entity
	* @return	mixed	$lock
	*/
	public static function &getEntityLock( $entity = NULL)
	{
		global $class_application, $verbose_mode;

		$class_locksmith = $class_application::getLocksmithClass();

		if ( is_null( $entity ) )
		
			$entity = ENTITY_ACTION;

		$store = &$class_locksmith::getStore();

		$lock = &$store[$entity];
		
		return $lock;
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
	* Check if an entity is locked
	*
	* @param	mixed	$entity		entity
	* @return	boolean	lock status
	*/
	public static function lockedEntity( $entity = NULL )
	{
		if ( is_null( $entity ) )
		
			$entity = ENTITY_ACTION;

		$lock = self::getEntityLock( $entity );
		
		switch ( $lock )
		{
			case PROPERTY_LOCKED:

				$entity_locked = TRUE;

					break;

			case PROPERTY_RELEASED:

				$entity_locked = FALSE;
				
					break;

			default:
			
				$entity_locked = PROPERTY_UNDEFINED;
		}
		
		return $entity_locked;
	}

	/**
	* Lock an entity
	*
	* @param	mixed	$entity	entity
	* @return	string	lock property
	*/
	public static function lockEntity( $entity = NULL)
	{
		if ( is_null( $entity ) )
		
			$entity = ENTITY_ACTION;

		$key = &self::getEntityLock( $entity );

		return ( $key = PROPERTY_LOCKED );
	}

	/**
	* Lock an entity
	*
	* @param	mixed	$entity	entity
	* @return	string	lock property
	*/
	public static function releaseEntity( $entity = NULL)
	{
		if ( is_null( $entity ) )
		
			$entity = ENTITY_ACTION;

		$key = &self::getEntityLock( $entity );

		return ( $key = PROPERTY_RELEASED );
	}
}
?>