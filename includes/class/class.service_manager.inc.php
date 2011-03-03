<?php

/**
* Service Manager class
*
* Class for services management
* @package  sefi
*/
class Service_Manager extends Deployer
{
	/**
    * Get a system property
    *
    * @param	string		$property	property name
    * @param	string		$service	service type
    * @return 	mixed		value
    */
	public static function getServiceProperty(
		$property,
		$service = SERVICE_MYSQL
	)
	{
		$file_path = dirname(__FILE__).'/../../.'.FILE_NAME_SERVICE_CONFIGURATION.EXTENSION_INI;

		$file_contents = self::loadFileContents($file_path, EXTENSION_INI);

		if (
			is_array($file_contents) &&
			count($file_contents) &&
			is_array($file_contents)
		)
		{
			if (
				!isset($file_contents[$service]) ||
				!count($file_contents[$service])
			)
			
				throw new Exception(EXCEPTION_INVALID_SERVICE_CONFIGURATION);
			
			if (!isset($file_contents[$service][$property]))

				throw new Exception(EXCEPTION_INCOMPLETE_SERVICE_CONFIGURATION);

			return $file_contents[$service][$property];
		}
		else
		
			throw new Exception(sprintf(fEXCEPTION_INVALID_CONFIGURATION_FILE, $file_path));
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
}
?>