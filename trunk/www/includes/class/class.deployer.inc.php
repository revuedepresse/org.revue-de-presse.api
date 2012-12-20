<?php

/**
* Deployer class
*
* Class for deployment handling
* @package  sefi
*/
class Deployer extends I18n
{
	/**
	* Initialize a deployment
	*
	* @return	nothing
	*/	
	public static function initialize()
	{
		$callback_parameters = NULL;

		$condition_deployment_first_level  =
			isset( $_SERVER['SERVER_NAME'] ) &&
			in_array(
				$_SERVER['SERVER_NAME'],
				array(
					'light.dev',
					'snaps.dev',
					'## FILL HOSTNAME ##',
                    '## FILL HOSTNAME ##',
                    '## FILL HOSTNAME ##',
					'## FILL HOSTNAME ##',
					'www.## FILL HOSTNAME ##'
				)
			) || (
				! isset( $_SERVER['SERVER_NAME'] ) &&
				isset( $_SERVER['SCRIPT_NAME'] ) &&
				preg_match('/tifa\//', $_SERVER['SCRIPT_NAME'])
			) || (
				! isset( $_SERVER['SERVER_NAME'] ) &&
				isset( $_SERVER['SCRIPT_NAME'] ) &&
				preg_match('/weaving-the-web\.org/', $_SERVER['SCRIPT_NAME'])
			) || (
				// on remote adama server
				isset( $_SERVER['DOCUMENT_ROOT'] ) &&
				$_SERVER['DOCUMENT_ROOT'] == '/var/www/## FILL DOCUMENT ROOT ##'
			) || (
				// on local adama server
				isset( $_SERVER['DOCUMENT_ROOT'] ) &&
				$_SERVER['DOCUMENT_ROOT'] == '## FILL ABSOLUTE PATH ##''
			)
		;

		if ( $condition_deployment_first_level && defined( 'DEPLOYMENT_MODE' ) )

			define( 'CURRENT_DEPLOYMENT_STAGE', DEPLOYMENT_MODE );

		else if ( $condition_deployment_first_level )

			define( 'CURRENT_DEPLOYMENT_STAGE', 0 );

		// preventing this case for compability issues encountered for other
		// application hosted on the same server
		else if (
			! isset( $_SERVER['SERVER_NAME'] ) &&
			! isset( $_SERVER['UNIT_TESTING'] ) &&
			isset( $_SERVER['argv'] )
			
			// forbidden deployment mode
			&& 0 
		)
		{
			$_SERVER['UNIT_TESTING'] = TRUE;	
			$_SERVER['HTTP_HOST'] = 'localhost';
			$_SERVER['REQUEST_URI'] = '/';	
			$_SERVER['SERVER_NAME'] = 'dummy.server.name';
			$_SERVER['SERVER_PORT'] = 80;
		}

		if (
			isset( $_SERVER['UNIT_TESTING'] ) ||
			defined( 'CURRENT_DEPLOYMENT_STAGE' )
		)
		{
			ini_set( 'html_errors', 1 );

			$class_application = CLASS_APPLICATION;

			if ( defined( 'CURRENT_DEPLOYMENT_STAGE' ) )
			{
				$class_exception_handler = CLASS_EXCEPTION_HANDLER;
				$class_exception_handler::deploy();
	
				header( base64_decode( 'WC1Qb3dlcmVkLUJ5OiBXVEY/' ) );
			}

			if ( defined( 'UNIT_TESTING_ASSERTIVE_MODE_STATUS' ) )
			{
				$assertive_options = array(
					UNIT_TESTING_ASSERTIVE_MODE_ENABLED => array(
						ASSERT_ACTIVE => TRUE,
						ASSERT_BAIL => FALSE,
						ASSERT_QUIET_EVAL => FALSE,
						ASSERT_WARNING => FALSE
					),
					UNIT_TESTING_ASSERTIVE_MODE_DISABLED => array(
						ASSERT_ACTIVE => FALSE,
						ASSERT_BAIL => FALSE,
						ASSERT_QUIET_EVAL => TRUE,
						ASSERT_WARNING => FALSE
					)					
				);	

				foreach (
					$assertive_options[UNIT_TESTING_ASSERTIVE_MODE_STATUS] as
						$name => $value
				)
					assert_options( $name, $value);
			}

			$callback_parameters = array(
				'assert' => UNIT_TESTING_ASSERTIVE_MODE_STATUS,
				'class_application' => $class_application,
				'symbols' => array(),
				'verbose_mode' => ! DEPLOYMENT_MODE
			);
			
			// protecting the routines and unit testing directories
			// by forcing an administator to be logged in
			if (
				isset( $_SERVER['REQUEST_URI'] ) &&
				(
					(
						(
							strpos(
								$_SERVER['REQUEST_URI'],
								PREFIX_ROOT.DIR_API
							) !== FALSE
						) || (
							strpos(
								$_SERVER['REQUEST_URI'],
								PREFIX_ROOT.DIR_ROUTINES
							) !== FALSE
						) || (
							strpos(
								$_SERVER['REQUEST_URI'],
								PREFIX_ROOT.DIR_OBSERVATORY
							) !== FALSE
						) || (
							strpos(
								$_SERVER['REQUEST_URI'],
								PREFIX_ROOT.DIR_TEMPLATES
							) !== FALSE
						) || (
							strpos(
								$_SERVER['REQUEST_URI'],
								PREFIX_ROOT.DIR_TEMPLATES_C
							) !== FALSE
						) || (
							(
								strpos(
									$_SERVER['REQUEST_URI'],
									PREFIX_ROOT.DIR_UNIT_TESTING
								) !== FALSE
							) && (
								$_SERVER['REQUEST_URI'] !==
									URI_UNIT_TESTING_DESTROY_SESSION
							)
						)
					) &&
					! in_array(
						$_SERVER['SCRIPT_NAME'],
						array(
							SCRIPT_API_ADOPTEUNMEC_SAVE_STORE,
							SCRIPT_API_FLICKR_AUTHENTICATE,
							SCRIPT_API_TWITTER_CHECK_RATE_LIMIT,
                            SCRIPT_API_TWITTER_DISPLAY_WALL,
							SCRIPT_API_TWITTER_FORGET_ACCESS_TOKEN,
							SCRIPT_API_TWITTER_REQUEST_ACCESS_TOKEN,
							SCRIPT_API_TWITTER_RESTORE_ACCESS,
						)
					) && (
						FALSE === strpos(
							$_SERVER['REQUEST_URI'], DIR_LIBRARY . '/' . DIR_SYMFONY
						)
                    ) && (
                        ! in_array($_SERVER['SERVER_NAME'], array(
                            '## FILL HOSTNAME ##', '## FILL HOSTNAME ##'
                        ))
                    )
				)
			)
			{
				$class_user_handler = CLASS_USER_HANDLER;
				
				if ( ! $class_user_handler::loggedIn( TRUE ) )

					$class_application::jumpTo( PREFIX_ROOT, 301 );
			}
		}

		return $callback_parameters;
	}

