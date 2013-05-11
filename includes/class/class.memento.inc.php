<?php

if (!defined('MEMCACHE_COMPRESSED')) {
    define('MEMCACHE_COMPRESSED', 2);
}

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

	/**
	* Get statistics from a memached server
	*
	* @param	string	$type	type
	* @param	string	$slabid	slabib
	* @param	integer	$limit	limit
	* @return 	mixed
	*/
	public static function getStatistics(
		$type = NULL,
		$slabid = NULL,
		$limit = NULL
	)
	{
		$memcached = self::openConnection();

		return $memcached->getExtendedStats( $type, $slabid, $limit );
	}

	/**
	* Open a connection with a memcache server
	*
	* @param	mixed	$host
	* @param	mixed	$port
	* @return 	mixed
	*/
	public static function openConnection(
		$host = NULL,
		$port = NULL
	)
	{
		global $class_application;

		$class_memory_cache = $class_application::getMemoryCacheClass();

        if (!class_exists($class_memory_cache)) {
            error_log('[memcache extension missing]');
            return;
        } else {
            $memory_cache = new $class_memory_cache;
        }

		if ( is_null( $host ) )
		
			$host = MEMCACHED_HOST;

		if ( is_null( $port ) )
		
			$host = MEMCACHED_PORT;

		if ( ! $memory_cache->addServer( MEMCACHED_HOST, MEMCACHED_PORT ) )
		
			throw new Exception( EXCEPTION_INVALID_MEMCACHED_SERVER );
		else
			return $memory_cache;
	}

	/**
	* Remove data from a memcached server
	*
	* @param	mixed	$key	key
	* @param	mixed	$flush	flushing flag
	* @return 	mixed
	*/
	public static function removeData( $key = NULL, $flush = FALSE )
	{
		$memory_cache = self::openConnection();
		
		if ( $flush === TRUE )

			$callback_parameters = $memory_cache->flush();
		else
			$callback_parameters = $memory_cache->delete( $key );

		return $callback_parameters;
	}

	/**
	* Retrieve data
	*
	* @param	mixed	$key		key
	* @param	mixed	$flags		flags
	* @return 	mixed	data
	*/
	public static function retrieveData(
		$key,
		&$flags = NULL
	)
	{
		$memory_cache = self::openConnection();
        if (!is_object($memory_cache)) {
            return;
        }
		
		if ( MEMCACHED_ACTIVE )
		
			$data = $memory_cache->get( $key, $flags );
		else
		{
			self::removeData( NULL, TRUE );

			$data = FALSE;
		}

		return $data;
	}

	/**
	* Store data
	*
	* @param	mixed	$data		data
	* @param	mixed	$key		key
	* @param	mixed	$replace	replace
	* @param	mixed	$flag		flag
	* @param	mixed	$expire		expiration time
	* @return 	mixed
	*/
	public static function storeData(
		$data = NULL,
		$key = NULL,
		$replace = FALSE,
		$flag = MEMCACHE_COMPRESSED,
		$expire = NULL
	)
	{
		$timestamp = time();

		if ( is_null( $expire ) )

			$expire = $timestamp + (int) MEMCACHED_EXPIRATION_TIME;

		if ( is_null( $key )  )
		{
			if (
				is_null( $data ) ||
				! is_null( $data ) &&
				(
					( is_string( $data ) && ! strlen( $data ) ) ||
					( is_array( $data ) && ! count( $data ) ) ||
					( is_object( $data ) && ! count( get_object_vars( $data ) ) )
				)
			)
				$key = sha1( gettype( $data ) );
			else
				$key = sha1( serialize( $data ) );
		}

		$memory_cache = self::openConnection();
        if (!is_object($memory_cache)) {
            return;
        }

		if ( $replace && ( FALSE !== self::retrieveData( $key ) ) )
		{
			$flag = FALSE;
			$method = 'replace';
		}
		else
			$method = 'add';

		$callback_parameters = $memory_cache->$method(
			$key, $data, $flag, $expire
		);

		return $callback_parameters;
	}
}

/**
*************
* Changes log
*
*************
* 2011 03 09
*************
* 
* Start implementing data storage by using memcached service
*
* methods affected ::
*
*	MEMENTO :: delete
*	MEMENTO :: getStatistics
*	MEMENTO :: openConnection
*	MEMENTO :: storeData
* 
* (branches 0.1 :: revision :: 595)
*
*************
* 2011 10 21
*************
* 
* project :: wtw ::
* 
* deployment :: performance ::
*
* Protect value replacement by checking if provided key has been used already
*
* method affected ::
*
* 	MEMENTO::storeData
* 
* (branch 0.1 :: revision :: 727)
* (branch 0.1 :: revision :: 393)
*
*************
* 2011 10 26
*************
* 
* project :: wtw ::
* 
* deployment :: cache ::
*
* Mark all cached values as expired as soon as memory cache has been set to inative
*
* method affected ::
*
* 	MEMENTO::removeData
* 
* (branch 0.1 :: revision :: 806)
* (branch 0.1 :: revision :: 402)
*
*/