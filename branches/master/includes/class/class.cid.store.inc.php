<?php

/// @cond DOC_SKIP

namespace cid
{

/// @endcond
	
	/**
	* Json class
	*
	* Class for handling Json stores
	* @package  sefi
	*/
	class Store extends \Data_Fetcher
	{
		/**
		* Get the class signature
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
}

/**
*************
* Changes log
*
*************
* 2012 04 03
*************
*
* development :: api :: facebook ::
*
* Implement Store in cid namespace
*
* method affected ::
*
* STORE :: getSignature
* 
* (branch 0.1 :: revision 839)
* (branch 0.2 :: revision 422)
*
*/