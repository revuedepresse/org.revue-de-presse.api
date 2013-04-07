<?php

/**
* Lsql class
*
* Class for querying advanced database transactions
* @package  sefi
*/
class Lsql extends DB
{
	private static $errors = array();
	private static $queries = array();
	private static $insert_identifiers = array();

	/**
	* Check the existence of a record 
	*
	* @param	string		$table				containing a table name
	* @param	string		$where_clause		containing a where clause
	* @param	object		$link				representing a SQL link
	* @param	boolean		$debugging			indicating if the debugging mode is enabled
	* @param	boolean		$exit				indicating if the script should terminate before the query execution
	* @param	string		$class_name			containing a class name
	* @param	string		$method_name		containing a method name
	* @param	boolean		$connection_issues	indicating if there are connection issues
	* @param	boolean		$pdo				PDO usage indicator
	* @return	array	containing queries
	*/
	public static function check_existence(
		$table,
		$where_clause,
		$link = FALSE,
		$debugging = FALSE,
		$exit = FALSE,
		$class_name = NULL,
		$method_name = NULL,
		$connection_issues = NULL,
		$pdo = FALSE
	)
	{
		$check_query = "
			SELECT
				*
			FROM
				$table
			WHERE
				$where_clause
		";

		// execute a checking query
		$results = self::query(
			$check_query,
			$link,
			$debugging,
			$exit,
			$class_name,
			$method_name,
			$connection_issues,
			$pdo
		);

		// check the results
		if (is_object($results) && $results->num_rows)

			return $results;
		else

			return FALSE;
	}

	/**
	* Open a MySQL connection
	*
	* @param	string 	$hostname	hostname
	* @param	string	$user_name	user name
	* @param	string	$password	password
	* @param	string	$database	database
	* @param	boolean	$pdo		PDO usage indicator
    * @param 	mixed	$checksum	checksum
	* @return	mixed
	*/
	public static function connect(
		$hostname = NULL,
		$database = NULL,
		$user_name = NULL,
		$password = NULL,
		$pdo = FALSE,
		$checksum = NULL
	)
	{
		return self::parent($hostname, $database, $user_name, $password, $pdo, $checksum);
	}

	/**
	* Get a link
	*
	* @param	boolean		$pdo	PDO usage indicator
	* @return	nothing
	*/
	public static function getLink($pdo = FALSE)
	{
		if (!$pdo)
		{
			// get the credentials
			$database = DB_SEFI;
			$hostname = DB_HOST;
			$password = DB_PASSWORD;
			$user_name = DB_USER_NAME;
	
			// check the credentials
			if (
				empty($database) ||
				empty($hostname) ||
				empty($user_name) ||
				empty($password) 
			)
	
				// throw an exception
				throw new Exception(EXCEPTION_INVALID_CREDENTIALS);
	
			// get the current MySQL connection
			$link = parent::getLink();
	
			// check the current MySQL connection
			if ($link === false)
	
				// throw an exception
				throw new Exception(EXCEPTION_CONNECTION_FAILURE);
	
			// return the current MySQL connection
			return $link;
		}
		else

			throw new Exception(EXCEPTION_DEVELOPMENT_PDO_DISCONTINUED);
	}

	/**
	* Flush the query store
	*
	* @return	nothing
	*/
	public static function flushQueryStore()
	{
		self::$queries = array();
	}

	/**
	* Get latest insert identifiers
	*
	* @return	array	containing queries
	*/
	public static function getInsertIds()
	{
		return self::$insert_identifiers;
	}
	
	/**
	* Get latest executed queries
	*
	* @return	array	containing queries
	*/
	public static function getLatestQueries()
	{
		return self::$queries;
	}

