<?php

/**
* Directory class
*
* Class for directory management
* @package  sefi
*/
class Folder extends File
{
	/**
	* Save a folder
	*
	* @return	mixed
	*/
	public function save_content()
	{
		global $class_application, $verbose_mode;

		$class_file = $class_application::getFileClass();

		if (
			isset( $this->{PROPERTY_CONTENT} ) &&
			is_array( $this->{PROPERTY_CONTENT} ) &&
			count( $this->{PROPERTY_CONTENT} )
		)
		{
			while ( list( , $item ) = each( $this->{PROPERTY_CONTENT} ) )

				if (
					is_object( $item ) &&
					( get_class( $item ) === CLASS_FILE )
				)

					$class_file::make(
						$item->{PROPERTY_PATH},
						$item->{PROPERTY_FOLDER},
						$item->{PROPERTY_TYPE}
					);

		}
	}

	/**
	* Scan the content of a folder
	*
	* @param	boolean	$recursively	recursively
	* @param	integer	$depth			depth
	* @return	object	folder
	*/
	public function scan( $recursively, $depth = NULL )
	{
		if ( isset( $this->{PROPERTY_PATH} ) )
		{
			$folder = self::scan_folder( $this->{PROPERTY_PATH}, $recursively, $depth );

			$this->{PROPERTY_CONTENT} = $folder->{PROPERTY_CONTENT};

			$this->{PROPERTY_SUB_FOLDER} = $folder->{PROPERTY_SUB_FOLDER};
		}
	}

	/**
	* Get a folder by providing its id
	*
	* @param	integer	$id	identifier
	* @return	object	Store
	*/
	public static function getById($id)
	{
		if ( ! is_numeric( $id  ) )
			
			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		return self::getByProperty(
			$id,
			PROPERTY_ID,
			array(
				PROPERTY_ID,
				PROPERTY_NAME,
				PROPERTY_PATH,
				PROPERTY_SIZE,
				PROPERTY_STATUS,
				PROPERTY_TYPE
			),
			CLASS_FOLDER,
			TRUE
		);	
	}

	/**
	* Get a folder by providing its path
	*
	* @param	string	$path	path
	* @return	object	Store
	*/
	public static function getByPath( $path )
	{
		if ( ! is_string( $path  ) )
			
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		return self::getByProperty(
			$path,
			PROPERTY_PATH,
			array(
				PROPERTY_ID,
				PROPERTY_NAME,
				PROPERTY_PATH,
				PROPERTY_SIZE,
				PROPERTY_STATUS,
				PROPERTY_TYPE
			),
			CLASS_FOLDER,
			TRUE
		);	
	}

	/**
	* Get a signature
	*
	* @param	boolean	$namespace	namespace flag
	* @return	string	signature
	*/
	public static function getSignature($namespace = TRUE)
	{
		$_class = __CLASS__;

		if (!$namespace)

			list($_namespace, $_class) = explode('\\', __CLASS__);

		return $_class;
	}

	/**
	* Make a folder
	*
	* @return	object	folder
	*/
	public static function make()
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$arguments = func_get_args();

		$size = 0;

		if ( isset( $arguments[0] ) )

			$path = $arguments[0];
		else

			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		if ( isset( $arguments[1] ) )

			$status = $arguments[1];
		else

			$status  = ENTITY_STATUS_INACTIVE;

		$sections = explode( '/', $path );

		if ( file_exists( $path ) )
		
			$size = parent::getFileSize( $path );

		$name = array_pop(  $sections );

		$properties = array(
			PROPERTY_NAME => $name,
			PROPERTY_SIZE => $size,
			PROPERTY_PATH => $path
		);

		return self::add( $properties );
	}

	/**
	* Scan a folder
	*
	* @param	string 	$path			path
	* @param	boolean	$recursively	recursively
	* @param	integer	$depth			depth
	* @return	object	folder
	*/
	public static function scan_folder(
		$path = NULL,
		$recursively = FALSE,
		$depth = NULL
	)
	{
		global $class_application, $verbose_mode;
		
		$class_file = $class_application::getFileClass();
		
		if ( is_null( $path ) || ! is_string( $path ) )
		
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );
		
		$sections = explode( '/', $path );
		
		$folder_name = $sections[count( $sections ) - 1];

		$folder = self::make( $path );
 
		$file_names = 

		$sub_folders = array();
 
		// Check the existence of the directory
		if ( is_dir( $path ) )
		
			// Open the directory
			if ( $directory_handle = opendir( $path ) )
			{
				// Loop on files
				while (
					( $name = readdir( $directory_handle ) )
						!== FALSE
				)
		
					// Check only visible files
					if ( ! preg_match( '/^\./', $name ) )
					{
						$type = NULL;

						// check if the current file is a directory
						if ( is_dir( $path . '/' . $name ) )
						
							$type = ENTITY_DIRECTORY;
		
						// Add the current file name to an array
						$file_names[] = new $class_file(
							array(
								PROPERTY_FOLDER => $folder->{PROPERTY_ID},
								PROPERTY_PATH => $path . '/' . $name,
								PROPERTY_TYPE => $type
							)
						);

						// Add the current directory to an array
						if ( ! is_null( $type ) )
						{
							$sub_folders[] = self::make( $path . '/' . $name );

							if (
								$recursively &&
								( is_null( $depth ) || $depth )
							)
							{
								if ( ! is_null( $depth ) )
	
									$depth--;
								
								$sub_folders[count( $sub_folders ) - 1 ]->scan( $recursively, $depth );

								$sub_folders[count( $sub_folders ) - 1 ]->save_content();
							}
						}
					}
		
				// Close the current directory 
				closedir( $directory_handle );
			}

		$folder->{PROPERTY_CONTENT} = $file_names;

		$folder->{PROPERTY_SUB_FOLDER} = $sub_folders;

        $folder->save_content();

		return $folder;
	}

	/**
	* Sync a folder
	*
	* @return	mixed
	*/
	public function sync()
	{
		if ( isset( $this->{PROPERTY_CONTENT} ) )
		{	
			$contents = $this->{PROPERTY_CONTENT};

			$this->{PROPERTY_CONTENT} = NULL;

			unset( $this->{PROPERTY_CONTENT} );
		}
	
		$callback_parameters = parent::sync();

		if ( isset( $contents ) )

			$this->{PROPERTY_CONTENT} = $contents;
		
		return $callback_parameters;
	}

}

/**
*************
* Changes log
*
*************
* 2011 04 15
*************
*
* Implement recursive folder scan and depth limitation
*
* (revision 645)
*
*************
* 2011 04 14
*************
* 
* Implement the folder scan
* 
* (revision 642)
*
*/