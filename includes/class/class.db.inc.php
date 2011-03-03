<?php

/**
* DB class
*
* @package  sefi
*/
class DB extends Database_Connection
{
    /**
    * Construct a new instance of Database Connecion object
    * 
    * @param	string	$host	host
    * @param	stirng	$database	database
    * @param 	string	$user_name	user name
    * @param 	string	$password	password
    * @param 	boolean	$pdo		PDO usage indicator
    * @param 	mixed	$checksum	checksum
    * @return 	object
    */	 	
	public function __construct(
		$host = DB_HOST,
		$database = DB_SEFI,
		$user_name = DB_USER_NAME,
		$password = DB_PASSWORD,
		$pdo = FALSE,
		$checksum = NULL
	)
	{
		// construct the parent object
		parent::__construct($host, $database, $user_name, $password, $pdo, $checksum);
	}


    /**
    * Check some the results of some query execution
    *
    * @param	string		$query		query
    * @param	mixed		$results	results
    * @param	object		$link		link
    * @param	boolean		$pdo		PDO usage indicator
    * @param	mixed		$checksum	checksum
    * @return 	object
    */
	public static function checkResults($query, $results, $link, $pdo = FALSE, $checksum = NULL)
	{
		$class_dumper = CLASS_DUMPER;

		$pdo_active = self::PDOEnabled($pdo, $checksum);

		if (
			strpos(strtolower($query), 'insert') !== FALSE ||
			strpos(strtolower($query), 'replace') !== FALSE
		)
		{
			if (!$pdo_active && ( $results === TRUE ))

				$results = $link->insert_id;

			else if ($pdo_active && is_object($results) && get_class($results) == CLASS_PDO)

				$results = $link->lastInsertId();
		}
		else if (
			strpos(strtolower($query), 'truncate') === FALSE &&
			strpos(strtolower($query), 'create table') === FALSE &&
			strpos(strtolower($query), 'create database') === FALSE &&
			strpos(strtolower($query), 'alter') === FALSE
		)
		{
			if (
				!$pdo_active &&
				!is_object($results) &&
				!empty($link->errno) &&
				!empty($link->error)
			)
			{
				$error_no = $link->errno;
				$error_info = $link->error;
			}
			else if (
				$pdo_active &&
				!is_object($results) &&
				get_class($link) == CLASS_PDO
			)
			{
				$error_description = $link->errorInfo();
				
				$error_no = $error_description[1];
				$error_info = $error_description[2];
			}

			if (!empty($error_no))
			{
				// show the latest query executed
				$class_dumper::log(
					__METHOD__,
					array($query),
					true
				);

				throw new Exception(
					(
							!empty($error_no)
						?
							'Error ['.$error_no.']: '
						:
							''
					).
					(
							!empty($error_info)
						?
							$error_info
						:
							''
					)
				);
			}
		}

		return $results;
	}

    /**
    * Connect
    * 
    * @param	string	$host	host
    * @param	string	$database	database
    * @param 	string	$user_name	user name
    * @param 	string	$password	password
    * @param 	boolean	$pdo		PDO usage indicator
    * @param 	mixed	$checksum	checksum
    * @return 	object
    */	 
	public static function connect(
		$host = NULL,
		$database = NULL,
		$user_name = NULL,
		$password = NULL,
		$pdo = FALSE,
		$checksum = NULL
	)
	{
		$class_dumper = CLASS_DUMPER;

		if (
			!isset($host) ||
			!isset($database) ||
			!isset($user_name) ||
			!isset($password)
		) 
		{
			$database = DB_SEFI;
			$host = DB_HOST;
			$password = DB_PASSWORD;
			$user_name = DB_USER_NAME;
		}

		// construct a new instance of the Database_Connection object
		$db = new self($host, $database, $user_name, $password, $pdo, $checksum);

		// return the database connection link
		return self::getConnection($pdo);
	}

    /**
    * Check if a link is active
    *
    * @param	mixed 	$pdo	PDO usage indicator
    * @return	mixed
    */	
	public static function linkActive($pdo = FALSE)
	{
		$link_active = FALSE;

		if (!$pdo)

			$link_active = (
				!$pdo &&
				isset(self::$properties) && 
				is_object(self::$properties) &&
				isset(self::$properties->{PROPERTY_PDO}) &&
				!self::$properties->{PROPERTY_PDO} &&
				isset(self::$properties->{PROPERTY_LINK}) &&
				is_object(self::$properties->{PROPERTY_LINK}) &&
				get_class(self::$properties->{PROPERTY_LINK}) == CLASS_MYSQLI
			);

		else 

			$link_active = (
				isset(self::$properties) && 
				is_object(self::$properties) &&
				isset(self::$properties->{PROPERTY_PDO}) &&
				self::$properties->{PROPERTY_PDO} &&			
				isset(self::$properties->{PROPERTY_LINK}) &&
				is_object(self::$properties->{PROPERTY_LINK}) &&
				get_class(self::$properties->{PROPERTY_LINK}) == CLASS_PDO
			);

		return $link_active;
	}

