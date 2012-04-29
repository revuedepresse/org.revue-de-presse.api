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
		$directory_current = dirname(__FILE__);
		$directory_facebook = '/fb/';
		$directory_ghost = 'ghost/'; 
		$directory_tifa = '## FILL PROJECT DIR ##/';
		$directory_web = 'www/';
		$directory_setting = 'settings/';
		$directory_services = '/var/services/';
		$directory_settings = $directory_services.$directory_setting;
		$directory_settings_ghost = $directory_settings.$directory_ghost;
		$directory_web_services = $directory_services.$directory_web;
		$directory_web_service_ghost = $directory_web_services.$directory_ghost;
		$directory_web_service_tifa = $directory_web_services.$directory_tifa;
	
		$host =
		$resquest_uri =
		$script_name =
		$server_name =
		$server_port = null;

		$host_dev_tifa = '## FILL HOSTNAME ##';
		$host_fr_snaps = '## FILL HOSTNAME ##';

		$mode_cli = defined('STDIN');

		$port_http = '80';
		$port_ghost = '8086';

		$prefix_file_hidden = '.';

		if ( isset( $_SERVER['HTTP_HOST'] ) )
			$host = $_SERVER['HTTP_HOST'];
		if ( isset( $_SERVER['REQUEST_URI'] ) )
			$request_uri = $_SERVER['REQUEST_URI'];
		if ( isset( $_SERVER['SCRIPT_NAME'] ) )
			$script_name = $_SERVER['SCRIPT_NAME'];
		if ( isset( $_SERVER['SERVER_NAME'] ) )
			$server_name = $_SERVER['SERVER_NAME'];
		if ( isset( $_SERVER['SERVER_PORT'] ) )
			$server_port = $_SERVER['SERVER_PORT'];

		$file_name_settings = $prefix_file_hidden.
			FILE_NAME_SERVICE_CONFIGURATION.EXTENSION_INI
		;

        $directory_facebook_detected =
			! is_null($request_uri) &&
			(0 === strpos($request_uri, $directory_facebook))
		;
		$cli_api_facebook =
			! is_null($request_uri) && !is_null($server_name) &&
            $directory_facebook_detected &&
            (in_array($host, array($host_dev_tifa, $host_fr_snaps)))
		;

		if (
			! $directory_facebook_detected && ! is_null($server_name) &&
			in_array($server_name, array($host_dev_tifa))
		) 
			$directory_parent = $directory_current.'/../../';

		else if (
			! $cli_api_facebook && ! is_null($server_port) &&
			in_array($server_port, array($port_http)) ||
			(
				$mode_cli && ! is_null($script_name) &&
				(0 === strpos($script_name, $directory_web_service_tifa))
			)
		)
			$directory_parent = $directory_settings;

		else if ( (
				! is_null($server_port) &&
				in_array($server_port, array($port_ghost)) ||
				(
					$mode_cli && ! is_null($script_name) &&
					(0 === strpos($script_name, $directory_web_service_ghost))
				) 
			) || 
			$cli_api_facebook
		)
			$directory_parent = $directory_settings_ghost;		

		$file_path = $directory_parent.$file_name_settings;

		$file_contents = self::loadFileContents($file_path, EXTENSION_INI);

		if (
			is_array($file_contents) && count($file_contents) &&
			is_array($file_contents)
		)
		{
			if (
				! isset($file_contents[$service]) ||
				! count($file_contents[$service])
			)
				throw new Exception(
					EXCEPTION_INVALID_SERVICE_CONFIGURATION.' ('.$service.')'
				);
			
			if (! isset($file_contents[$service][$property]))

				throw new Exception(EXCEPTION_INCOMPLETE_SERVICE_CONFIGURATION);

			return $file_contents[$service][$property];
		}
		else
			throw new Exception(sprintf(
				EXCEPTION_INVALID_CONFIGURATION_FILE, $file_path
			));
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

/**
*************
* Changes log
*
*************
* 2011 09 26
*************
* 
* Revise path to configuration file
*
* method affected ::
*
* SERVICE_MANAGER :: getServiceProperty
* 
* (trunk :: revision :: 243)
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
* SERVICE_MANAGER :: getServiceProperty
* 
* (trunk :: revision 451)
* (v0.2 :: revision 452)
*
*/