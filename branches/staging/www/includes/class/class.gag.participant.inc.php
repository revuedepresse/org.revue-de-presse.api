<?php

namespace gag
{
	class Participant extends Entity
	{

		/**
		* Get a signature
		*
		* @param	boolean	$namespace	namespace flag
		* @return	string	signature
		*/
		public static function getSignature( $namespace = FALSE )
		{
			$_class = __CLASS__;
	
			if ( ! $namespace )
	
				list( $_namespace, $_class ) = explode( '\\', __CLASS__ );
	
			return $_class;
		}
	}
}

/**
*************
* Changes log
*
*************
* 2012 05 05
*************
* 
* project :: gag 
*
*  
* 
* (revision 819) 
*
*/