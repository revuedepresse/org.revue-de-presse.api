<?php

/**
* Data connection class
*
* @package  sefi
*/
class Database_Connection
{	
    protected $database;
    protected $host;
    protected $username;
    protected $password;
    protected static $properties;

    /**
    * Construct a new database connecion
    * 
    * @param	string	$host	host
    * @param 	string	$database	database
    * @param 	string	$password	password
    * @param 	string	$username	username
    * @param 	boolean	$pdo		PDO usage indicator
    * @param 	mixed	$checksum	checksum
    * @return 	object
    */	    
    public function __construct(
		$host = DB_HOST,
		$database = DB_SEFI,
		$username = DB_USER_NAME,
		$password = DB_PASSWORD,
		$pdo = FALSE,
		$checksum = NULL
	)
	{
		global $class_application, $verbose_mode;

		if ( empty( $class_application ))
		
			$class_application = CLASS_APPLICATION;

		$class_dumper = $class_application::getDumperClass();

		$class_exception_handler = $class_application::getExceptionHandlerClass();

		$class_pdo = $class_application::getPdoClass();
			
		if (!is_array($host) || !isset($host[PROPERTY_PDO]))
		{
			if (empty($username))
			
				throw new Exception(EXCEPTION_DATABASE_CONNECTION_WRONG_USER_NAME);
	
			if (empty($database))
			
				throw new Exception(EXCEPTION_DATABASE_CONNECTION_WRONG_DATABASE_NAME);
	
			if (empty($host))
			
				throw new Exception(EXCEPTION_DATABASE_CONNECTION_WRONG_HOST_NAME);
		}
		else
		{
			$host = DB_HOST;
			$database = DB_SEFI;
			$username = DB_USER_NAME;
			$password = DB_PASSWORD;
			$pdo = TRUE;
		}

		self::$properties = new stdClass();

		self::$properties->{PROPERTY_PDO} = FALSE;

		if ( ! $pdo )
		{     
			$this->setHost($host);
			$this->setDatabase($database);        
			$this->setUsername($username);
			$this->setPassword($password);

			self::$properties->{PROPERTY_LINK} = $this->buildInstance();
		}
		else
		{
			self::$properties->{PROPERTY_PDO} = TRUE;

			if ( ! is_object( $pdo ) )
			{
				$pdo = new stdClass();
				
				$pdo->DB_DSN_PREFIX = DB_DSN_PREFIX_MYSQL;
			}

			/**
			*
			* Restore the native error handler
			* to bypass broken pipe error at MySQL server reboot
			*
			*/

			restore_error_handler();

			self::$properties->{PROPERTY_LINK} = new $class_pdo(
				$pdo->DB_DSN_PREFIX.':host='.$host.';dbname='.$database,
				$username,
				$password,
				array( PDO::ATTR_PERSISTENT => TRUE )
			);

			$class_exception_handler::deploy();
		}
    }

    /**
    * Set the database to select
    * 
    * @param 	string	$database	string     
    * @return 	nothing
    */	        
    private function setDatabase($database)
	{
        if (is_string($database))
            $this->database = $database;
        else
            throw new Exception("Data type error: a database has to be a string.");
    }
    
    /**
    * Set the connection host
    * 
    * @param 	string		$host   	host  
    * @return 	nothing
    */	        
    private function setHost($host)
	{
        if (is_string($host))

            $this->host = $host;
        else

            throw new Exception("Data type error: a host name has to be a string.");
    }    

    /**
    * Set the connection password
    * 
    * @param 	string	$password     password
    * @return 	nothing
    */	        
    private function setPassword($password)
	{
        if (is_string($password))

            $this->password = $password;
        else

            throw new Exception("Data type error: a password has to be a string.");
    }
    
    /**
    * Set the connection username
    * 
    * @param 	string	$username    user name
    * @return 	nothing
    */	        
    private function setUsername($username)
	{
        if (is_string($username))

            $this->username = $username;
        else

            throw new Exception("Data type error: a username has to be a string.");
    }    
    
    /**
    * Execute a SQL query through existing connection
    * 
    * @param	string	$query				query
    * @param	boolean	$expected_data		data expectation flag
    * @param	boolean	$pdo				PDO usage indicator
    * @return 	array 	result
    */	
    public function executeQuery($query, $expected_data = FALSE, $pdo = FALSE)
	{
		if (
			!$pdo &&
			(
				isset(self::$properties) &&
				(
					!isset(self::$properties) ||
					!is_object(self::$properties) ||
					!isset(self::$properties->{PROPERTY_PDO}) ||
					!self::$properties->{PROPERTY_PDO}
				) &&
				isset(self::$properties->{PROPERTY_LINK}) &&
				is_object(self::$properties->{PROPERTY_LINK}) &&
				get_class(self::$properties->{PROPERTY_LINK}) === CLASS_MYSQLI
			)
		)
		{
			$mysqli = &self::$properties->{PROPERTY_LINK};

			// execute the query
			$result = $mysqli->query($query);
	
			// declare an empty array
			$rows = array();
	
			// check the result		
			if (!$result)
	
				throw new Exception("Invalid query: ".$mysqli->error);
	
			if ($expected_data)
	        {
				while ($row = $result->fetch_object())
	
			        $rows[] = $row;

                $result->free_result();
            }
			else 
	
				$rows['result'] = $result;
	
			return $rows;
		}
		else

			throw new Exception(EXCEPTION_DEVELOPMENT_PDO_DISCONTINUED);
    }

