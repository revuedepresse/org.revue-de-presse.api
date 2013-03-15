<?php

/**
* File manager class
*
* Class for file management
* @package  sefi
*/
class File_Manager extends Toolbox
{
	/**
	* Dump a message to file
	*
	* @param	mixed	$message	message
	* @return	boolean	success indicator
	*/
	public static function dump( $message = NULL )
	{
		$class_dumper = self::getDumperClass();

		$class_exception_handler = self::getExceptionHandlerClass();

		if ( is_null( $message ) )
		{
			$backtrace = debug_backtrace( TRUE );
			
			while ( list( $index , $trace ) = each( $backtrace ) )
			{
				if (
					isset( $backtrace[$index][PROPERTY_FUNCTION] ) &&
					$backtrace[$index][PROPERTY_FUNCTION] === __FUNCTION__
					||
					isset( $backtrace[$index][PROPERTY_CLASS] ) &&
					$backtrace[$index][PROPERTY_CLASS] === $class_dumper
					||
					isset( $backtrace[$index][PROPERTY_CLASS] ) &&
					$backtrace[$index][PROPERTY_CLASS] === $class_exception_handler					
				)

					unset( $backtrace[$index] );
				
				if ( isset( $backtrace[$index][PROPERTY_OBJECT] ) )
					
					unset( $backtrace[$index][PROPERTY_OBJECT] );

				if ( isset( $backtrace[$index][PROPERTY_SHORTHAND_ARGUMENTS] ) )
					
					unset( $backtrace[$index][PROPERTY_SHORTHAND_ARGUMENTS] );
			}
		}

		$_message =
			fprint(
				(
						is_null( $message )
					?
						print_r( $backtrace, TRUE )
					:
						$_message
				),
				TRUE,
				FALSE,
				TRUE
			)
		;

		$handle = fopen(
			dirname( __FILE__ ) .
			'/../../../../../logs/logs_'.NAMESPACE_TIFA.'_'.
			date('Ymd_H').'.log',
			'a+'
		);

		$success = fwrite(
			$handle,
			date('H:m') . $_message . "\n\n"
		);

		return array( $success && fclose( $handle ) => $_message );
	}

	/**
    * Extract metadata
    *
    * @param	string		$resource 	a resource
    * @param	integer		$type		the type of metadata to be extracted
    * @return 	string
	*/
	public static function extractMetadata($resource , $type = METADATA_TYPE_XPACKET)
	{
		$metadata = '';

		switch ($type)
		{
			case METADATA_TYPE_RDF:
			case METADATA_TYPE_XPACKET:

				$xpacket_start = 0;

				if (is_array($resource) && isset($resource[PROPERTY_PATH]))
				
					$file_contents = file_get_contents($resource[PROPERTY_PATH]);
				else

					$file_contents = $resource;

				if (FALSE !== ($xpacket_start = strpos($file_contents, '<?xpacket begin="', $xpacket_start)))

					if (FALSE !== ($xpacket_end = strpos($file_contents, '<?xpacket end="w"?>', $xpacket_start + 1)))

						$metadata = substr(
							$file_contents,
							$xpacket_start,
							$xpacket_end - $xpacket_start + strlen('<?xpacket end="w"?>')
						);

				if ($type == METADATA_TYPE_RDF && !empty($metadata))
				{
					$rdf_start = strpos($metadata, '<rdf:RDF ');

					$rdf_end = strpos($metadata, '</rdf:RDF>', $rdf_start + 1);
					
					$metadata = substr($metadata, $rdf_start, $rdf_end - $rdf_start + strlen('</rdf:RDF>'));
				}

					break;
		}
	
		return $metadata;
	}

	/**
	* Get the base directory
	* 
	* @return	string	directory
	*/
	public static function getBaseDirectory()
	{
		return DIR_BASE;
	}

	/**
	* Get a directory
	* 
	* @param	string	$type
	* @return	string	directory
	*/
	public static function getDirectory( $type = NULL )
	{
		$directory = NULL;

		if ( is_null( $type ) ) $type = DIRECTORY_TYPE_ROOT;

		$base_directory = self::getBaseDirectory();

		if (
			file_exists( $base_directory . $type ) &&
			is_dir( $base_directory . $type )
		)
			$directory = $base_directory . $type;
		else
		
			throw new Exception( sprintf(
				EXCEPTION_INVALID_ENTITY, ENTITY_DIRECTORY
			) );
		
		return $directory;
	}

	/**
	* @see	FILE_MANAGER :: loadFileContents
	*/
	public static function getFileContents()
	{
		$arguments = func_get_args();
		return call_user_func_array(
			array( __CLASS__, 'loadFileContents' ), $arguments
		);
	}

