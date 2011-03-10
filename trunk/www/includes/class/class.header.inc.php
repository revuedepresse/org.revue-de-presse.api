<?php
/**
*************
* Changes log
*
*************
* 2011 03 10
*************
* 
* Revise header hash calculation
*
* method affected ::
*
* HEADER :: make
*
* (branch 0.1 :: revision :: 597)
* (trunk :: revision :: 156)
* 
*************
* 2011 03 05
*************
* 
* Add the keywords property to instances of the header class
*
* method affected ::
*
* HEADER :: make
*
* (branch 0.1 :: revision :: 571)
*
*/

/**
* Header class
*
* Class for handling header
* @package  sefi
*/
class Header extends Api
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

			list( $_namespace, $_class ) = explode( '\\', __CLASS__ );

		return $_class;
	}

	/**
	* Make an instance of the Header class
	*
	* @return	object	Header instance
	*/
	public static function make()
	{
		global $class_application;
		
		$class_dumper = $class_application::getDumperClass();

		$arguments = func_get_args();

		if ( isset( $arguments[0] ) )

			$value = $arguments[0];
		else

			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		if ( isset( $arguments[1] ) )

			$uid = $arguments[1];
		else

			$uid = NULL;

		if ( isset( $arguments[2] ) )

			$sender = $arguments[2];
		else

			$sender = NULL;

		if ( ! isset($arguments[3] ) )

			$subject = NULL;
		else

			$subject = $arguments[3];

		if ( ! isset($arguments[4] ) )

			$keywords = NULL;
		else

			$keywords = $arguments[4];

		if ( ! isset( $arguments[5] ) )

			$hash = md5(
				$value . SEPARATOR_HASH_WORDS .
					( is_null( $keywords ) ? '' : $keywords ) 
			);

		else if ( strlen( $arguments[5] ) )

			$hash = $arguments[5];
		else
		
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );
			
		$properties = array(
			PROPERTY_VALUE => $value,
			PROPERTY_HASH => $hash
		);

		if ( ! is_null( $uid ) )

			$properties[PROPERTY_IMAP_UID] = $uid;

		if ( ! is_null( $sender ) )

			$properties[PROPERTY_SENDER] = $sender;

		if ( ! is_null( $subject ) )

			$properties[PROPERTY_SUBJECT] = $subject;

		if ( ! is_null( $keywords ) )

			$properties[PROPERTY_KEYWORDS] = $keywords;

		return self::add( $properties );
	}
}
