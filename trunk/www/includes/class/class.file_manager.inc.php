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
			'/../../../logs/logs_'.NAMESPACE_TIFA.'_'.
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
		$file_path, $file_extension = NULL
	)
	{
		$file_contents = NULL;

		if (file_exists($file_path))

			switch ($file_extension)
			{
				case EXTENSION_INI:
	
					$file_contents  = parse_ini_file($file_path, TRUE);
					
						break;
	
				default:
				
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

		$path_directory =
			dirname(__FILE__).
			DIR_PARENT_DIRECTORY.
			DIR_PARENT_DIRECTORY.
			CHARACTER_SLASH.DIR_CONFIGURATION.CHARACTER_SLASH
		;

		$path_file = $path_directory.$file_name;

		if ( file_exists( $path_file ) ) // deserialize a YAML file
			$output = $class_yaml::deserialize( $path_file );
		else
			throw new Exception( sprintf(
				EXCEPTION_INVALID_CONFIGURATION_FILE, $path_file
			) );

		return $output;
	}
}

/**
*************
* Changes log
*
*************
* 2012 04 29
*************
* 
* deployment :: service management ::
*
* Revise declaration of file settings
*
* method affected ::
*
* FILE_MANAGER :: loadFileContent
*
* (trunk :: revision 451)
* (v0.2 :: revision 452)
*
*/