	/**
	* Import a MySQL database context
	*
	* @param	integer		$storage_model	storage model
	* @param	boolean		$verbose		verbose mode
	* @return	nothing
	*/
	public static function importContextDatabaseMySQL(
		$verbose = FALSE,
		$storage_model = STORE_DATABASE
	)
	{
		global $class_application, $verbose_mode;

		$class_entity = $class_application::getEntityClass();

		$class_db = $class_application::getDbClass();

		$class_dumper = $class_application::getDumperClass();

		// get the user defined constants
		$constants = get_defined_constants(true);

		$column_prefixes = 
		$tables = 
		$tables_aliases = array();
		
		while (list($name, $value) = each($constants['user']))
		{
			if (FALSE !== ($pos = strpos($name, strtoupper(PREFIX_TABLE))) && !$pos)
			{
				if (FALSE !== strpos($name, strtoupper(PREFIX_ALIAS)))
	
					$tables_aliases[$name] = $value;
				else 
	
					$tables[$name] = $value;
			}
			else if (
				FALSE !== strpos(
					$name,
					strtoupper( PREFIX_PREFIX.PREFIX_TABLE.PREFIX_COLUMN )
				)
			)

				$column_prefixes[$name] = $value;
		}
		
		$keys_column_prefixes = array_keys($column_prefixes);

		$keys_tables_aliases = array_keys($tables_aliases);
		
		$class_dumper::log(
			__METHOD__,
			array(
				$tables,
				$tables_aliases,
				$column_prefixes
			),
			$verbose_mode
		);

		while ( list( $index, $table ) = each( $tables ) )
		{
			$column_prefix =
			$table_alias = '';

			$entity = substr($table, strlen(PREFIX_TABLE) + 2);

			$key_column_prefix = array_search(
				strtoupper(
					PREFIX_PREFIX.
						PREFIX_TABLE.
							PREFIX_COLUMN.
								$entity
				),
				$keys_column_prefixes,
				TRUE
			);

			$key_table_alias = array_search(
				strtoupper(
					PREFIX_TABLE.
						PREFIX_ALIAS.
							$entity
				),
				$keys_tables_aliases,
				TRUE
			);

			if ($key_column_prefix !== FALSE)
			
				$column_prefix = $column_prefixes[$keys_column_prefixes[$key_column_prefix]];
		
			if ($key_table_alias !== FALSE)
			
				$table_alias = $tables_aliases[$keys_tables_aliases[$key_table_alias]];

			$class_entity::add(
				array(
					PROPERTY_COLUMN_PREFIX => $column_prefix,
					PROPERTY_NAME => $entity,
					PROPERTY_TABLE_ALIAS => $table_alias,
					PROPERTY_TABLE => $table
				),
				$storage_model,
				$verbose_mode
			);
		}	
	}

