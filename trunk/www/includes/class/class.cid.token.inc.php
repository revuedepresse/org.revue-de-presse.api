<?php


/// @cond DOC_SKIP

namespace cid
{

/// @endcond
	
	/**
	* Token class
	*
	* Class for managing tokens
	* @package  sefi
	*/
	class Token extends \Source
	{
		/**
		* Get a signature
		*
		* @param	boolean	$wrapped	namespace flag
		* @return	string	signature
		*/
		public static function getSignature( $wrapped = TRUE )
		{
			$_class = __CLASS__;

			if ( $wrapped )
			{
				list( $_namespace, $_class ) = explode( '\\', __CLASS__ );
				self::$namespace = $_namespace;
			}
	
			return $_class;
		}
	}


/// @cond DOC_SKIP

}

/// @endcond