	/**
	* Import persistency declarations
	*
	* @param	mixed	$properties
	* @return 	nothing
	* @see		FILE_MANAGER :: importPersistencyDeclarations
	*/
	public static function importPersistencyDeclarations( $properties )
	{
		$_targets =
		$_files_names =
		$resource_properties = array();
		$files_names_available = FALSE;

		if ( ! isset( $properties[PROPERTY_DESTINATION] ) )
			
			throw new Exception( sprintf(
				EXCEPTION_INVALID_ENTITY, ENTITY_DESTINATION
			) );
		else
		{
			if (
				! isset( $properties[PROPERTY_TARGETS] ) &&
				! isset( $properties[PROPERTY_FILES_NAMES] )
			)
				throw new Exception( sprintf(
					EXCEPTION_INVALID_ENTITY, ENTITY_TARGET
				) );

			else if ( isset( $properties[PROPERTY_TARGETS] ) )

				$_targets =
				$targets = $properties[PROPERTY_TARGETS];

			else if ( isset( $properties[PROPERTY_FILES_NAMES] ) )
			{	
				$_files_names =
				$_targets =
				$files_names = $properties[PROPERTY_FILES_NAMES];
				$files_names_available = TRUE;
			}

			$destination_directory = $properties[PROPERTY_DESTINATION] . '/';
			$root_directory = self::getDirectory();
			$parent_directory = $root_directory . DIRECTORY_TYPE_INCLUDES . '/';
			$prefix = 'constants.';
			$suffix = EXTENSION_PHP;
			$placeholder_target = PLACEHOLDER_TARGET;
			$file_name_template = $prefix . $placeholder_target . $suffix;
			
			if ( ! $files_names_available )
			{
				$destination_template = $destination_directory . $file_name_template;
				$path_template = $parent_directory . $file_name_template ;
			}

			if ( is_string( $files_names ) && strlen( trim( $files_names ) ) )
			
				$_targets =
				$_files_names = array( $files_names );

			else if ( is_string( $targets ) && strlen( trim( $targets ) ) )
			
				$_targets = array( $targets );
			
			if ( is_array( $_targets ) && count( $_targets ) )

				foreach( $_targets as $target )
				{
					if ( ! $files_names_available )
					{
						$destination_path = str_replace(
							$placeholder_target, $target, $destination_template
						);
						$target_path = str_replace(
							$placeholder_target, $target, $path_template
						);
					}
					else
					{
						$destination_path = $destination_directory . $target . $suffix; 
						$target_path = $parent_directory . $target . $suffix;
					}

					$resource_properties[$target] = array(
						PROPERTY_CONTENT => self::getFileContents( $target_path ),
						PROPERTY_PATH => $destination_path
					);
					$content = &$resource_properties[$target][PROPERTY_CONTENT];
					if ( strlen( $content ) > 0 )
	
						self::writeOnDisk( $destination_path, $content, TRUE );
				}
		}

		return $resource_properties;
	}

	/**
    * Alias to loadSettings method
    *
    * @param	string		$file_name		file name
    * @param	integer		$store_type		store type
    * @return 	string
	*/
	public static function load_configuration(
		$file_name,
		$store_type = STORE_YAML
	)
	{
		return self::loadSettings( $file_name, $store_type );
	}

	/**
    * load a file contents
    *
    * @param	string		$file_path			file path
    * @param	integer		$file_extension		file extension
    * @return 	mixed		file contents
    */
	public static function loadFileContents(
		$file_path,
		$file_extension = NULL
	)
	{
		$file_contents = NULL;

		switch ($file_extension)
		{
			case EXTENSION_INI:

				if (file_exists($file_path))

					$file_contents  = parse_ini_file($file_path, TRUE);
				
					break;

			default:
			
				if (file_exists($file_path))

					$file_contents = file_get_contents($file_path);
		}

		return $file_contents;
	}

    /**
    * Load settings from file
    *
    * @param	string	$file_name	containing a file name
    * @param	string	$store_type	$file type
    * @return 	nothing
	*/
	public static function loadSettings(
		$file_name,
		$store_type = STORE_YAML
	)
	{
		// set the YAML class name
		$class_yaml = self::getYamlClass();

		$output = NULL;

		$path_directory = PATH_CONFIG;

		$path_file = $path_directory . CHARACTER_SLASH . $file_name;

		if ( file_exists( $path_file ) )

			// deserialize a YAML file
			$output = $class_yaml::deserialize( $path_file );
		else

			throw new Exception(
				sprintf(
					EXCEPTION_INVALID_CONFIGURATION_FILE,
					$path_file
				)
			);

		return $output;
	}
	
	/**
	* Write a file on a disk
	*
	* @param	string	$path		path leading to a file
	* @param	string	$content	file content
	* @param	boolean	$truncate	truncate flag
	* @return	boolean	operation success
	*/
	public static function writeOnDisk(
		$path, $content = NULL, $truncate = FALSE
	)
	{
		$results = NULL;

		if (
			is_null( $content ) &&
			(
				! is_string( $content ) ||
				( strlen( $content ) === 0 )
			) ||
			! is_string( $path ) ||
			( strlen( $content ) === 0 )
		)
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		else if ( file_exists( $path ) && ! $truncate )

			throw new Exception(
				EXCEPTION_INVALID_ARGUMENT . ' (' . PROPERTY_PATH . ')'
			);
		else
			$results = file_put_contents( $path, $content );

		return $results;
	}
}

/**
*************
* Changes log
*
*************
* 2011 10 26
*************
*
* deployment :: settings ::
*
* Mutualize retrieval of configuration folder
* 
* (branch 0.1 :: revision :: 796)
* (branch 0.2 :: revision :: 402)
*
*************
* 2011 11 01
*************
*
* development :: class definition ::
*
* Implement file write on disk
*
* method affected ::
*
* 	FILE_MANAGER :: writeOnDisk
* 
* (branch 0.1 :: revision :: 819)
*
*************
* 2012 05 08
*************
*
* development :: file management ::
*
* Add alias to file contents getter
*
* method affected ::
*
* 	FILE_MANAGER :: getFileContents
* 
* (branch 0.1 :: revision :: 923)
*
*************
* 2012 05 09
*************
*
* development :: file management ::
*
* Implement script copy
*
* method affected ::
*
* 	FILE_MANAGER :: importPersistencyDeclarations
* 	FILE_MANAGER :: writeOnDisk
* 
* (branch 0.1 :: revision :: 926)
*
*/