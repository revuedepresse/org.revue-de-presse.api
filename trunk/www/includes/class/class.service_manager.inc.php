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
        global $argv,
            $build_jenkins,
            $debug_enabled,
            $directory_web_services,
            $jenkins_workspace,
            $symfony_detected;

		$directory_current = dirname( __FILE__ );

		$forwarded_host =
		$script_name =
		$server_name =
		$server_port = null;

		$host_lan_ip = '192.168.0.15';
		$host_local_ip = '127.0.0.1';
		$host_local_livecoding = 'livecoding.dev';
		$host_local_snaps = 'snaps.dev';
		$host_dev_tifa = '## FILL HOSTNAME ##';
		$host_dev_wtw_stable = 'stable.## FILL HOSTNAME ##';
		$host_dev_wtw = '## FILL HOSTNAME ##';
		$host_org_wtw_build = '## FILL HOSTNAME ##';
        $host_org_wtw_stable = '## FILL HOSTNAME ##';

		$mode_cli = defined('STDIN');

		$port_http = '80';
		$port_ghost = '8086';

		$prefix_file_hidden = '.';
		$script_app_console = 'app/console';

		if ( isset( $_SERVER['HTTP_HOST'] ) )
            $host = $_SERVER['HTTP_HOST'];
        if ( isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) )
			$forwarded_host = $_SERVER['HTTP_X_FORWARDED_HOST'];
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

        $host_dev_wtw_stable_detected = (
                isset( $forwarded_host ) &&
                in_array( $forwarded_host, array( $host_dev_wtw_stable, $host_org_wtw_stable ) )
        );

        $environment_development = ! $build_jenkins && ( 
                ! $symfony_detected &&
                ! $host_dev_wtw_stable_detected &&
                ! is_null( $server_name ) &&
                in_array(
                    $server_name,
                    array(
                        $host_local_ip,
                        $host_lan_ip,
                        $host_local_livecoding,
                        $host_local_snaps,
                        $host_dev_tifa
                    )
                ) || (
                    $mode_cli && (
                        ( false === strpos( $script_name, 'phpunit' ) ) &&
                        ( false === strpos( $script_name, 'app/console' ) ) &&
                        ( false === strpos( $script_name, 'installer.php' ) ) &&
                        ( isset($argv[1]) && false !== strpos($argv[1], 'wtw:api') )
                    )
                )
            )
        ;

        if ($debug_enabled)
        {
            error_log( '[build jenkins] ' . ( $build_jenkins ? 'true' : 'false' ) );
            error_log( '[development environment] ' . ( $environment_development ? 'true' : 'false' ) );
            error_log( '[forwarded host] ' . $forwarded_host );
            error_log( '[jenkins workspace] ' . $jenkins_workspace );
            error_log( '[server name] ' . $server_name );
        }

        if ($environment_development)
			$file_path =
				$directory_current . '/../../' .
				$file_name_settings
			;

		else if (
            ! $build_jenkins &&
            ! in_array( $server_name, array( $host_dev_wtw, $host_dev_wtw_stable ) ) &&
			isset( $server_port ) &&
			in_array( $server_port, array( $port_http ) ) ||
			(
				$mode_cli &&  ! is_null( $script_name ) &&
				( 0 === strpos( $script_name, '/var/www/## FILL DOCUMENT ROOT ##/' ) )
			)
		)
			$file_path = $directory_current.'/../../../../settings/'.
				$file_name_settings
			;

		else if (
			! $build_jenkins &&
            $mode_cli && ! is_null( $script_name ) && ( FALSE === strpos( $script_name, 'phpunit' ) ) &&
            (
				( FALSE !== strpos( $script_name, '/web/## FILL PROJECT DIR ##/branches' ) ) ||
				( FALSE !== strpos( $script_name, 'pear' ) ) ||
				( $script_name === $script_app_console )
			)
		)
			$file_path = $directory_current.'/../../'.
				$file_name_settings
			;

		else if (
			isset( $server_port ) &&
			in_array( $server_port, array( $port_ghost ) ) ||
			(
				$mode_cli && ! is_null( $script_name ) &&
                (
                    ( 0 === strpos( $script_name, '/var/www/## FILL DOCUMENT ROOT ##/' ) ) ||
                    ( FALSE !== strpos( $script_name, 'phpunit' ) ) ||
                    $build_jenkins
                )
			) ||
            in_array( $server_name, array( $host_dev_wtw, $host_org_wtw_build ) ) ||
            $host_dev_wtw_stable_detected 
        ) {
            $target = 'ghost';
            if (isset($jenkins_workspace))
                $target = $jenkins_workspace;
            else if ($build_jenkins) 
                $target = '## FILL ME ##';
			$file_path = $directory_web_services.'/../settings/' . $target . '/'.
				$file_name_settings
            ;	
        }	


        if ($build_jenkins && $debug_enabled) 
        {
            error_log('[file path] ' . $file_path);
            error_log('[script name] ' . $script_name);
        }

        $file_contents = self::loadFileContents( $file_path, EXTENSION_INI );

		if (
			is_array( $file_contents ) && count( $file_contents)
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
* 	SERVICE_MANAGER :: getServiceProperty
* 
* (trunk :: revision :: 243)
*
*************
* 2011 10 25
*************
* 
* Add babylone_crusader settings
*
* method affected ::
*
* 	SERVICE_MANAGER :: getServiceProperty
* 
* (branch 0.1 :: revision 772)
*
*************
* 2012 05 01
*************
*
* development :: service management ::
* 
* Prepare service provision for calling Symphony in CLI mode
*
* method affected ::
*
* 	SERVICE_MANAGER :: getServiceProperty
* 
* (branch 0.1 :: revision 873)
*
*/
