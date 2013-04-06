<?php

/// @cond DOC_SKIP

namespace sefi
{

/// @endcond

	/**
	* Database class
	*
	* Class for representing an Database
	* @package  sefi
	*/
	class Database extends \Query
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
				list($_namespace, $_class) = explode('\\', __CLASS__);
				self::$namespace = $_namespace;
			}
	
			return $_class;
		}
	}

/// @cond DOC_SKIP

}

/// @endcond

/**
*************
* Changes log
*
*************
* 2012 05 01
*************
* 
* project :: api ::
*
* development :: facebook ::
*
* Move Database class to sefi namespace 
*
* (branch 0.1 :: revision 874)
*
*/