    /**
    * Sanitize a string
    *
    * @param	mixed 	$data	data
    * @param	mixed 	$pdo	PDO usage indicator

    * @return	mixed
    */	
	public static function sanitize($data, $pdo = FALSE)
	{
		if ( ! $pdo )
		{
			$link = self::getLink($pdo);
	
			if ( is_array( $data ) )
			{
				$safe_values = array();
			
				foreach ($data as $key => $value)
	
					if ( is_string( $value ) )
	
						$safe_values[$key] = self::sanitize( $value );
					else
	
						$safe_values[$key] = $value;
				
				return $safe_values;
			}
		}
		else
		
			throw new Exception (EXCEPTION_FURTHER_IMPLEMENTATION_REQUIRED);

		// get a link
		return $link->real_escape_string( $data );
	}
	
    /**
    * Get a link
    * 
    * @param	boolean		$pdo		PDO usage indicator
    * @return	mixed
    */	
	public static function getLink($pdo = FALSE)
	{
		if (!$pdo)

			// get a link
			return self::connect();
		else

			return parent::getConnection($pdo);
	}

    /**
    * Alias to the executeQuery method
    * 
    * @param	string		$query		query
    * @param	boolean		$pdo		PDO usage indicator
    * @param	mixed		$checksum	checksum
    * @return 	array 		result
    */	
    public static function query($query, $pdo = FALSE, $checksum = NULL)
	{
		$class_dumper = CLASS_DUMPER;

		if ( !self::PDOEnabled($pdo) )
		{
			if (is_null($checksum))

				// connect
				$link = self::connect();
			else

				$link = self::connect(NULL, NULL, NULL, NULL, FALSE, $checksum);
		}
		else
		{
			$results = NULL;

			if (self::linkActive())

				self::closeConnection($pdo);

			self::connect(
				DB_HOST,
				DB_SEFI,
				DB_USER_NAME,
				DB_PASSWORD,
				$pdo
			);

			$link = parent::getConnection();
		}

		// execute a query
		$results = $link->query($query);

		try {
			$callback_parameters = self::checkResults($query, $results, $link, $pdo, $checksum);
		}
		catch (Exception $exception)
		{
			$class_dumper::log(
				__METHOD__,
				array($exception),
				DEBUGGING_DISPLAY_EXCEPTION,
				AFFORDANCE_CATCH_EXCEPTION
			);						
		}

		// return the results
		return $callback_parameters;
    }

    /**
    * Close a connection
    *
    * @param	boolean		$pdo		PDO usage indicator
    * @return 	nothing
    */	
    public static function closeConnection($pdo = FALSE)
	{
		if (
			!$pdo &&
			self::linkActive($pdo) &&
			is_object(self::$properties->{PROPERTY_LINK}) &&
			get_class(self::$properties->{PROPERTY_LINK}) == CLASS_MYSQLI
		)

			self::$properties->{PROPERTY_LINK}->close();

		if (isset(self::$properties->{PROPERTY_LINK}))

			self::$properties->{PROPERTY_LINK} = NULL;
	}

    /**
    * Fork a connection
    *
    * @param	boolean		$pdo		PDO usage indicator
    * @return 	nothing
    */	
    public static function forkConnection($pdo = FALSE)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$forked_connection = NULL;

		if ( !$pdo && self::linkActive($pdo) )

			$forked_connection = self::buildMysqliInstance(
				self::getProperty(PROPERTY_HOST),
				self::getProperty(PROPERTY_DATABASE),
				self::getProperty(PROPERTY_USER_NAME),
				self::getProperty(PROPERTY_PASSWORD)
			);

		return $forked_connection;
	}

    /**
    * Get SQL keywords
    * 
    * @return 	array 	keywords
    */	
    public static function getSQLKeywords()
	{
		return array(
			'default',
			'index',
			'order',
			'key',
			'status'
		);
	}
	
    /**
    * Execute multiple queries
    * 
    * @param	string		$multiple_queries	multiple queries
    * @param	boolean		$pdo				PDO usage indicator
    * @param	mixed		$checksum			checksum
    * @return 	array 		result
    */	
    public static function multiQuery($multiple_queries, $pdo = FALSE, $checksum = NULL)
	{
		if (!self::PDOEnabled($pdo))
		{
			if (is_null($checksum))

				// connect
				$link = self::connect();
			else
			
				$link = self::connect(NULL, NULL, NULL, NULL, FALSE, $checksum);

			// execute a query
			$results = $link->multi_query($multiple_queries);
		}
	}

    /**
    * Check if PDO has been used
    *
    * @param	boolean		$pdo		PDO usage indicator
    * @param	mixed		$checksum	checksum
    * @return 	boolean		PDO usage indicator
    */		
	public static function PDOEnabled($pdo, $checksum = NULL)
	{
		return
			$pdo ||
			(
				isset(self::$properties) &&
				is_object(self::$properties) &&
				isset(self::$properties->{PROPERTY_PDO}) &&
				self::$properties->{PROPERTY_PDO}
			)
		;
	}
}
?>