	/**
	* Get magically a property featuring an entity
	*
	* @param	string	$name	name
	* @return	mixed	property value
	*/
	public function &__get($name)
	{
		$property = &$this->getProperty($name);
		
		return $property;
	}

	/**
	* Check if a property is set
	*
	* @param	string	$name	name
	* @return	nothing
	*/
	public function __isset($name)
	{
		$isset = FALSE;

		if (isset($this->$name) || $this->__get($name) !== NULL)
		
			$isset = TRUE;

		return $isset;
	}

	/**
	* Set magically a property featuring an entity
	*
	* @param	string	$name	name
	* @param	string	$value	value
	* @return	nothing
	*/
	public function __set($name, $value)
	{
		return self::setProperty($name, $value);
	}

	/**
	* Unset magically a property featuring an entity
	*
	* @param	string	$name	name
	* @return	nothing
	*/
	public function __unset($name)
	{
		$properties = self::getProperties();

		if (isset($properties->$name))
		
			unset($properties->$name);
	}
	
	/**
	* Get the properties featuring an entity
	*
	* @return	mixed	properties
	*/
	public static function &getProperties()
	{
		return self::$properties;
	}

	/**
	* Get the value of a property featuring an entity
	*
	* @param	string	$name	name
	* @return	mixed	property value
	*/
	public static function &getProperty($name)
	{
		if ( ! isset( self::$properties->$name ) )

			self::$properties->$name = NULL;

		return self::$properties->$name;
	}

	/**
	* Build a new instance of MySQLi object
	* 
	* @param	string	$host		hostname
	* @param	string	$database	database
	* @param	string	$user_name	user name
	* @param	string	$password	password
	* @return	object	mysqli instance
	*/
	public function buildInstance(
		$host = NULL,
		$database = NULL,
		$user_name = NULL,
		$password = NULL
	)
	{
		if (is_null($database) && !empty($this->database))
		
			$database = $this->database;
		else
		
			throw new Exception(EXCEPTION_DATABASE_CONNECTION_WRONG_DATABASE_NAME);

		if (is_null($host) && !empty($this->host))

			$host = $this->host;
		else
			
			$host = DB_DEFAULT_HOST;

		if (is_null($user_name) && !empty($this->username))
		
			$user_name = $this->username;
		else

			throw new Exception(EXCEPTION_DATABASE_CONNECTION_WRONG_USER_NAME);

		if (is_null($password) && !empty($this->password))
		
			$password = $this->password;

		return self::buildMysqliInstance($host, $database, $user_name, $password);
	}

	/**
	* Build a new instance of MySQLi object
	* 
	* @param	string	$host		hostname
	* @param	string	$database	database
	* @param	string	$user_name	user name
	* @param	string	$password	password
	* @return	object	mysqli instance
	*/
	public static function buildMysqliInstance(
		$host = NULL,
		$database = NULL,
		$user_name = NULL,
		$password = NULL
	)
	{
		$class_dumper = CLASS_DUMPER;

		$class_mysqli = CLASS_MYSQLI;

		//$set_names_ut8 = 'SET NAMES UTF8';

		if ( is_null( $database ) )
		
			throw new Exception(EXCEPTION_DATABASE_CONNECTION_WRONG_DATABASE_NAME);

		if ( is_null( $host ) )
			
			$host = DB_DEFAULT_HOST;

		if ( is_null( $user_name ) )

			throw new Exception(EXCEPTION_DATABASE_CONNECTION_WRONG_USER_NAME);

		$mysqli = new $class_mysqli();

		$mysqli->init();

		//$mysqli->options(MYSQLI_INIT_COMMAND, $set_names_ut8);

		// check the connection
		if ( ! $mysqli->real_connect( $host, $user_name, $password ) )

			throw new Exception( 'Could not connect: '.$mysqli->connect_error );

		else if ( ! $mysqli->select_db($database) )

			throw new Exception(sprintf('Cannot use %s [%s]', $database, $mysqli->error));
		else

			return $mysqli;
	}

    /**
    * Get the display mode 
    *
    * @param	boolean	$pdo	PDO usage indicator
    * @return 	integer
    */	        
    public static function getConnection( $pdo = FALSE )
	{
		if ( ! isset( self::$properties->{PROPERTY_LINK} ) )
		{
			// construct a new instance of the Database_Connection object
			$arguments = ! $pdo ? array() : array( PROPERTY_PDO => $pdo );
			new self( $arguments );
		}

	    return self::$properties->{PROPERTY_LINK};
    }

	/**
	* Set the values of properties
	*
	* @param	mixed	$properties		properties values
	* @return	nothing
	*/	
	public static function setProperties($properties)
	{	
		if (
			(is_array($properties) &&  count($properties) != 0) ||
			(is_object($properties) && count(get_object_vars($properties) != 0))
		)

			foreach ($properties as $name => $value)

				self::setProperty($name, $value);
	}

	/**
	* Set the value of a property featuring an entity
	*
	* @param	string	$name	name
	* @param	mixed	$value	value
	* @return	nothing
	*/
	public static function setProperty($name, $value)
	{
		$_value = &self::getProperty($name);

		$_value = $value;
	}
}

/**
*************
* Changes log
*
*************
* 2011 10 22
*************
* 
* project :: wtw ::
*
* deployment :: unit testing ::
*
* Revise instantiation
*
* methods affected ::
*
*	DATABASE_CONNECTION :: getConnection
*
* (branch 0.1 :: revision :: 731)
* (branch 0.1 :: revision :: 400)
*
*/
