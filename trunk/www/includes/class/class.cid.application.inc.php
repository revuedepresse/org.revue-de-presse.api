<?php

namespace cid
{
	class Application
	{
		/**
		* Construct a new instance of /cid/Application
		*
		* @return object
		*/
		private function __construct()
		{
		}
	
		public static function bootstrap()
		{
			$application = new self();
		}
	
		public static function addEntityTypes( $type = NULL )
		{
		}
	
		public static function addEntity( 
			$name = NULL, 
			$types = NULL, 
			$persistency = NULL )
		{
		}
		
	
		public static function addPersistency( 
			$definition = NULL, 
			$type = NULL
		)
		{
		}
	
		public static function addHost()
		{
		}
		
		public static function bindMethod( 
			$class = NULL, 
			$function = NULL,
			$type = NULL 
		)
		{
		}
	
		public static function functionStatic()
		{
		}
		
		public static function getSource()
		{
		}	
	
		public static function makeClass( 
			$name = NULL, 
			$namespace = NULL, 
			$prepend = NULL, 
			$append = NULL  
		)
		{
		}
		
		public static function makeFunctiom( 
			$name = NULL, 
			$type = NULL,
			$source = NULL,
			$prepend = NULL,
			$append = NULL
		)
		{
		}
	
		public static function syncContext( $context_type = NULL )
		{
			self::syncEntities();
			self::syncPersistencyLayer();
		}
	
		public static function syncEntities()
		{
		}
	
		public static function syncPersistencyLayer()
		{
		}
		
		public static function trace()
		{
		}
	}
}

/**
*************
* Changes log
*
*************
* 2011 09 26
*************
* 
* development :: cid :: application ::
*
* Start implementing Cid namespace 
*
* (branch 0.1 :: revision :: 658)
*
*/