	/**
	* Import entity types
	*
	* @param	boolean		$verbose		verbose mode
	* @param	integer		$storage_model	storage model
	* @return	nothing
	*/
	public static function importContextEntityTypes(
		$verbose = FALSE,
		$storage_model = STORE_DATABASE
	)
	{
		global $class_application, $verbose_mode;

		$class_db = $class_application::getDbClass();

		$class_dumper = $class_application::getDumperClass();

		$class_entity = $class_application::getEntityClass();

		$physical_declarations = file_get_contents(
			dirname(__FILE__).'/../'.
				REQUIREMENT_CONSTANTS_DECLARATIONS_ENTITIES_TYPES
		);

		// get the user defined constants
		$constants = get_defined_constants(true);

		$entity_types =
		$discrepancies = array();

		while (list($name, $value) = each($constants['user']))
		{
			if (FALSE !== ($pos = strpos($name, strtoupper('_'.PREFIX_TYPE))))
			{
				// check if the current constant has been defined from the dedicated configuration file
				if (FALSE !== strpos($physical_declarations, $name))
				{
					$entity = strtolower(substr($name, 0, $pos));

					if (!isset($entity_types[$entity]))
					
						$entity_types[$entity] = array();

					$type = substr($name, $pos + strlen(PREFIX_TYPE) + 1);

					$type_trimmed = rtrim(substr($name, $pos + strlen(PREFIX_TYPE) + 1), '*');

					$entity_types[$entity][strtolower($type_trimmed)] =
						$value.($type != $type_trimmed ? '*' : '');
				}
				else

					$discrepancies[$name] = $value; 
			}
		}

		$class_dumper::log(
			__METHOD__,
			array(
				$entity_types,
				$discrepancies	  
			),
			$verbose_mode
		);

		while (list($name, $types) = each($entity_types))
		{
			$index = 0;

			list(, $type) = each($types);
			reset($types);

			// use pre-existing order if non-numeric values are found
			if (is_numeric($type))

				asort($types, SORT_NUMERIC);

			while (list($type, $value) = each($types))
			{
				$class_entity::add(
					array(
						PROPERTY_DEFAULT => ( rtrim($value, '*') != $value ? 1 : 'NULL' ),
						PROPERTY_INDEX => $index,
						PROPERTY_NAME => $name,
						PROPERTY_TYPE => $type,
						PROPERTY_VALUE => rtrim($value, '*')
					),
					$storage_model,
					$verbose_mode
				);

				$index++;
			}
		}
	}

	/**
	* Check if the preproduction environment is running
	*
	* @return	boolean	indicator
	*/	
	public static function preproductionEnvironment()
	{
		return
				isset( $_SERVER['REQUEST_URI'] )
			?
				preg_match(
					'/http:\/\/[^\/]+\.[^\/]+dev\//',
					$_SERVER['REQUEST_URI']
				)
			:
				TRUE
		;
	}

	/**
	* Check if the unit testing environment is running
	*
	* @return	boolean	indicator
	*/	
	public static function unitTestingEnvironment()
	{
		return isset($_SERVER['REQUEST_URI']);
	}

	/**
	* Check if the unit testing mode is enabled
	*
	* @return	boolean	indicator
	*/
	public static function unitTestingMode()
	{
		return UNIT_TESTING_MODE_STATUS;
	}
}

/**
*************
* Changes log
*
*************
* 2011 03 26
*************
* 
* Revise bootstrap
*
* method affected ::
*
* 	DEPLOYER :: initialize
* 
* (branch 0.1 :: revision 636)
* (trunk :: revision :: 204)
*
*************
* 2011 09 27
*************
*
* project :: wtw ::
*
* development :: deployment ::
* 
* Implement deployment modes
*
* method affected ::
*
* 	DEPLOYER :: initialize
* 
* (branch 0.2 :: revision 327)
*
*************
* 2011 10 06
*************
*
* Revise command-line mode deployment
*
* methods affected ::
*
* 	DEPLOYER::initialize
*
* (branch 0.1 :: revision :: 683)
* (branch 0.2 :: revision :: 381)
*
*/
