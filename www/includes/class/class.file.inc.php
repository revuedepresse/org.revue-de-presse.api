<?php

/**
* File class
*
* Class for file handling
* @package  sefi
*/
class File extends Content_Manager
{
	/**
    * Construct a File
    *
	* @return	object	File
	*/
	public function __construct()
	{
		$arguments = func_get_args();

		$class_parent = get_parent_class( __CLASS__ );

		$file = call_user_func_array(
			array( $class_parent, '__construct' ) ,
			$arguments
		);

		if ( isset( $file->{PROPERTY_PATH} ) )

			$file->{PROPERTY_SIZE} = $file->getSize();

		return $file;
	}

    /**
    * Get the size of an File instance
    *
	* @return	integer	size
	*/
	public function getSize()
	{
		if ( ! isset( $this->{PROPERTY_PATH} ) )
		
			throw new Exception(
				sprintf( EXCEPTION_INVALID_ENTITY, ENTITY_PATH )
			);

		return self::getFileSize( $this->{PROPERTY_PATH} );
	}

	/**
	* Get a file by providing its id
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
				PROPERTY_FOLDER =>
					array(
						PROPERTY_FOREIGN_KEY =>
							PREFIX_TABLE_COLUMN_FOLDER.PROPERTY_ID
					),
				PROPERTY_NAME,
				PROPERTY_PATH,
				PROPERTY_SIZE,
				PROPERTY_STATUS,
				PROPERTY_TYPE
			),
			CLASS_FILE,
			TRUE
		);	
	}

    /**
    * Get a file size
    *
	* @param	string	$path
	* @return	string	mixed
	*/
	public static function getFileSize( $path = NULL )
	{
		global $class_application, $verbose_mode;

		if ( is_null( $path ) )
			
			throw new Exception(
				sprintf( EXCEPTION_INVALID_ENTITY, ENTITY_PATH )
			);

		$file_size = 0;

		$return = $class_application::out( '$du -sk ' . $path );

		$file_size = $return[0][0];

		return $file_size;
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
	* Make a file
	*
	* @return	object	fiel
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

			$folder_id = $arguments[1];
		else

			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		if ( ! isset( $arguments[2] ) )

			$type = NULL;
		else

			$type = $arguments[2];

		if ( isset( $arguments[3] ) )

			$status = $arguments[3];
		else

			$status  = ENTITY_STATUS_INACTIVE;

		if ( is_null( $type ) )

			$file_type = self::getDefaultType();
		else
		{
			$properties = array(
				PROPERTY_NAME => $type,
				PROPERTY_ENTITY => ENTITY_FILE
			);

			// fetch the selected store type
			$file_type = self::getTypeValue( $properties );
		}

		if ( file_exists( $path ) )
		
			$size = self::getFileSize( $path );

		$sections = explode( '/', $path );

		$name = array_pop(  $sections );

		$properties = array(
			PREFIX_TABLE_COLUMN_FOLDER.PROPERTY_ID => array(
				PROPERTY_FOREIGN_KEY => $folder_id
			),
			PROPERTY_NAME => $name,
			PROPERTY_PATH => $path,
			PROPERTY_SIZE => $size,
			PROPERTY_STATUS => $status,
			PROPERTY_TYPE => $file_type
		);

		return self::add( $properties );
	}
}

/**
*************
* Changes log
*
*************
* 2011 04 14
*************
* 
* Implement the following methods
*
* File::make
* 
* (revision 642)
*
*************
* 2011 04 20
*************
* 
* Implement the following methods
*
* File::getFileSize
* File->getSize
* 
* (revision 647)
* 
*/