	/**
	* Combine string replacements operating on queries to be executed and these queries execution
	*
	* @param	mixed		$regexp		containing regular expressions
	* @param	mixed		$replace	containing replacement values
	* @param	string		$query		containing a SQL query model
	* @param	boolean		$index		enabling an indexing mode on returned result
	* @param	boolean		$debug		enabling a debugging mode
	* @param	object		$link		representing a SQL link
	* @param	boolean		$pdo		PDO usage indicator
	* @return	mixed	
	*/			
	public static function preg_query(
		$regexp,
		$replace,
		$query,
		$index = false,
		$debug = false,
		$link = false,
		$pdo = FALSE		
	)
	{
		$results = array();

		if (
			!$pdo &&
			(
				!isset(self::$properties) ||
				!is_object(self::$properties) ||
				!isset(self::$properties->{PROPERTY_PDO}) ||
				!self::$properties->{PROPERTY_PDO}
			)
		)
		{
			if (!is_string($query))
				throw new Exception('Wrong data type: a SQL query has to be passed as a query');
	
			if (is_array($regexp) && is_array($replace))
			{
				if (count($regexp) == 0 || count($replace) == 0)
					throw new Exception('Wrong data type: regular expressions and strings have to be non-empty');
	
				if (count($regexp) == count($replace))
	
					while (list($regexp_index) = each($regexp))
					{
						if ($index)
							self::$queries[$replace[$regexp_index]] = preg_replace(
								$regexp[$regexp_index],
								$replace[$regexp_index],
								$query
							);
						else
							self::$queries[] = preg_replace(
								$regexp[$regexp_index],
								$replace[$regexp_index],
								$query
							);
					}
		
				else if (count($regexp) == 1 && count($replace) > 1)
		
					while (list($replace_index) = each($replace))
					{
						if ($index)
							self::$queries[$replace[$replace_index]] = preg_replace(
								$regexp[0],
								$replace[$replace_index],
								$query
							);
						else
							self::$queries[] = preg_replace(
								$regexp[0],
								$replace[$replace_index],
								$query
							);						
					}
			}
			else if (is_string($regexp) && is_array($replace) && count($replace) > 0)
			{
				while (list($replace_index) = each($replace))
				{
					if ($index)
						self::$queries[$replace[$replace_index]] = preg_replace(
							$regexp,
							$replace[$replace_index],
							$query
						);
					else
						self::$queries[] = preg_replace(
							$regexp,
							$replace[$replace_index],
							$query
						);					
				}			
			}
			else if (is_string($regexp) && (is_string($replace) || is_integer($replace)))
			{
				self::$queries[] = preg_replace(
					$regexp,
					$replace,
					$query
				);
			}
			else if (
				(is_array($regexp) && !is_array($replace) || !is_array($regexp) && is_array($replace)) ||
				(!is_string($regexp) && (is_string($replace) || is_integer($replace))) ||
				is_string($regexp) && !is_string($replace) && !is_integer($replace)
			)
			{
				if (is_array($regexp) && !is_array($replace) || !is_array($regexp) && is_array($replace))
					throw new Exception('Wrong data type: both regular expressions and replacement strings have to be passed as arrays (or strings)');
				else if (
					is_string($regexp) && !is_string($replace) && !is_integer($replace) ||
					!is_string($regexp) && (is_string($replace) || is_integer($replace))
				)
					throw new Exception('Wrong data type: both regular expressions and replacement strings have to be passed as strings (or arrays)');
			}
	
			while (list($query_index) = each(self::$queries))
			{
				if (preg_match("/INSERT/i", $query))
				{
					if ($index)
						self::$insert_identifiers[$query_index] = self::query(self::$queries[$query_index]);				
					else 
						self::$insert_identifiers[] = self::query(self::$queries[$query_index]);
				}
				else
				{
					if ($index)
						$results[$query_index] = self::query(self::$queries[$query_index]);
					else
						$results[] = self::query(self::$queries[$query_index]);
				}
	
				if ($debug && mysqli_error(self::getLink())) 

					$errors[] = mysqli_error(self::getLink());
			}
	
			if ($debug)
			{
				$firephp = FirePHP::getInstance(true);
				$firephp->log(self::getLatestQueries());
			}
	
			self::flushQueryStore();
		}
		else

			throw new Exception(EXCEPTION_DEVELOPMENT_PDO_DISCONTINUED);
		
		return $results;
	}

	/**
	* Execute a query
	*
	* @param	string		$query				containing a query
	* @param	object		$link				representing a SQL link
	* @param	boolean		$debugging			indicating if the debugging mode is enabled
	* @param	mixed		$exit				indicating if the script should terminate before the query execution
	* @param	string		$class_name			containing a class name
	* @param	string		$method_name		containing a method name
	* @param	boolean		$connection_issues	indicating if there are connection issues
	* @param	boolean		$pdo				PDO usage indicator
	* @param	mixed		$checksum			checksum
	* @return	array		containing queries
	*/
	public static function query(
		$query,
		$link = FALSE,
		$debugging = FALSE,
		$exit = FALSE,
		$class_name = NULL,
		$method_name = NULL,
		$connection_issues = FALSE,
		$pdo = FALSE,
		$checksum = NULL
	)
	{
		$class_dumper = CLASS_DUMPER;

		// declare the debugging parameters
		$debugging_parameters = array();

		// declare the default result
		$result = NULL;

		// check if a class or a method name have been passed as argument
		if (
			$debugging &&
			(
				!empty($class_name) ||
				!empty($method_name)
			)
		)
		{
			// check if the class name as been passed as an argument
			if (!empty($class_name))

				// append the class name to the debugging parameters
				$debugging_parameters += array(
					'calling class: '.$class_name
				);

			// check if the method name as been passed as an argument
			if (!empty($method_name))
			{
				// append the method name to the debugging parameters
				$debugging_parameters = array_merge(
					$debugging_parameters, 
					array('calling method: '.$method_name)
				);
			}
		}

		// check if the debugging mode is enabled
		if ($debugging)

			// append the query to the debugging parameters
			$debugging_parameters = array_merge(
				$debugging_parameters,
				array(
					'query:',
					$query
				)
			);

		// check if there are connection issues
		if ($connection_issues)

			// append the connection issues to the debugging parameters
			$debugging_parameters = array_merge(
				$debugging_parameters,
				array(
					'connection issues',
					$link
				)
			);

		// check if the debugging mode is enabled
		if ($debugging || $connection_issues)

			// check the query
			$class_dumper::log(
				__METHOD__,
				$debugging_parameters,
				TRUE,
				$exit
			);

		// execute the query
		$result = parent::query($query, $pdo, $checksum);

		// check the result 
		if (
			is_integer($result) ||			
			is_object($result) 
		)
		
			// return the query result
			return $result;
		
		else
		{
			$result = new stdClass();
			$result->num_rows = 0;

			// return an instance of the standard class
			return $result;
		}	
	}
}
?>