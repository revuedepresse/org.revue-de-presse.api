<?php

/**
* Serializer class
* 
* Class to construct a serializer
* @package sefi
*/
class Serializer extends Executor
{
	/**
	* Execute a serialization plan
	*
	* @param	mixed	$details		details describing the plan
	* @param	mixed	$callback		callback
	* @param	string	$storage_model	storage model
	* @param	boolean	$verbose		verbose mode
	* @param	mixed	$informant		informant
	* @return	mixed	last insert id
	*/
	public static function executePlan(
		$details,
		$callback = NULL,
		$storage_model = STORE_DATABASE,
		$verbose = FALSE,
		$informant = NULL
	)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$last_insert_id = NULL;

		$callback_type_operation_running = self::getEntityTypeValue(
			array(
				PROPERTY_NAME => OPERATION_STATUS_RUNNING,
				PROPERTY_ENTITY => ENTITY_CALLBACK
			)
		);

		$exception = EXCEPTION_INVALID_ARGUMENT;

		if (
			is_array($details) && count($details) && 
			isset($details[0]) && is_array($details[0])
		)
		{
			$entities = $details[0];

			unset($details[0]);

			if (
				is_null($callback) ||
				is_array($callback) && !count($callback)
			)

				$callback = $details;

			else if ( is_array($callback) && count($callback) )

				array_merge($callback, $details);

			else

				throw new Exception(EXCEPTION_INVALID_ARGUMENT);

			while (list($index, $instance) = each($entities))

				if (is_null($details[$index]))
				{
					list($entity, $properties) = each($instance);

					$callback[$callback_type_operation_running] = $index;

					$entity_instance = new $entity($properties);

					if (
						(
							is_array($properties) &&
							count($properties) != 0
						) ||
						(
							is_object($properties) &&
							count(get_object_vars($properties) != 0)
						)
					)

						$last_insert_id = self::saveInstance(
							$entity,
							$entity_instance,
							$callback,
							$storage_model,
							$verbose
						);

					if (isset($entity_instance->{PROPERTY_ID}))

						$callback[$index] = $entity_instance->{PROPERTY_ID};

					$class_dumper::log(
						__METHOD__,
						array(
							sprintf(LOG_MESSAGE_INSTANCE_PROPERTIES_AFTER_SERIALIZATION_ATTEMPT, $entity),
							$entity_instance
						),
						$verbose
					);
				}
		}
		else

			throw new Exception( $exception.': '.EXCEPTION_EXPECTATION_ARRAY );			

		return $last_insert_id;
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

	/**
	* Import a language item
	*
	* @param	mixed	$properties	properties
	* @param	boolean	$update		update flag
	* @return	nothing
	*/
	public static function importLanguageItem($properties, $update = FALSE)
	{
		global $class_application, $verbose_mode;

		$class_db = $class_application::getDbClass();

		$class_event = $class_application::getEventClass();

		$class_event_manager = $class_application::getEventManagerClass();

		if ( ! empty($properties[PROPERTY_LANGUAGE] ) )

			$language_code = $class_application::escape($properties[PROPERTY_LANGUAGE]);
		else
		
			$language_code = I18N_DEFAULT_LANGUAGE;

		if (!empty($properties[PROPERTY_NAME]))

			$name = $class_application::escape($properties[PROPERTY_NAME]);
		else
		
			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		if (!empty($properties[PROPERTY_NAMESPACE]))

			$namespace = $properties[PROPERTY_NAMESPACE];
		else
		
			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		if (!empty($properties[PROPERTY_VALUE]))

			$value = $class_application::escape($properties[PROPERTY_VALUE]);
		else
		
			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		$insert_language_model = '
			INSERT INTO '.TABLE_LANGUAGE.'
			SET
				lang_code = TRIM(LOWER("{code}"))
		';

		$insert_language_item_model = '
			INSERT INTO '.TABLE_LANGUAGE_ITEM.'
			SET
				nsp_id = "{namespace}",
				lang_id = {language},
				lgi_name = TRIM(LOWER("{name}")),
				lgi_value = TRIM("{value}")
		';

		$insert_namespace_model = '
			INSERT INTO '.TABLE_NAMESPACE.'
			SET
				nsp_name = TRIM(LOWER("{namespace}")
		';
		
		$select_language_model = '
			SELECT
				lang.lang_id id
			FROM
				'.TABLE_LANGUAGE.' lang
			WHERE
				lang_code = TRIM(LOWER("{code}"))
		';

		$select_language_item_model = '
			SELECT
				lgi.lgi_id id
			FROM
				'.TABLE_LANGUAGE_ITEM.' lgi
			WHERE
				lgi.lang_id = {language} AND
				lgi.lgi_name = TRIM(LOWER("{name}")) AND
				lgi.nsp_id = {namespace}
		';

		$select_namespace_model = '
			SELECT
				nsp.nsp_id id
			FROM
				'.TABLE_NAMESPACE.' nsp
			WHERE
				nsp_name = TRIM(LOWER("{namespace}"))
		';

		$update_language_item_model = '
			UPDATE '.TABLE_LANGUAGE_ITEM.'
			SET
				lgi_value = TRIM("{value}")
			WHERE
				lgi_id  = {id}
		';

		$select_language = str_replace('{code}', $language_code, $select_language_model);

		$select_namespace = str_replace('{namespace}', $namespace, $select_namespace_model);

		$results_language = $class_db::query($select_language);

		$results_namespace = $class_db::query($select_namespace);

		if ($results_language->num_rows)

			$language_id = $results_language->fetch_object()->id;
		else
		{
			$insert_language = str_replace('{code}', $language_code, $insert_language_model);

			$language_id = $class_db::query($insert_language);
		}

		if ($results_namespace->num_rows)
		
			$namespace_id = $results_namespace->fetch_object()->id;
		else
		{
			$insert_namespace = str_replace('{namespace}', $namespace, $insert_namespace_model);

			$namespace_id = $class_db::query($insert_namespace);
		}

		$select_language_item = str_replace(
			array(
				'{name}',
				'{language}',
				'{namespace}'
			),
			array(
				$name,
				$language_id,
				$namespace_id
			),
			$select_language_item_model
		);
		
		$results_language_item = $class_db::query($select_language_item);

		if (
			$results_language_item->num_rows &&
			$language_item_id = $results_language_item->fetch_object()->id
		)
		{
			if (!$update)

				$class_event::logEvent(
					array(
						PROPERTY_DESCRIPTION => sprintf(EVENT_DESCRIPTION_LANGUAGE_ITEM_ALREADY_EXISTS, $name),
						PROPERTY_TYPE => EVENT_TYPE_LANGUAGE_ITEM_IMPORT						
					)
				);
			else
			{
				$update_language_item = str_replace(
					array(
						'{id}',
						'{value}'
					),
					array(
						$language_item_id,
						$value
					),
					$update_language_item_model
				);
				
				$class_db::query($update_language_item);
			}
		}
		else
		{
			$insert_language_item = str_replace(
				array(
					'{name}',
					'{namespace}',
					'{language}',
					'{value}'
				),
				array(
					$name,
					$namespace_id,
					$language_id,
					$value
					
				),
				$insert_language_item_model
			);

			$insert_id = $class_db::query($insert_language_item);
		}
	}

	/**
	* Log a message
	*
	* @param	string	$properties		properties
	* @param	integer	$entity_type	type of entity
	* @return	mixed	insertion result
	*/
	public static function log(
		$properties,
		$entity_type = NULL
	)
	{
		$class_db = CLASS_DB;

		$class_entity = CLASS_ENTITY;

		$class_toolbox = CLASS_TOOLBOX;

		if (is_null($entity_type))

			$entity_type = $class_entity::getByName(ENTITY_EVENT)->{PROPERTY_ID};

		if (!empty($properties->{PROPERTY_TYPE}))
		
			$log_type = $properties->{PROPERTY_TYPE};

		if (!empty($properties->{PROPERTY_DESCRIPTION}))

			$log_description = $class_toolbox::escapeString($properties->{PROPERTY_DESCRIPTION});
		else
		
			$log_description = 'NULL';
			
		if (!empty($properties->{PROPERTY_CONTEXT}))

			$log_context = $class_toolbox::escapeString(
				serialize( $properties->{PROPERTY_CONTEXT} )
			);
		else
		
			$log_context = 'NULL';

		if (
			!empty($properties->{PROPERTY_EXCEPTION}) &&
			is_object($properties->{PROPERTY_EXCEPTION}) &&
			(
				(
					get_class( $properties->{PROPERTY_EXCEPTION} ) ==
						CLASS_EXCEPTION
				) ||
				is_subclass_of(
					$properties->{PROPERTY_EXCEPTION},
					CLASS_EXCEPTION
				)
			)
		)
		{
			$message = array(
				'message' => $properties->{PROPERTY_EXCEPTION}->getMessage(),
				'code' => $properties->{PROPERTY_EXCEPTION}->getCode(),
				'file' => $properties->{PROPERTY_EXCEPTION}->getFile(),
				'line' => $properties->{PROPERTY_EXCEPTION}->getLine(),
				'previous' => $properties->{PROPERTY_EXCEPTION}->getPrevious(),
				'traceAsString' => $properties->{PROPERTY_EXCEPTION}->getTraceAsString()
			);

			$log_exception = $class_toolbox::escapeString(serialize($message));
		}
		else

			$log_exception = 'NULL';

		$select_message = '
			SELECT
				log_id id
			FROM
				'.TABLE_LOG.'
			WHERE
				ent_id = '.$entity_type.' AND
				md5(log_context) = md5(TRIM("'.$log_context.'")) AND
				md5(log_message) = md5(TRIM("'.$log_description.'")) AND
				log_type = '.$log_type.'
		';

		$insert_message = '
			INSERT INTO '.TABLE_LOG.'
			SET
				ent_id = '.$entity_type.',
				log_context = TRIM("'.$log_context.'"),
				log_exception = TRIM("'.$log_exception.'"),
				log_type = '.$log_type.',
				log_status = '.LOG_STATUS_UNREAD.',
				log_message = TRIM("'.$log_description.'"),
				log_creation_date = NOW()
		';

		$update_message = '
			UPDATE '.TABLE_LOG.'
			SET
				log_occurrence = log_occurrence + 1,
				log_update_date = NOW()
			WHERE
				log_id = {id}
		';

		$results_select = $class_db::query($select_message);

		if ( $results_select->num_rows )
		
			$class_db::query(
				str_replace(
					'{id}',
					$results_select->fetch_object()->id,
					$update_message
				)
			);
		else 

			return $class_db::query($insert_message);
	}

	/**
	* Log a error message 
	*
	* @param	integer	$entity_id		entity id
	* @param	integer	$entity_type	entity type
	* @param	string	$error_message	error message
	* @return	nothing
	*/
	public static function logErrorMessage($entity_id, $entity_type = ENTITY_EMAIL, $error_message)
	{
		$class_db = CLASS_DB;

		switch ($entity_type)
		{
			case ENTITY_EMAIL:

				$table = TABLE_OUTGOING;
				$column = 'out_error_message';
				$where_clause = 'out_id';

					break;
		}

		$update_entity = '
			UPDATE '.$table.' SET
				'.$column.' = "'.$error_message.'"
			WHERE '.
				$where_clause.' = '.$entity_id
		;

		$class_db::query($update_entity);		
	}

	/**
	* Prepare an event
	*
	* @param	integer	$instance_id	instance identifier
	* @param	string	$class_name		class name
	* @param	string	$action			action
	* @return	nothing
	*/
	public static function prepareEvent($instance_id, $class_name, $action = ACTION_INSTANTIATE_ENTITY)
	{
		$event_properties = array();
 
		if (is_numeric($instance_id))
		{
			$operation_type_instantiate_entity = self::getEntityTypeValue(
				array(
					PROPERTY_NAME => str_replace('.', '_', $action),
					PROPERTY_ENTITY => ENTITY_OPERATION
				)
			);
	
			$event_type_instantiate_entity = self::getEntityTypeId(
				array(
					PROPERTY_NAME => $operation_type_instantiate_entity,
					PROPERTY_ENTITY => ENTITY_EVENT
				)
			);
	
			$event_properties = array(
				// the database class is reponsible for instantiating an object of the argument class
				PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID =>
	
					self::getEntityIdByName(CLASS_DATABASE),
	
				// the argument class is considered as the event source
				PREFIX_TABLE_COLUMN_EVENT.PROPERTY_SOURCE =>
	
					self::getEntityIdByName($class_name),
	
				// the success depends the insertion of a new record
				PREFIX_TABLE_COLUMN_EVENT.PROPERTY_SUCCESS =>
	
					( $instance_id !== FALSE ? 1 : 0 ),
	
				// the instantiated object is considered as the event target
				PREFIX_TABLE_COLUMN_EVENT.PROPERTY_TARGET =>
	
					( $instance_id !== FALSE ? $instance_id : NULL ),
	
				// the event type is of instantiate entity operation
				PREFIX_TABLE_COLUMN_ENTITY_TYPE.PROPERTY_ID => $event_type_instantiate_entity,
			);
		}

		return $event_properties;
	}
	
	/**
	* Record a link
	*
	* @param	array	$properties	properties
	* @return	integer	identifier
	*/
	public static function recordLink($properties)
	{
		$insert_link = '
			INSERT INTO '.TABLE_LINK .' SET
				'.(isset($properties['qry_id']) ? 'qry_id = '.$properties['qry_id'].',' : '').'
				usr_id = '.$properties['usr_id'].',
				lnk_value = "'.$properties['lnk_value'].'",
				lnk_type = '.LINK_TYPE_CONFIRMATION.'
		';

		$identifier = DB::query($insert_link);

		return $identifier;
	}

	/**
	* Serialize data
	*
	* @param	mixed	&$context		context
	* @param	string	$data_type		data type
	* @param	mixed	$callback		callback
	* @param	integer	$storage_model	storage model
	* @param	boolean	$verbose		verbose mode
	* @param	mixed	$informant		informant
	* @return	nothing
	*/
	public static function save(
		&$context,
		$data_type = CLASS_FIELD_HANDLER,
		$callback = NULL,
		$storage_model = STORE_DATABASE,
		$verbose = FALSE,
		$informant = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$return = NULL;

		// switch from the data type
		switch ( $data_type )
		{
			case ENTITY_EMAIL:

				$return = self::saveEmail( $context, $storage_model );

					break;

			case CLASS_FIELD_HANDLER:
				
				$return = self::saveFieldHandler( $context, $storage_model );
				
					break;

			case CLASS_INSIGHT:

				$return = self::saveInsight( $context, $storage_model );

					break;	

			case CLASS_PHOTO:

				$return = self::savePhotograph( $context, $storage_model );

					break;

			case ENTITY_MESSAGE:

					$return = self::saveMessage( $context, $storage_model );

					break;

			default:

				try {
					$return = self::saveEntity(
						$context,
						$callback,
						$storage_model,
						$verbose,
						$informant
					);
				}
				catch (Exception $exception)
				{
					$class_dumper::log(						
						__METHOD__,
						array(
							$exception
						),
						DEBUGGING_DISPLAY_EXCEPTION,
						AFFORDANCE_CATCH_EXCEPTION
					);			
				}
		}

		return $return;
	}

	/**
	*  Save a contact
	*
	* @param	mixed	$properties	properties
	* @return	integer	contact identifier
	*/
	public static function saveContact($properties)
	{
		$contact_identifier = NULL;

		// set the member class name
		$class_member = CLASS_MEMBER;

		if (isset($properties[PROPERTY_STORE]))

			switch ($properties[PROPERTY_STORE])
			{
				case STORE_DATABASE:

					$email = $class_member::getEmail();
					$user_name = $class_member::getUserName();
					
					$columns = $properties[PROPERTY_COLUMN];

					$table = $properties[PROPERTY_TABLE];

					$select_contact = '
						SELECT
							cnt_id
						FROM
							'.$table.'
						WHERE
							cnt_value != "'.$email.'" AND
							cnt_value != "'.$user_name. '" AND
					';

					if (is_array($columns) && count($columns) > 0)
					{
	
						while (list($column_name, $column_value) = each($columns))
				
							$select_contact .=
								$column_name.' = '.
									(!is_numeric($column_value) ? '"'.$column_value.'"' : $column_value).
										(next($columns) ? ' AND'."\n" : '');
			
						reset($columns);
	
						//  check if the contact has not been recorded yet				
						$contact_results = DB::query($select_contact);
						
						if ($contact_results->num_rows)
				
							return $contact_results->fetch_object()->cnt_id;
					}
					else

						throw new Exception(EXCEPTION_INVALID_ARGUMENT);

					$insert_contact = '
						INSERT INTO '.$table.' SET
					';

					if (
						!in_array($user_name, $columns) &&
						!in_array($email, $columns)					
					)
					{

						while (list($column_name, $column_value) = each($columns))
				
							$insert_contact .=
								$column_name.' = '.
									(!is_numeric($column_value) ? '"'.$column_value.'"' : $column_value).
										(next($columns) ? ',' : '');

						// insert a new contact into the database			
						$contact_identifier = DB::query($insert_contact);
					}
			}

		return $contact_identifier;
	}

	/**
	* Save an email
	*
	* @param	mixed	&$context			reference to a context
	* @param	mixed	$storage_model		storage model
	* @return	nothing
	*/
	public static function saveEmail(
		&$context,
		$storage_model = STORE_DATABASE
	)
	{
		global $class_application, $verbose_mode;

		// set the database connector class name
		$class_db = $class_application::getDbClass();;

		if (
			is_object( $context ) &&
			get_class( $context ) == CLASS_ZEND_MAIL
		)
		{
			$insert_email = '
				INSERT INTO '.TABLE_OUTGOING.' SET
					out_value = "'.base64_encode( serialize( $context ) ).'",
					out_status = '.EMAIL_STATUS_UNSENT
			;

			$class_db::query( $insert_email );
		}
	}

	/**
	* Save an entity
	*
	* @param	mixed		$context		context
	* @param	boolean		$callback		callback
	* @param	integer		$storage_model	storage model
	* @param	boolean		$verbose 		verbose mode
	* @param	mixed		$informant		informant
	* @return	nothing
	*/
	public static function saveEntity(
		&$context,
		$callback = NULL,
		$storage_model = STORE_DATABASE,
		$verbose = FALSE,
		$informant = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_db = $class_application::getDbClass();

		$class_dumper = $class_application::getDumperClass();

		$last_insert_id = NULL;

		$serialization_plan = array(0 => array());

		if ( is_object( $context ) )
		{
			$class_name = get_class( $context );

			if (
				// check possible specific serialization of the argument class
				(
					! in_array(
						'save'.ucfirst( $class_name ),
						get_class_methods(__CLASS__)
					) ||

					// exception made for the Message class
					$class_name == CLASS_MESSAGE && 
					is_object( $context )
				) &&

				// check the serialization settings of the argument class
				in_array(
					'getConfiguration',
					get_class_methods($class_name))
			)

				$last_insertion_properties = self::saveInstance(
					$class_name,
					$context,
					$callback,
					$storage_model,
					$verbose,
					$informant
				);

			else if (
				in_array(
					'save'.ucfirst($class_name),
					get_class_methods(__CLASS__)
				)
			)
			{
				if ( $class_name != CLASS_ENTITY )

					$last_insertion_properties = call_user_func_array(
						array(
							__CLASS__,
							'save'.ucfirst($class_name)),
						array($context, $storage_model)
					);
	
				else if (
					isset($context->{PROPERTY_NAME}) &&
					( $name = $context->{PROPERTY_NAME} )
				)
				{
					$insert_entity_model = '
						INSERT INTO '.TABLE_ENTITY.' SET
							ety_name = "{entity_name}"
					';
	
					$insert_entity_table_model = '
						INSERT INTO '.TABLE_ENTITY_TABLE.'
						(
							ety_id,
							ett_column_prefix,
							ett_table_name,
							ett_table_alias
						)
						SELECT 
							ety_id,
							{column_prefix},
							{table_name},
							{table_alias}
						FROM
							'.TABLE_ENTITY.' ety
						WHERE
							ety.ety_name = "{entity_name}" AND
							ety.ety_id NOT IN (
								SELECT
									ety_id
								FROM
									'.TABLE_ENTITY_TABLE.'
							)
					';
	
					$insert_entity_type_model = '
						INSERT INTO '.TABLE_ENTITY_TYPE.'
						(
							ety_id,
							etp_default,
							etp_index,
							etp_name,
							etp_value
						)
						SELECT 
							ety_id,
							{default},
							{index},
							"{type}",
							"{value}"
						FROM
							'.TABLE_ENTITY.' ety
						WHERE
							ety.ety_name = "{entity_name}" AND
							(
								ety.ety_id,						
								{index},
								"{type}",
								"{value}"
							) NOT IN (
								SELECT
									etp.ety_id,
									etp.etp_index,
									etp.etp_name,
									etp.etp_value
								FROM
									'.TABLE_ENTITY_TYPE.' etp
							)
					';
	
					$update_entity_table_model = '
						UPDATE
							'.TABLE_ENTITY_TABLE.' ett,
							'.TABLE_ENTITY.' ety
						SET
							ett_column_prefix = {column_prefix},
							ett_table_name = {table_name},
							ett_table_alias = {table_alias}
						WHERE
							ett.ety_id = ety.ety_id AND
							ety.ety_name = "{entity_name}"
					';
	
					$update_entity_type_model = '
						UPDATE
							'.TABLE_ENTITY_TYPE.' etp,
							'.TABLE_ENTITY.' ety
						SET
							etp.etp_default = {default},
							etp.etp_value = "{value}"
						WHERE
							ety.ety_name = "{entity_name}" AND
							etp.ety_id = ety.ety_id AND
							etp.etp_index = {index} AND
							etp.etp_name = "{type}"
					';
	
					$insert_entity = str_replace(
						'{entity_name}',
						$name,
						$insert_entity_model
					);
	
					$last_insertion_properties = $class_db::query($insert_entity);
	
					if (
						isset($context->{PROPERTY_TABLE}) &&
						( $table = '"'.strtolower($context->{PROPERTY_TABLE}).'"' )
					)
					{
						if (!empty($context->{PROPERTY_COLUMN_PREFIX}))
	
							$column_prefix = '"'.$context->{PROPERTY_COLUMN_PREFIX}.'"';
						else
						
							$column_prefix = 'NULL';
	
						if (!empty($context->{PROPERTY_TABLE_ALIAS}))
	
							$table_alias = '"'.$context->{PROPERTY_TABLE_ALIAS}.'"';
						else
						
							$table_alias = 'NULL';
							
						$insert_entity_table = str_replace(
							array(
								'{column_prefix}',
								'{entity_name}',
								'{table_alias}',
								'{table_name}'
							),
							array(
								$column_prefix,
								$name,
								$table_alias,
								$table
							),
							$insert_entity_table_model
						);
					
						$update_entity_table = str_replace(
							array(
								'{column_prefix}',
								'{entity_name}',
								'{table_alias}',
								'{table_name}'
							),
							array(
								str_replace('_', '', $column_prefix),
								$name,
								$table_alias,
								$table
							),
							$update_entity_table_model
						);
	
						$last_entity_table_id = $class_db::query($insert_entity_table);
	
						$class_db::query($update_entity_table);
					}
	
					if (
						isset($context->{PROPERTY_DEFAULT}) &&
						isset($context->{PROPERTY_TYPE}) &&
						isset($context->{PROPERTY_VALUE}) &&
						isset($context->{PROPERTY_INDEX}) &&
						( $type = strtolower($context->{PROPERTY_TYPE}) )
					)
					{
						$default = $context->{PROPERTY_DEFAULT};

						$index = $context->{PROPERTY_INDEX};
	
						$value = $context->{PROPERTY_VALUE};
	
						$insert_entity_type = str_replace(
							array(
								'{default}',
								'{entity_name}',
								'{index}',
								'{type}',
								'{value}'
							),
							array(
								$default,
								$name,
								$index,
								$type,
								$value
							),
							$insert_entity_type_model
						);
	
						$update_entity_type = str_replace(
							array(
								'{default}',
								'{entity_name}',
								'{index}',
								'{type}',
								'{value}'
							),
							array(
								$default,								
								$name,
								$index,
								$type,
								$value
							),
							$update_entity_type_model
						);
	
						$last_entity_type_id = $class_db::query(
							$insert_entity_type
						);
	
						$class_db::query( $update_entity_type );
					}
	
					$class_dumper::log(
						__METHOD__,
						array(
							'insert entity: ',
							$insert_entity, 
							'insert entity table: ',
							(
								isset( $insert_entity_table )
							?
								$insert_entity_table
							:
								'_'
							), 
							'update entity table: ',
							(
								isset( $update_entity_table )
							?
								$update_entity_table
							:
								'_'
							),
							'insert entity type: ',
							(
								isset( $insert_entity_type )
							?
								$insert_entity_type
							:
								'_'
							),
							'update entity type: ',
							(
								isset( $update_entity_type )
							?
								$update_entity_type
							:
								'_'
							)
						),
						$verbose
					);				
				}
			}
			else if (
				! in_array(
					'save'.ucfirst( $class_name ),
					get_class_methods( __CLASS__ )
				)
			)

				throw new Exception(
					EXCEPTION_FURTHER_IMPLEMENTATION_REQUIRED.': '.
					sprintf(
						EXCEPTION_DEVELOPMENT_CLASS_METHOD_REQUIRED,
						'save'.ucfirst($class_name),
						__CLASS__
					)
				);

			$class_dumper::log(
				__METHOD__,
				array( $last_insertion_properties )
			);

			if (
				isset( $last_insertion_properties ) &&
				$last_insertion_properties
			)
			{
				if ( isset( $last_insertion_properties[ENTITY_PLAN] ) )
				{
					// check if there is such thing as a plan
					if (
						is_array( $last_insertion_properties[ENTITY_PLAN] ) &&
						count( $last_insertion_properties[ENTITY_PLAN] )
					)
					{
						while (
							list( $entity_index, $properties ) =
								each( $last_insertion_properties[ENTITY_PLAN] )
						)
						{
							list( $entity, $_properties ) = each( $properties );
							reset( $properties );

							if ( $entity == ENTITY_EVENT )
							{
								$key_event = $entity_index;

								$event_properties = $last_insertion_properties
									[ENTITY_PLAN]
										[$entity_index]
											[ENTITY_EVENT];
							}

							$serialization_plan[0][$entity_index] = $properties;

							$serialization_plan[$entity_index] = NULL;
						}

						reset( $last_insertion_properties[ENTITY_PLAN] );
					}
				}
				else
				{
					if ( is_numeric( $last_insertion_properties ) )
					{
						// update the context
						if ( is_object( $context ) )
						
							$context->{PROPERTY_ID} = $last_insertion_properties;

						$event_properties = self::prepareEvent(
							$last_insertion_properties,
							$class_name
						);
					}
					else 

						$event_properties = $last_insertion_properties;
				}

				if (
					! is_array( $event_properties ) ||
					! isset( $event_properties[ENTITY_OBSERVATION] )
				)
	
					$last_insert_id = call_user_func_array(
						array(
							__CLASS__,
							'saveEvent',
						),
						array( $event_properties, $storage_model )
					);

				if ( $last_insert_id && $serialization_plan[0] )
				{
					$serialization_plan[$key_event] = $last_insert_id;

					if ( isset( $callback[ENTITY_SYNCHRONIZATION] ) )

						unset( $callback[ENTITY_SYNCHRONIZATION] );

					$last_insert_id = self::executePlan(
						$serialization_plan,
						$callback,
						$storage_model,
						$verbose,
						$informant
					);
				}
				else

					$last_insert_id = $event_properties;
			}
			else if ($class_name != CLASS_ENTITY)
			
				throw new Exception( EXCEPTION_EVENT_BACKUP_FAILURE );
		}
		else if ( ! is_object( $context ) )

			throw new Exception(
				EXCEPTION_INVALID_ARGUMENT.': '.
				EXCEPTION_EXPECTATION_OBJECT
			);

		return $last_insert_id;
	}

	/**
	* Save an event
	*
	* @param	mixed	$context	context
	* @param	integer	$storage_model	storage model
	* @return	integer	last event id
	*/
	public static function saveEvent($context, $storage_model = STORE_DATABASE)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$class_db = $class_application::getDbClass();

		$column_prefix = PREFIX_TABLE_COLUMN_EVENT;

		$assignments = '';

		$results = NULL;

		$operation_type_instantiate_entity = self::getEntityTypeValue(
			array(
				PROPERTY_NAME => str_replace('.', '_', ACTION_INSTANTIATE_ENTITY),
				PROPERTY_ENTITY => ENTITY_OPERATION
			)
		);

		$event_type_instantiate_entity = self::getEntityTypeId(
			array(
				PROPERTY_NAME => $operation_type_instantiate_entity,
				PROPERTY_ENTITY => ENTITY_EVENT
			)
		);

		if (is_array($context) && count($context) > 0)
		{
			$insert_event = '
				INSERT INTO '.TABLE_EVENT.' SET
			';

			$select_event_model = '
				SELECT
					'.$column_prefix.PROPERTY_ID.' '.PROPERTY_ID.'
				FROM
					'.TABLE_EVENT.'
				WHERE
					{clause_where}
			';

			$update_event_occurence_model = '
				UPDATE '.TABLE_EVENT.' SET
					'.$column_prefix.PROPERTY_OCCURENCE.' = '.$column_prefix.PROPERTY_OCCURENCE.' + 1,
					'.$column_prefix.PROPERTY_DATE_LAST_OCCURRENCE.' = NOW()
				WHERE
					'.$column_prefix.PROPERTY_ID.' = {id}
			';

			if (
				isset($context[PREFIX_TABLE_COLUMN_ENTITY_TYPE.PROPERTY_ID]) &&
				$context[PREFIX_TABLE_COLUMN_ENTITY_TYPE.PROPERTY_ID] == $event_type_instantiate_entity
			)

				$insert_event = str_replace('INSERT INTO', 'REPLACE INTO', $insert_event);
	
			end($context);
			list($last_property) = each($context); 
			reset($context);

			while (list($property, $value) = each($context))
	
				$assignments .=
					$property.
					' = '.
					(
						!is_null($value)
					?
						( !is_numeric($value) ? '"' : '' ).
						$value.
						( !is_numeric($value) ? '"' : '' )
					:
						'NULL'
					).
					( $property != $last_property ? ',' : '' )."\n"
				;

			reset($context);

			$insert_event .= $assignments;

			$select_event = str_replace(
				'{clause_where}',
				str_replace(','."\n", SQL_AND, $assignments),
				$select_event_model
			);

			$results_selection = $class_db::query($select_event);

			if (!$results_selection->num_rows)

				$results = $class_db::query($insert_event);
			else
			{
				$event_id =
					$results_selection
						->fetch_object()
						->{PROPERTY_ID}
				;

				$update_instance_occurence = str_replace(
					'{id}',
					$event_id,
					$update_event_occurence_model
				);

				if ($class_db::query($update_instance_occurence))

					$results = $event_id;
			}

		}

		return $results;
	}

	/**
	* Save a field handler
	*
	* @param	mixed	&$context			reference to a context
	* @param	mixed	$storage_model		storage model
	* @return	nothing
	*/
	public static function saveFieldHandler(
		&$context,
		$storage_model = STORE_DATABASE
	)
	{
		global $class_application, $verbose_mode;

		// set the database connector class name
		$class_db = $class_application::getDbClass();

		// set the dumper class name
		$class_dumper = $class_application::getDumperClass();

		// set the member class name
		$class_member = $class_application::getMemberClass();

		// set the messenger class name
		$class_messenger = $class_application::getMessengerClass();

		// set the photo class name
		$class_photo = $class_application::getPhotoClass();

		// set the processor class name
		$class_processor = $class_application::getProcessorClass();

		// set the toolbox class name
		$class_toolbox = $class_application::getToolboxClass();

		// set the user handler class name
		$class_user_handler = $class_application::getUserHandlerClass();

		// set the test case class name
		$class_test_case = $class_application::getTestCaseClass();

		// declare an empty feedback store
		$feedback = array();

		$filing_forms = 
			array(
				AFFORDANCE_UPLOAD_PHOTOGRAPH,
				AFFORDANCE_SEND_DOCUMENT
			)
		;

		// check the context
		if ( is_object( $context ) && get_class( $context ) )

			$field_handler = $context;

		$alias =

		// set the attributes
		$attributes = '';

		$constraint_type_unique = self::getEntityTypeValue(
			array(
				PROPERTY_NAME => PROPERTY_UNIQUE,
				PROPERTY_ENTITY => ENTITY_CONSTRAINT
			)
		);

		// get the database
		$database = DB_SEFI;

		// get the fields of the current field handler
		$fields = $field_handler->getProperty( PROPERTY_FIELDS );

		// get the feedback events
		$events_feedback = $field_handler->getProperty( PROPERTY_FEEDBACK );

		// set the form identifier
		$form_identifier = $field_handler->getProperty( PROPERTY_FORM_IDENTIFIER );

		// count the fields
		$field_count = count( $fields );

		// get the dashboard of the current field handler
		$dashboard = &$field_handler->getDashboard();

		$events_exists =
			is_array( $events_feedback ) &&
			count( $events_feedback )
		;

		// set the save pattern 
		$pattern_save =
			REGEXP_OPEN.

				REGEXP_CATCH_START.REGEXP_NON_GROUPING.
				
					// checking database shortand existence
					REGEXP_CATCH_START.
						SHORTHAND_DATABASE.
					REGEXP_CATCH_END.

					// simple dot used as separator 
					REGEXP_ESCAPE.REGEXP_WILDCARD.
				
				REGEXP_CATCH_END.REGEXP_FACULTATIVE.

				REGEXP_CATCH_START.

					// anything but a dot
					REGEXP_EXPRESSION_START.
						REGEXP_NOT.REGEXP_ESCAPE.REGEXP_WILDCARD.
					REGEXP_EXPRESSION_END.REGEXP_ANY.
				
				REGEXP_CATCH_END.

					// simple dot used as separator 
					REGEXP_ESCAPE.REGEXP_WILDCARD.

				REGEXP_CATCH_START.

					// anything but a dot					
					REGEXP_EXPRESSION_START.
						REGEXP_NOT.REGEXP_ESCAPE.REGEXP_WILDCARD.
					REGEXP_EXPRESSION_END.REGEXP_ANY.REGEXP_FACULTATIVE.
				
				REGEXP_CATCH_END.
				
				// saving default column values works is accepted
				REGEXP_CATCH_START.

					REGEXP_ESCAPE."*".
				REGEXP_CATCH_END.REGEXP_FACULTATIVE.

				REGEXP_END.

			REGEXP_CLOSE
		;

		// set the select primary key query model
		$select_constraints_model = "
			SELECT
				k.column_name AS target
			FROM
				information_schema.table_constraints t
			JOIN
				information_schema.key_column_usage k
			USING
			(
				constraint_name,
				table_schema,
				table_name
			)
			WHERE
				t.constraint_type='{constraint}' AND
				t.table_schema='{database}' AND
				t.table_name='{table}'
		";

		// set the select existing values query model
		$select_existing_values_model = "
			SELECT
				count({column}) AS ".PROPERTY_OCCURRENCES.",
				{primary_key} AS ".PROPERTY_PRIMARY_KEY."
			FROM
				{table}
			WHERE
				{column} = {value}
		";

		// set the select member qualities query model
		$select_member_qualities_model = "
			SELECT
				{column} AS ".PROPERTY_QUALITY."
			FROM
				{table}
			WHERE
				{primary_key} = {identifier}
		";				

		// set the update column query model
		$update_column_model = "
			UPDATE {table} SET
				{column} = {value}
			WHERE
				{primary_key} = {identifier}
		";

		// get field values
		$field_values = $field_handler->getValues();

		// loop on the fields
		while ( list( $field_index, $field_attributes ) = each( $fields ) )
		{
			if (
				! empty( $field_attributes[ACTION_SAVE_TO] ) ||
				! empty( $field_attributes[ACTION_MOVE_TO] )
			)
			{
				$match_serialization = preg_match(
					$pattern_save,
						! empty( $field_attributes[ACTION_MOVE_TO] )
					?
						$field_attributes[ACTION_MOVE_TO]
					:
						$field_attributes[ACTION_SAVE_TO]
					,
					$matches
				);

				if ( empty( $field_attributes[ACTION_MOVE_TO] ) )
				{
					if ( $match_serialization && ! empty( $matches[3] ) )
					{
						$field_value =
							$field_values
								[$field_attributes[HTML_ATTRIBUTE_NAME]]
						;

						if (
							(
								$field_attributes[HTML_ATTRIBUTE_NAME] ===
									PROPERTY_HASH
							) &&
							preg_match(
								'/([^(]+)\(([^\)]+)\)/',
								$field_value,
								$matches_hash_target
							)
						)
						{
							if (
								isset( $matches_hash_target[1] ) &&
								( $function_hash = $matches_hash_target[1] ) &&
								isset( $matches_hash_target[2] ) &&
								( $field_list = $matches_hash_target[2] )
							)
							{
								$separator = '$_$_$';

								$items = explode( ',', $field_list );
								
								$missing_value = in_array(
									FALSE,
									$hash_arguments = forEachItem(
										$items,
										function( $item )
										use ( $field_values, $class_dumper )
										{
											if ( isset( $field_values[$item] ) )
											
												return trim( $field_values[$item] );
											else

												return FALSE;
										}
									)
								);
			
								if ( ! $missing_value )
						
									$field_value = call_user_func(
										$function_hash,
										implode( $separator, $hash_arguments )
									);
								else
						
									throw new Exception( EXCEPTION_CONSISTENCY_ISSUE );
							}
						}

						if ( $field_index + 2 < $field_count )

							$attributes .=
								'{'.PLACEHOLDER_ALIAS.'}'.$matches[3].' = '.
								(
									is_string( $field_value )
								?
									'"'.$field_value.'"'
								:
									$field_value
								).",\n"
							;

						else if ( ! empty( $matches[2] ) )
						{
							$attributes .=
								'{'.PLACEHOLDER_ALIAS.'}'.$matches[3].' = '.
								(
									is_string( $field_value )
								?
									'"'.$field_value.'"'
								:
									$field_value
								)."\n"
							;

							if ( defined( strtoupper( PREFIX_TABLE.$matches[2] ) ) )
							{
								$insert_query = '
									INSERT INTO '.constant(
											strtoupper(
												PREFIX_TABLE.$matches[2]
											)
										)." SET\n"
								;

								if (
									in_array(
										$form_identifier,
										$filing_forms
									)
								)
								{
									// get the qualities of the logged in member 
									$qualities = $class_member::getQualities(
											$form_identifier ===
												AFFORDANCE_UPLOAD_PHOTOGRAPH
										?
											TRUE
										:
											FALSE 
									);

									// set a hash
									$hash = $class_photo::createHash();
									
									if (
										!isset($field_values['file']['name']) &&
										isset($_FILES['file']) && is_array($_FILES['file'])
									)
									
										$field_values['file'] = $_FILES['file'];

									$extension_match = preg_match(
										"/(.*)\.([^\.]*)?$/",
										$field_values['file']['name'],
										$extension_matches
									);

									if (
										! $extension_match &&
											empty( $extension_matches[2] )
									)

										$original_file_name = $class_toolbox::rewrite(
											$field_values['file']['name']
										);
									else

										$original_file_name = $class_toolbox::rewrite(
												$extension_matches[1]
											).
											".".
											$extension_matches[2]
										;

									$path_temporary = $field_values['file']['tmp_name'];

									if (isset($destination_folder))

										$path_destination =
											dirname(__FILE__).
												DIR_PARENT_DIRECTORY.
													DIR_PARENT_DIRECTORY."/".
														$destination_folder."/".
															$hash.$original_file_name;
									else
									
										throw new Exception( EXCEPTION_INVALID_DESTINATION_PATH );

									if ( ! empty( $field_values['file']['tmp_name'] ) )

										// get the photograph dimensions
										$photo_dimensions = getimagesize(
											$path_temporary
										);

									$insert_query .= '
										author_id = '.$qualities->{ROW_MEMBER_IDENTIFIER}.',
										hash = "'.$hash.'",
										height = '.
											(
												! empty( $photo_dimensions[1] )
											?
												$photo_dimensions[1]
											:
												0
											).',
										mime_type = "'.$field_values['file']['type'].'",
										size = '.$field_values['file']['size'].',
										original_file_name = "'.$field_values['file']['name'].'",
										pht_status = '.PHOTOGRAPH_STATUS_VALID.',
										width = '.
											(
												! empty( $photo_dimensions[0] )
											?
												$photo_dimensions[0]
											:
												0
											).',
									';

									$class_dumper::log(
										__METHOD__,
										array(
											'[post update event]',
											$field_attributes
										)
									);

									/**
									*
									* FIXME
									*
									*/

									if (
										! empty(
											$field_attributes[EVENT_AFTER_UPDATE]
										)  &&
										$form_identifier ===
											AFFORDANCE_SEND_DOCUMENT
									)
	
										// let a processor taking action
										$class_processor::takeAction(
											$field_attributes[EVENT_AFTER_UPDATE],
											$feedback
										);
								}
							}
							else

								throw new Exception( EXCEPTION_INVALID_DATABASE );
						}
					}
				}
				else if ( ! empty( $matches[3] ) )
				{
					if ( defined( strtoupper( PREFIX_DIRECTORY.$matches[3] ) ) )

						$destination_folder = constant(
							strtoupper( PREFIX_DIRECTORY.$matches[3] )
						);
					else

						throw new Exception( EXCEPTION_INVALID_DIRECTORY );
				}
			}
			else if (
				! empty( $field_attributes[AFFORDANCE_DISPLAY_DEFAULT_VALUE] ) ||
				! empty( $field_attributes[AFFORDANCE_UPDATE] )
			)
			{
				$pattern =
					REGEXP_OPEN.

						REGEXP_GROUP_START.

							REGEXP_CATCH_START.
								REGEXP_WILDCARD_LITERAL_NUMERIC.REGEXP_ANY.
							REGEXP_CATCH_END.

							// dot separator
							REGEXP_ESCAPE.'.'.
						REGEXP_GROUP_END.REGEXP_FACULTATIVE.								
						
						SHORTHAND_DATABASE.

						// dot separator						
						REGEXP_ESCAPE.'.'.
						
						REGEXP_CATCH_START.
							REGEXP_WILDCARD_LITERAL.REGEXP_ANY.
						REGEXP_CATCH_END.
						
						// dot separator
						REGEXP_ESCAPE.'.'.

						REGEXP_CATCH_START.
							REGEXP_WILDCARD_LITERAL.REGEXP_ANY.
						REGEXP_CATCH_END.

					REGEXP_CLOSE
				;

				if (
					! empty( $field_attributes[AFFORDANCE_DISPLAY_DEFAULT_VALUE] ) &&
					empty( $field_attributes[AFFORDANCE_UPDATE] )
				)

					$match = preg_match(
						$pattern,
						$field_attributes[AFFORDANCE_DISPLAY_DEFAULT_VALUE],
						$matches
					);

				else if ( ! empty( $field_attributes[AFFORDANCE_UPDATE] ) )

					$match = preg_match(
						$pattern,
						(
							is_array( $field_attributes[AFFORDANCE_UPDATE] )
						?
							$field_attributes[AFFORDANCE_UPDATE][PROPERTY_NAME]
						:
							$field_attributes[AFFORDANCE_UPDATE]
						),
						$matches
					);

				// check  the match
				if (
					$match &&
					! empty( $matches[2] ) &&
					(
						! empty( $field_values[$matches[3]] ) ||
						empty( $field_values[$matches[3]] ) &&
						substr(
							$field_attributes[HTML_ATTRIBUTE_TYPE] , -1, 1
						) != SUFFIX_MANDATORY ||
						$matches[3] != $field_attributes[HTML_ATTRIBUTE_NAME]
					)
				)
				{
					$authorization_granted =
						$class_user_handler::authorizedUser(
							$field_handler->getProperty( PROPERTY_FORM_IDENTIFIER ) 
						) ||
						in_array(
							$form_identifier,
							$filing_forms
						)	
					;

					if ( $authorization_granted == TRUE)
						
						$class_member::logout();
					
					if ( $authorization_granted == FALSE )
					{
						// set the field name		
						$field_name = $matches[3];

						// get field type
						$field_type = rtrim(
							$field_attributes[HTML_ATTRIBUTE_TYPE],
							SUFFIX_MANDATORY
						);
					}
					else
					{
						$field_name = $field_attributes[HTML_ATTRIBUTE_NAME];

						$field_type = FIELD_TYPE_TEXT;
					}
					
					/**
					*
					* TODO
					*
					*/
					$class_test_case::perform(
						DEBUGGING_FIELD_HANDLING_DEFAULT_FIELD_VALUES,
						! $verbose_mode,
						array(
							'field_attributes' => $field_attributes,
							'field_values' => $field_values,
							'match' => $matches[3],
							'pattern' => $pattern
						)
					);

					if (
						! empty(
							$field_attributes[AFFORDANCE_DISPLAY_DEFAULT_VALUE]
						) &&
						isset( $field_values[$matches[3]] ) &&
						strpos(
							$field_values[$matches[3]],
							SHORTHAND_DATABASE.'.'
						) !== 0
					)

						// set the field value
						$field_value = $field_values[$matches[3]];

					else if (
						strpos(
							$field_values[$field_attributes[HTML_ATTRIBUTE_NAME]],
							SHORTHAND_DATABASE.'.'
						) !== 0
					)

						// set the field value
						$field_value =
							$field_values[$field_attributes[HTML_ATTRIBUTE_NAME]];
					else

						$field_value = '';

					// set the default value type
					$value_type = MYSQLI_STATEMENT_TYPE_INTEGER;

					// switch from the field type
					switch ( $field_type )
					{
						case FIELD_TYPE_PASSWORD:

							if ( ! empty( $matches[3] ) )

								$field_value =
									"'".
									call_user_func_array(
										$matches[1],
										array($field_value)
									).
									"'"
								;
							else 

								$field_value = "'".$field_value."'";

							$value_type = MYSQLI_STATEMENT_TYPE_STRING;

								break;

						case FIELD_TYPE_EMAIL:
						case FIELD_TYPE_TEXT:

							$field_value = "'$field_value'";

							$value_type = MYSQLI_STATEMENT_TYPE_STRING;

								break;
					}

					// set the default number of existing occurences
					$occurences = 0;

					if ( empty($matches[3] ) )

						// set the store name
						$store_name = $matches[1];
					else 
						// set the store name
						$store_name = $matches[2];

					// set the default update field value flag
					$update_field_value = TRUE;

					// check if a user is logged in
					if ( $authorization_granted || $class_user_handler::loggedIn() )
 					{
						// set a column name
						$column =
							constant(
								strtoupper(
									PREFIX_PREFIX.
										PREFIX_TABLE.
											PREFIX_COLUMN.
												$store_name
								)
							).
							$field_name
						;

						$constraint_type_primary_key = self::getEntityTypeValue(
							array(
								PROPERTY_NAME => PROPERTY_PRIMARY_KEY,
								PROPERTY_ENTITY => ENTITY_CONSTRAINT
							)
						);

						// get a database connection resource
						$link = $class_db::getLink();

						if ( FALSE == $authorization_granted )

							// get the qualities of the logged in member 
							$qualities = $class_member::getQualities();

						// set the default array of unique
						$unique = array();
					
						// set a table name
						$table = constant( strtoupper( PREFIX_TABLE.$store_name ) );

						// set the select primary key query
						$select_primary_key = str_replace(
							array(
								'{constraint}',
								'{database}',
								'{table}'
							),
							array(
								strtoupper($constraint_type_primary_key),
								$database,
								$table
							),
							$select_constraints_model
						);

						// set the select unique query
						$select_unique = str_replace(
							array(
								'{constraint}',
								'{database}',
								'{table}'
							),
							array(
								strtoupper( $constraint_type_unique ),
								$database,
								$table
							),
							$select_constraints_model
						);

						// execute the select primary key query 
						$results_primary_key = $class_db::query( $select_primary_key );

						// execute the select unique query 
						$results_unique = $class_db::query( $select_unique );

						// check the results of the primary key constraints
						if ( $results_primary_key->num_rows )
						{
							// get the columns
							$columns = $results_primary_key->fetch_object();

							$primary_key = $columns->{ENTITY_TARGET};
						}

						// check the results of the unique constraints
						if ( $results_unique->num_rows )

							// get the columns
							while ( $columns = $results_unique->fetch_object() )
							
								$unique[$columns->{ENTITY_TARGET}] = $columns->{ENTITY_TARGET};

						$search = array(
							'{column}',
							'{primary_key}',
							'{table}',
							'{value}',
							'{identifier}'
						);

						$replace = array(
							FALSE == $authorization_granted ? $column : $primary_key,
							FALSE == $authorization_granted ? $primary_key : $column,
							$table,
							trim( $field_value )
						);

						if ( FALSE == $authorization_granted )

							$replace[] = $qualities->{ROW_MEMBER_IDENTIFIER};
						else

							$replace[] = '\''.$field_values[$field_attributes[HTML_ATTRIBUTE_NAME]].'\'';

						// set the select member qualities query
						$select_member_qualities = str_replace(
							$search,
							$replace,
							$select_member_qualities_model
						);

						if ( $authorization_granted == TRUE )
						{
							// update the value replacement
							$replace[0] = constant(
									strtoupper(
										PREFIX_PREFIX.
											PREFIX_TABLE.
												PREFIX_COLUMN.
													$store_name
									)
								).$matches[3]
							;
							
							if (
								isset( $field_attributes[AFFORDANCE_UPDATE] ) &&
								isset( $field_attributes[AFFORDANCE_UPDATE][PROPERTY_VALUE] )
							)
							{
								$match = preg_match(
									'/'.ACTION_CALL.'\.([a-z_]+)\.([a-z_]+)/',
									$field_attributes[AFFORDANCE_UPDATE][PROPERTY_VALUE],
									$matches
								);

								if ( $match && !empty( $matches[2] ) )
								{
									$class_name_levels = explode( '_', $matches[1] );

									$method_name_levels= explode( '_', $matches[2] );

									$uppercase = function( &$value, $key )
									{
										$value = ucfirst( $value );
									};

									$camelize = function( &$value, $key )
									{
										if ( $key > 0 )

											$value = ucfirst( $value );
									};
									
									array_walk( $class_name_levels, $uppercase );

									array_walk( $method_name_levels, $camelize );

									$class = implode( '_', $class_name_levels );

									$method = implode( $method_name_levels );

									// update the column replacement
									$password_container = call_user_func(
										array( $class, $method )
									);
								
									$replace[3] = '\''.$password_container['sha1'].'\'';
								}
							}
						}

						// set the update column query
						$update_column = str_replace(
							$search,
							$replace,
							$update_column_model
						);

						// execute the select member qualities query
						$result_member_qualities = $class_db::query( $select_member_qualities );

						// get the member qualities
						$member_qualities = $result_member_qualities->fetch_object();

						if ( isset( $unique[$column] ) )
						{
							// set the select existing values query
							$select_existing_values = str_replace(
								array(
									"{column}",
									"{primary_key}",
									"{table}",
									"{value}"
								),
								array(
									$column,
									$primary_key,
									$table,
									$field_value
								),
								$select_existing_values_model
							);
				
							$result_existing_values = $class_db::query(
								$select_existing_values
							);

							// get existing values
							$existing_values = $result_existing_values->fetch_object();

							$key = $existing_values->{PROPERTY_PRIMARY_KEY};

							$occurences = $existing_values->{PROPERTY_OCCURRENCES};

							if (
								$occurences != 0 &&
								$key == $member_qualities->{PROPERTY_QUALITY}
							)

								$occurences = 0;
						}

						if (
							$value_type == MYSQLI_STATEMENT_TYPE_STRING &&
							is_object( $member_qualities ) &&
							"'".$member_qualities->{PROPERTY_QUALITY}."'" == $field_value ||
							$value_type == MYSQLI_STATEMENT_TYPE_INTEGER &&
							$member_qualities->{PROPERTY_QUALITY} == $field_value
						)

							// toggle the update field value flag
							$update_field_value = FALSE;

						if ( empty( $field_attributes[EVENT_BEFORE_UPDATE] ) )
						{
							// check the existing occurences and the update field value flag
							if ( ! $occurences && $update_field_value )
							{
								if ( ! empty( $field_attributes[EVENT_AFTER_UPDATE] ) )

									// let a processor taking action
									$class_processor::takeAction(
										$field_attributes[EVENT_AFTER_UPDATE],
										$feedback
									);

								// update the current column
								$class_db::query( $update_column );
							}
							else if ( $update_field_value )

								$dashboard[$field_name][ERROR_ALREADY_TAKEN] =
									FORM_DISCLAIMER_ALREADY_TAKEN;
						}
						else if (
							$update_field_value &&
							(
								! $occurences ||
								TRUE == $authorization_granted &&
								$occurences === 1
							)
						)
						{
							// append a new query to the store table
							$query_id = self::saveQuery(
								array('value' => $update_column )
							);

							$actions = array_merge(
								array( ACTION_BIND => $query_id ),
								$field_attributes[EVENT_BEFORE_UPDATE]
							);

							$context = array(
								PROPERTY_TARGET => $field_name,
								PROPERTY_HANDLER => $field_handler
							);

							if ( $authorization_granted )
							{
								$context[PROPERTY_KEY] =
									constant(
										strtoupper(
											PREFIX_PREFIX.
												PREFIX_TABLE.
													PREFIX_COLUMN.
														$store_name
										)
									).$field_name
								;

								$context[PROPERTY_IDENTIFIER] =
									$field_values[$field_attributes[HTML_ATTRIBUTE_NAME]];

								if (
									isset( $password_container ) &&
									isset( $password_container['plain'] )
								)

									$context[PROPERTY_VALUE] = $password_container['plain'];
							}

							// let a processor taking action
							$class_processor::takeAction(
								$actions,
								$feedback,
								$context
							);
						}
						else if ( $update_field_value )

							$dashboard[$field_name][ERROR_ALREADY_TAKEN] =
								FORM_DISCLAIMER_ALREADY_TAKEN
							;
					}
				}
			}
		}

		if ( ! empty( $insert_query ) )
		{
			if ( ! empty( $path_temporary ) && !empty( $path_destination ) )

				// move some uploaded file from a temporary path to a destination path
				move_uploaded_file( $path_temporary, $path_destination );

			if (
				in_array(
					$form_identifier,
					$filing_forms
				) &&
				! empty( $path_destination )
			)

				$insert_query .= '
					bytes = "'.base64_encode(
						file_get_contents(
							$path_destination,
							FILE_BINARY
						)
					).'",
				';

			if (
				! empty( $matches[2] ) &&
				defined(
					strtoupper(
						PREFIX_PREFIX.PREFIX_TABLE.PREFIX_COLUMN.$matches[2]
					)
				) 
			)

				$alias =
					constant( 
					strtoupper(
						PREFIX_PREFIX.PREFIX_TABLE.PREFIX_COLUMN.$matches[2]
					)
				);

			$insert_query .= str_replace(
				'{'.PLACEHOLDER_ALIAS.'}',
				$alias,				
				$attributes
			);

			$link = $class_db::getLink();

			$insertion_result = $link->query( $insert_query );

			if ( ! $insertion_result )

				try {
					throw new Exception( $link->error );
				}
				catch ( Exception $exception )
				{
					$pattern_placeholder =
						'/((?:'.ENTITY_EMAIL.')|(?:'.ENTITY_USER_NAME.'))/'
					;
			
					if (
						! empty( $matches[1] ) &&
						defined(
							strtoupper( 
								LANGUAGE_PREFIX_FORM.
									PREFIX_FEEDBACK.
										$class_application::translate_entity(
											$field_handler->getProperty(
												PROPERTY_FORM_IDENTIFIER
											),
											ENTITY_CSS_CLASS
										)."_".
											FEEDBACK_TYPE_FAILURE
							)
						)	
					)

						$feedback = array(
							AFFORDANCE_DISPLAY =>
								constant(
									strtoupper( 
										LANGUAGE_PREFIX_FORM.
											PREFIX_FEEDBACK.
												$class_application::translate_entity(
													$field_handler->getProperty(
														PROPERTY_FORM_IDENTIFIER
													),
													ENTITY_CSS_CLASS
												)."_".
													FEEDBACK_TYPE_FAILURE
									)
								)
						);
				}

			else if (
				$events_exists &&
				defined(
					strtoupper( 
						LANGUAGE_PREFIX_FORM.
							PREFIX_FEEDBACK.
								$class_application::translate_entity(
									$field_handler->getProperty(
										PROPERTY_FORM_IDENTIFIER
									),
									ENTITY_CSS_CLASS
								)."_".
									FEEDBACK_TYPE_SUCCESS
					)
				)
			)

				$feedback = array(
					AFFORDANCE_DISPLAY =>
						str_replace(
							'{'.PLACEHOLDER_PROJECT.'}',
							PROJECT_WEAVING_THE_WEB,
							constant(
								strtoupper( 
									LANGUAGE_PREFIX_FORM.
										PREFIX_FEEDBACK.
											$class_application::translate_entity(
												$field_handler->getProperty(
													PROPERTY_FORM_IDENTIFIER
												),
												ENTITY_CSS_CLASS
											)."_".
												FEEDBACK_TYPE_SUCCESS
								)
							)
						)
				);
		}

		// reset the fields
		reset( $fields );

		if ( count( $feedback ) )

			$class_messenger::provideWithFeedback(
				$feedback,
				$field_handler->getProperty( PROPERTY_FORM_IDENTIFIER )
			);
	}

	/**
	* Save an insight
	*
	* @param	mixed	&$context			reference to a context
	* @param	mixed	$storage_model		storage model
	* @return	nothing
	*/
	public static function saveInsight(&$context, $storage_model = STORE_DATABASE)
	{
		// set the database connector class name
		$class_db = CLASS_DB;

		// set the class entity
		$class_entity = CLASS_ENTITY;

		// set the member class name
		$class_member = CLASS_MEMBER;

		// set the toolbox class name
		$class_toolbox = CLASS_TOOLBOX;

		$arc_type_ownership = self::getEntityTypeValue(
			array(
				PROPERTY_NAME => ARC_MODEL_OWNERSHIP,
				PROPERTY_ENTITY => ENTITY_ARC
			)
		);

		$entity_id_insight = $class_entity::getByName(CLASS_INSIGHT)->{PROPERTY_ID};

		$entity_id_insight_node = $class_entity::getByName(CLASS_INSIGHT_NODE)->{PROPERTY_ID};

		$entity_id_member = $class_entity::getByName(CLASS_MEMBER)->{PROPERTY_ID};

		// set the user handler class name
		$class_user_handler = CLASS_USER_HANDLER;		

		if (NULL != $context->{PROPERTY_ID})

			return self::updateInsight($context);

		$body = trim($class_toolbox::escape($context->{PROPERTY_BODY}));

		$insert_arc_model = '
			INSERT INTO '.TABLE_ARC.' SET
				arc_type = '.$arc_type_ownership.',
				arc_source = {source},
				arc_destination = {destination}
		';

		$insert_edge_model = '
			INSERT INTO '.TABLE_EDGE.' SET
				ety_id = {entity_id},
				edg_key = {key}
		';

		$insert_insight_model = '
			INSERT INTO '.TABLE_INSIGHT.' SET
				isg_status = {insight_default_status},
				ety_id = {insight_target_type},
				isg_target = {insight_target}
		';

		$insert_insight_node_model = '
			INSERT INTO '.TABLE_INSIGHT_NODE.' SET
				isg_id = {insight_id},
				isn_body = "{body}",
				isn_parent = {parent},
				isn_status = {insight_node_default_status},
				isn_type = {insight_node_default_type}
		';

		$select_edge_model = '
			SELECT
				edg_id '.PROPERTY_ID.'
			FROM
				'.TABLE_EDGE.'
			WHERE
				ety_id = {entity_id} AND
				edg_key = {key}				
		';

		$select_insight_model = '
			SELECT
				isg_id '.PROPERTY_ID.'
			FROM
				'.TABLE_INSIGHT.' isg
			LEFT JOIN
				'.TABLE_INSIGHT_NODE.' isn
			USING
				(isg_id)
			WHERE
				isg.isg_target = {insight_target} AND
				isg.ety_id = {insight_target_type} AND
				isn.isn_parent = 0
		';

		$select_insight_node_body_model = '
			SELECT
				isn_id '.PROPERTY_ID.'
			FROM
				'.TABLE_INSIGHT.' isg
			LEFT JOIN
				'.TABLE_INSIGHT_NODE.' isn
			USING
				(isg_id)
			WHERE
				isg.isg_target = {insight_target} AND
				isg.ety_id = {insight_target_type} AND
				isn.isn_parent = {insight_parent} AND
				isn.isn_body = "{body}"
		';

		$select_insight_root_node_model = '
			SELECT
				isg_id '.PROPERTY_ID.'
			FROM
				'.TABLE_INSIGHT.' isg
			LEFT JOIN
				'.TABLE_INSIGHT_NODE.' isn
			USING
				(isg_id)
			WHERE
				isg.isg_target = {insight_target} AND
				isg.ety_id = {insight_target_type} AND
				isn.isn_parent = 0
		';

		// check if a user is logged in
		if ($class_user_handler::loggedIn())
		{
			// get the qualities of the logged in member 
			$qualities = $class_member::getQualities();	

			$insert_edge_source = str_replace(
				array(
					'{entity_id}',
					'{key}'
				),
				array(
					$entity_id_member,
					$qualities->{ROW_MEMBER_IDENTIFIER}
				),
				$insert_edge_model
			);

			$select_edge_source = str_replace(
				array(
					'{entity_id}',
					'{key}'
				),
				array(
					$entity_id_member,
					$qualities->{ROW_MEMBER_IDENTIFIER}
				),
				$select_edge_model
			);

			$results_edge_source = $class_db::query($select_edge_source);

			if ($results_edge_source->num_rows)
			
				$edge_source_member = $results_edge_source->fetch_object()->{PROPERTY_ID};
			else

				$edge_source_member = $class_db::query($insert_edge_source);
				
			$select_insight_root_node = str_replace(
				array(
					'{insight_target}',
					'{insight_target_type}'
				),
				array(
					intval($context->{PROPERTY_TARGET}),
					intval($context->{PROPERTY_TARGET_TYPE})
				),
				$select_insight_root_node_model
			);

			$results_insight_root_node = $class_db::query($select_insight_root_node);

			if (
				$context->{PROPERTY_PARENT} ||
				$results_insight_root_node->num_rows
			)
			{
				$select_insight_node_body = str_replace(
					array(
						'{body}',
						'{insight_parent}',
						'{insight_target}',
						'{insight_target_type}'
					),
					array(
						$body,
						intval($context->{PROPERTY_PARENT}),
						intval($context->{PROPERTY_TARGET}),
						intval($context->{PROPERTY_TARGET_TYPE})
					),
					$select_insight_node_body_model
				);						
			
				$results_insight_node_body = $class_db::query($select_insight_node_body);

				// check if there is a risk of duplicate contents
				if (
					!$results_insight_node_body->num_rows &&
					$results_insight_root_node->num_rows
				)
				{
					$root_node = $results_insight_root_node->fetch_object();
					
					$insight_id = $root_node->{PROPERTY_ID};
				}
			}
			else if (
				isset($context->{PROPERTY_TARGET}) &&
				isset($context->{PROPERTY_TARGET_TYPE})
			)
			{
				$insert_insight = str_replace(
					array(
						'{insight_default_status}',	
						'{insight_target}',
						'{insight_target_type}',
					),
					array(
						INSIGHT_STATUS_ACTIVE,
						$context->{PROPERTY_TARGET},
						$context->{PROPERTY_TARGET_TYPE}
					),
					$insert_insight_model
				);

				$insight_id = $class_db::query($insert_insight);

				$insert_edge_destination = str_replace(
					array(
						'{entity_id}',
						'{key}'
					),
					array(
						$entity_id_insight,
						$insight_id
					),
					$insert_edge_model
				);

				$select_edge_destination = str_replace(
					array(
						'{entity_id}',
						'{key}'
					),
					array(
						$entity_id_insight,
						$insight_id
					),
					$select_edge_model
				);

				$results_edge_destination = $class_db::query($select_edge_destination);

				if ($results_edge_destination->num_rows)
				
					$edge_destination_insight = $results_edge_destination->fetch_object()->{PROPERTY_ID};
				else

					$edge_destination_insight = $class_db::query($insert_edge_destination);

				$insert_arc = str_replace(
					array(
						'{source}',
						'{destination}'
					),
					array(
						$edge_source_member,
						$edge_destination_insight
					),
					$insert_arc_model
				);				

				$arc_id_insight = $class_db::query($insert_arc);
			}

			if (isset($insight_id))
			{
				$insert_insight_node = str_replace(
					array(
						'{body}',
						'{insight_node_default_status}',
						'{insight_node_default_type}',
						'{member_id}',
						'{parent}',
						'{insight_id}'
					),
					array(
						$body,
						INSIGHT_NODE_STATUS_ACTIVE,
						INSIGHT_NODE_TYPE_LOCAL,
						$qualities->{ROW_MEMBER_IDENTIFIER},
						$context->{PROPERTY_PARENT},
						$insight_id
					),
					$insert_insight_node_model
				);

				if (!empty($body))
				{
					$insight_node_id = $class_db::query($insert_insight_node);

					$insert_edge_destination = str_replace(
						array(
							'{entity_id}',
							'{key}'
						),
						array(
							$entity_id_insight_node,
							$insight_node_id
						),
						$insert_edge_model
					);

					$select_edge_destination = str_replace(
						array(
							'{entity_id}',
							'{key}'
						),
						array(
							$entity_id_insight_node,
							$insight_node_id
						),
						$select_edge_model
					);
	
					$results_edge_destination = $class_db::query($select_edge_destination);

					if ($results_edge_destination->num_rows)
					
						$edge_destination_insight_node = $results_edge_destination->fetch_object()->{PROPERTY_ID};
					else
	
						$edge_destination_insight_node = $class_db::query($insert_edge_destination);

					$insert_arc = str_replace(
						array(
							'{source}',
							'{destination}'
						),
						array(
							$edge_source_member,
							$edge_destination_insight_node
						),
						$insert_arc_model
					);				
	
					$arc_id_insight_node = $class_db::query($insert_arc);

					return $insight_node_id;
				}
			}
		} 
	}

	/**
	* Save an instance of Entity or one of its inherited classes representative
	*
	* @param	string	$class_name		instance class name
	* @param	mixed	&$context		reference to a context
	* @param	mixed	$callback		callback
	* @param	mixed	$storage_model	storage model
	* @param	boolean	$verbose		verbose mode
	* @param	mixed	$informant		informant
	* @return	nothing
	*/
	public static function saveInstance(
		$class_name,
		&$context,
		&$callback = NULL,
		$storage_model = STORE_DATABASE,
		$verbose = FALSE,
		$informant = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_db = $class_application::getDbClass();

		$class_dumper = $class_application::getDumperClass();

		$configuration = $class_name::getConfiguration();

		$pdo =
		$synchronization = FALSE;

		$callback_parameters =
		$current_operation_id = 
		$instance_id =
		$reference =
		$results_synchronization = NULL;

		$entity_name = $class_name;

		if ( strpos( $class_name, '\\' ) !== FALSE )
		
			list($namespace, $entity_name) = explode('\\', $class_name );

		$entity_id = self::getEntityIdByName($entity_name);

		if (is_null($entity_id))

			throw new Exception(EXCEPTION_INVALID_EVENT_SOURCE);

		if (
			is_array($callback) &&
			count($callback) &&
			isset($callback[ENTITY_SYNCHRONIZATION]) &&
			!$callback[ENTITY_SYNCHRONIZATION]
		)
		
			$synchronization = TRUE;

		if (
			empty($configuration[PROPERTY_COLUMN_PREFIX]) ||
			empty($configuration[PROPERTY_DATABASE]) ||
			empty($configuration[PROPERTY_TABLE])
		)

			throw new Exception(EXCEPTION_INVALID_CREDENTIALS_DATABASE_CONNECTION);

		$operation_type_execute_query = self::getEntityTypeValue(
			array(
				PROPERTY_NAME => str_replace('.', '_', ACTION_EXECUTE_QUERY),
				PROPERTY_ENTITY => ENTITY_OPERATION
			)
		);

		$operation_type_instantiate_entity = self::getEntityTypeValue(
			array(
				PROPERTY_NAME => str_replace('.', '_', ACTION_INSTANTIATE_ENTITY),
				PROPERTY_ENTITY => ENTITY_OPERATION
			)
		);

		$operation_type_synchronize_entity = self::getEntityTypeValue(
			array(
				PROPERTY_NAME => str_replace('.', '_', ACTION_SYNCHRONIZE_ENTITY),
				PROPERTY_ENTITY => ENTITY_OPERATION
			)
		);

		$arc_type_execute_query = self::getEntityTypeValue(
			array(
				PROPERTY_NAME => $operation_type_execute_query,
				PROPERTY_ENTITY => ENTITY_ARC
			)
		);

		$event_type_instantiate_entity = self::getEntityTypeId(
			array(
				PROPERTY_NAME => $operation_type_instantiate_entity,
				PROPERTY_ENTITY => ENTITY_EVENT
			)
		);

		$event_type_synchronize_entity = self::getEntityTypeId(
			array(
				PROPERTY_NAME => $operation_type_synchronize_entity,
				PROPERTY_ENTITY => ENTITY_EVENT
			)			
		);

		$column_prefix = $configuration[PROPERTY_COLUMN_PREFIX];

		$database = $configuration[PROPERTY_DATABASE];

		$table = $configuration[PROPERTY_TABLE];

		$constraint_type_index = self::getEntityTypeValue(
			array(
				PROPERTY_NAME => PROPERTY_INDEX,
				PROPERTY_ENTITY => ENTITY_COLUMN
			)
		);

		$constraint_type_unique = self::getEntityTypeValue(
			array(
				PROPERTY_NAME => PROPERTY_UNIQUE,
				PROPERTY_ENTITY => ENTITY_CONSTRAINT
			)
		);

		$constraints_unique = self::getTableConstraints(
			$table,
			$database,
			$constraint_type_unique,
			$verbose,
			$informant
		);

		$constraints = array();

		if (isset($constraints_unique[0]))

			$constraints[$constraints_unique[0]->{PROPERTY_TARGET}] =
				$constraints_unique[0]->{PROPERTY_TARGET};

		else if (count($constraints_unique))

			$constraints = $constraints_unique;

		$constraints_index = self::getTableConstraints(
			$table,
			$database,
			$constraint_type_index,
			$verbose,
			$informant
		);

		if (isset($constraints_index[0]))

			$constraints[$constraints_index[0]->{PROPERTY_TARGET}] =
				$constraints_index[0]->{PROPERTY_TARGET};

		else if (count($constraints_index))

			array_merge($constraints, $constraints_index);

		$assignments = '';

		$properties = (array)$context->getProperties();

		if ( ! empty( $properties[PROPERTY_SIGNATURE] ) )

			unset( $properties[PROPERTY_SIGNATURE] );

		if ( ! empty( $properties[PROPERTY_REFERENCE] ) )
		{
			$_reference = $properties[PROPERTY_REFERENCE];

			if ( is_array( $_reference ) )
			{
				list($operation_id, $reference_name) = each($_reference);
	
				$class_dumper::log(
					__METHOD__,
					array(
						'operation id: ', $operation_id,
						'reference name: ', $reference_name,
						'callback: ', $callback 
					)
				);
	
				if (
					isset($callback[$operation_id]) ||
					is_null($callback[$operation_id])
				)
				{
					if (!is_array($callback[$operation_id]))

						$callback[$operation_id] = array();
				}
				else

					throw new Exception(
						EXCEPTION_INVALID_ARGUMENT.
						' [missing index: '.$operation_id.'].'
					);

				$callback[$operation_id][$reference_name] = 0;

				// prepare a reference to be saved as a callback parameter
				$reference = &$callback[$operation_id][$reference_name];
			}

			unset($properties[PROPERTY_REFERENCE]);

			unset($context->{PROPERTY_REFERENCE});
		}

		if (
			is_array($callback) && count($callback) &&
			(
				count($callback) > 1 ||
				!isset($callback[ENTITY_SYNCHRONIZATION])
			)
		)
		{
			$callback_type_operation_running = self::getEntityTypeValue(
				array(
					PROPERTY_NAME => OPERATION_STATUS_RUNNING,
					PROPERTY_ENTITY => ENTITY_CALLBACK
				)
			);
	
			if (isset($callback[$callback_type_operation_running]))

				$current_operation_id = $callback[$callback_type_operation_running];

			$class_dumper::log(
				__METHOD__,
				array(
					'operation id? ', $current_operation_id,
					'callback', $callback,
					'callback type operation running: ' , $callback_type_operation_running
				)
			);
		}

		end($properties);
		list($last_name) = each($properties);
		reset($properties);

		$class_dumper::log(
			__METHOD__,
			array(
				'properties of the instance to be serialized',
				$properties
			)
		);

		while (list($name, $value) = each($properties))
		{
			if (
				!in_array($name, $constraints) ||
				( FALSE !== strpos($name, $column_prefix) )
			)

				$column_name = $column_prefix.$name;
			else

				$column_name = $name;

			if (is_array($value))
			{
				if (isset($value[PROPERTY_FOREIGN_KEY]))
				{
					$column_name = $name;

					$context->$name =

					$value = $value[PROPERTY_FOREIGN_KEY];
				}
				else if (isset($value[PROPERTY_LAST_INSERT_ID]))
				{	
					if ( isset($callback[$value[PROPERTY_LAST_INSERT_ID]]) )

						$context->$name =

						$value = $callback[$value[PROPERTY_LAST_INSERT_ID]];
					else
					
						throw new Exception(EXCEPTION_INVALID_ARGUMENT);
				}
				else if (isset($value[PROPERTY_REFERENCE]))
				{
					$reference_name = $value[PROPERTY_REFERENCE];

					if (
							isset($callback[$current_operation_id]) &&
							isset($callback[$current_operation_id][$reference_name])
					)
					{
						$context->$name =

						$value = $callback[$current_operation_id][$reference_name];
					}
				}
				else

					throw new Exception(EXCEPTION_INVALID_ARGUMENT);
			}

			$assignments .=
				$column_name.' = '.
				( !is_numeric($value) ? '"'.$class_db::sanitize($value, $pdo).'"' : $value ).
				( $name != $last_name ? ',' : '' )."\n"
			;
		}

		$store_assignments = explode(',', $assignments);

		// check if the synchronization process is enabled
		// with the synchronization type set from physical to layer
		if (
			$synchronization &&
			is_array($constraints_index) &&
			count($constraints_index)
		)
		{
			while (
				list( $index_assignment , $assignment ) =
				each( $store_assignments )
			)
			{
				list( $_assignee, $_assignment ) =
					explode( '=', $assignment );

				if (
					count($constraints_index) == 1 &&
					isset($constraints_index[0])
				)

					$_constraint_index = $constraints;
				else
				
					$_constraint_index = $constraints_index;

				while ( list(, $_constraint) = each( $_constraint_index ))
				{
					if ( strpos( $_assignee, $_constraint) !== FALSE )

						$store_assignments[$index_assignment] =

							$_constraint . ' =' . $_assignment;
				}

				reset( $_constraint_index );				
			}	
			
			$assignments = implode(','."\n", $store_assignments);

			reset( $store_assignments );
		}

		$insert_instance = '
			INSERT INTO '.$table.' SET
				'.$assignments
		;

		$select_instance_model = '
			SELECT
				'.$column_prefix.PROPERTY_ID.' '.PROPERTY_ID.'
			FROM
				'.$table.'
			WHERE
				{clause_where}
		';

		$update_instance_model = '
			UPDATE '.$table.' SET
				'.$assignments.'
			WHERE
				{clause_where}
		';

		$class_dumper::log(
			__METHOD__,
			array(
				'assignments: ', $assignments,
				'instance insertion query: ', $insert_instance,
				'update instance model: ', $update_instance_model
			)
		);

		if ( !empty( $assignments ) )
		{
			// try to insert a new instance of an object into the database
			$instance_id = $class_db::query($insert_instance, $pdo);

			if ( $instance_id )
	
				$context->{PROPERTY_ID} = $instance_id;
			else
			{
				$select_instance = str_replace(
					'{clause_where}',
					str_replace(','."\n", SQL_AND, $assignments),
					$select_instance_model
				);

				// check existing records for provided properties
				$results = $class_db::query($select_instance, $pdo);

				$class_dumper::log(
					__METHOD__,
					array(
						'instance selection query',
						$select_instance,
						'constraints',
						$constraints
					)
				);

				if ( is_object( $results ) && $results->num_rows )

					$instance_id =
					$context->{PROPERTY_ID} =
						$results->fetch_object()->{PROPERTY_ID}
					;

				else if ( ! $results->num_rows && count( $constraints ) )
				{
					$result_by_constraint = NULL;

					while (
						( list(, $constraint) = each($constraints) )  &&
						(
							is_null( $result_by_constraint ) ||
							(
								is_object( $result_by_constraint ) &&
								get_class( $result_by_constraint ) ==
									CLASS_MYSQLI_RESULT &&
								! $result_by_constraint->num_rows
							)
						)
					)
					{
						$select_instance_by_constraint = '';
	
						while (
							(
								list ( $index, $assignment ) =
									each( $store_assignments )
							) &&
							empty( $select_instance_by_constraint )
						)
						{
							$_assignment = explode ('=', $assignment);
	
							if (
								is_array( $_assignment ) &&
								count( $_assignment ) > 1 &&
								trim( strtolower( $_assignment[0] ) ) === 
									trim( strtolower( $constraint ) )
							)
							{
								$constraint = $assignment;
	
								$select_instance_by_constraint = str_replace(
									'{clause_where}',
									$assignment,
									$select_instance_model
								);
	
								if ( ! empty( $select_instance_by_constraint ) )
								{
									// check existing records for provided constraints
									$result_by_constraint = $class_db::query(
										$select_instance_by_constraint,
										$pdo
									);
			
									if (
										is_object( $result_by_constraint ) &&
										$result_by_constraint->num_rows
									)
									{
										$instance_id =
										$context->{PROPERTY_ID} =
										$result_by_constraint->fetch_object()->{PROPERTY_ID};
			
										// synchronize an existing object of the persistency
										// layer from some physical input
										if (
											$synchronization &&
											strlen(trim($constraint)) != 0
										)
										{
											$update_instance = str_replace(
												'{clause_where}',
												$constraint,
												$update_instance_model
											);
			
											$results_synchronization =
	
												$class_db::query($update_instance);
										}
									}
									else if ( ! is_object( $result_by_constraint ) )
				
										throw new Exception(EXCEPTION_INVALID_QUERY);
								}
							}
						}
	
						reset( $store_assignments );						
					}

					reset($constraints);

					if ( ! $result_by_constraint->num_rows )

						throw new Exception(EXCEPTION_INCONSISTENT_RECORDS);
				}
				else if ( ! is_object( $results ) )

					throw new Exception(EXCEPTION_INVALID_QUERY);
				else

					throw new Exception(EXCEPTION_INCONSISTENT_RECORDS);
			}

			if (!is_null($reference))

				$reference = $instance_id;

			if (isset($callback[$current_operation_id]))
	
				$callback[$current_operation_id] = $instance_id;
		}

		if (
			$synchronization ||
			is_null($callback) ||
			is_array($callback) &&
			!count($callback)
		)
	
			// return the last insertion properties
			$callback_parameters = array(
	
				// the id resulting from the insertion of the properties is used to create a contextual arc
	
				// the last insert id is indexed accordingly to the table of its declaration level
	
				ENTITY_PLAN => array(
					
					1 => array(
							   
						ENTITY_EVENT => array(
		
							// the database class is reponsible for instantiating an object of the argument class
							PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID =>
			
								self::getEntityIdByName(CLASS_DATABASE),
				
							// the argument class is considered as the event source
							PREFIX_TABLE_COLUMN_EVENT.PROPERTY_SOURCE =>
			
								$entity_id,
				
							// the success depends the insertion of a new record
							PREFIX_TABLE_COLUMN_EVENT.PROPERTY_SUCCESS =>
			
								(
										!$synchronization  &&
										$instance_id !== FALSE ||
										$synchronization &&
										isset($update_instance) &&
										$results_synchronization ||
										$synchronization &&
										!isset($update_instance)
									?
										1
									:
										0
								),
				
							// the instantiated object is considered as the event target
							PREFIX_TABLE_COLUMN_EVENT.PROPERTY_TARGET =>
			
								( $instance_id !== FALSE ? $instance_id : NULL ),
				
							// the event type is of instantiate entity operation
							PREFIX_TABLE_COLUMN_ENTITY_TYPE.PROPERTY_ID =>
							(
									$synchronization
								?
									$event_type_synchronize_entity
								:
									$event_type_instantiate_entity
							),
	
						)
	
					),
	
					2 => array(
	
						ENTITY_EDGE => array(
						
							PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID =>
	
								self::getEntityIdByName(ENTITY_EVENT),

							// the value will be the id of a previously inserted record		
							PROPERTY_KEY => array(PROPERTY_LAST_INSERT_ID => 1),
		
							PROPERTY_REFERENCE => array(5 => PROPERTY_DESTINATION)
						)
	
					),
	
					3 => array(
	
						ENTITY_QUERY => array(
	
							PROPERTY_TYPE =>
							(	
									$synchronization
								?
									QUERY_TYPE_UPDATE
								:
									QUERY_TYPE_INSERT
							),
		
							PROPERTY_VALUE => trim
							(
									$synchronization
								?
								(
										isset($update_instance)
									?
										$update_instance
									:
										$select_instance
								)
								:
									$insert_instance
							)
		
						)
	
					),
	
					4 => array(
	
						ENTITY_EDGE => array(
							
							PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID =>
	
								self::getEntityIdByName(ENTITY_QUERY),

							// the value will be the id of a previously inserted record
							PROPERTY_KEY => array(PROPERTY_LAST_INSERT_ID => 3),
		
							PROPERTY_REFERENCE => array(5 => PROPERTY_SOURCE)
						)
	
					),
		
					5 => array(
	
						ENTITY_ARC => array(

							PROPERTY_TYPE => $arc_type_execute_query,

							PROPERTY_SOURCE => array(PROPERTY_REFERENCE => PROPERTY_SOURCE),
		
							PROPERTY_DESTINATION => array(PROPERTY_REFERENCE => PROPERTY_DESTINATION)
						)
		
					)
		
				)
		
			);

		if ( $synchronization && !isset($update_instance) )
		{
			$callback_parameters[ENTITY_OBSERVATION] = $callback_parameters[ENTITY_PLAN];

			unset($callback_parameters[ENTITY_PLAN]);
		}

		return $callback_parameters;
	}

	/**
	* Save a message
	*
	* @param	mixed	&$context			reference to a context
	* @param	mixed	$storage_model		storage model
	* @return	nothing
	*/
	public static function saveMessage(
		&$context,
		$storage_model = STORE_DATABASE
	)
	{
		global $class_application, $verbose_mode;

		$class_db = $class_application::getDbClass();

		$class_dumper = $class_application::getDumperClass();

		$class_exception_handler = $class_application::getExceptionHandlerClass();

		$class_member = $class_application::getMemberClass();

		$class_user_handler = $class_application::getUserHandlerClass();

		$argument_error = FALSE;

		if (
			! ( $authorization_granted = $class_user_handler::authorizedUser() )
			&& ! $class_user_handler::loggedIn()
		)
		{
			$exception = new Exception(
				EXCEPTION_RIGHTS_MANAGEMENT_CREDENTIALS_INSUFFICIENT.':'.
				"\n".'executing method '.__METHOD__.
				"\n".'at line: '.__LINE__.
				"\n".'in file '.__FILE__
			);

			$context_http = array(
				PROTOCOL_HTTP_METHOD_GET => $_GET,
				PROTOCOL_HTTP_METHOD_POST => $_POST
			);

			$context = array(
				PROPERTY_CONTEXT => print_r( $context_http, TRUE ),
				PROPERTY_DESCRIPTION => sprintf(
					EVENT_DESCRIPTION_EXCEPTION_CAUGHT,
					$exception->getCode(),
					$exception->getFile(),
					$exception->getLine(),
					$exception->getMessage(),
					$exception->getTraceAsString()
				),
				PROPERTY_EXCEPTION => $exception,
				PROPERTY_TYPE => EVENT_TYPE_EXCEPTION_CAUGHT
			);

			$class_exception_handler::logContext( $context );

			$class_application::jumpTo( PREFIX_ROOT );
		}

		$link = $class_db::getLink();

		if ( ! $authorization_granted )

			// get the qualities of the logged in member 
			$qualities = $class_member::getQualities();
		
		else if ( isset( $context['identifier'] ) )
		{
			$qualities = $class_member::fetchQualities(
				array( 'usr_email' => $context['identifier'] )
			);

			$qualities->{ROW_MEMBER_IDENTIFIER} = &$qualities->id;
		}

		$member_id = $qualities->{ROW_MEMBER_IDENTIFIER};

		$select_contact_model = '
			SELECT
				cnt_id id,
				usr_id member_id
			FROM
				'.TABLE_CONTACT.'
			WHERE
				cnt_value = TRIM("{email}") AND
				cnt_type = '.CONTACT_TYPE_EMAIL
		;

		$select_recipient_model = '
			SELECT
				count(*) size
			FROM
				'.TABLE_RECIPIENT.'
			WHERE
				cnt_id = {id} AND
				rcp_full_name = TRIM("{full_name}") AND
				rcl_id = {recipient_list} AND
				usr_id = {member_id}
		';

		$select_recipient_list_model = '
			SELECT
				rcl_id
			FROM
				'.TABLE_RECIPIENT_LIST.'
			WHERE
				rcl_name = TRIM("{recipient_list}")
		';

		$insert_header_model = '
			INSERT INTO '.TABLE_HEADER.' (
				hdr_sender,
				hdr_subject,
				rcl_id,
				cnt_id
			)
			SELECT
				"{sender_name}",
				"{subject}",
				{recipient_list},
				cnt_id
			FROM
				'.TABLE_CONTACT.' cnt
			WHERE
				cnt.cnt_value = "{sender_email}" AND
				cnt.usr_id = {member_id} AND
				cnt.cnt_type = '.CONTACT_TYPE_EMAIL				
		;

		$insert_message_model = '
			INSERT INTO '.TABLE_MESSAGE.' SET
				hdr_id = {header},
				msg_body_text = "{body_text}",
				msg_body_html = "{body_html}"
		';

		$insert_recipient_list_model = '
			INSERT INTO '.TABLE_RECIPIENT_LIST.' SET
				rcl_status = '.RECIPIENT_LIST_STATUS_ACTIVE.',
				rcl_name = "{recipient_list}"
		';

		$insert_recipient_model = '
			INSERT INTO '.TABLE_RECIPIENT.' SET
				cnt_id = {id},
				rcp_full_name = "{full_name}",
				rcl_id = {recipient_list},
				usr_id = {member_id}
		';

		if ( is_array( $context ) && count( $context ) )
		{
			if (
				isset( $context['to'] ) &&
				is_array( $context['to'] ) &&
				count( $context['to'] ) &&
				isset( $context['from'] ) &&
				is_array( $context['from'] ) &&
				count( $context['from'] )
			)
			{
				list( $name ) = each( $context['to'] );
				reset( $context['to'] );

				$select_recipient_list_results = $class_db::query(
					str_replace(
						'{recipient_list}',
						trim($class_db::sanitize($name)),
						$select_recipient_list_model
					)
				);

				if ( $select_recipient_list_results->num_rows )
				
					$recipient_list_id = $select_recipient_list_results->fetch_object()->rcl_id;
				else

					$recipient_list_id = $class_db::query(
						str_replace(
							'{recipient_list}',
							trim( $class_db::sanitize( $name ) ),
							$insert_recipient_list_model
						)
					);

				while ( list( $name, $email ) = each( $context['to'] ) )
				{
					$selection_results =
						$class_db::query(
							str_replace(
								'{email}',
								$email,
								$select_contact_model
							)
					);
					
					if ( $selection_results->num_rows )
					{
						$contact = $selection_results->fetch_object();

						$select_recipient_results = $class_db::query(
							str_replace(
								array(
									'{full_name}',
									'{id}',
									'{member_id}',
									'{recipient_list}'
								),
								array(
									$class_db::sanitize( trim( $name ) ),
									$contact->id,
									$contact->member_id,
									$recipient_list_id
								),
								$select_recipient_model
							)
						);

						if ( ! $select_recipient_results->fetch_object()->size )

							$class_db::query(
								str_replace(
									array(
										'{full_name}',
										'{id}',
										'{member_id}',
										'{recipient_list}'
									),
									array(
										$class_db::sanitize( trim( $name ) ),
										$contact->id,
										$contact->member_id,
										$recipient_list_id
									),
									$insert_recipient_model
								)
							);
					}
				}

				list( $sender_name, $sender_email ) = each( $context['from'] );

				$selection_sender_results = $class_db::query(
					str_replace(
						'{email}',
						$sender_email,
						$select_contact_model
					)
				);

				if ( $selection_sender_results->num_rows )

					$header_id = $class_db::query(
						str_replace(
							array(
								'{recipient_list}',
								'{subject}',
								'{sender_name}',
								'{sender_email}',
								'{member_id}'
							),
							array(
								$recipient_list_id,
								$class_db::sanitize($context['subject']),
								$class_db::sanitize(trim($sender_name)),
								$sender_email,
								$selection_sender_results->fetch_object()->member_id
							),
							$insert_header_model
						)
					);

				if (
					$header_id &&
					isset($context['body_text']) &&
					isset($context['body_html'])
				)

					$message_id = $class_db::query(
						str_replace(
							array(
								'{body_html}',
								'{body_text}',
								'{header}'
							),
							array(
								$class_db::sanitize( $context['body_html'] ),
								$class_db::sanitize( $context['body_text'] ),
								$header_id
							),
							$insert_message_model
						)
					);
			}
			else
		
				$argument_error = TRUE;
		}
		else
				$argument_error = TRUE;
		
		if ( $argument_error )
		
			throw new Exception (EXCEPTION_INVALID_ARGUMENT);
	}

	/**
	* Save a photograph
	*
	* @param	mixed	&$context			reference to context
	* @param	mixed	$storage_model		storage model
	* @return	nothing
	*/
	public static function savePhotograph(
		&$context,
		$storage_model = STORE_DATABASE
	)
	{
		global $class_application, $verbose_mode;

		$class_db = $class_application::getDbClass();

		$class_dumper = $class_application::getDumperClass();

		$class_feed_reader = $class_application::getFeedReaderClass();

		$class_file_manager = $class_application::getFileManagerClass();

		$class_member = $class_application::getMemberClass();

		$class_photo = $class_application::getPhotoClass();

		$class_toolbox = $class_application::getToolboxClass();

		// set a hash
		$hash = $class_photo::createHash();

		$insert_query = '
			INSERT INTO '.TABLE_PHOTOGRAPH.' SET'."\n"
		;

		if (isset($context[PROPERTY_NAME]))	

			$file_name = $context[PROPERTY_NAME];
		else
		
			$file_name = NAME_NO_NAME;

		if ( isset( $context[PROPERTY_AUTHOR] ) )

			$author = $context[PROPERTY_AUTHOR];
		else

			$author = AUTHOR_USER_NAME_UNKNOWN;

		if ( isset( $context[PROPERTY_DATE_CREATION] ) )	

			$date_creation = $context[PROPERTY_DATE_CREATION];
		else
			
			$date_creation = date( 'Y-m-d' );

		if (isset($context[PROPERTY_LOCATION]))	

			$location = $context[PROPERTY_LOCATION];
		else

			$location = LANGUAGE_CODE_WORLD;

		list( $rewritten_file_name, $file_extension ) = 
			$class_photo::rewriteFileName( $context[PROPERTY_NAME] );

		$path_source =
			dirname(__FILE__).
				DIR_PARENT_DIRECTORY.
					DIR_PARENT_DIRECTORY.'/'.
						DIR_PHOTOGRAPHS.'/'.
							$location.'/'.
								$date_creation.'/'.
									$author.'/'.
										$file_name
		;

		$path_destination =
			dirname(__FILE__).
				DIR_PARENT_DIRECTORY.
					DIR_PARENT_DIRECTORY."/".
						DIR_SNAPSHOTS."/".
							$hash.$rewritten_file_name;

		if ( file_exists( $path_source ) )

			// get the photograph dimensions
			$photo_dimensions = getimagesize($path_source);
		else

			throw new Exception( EXCEPTION_INVALID_FILE_PATH.': '.$path_source );

		if (
			file_exists( $path_destination ) ||
			FALSE === rename( $path_source, $path_destination )
		)

			throw new Exception(
				EXCEPTION_INVALID_DESTINATION_PATH.': '.
				$path_destination
			);

		if ( $author )

			$qualities = $class_member::fetchQualities(
				array( 'usr_user_name' => $author )
			);
		else 

			$qualities = $class_member::fetchQualities( array( 'usr_id' => 0 ) );

		$file_contents = file_get_contents( $path_destination );

		$rdf_contents = $class_file_manager::extractMetadata(
			$file_contents,
			METADATA_TYPE_RDF
		);

		$rdf_file_name =
			dirname(__FILE__).
			'/../../rdf/'.
			$hash.
			$rewritten_file_name.
			EXTENSION_RDF
		;

		if ( ! file_exists( $rdf_file_name ) )

			file_put_contents( $rdf_file_name, $rdf_contents );

		$metadata = $class_feed_reader::parseRDF( $rdf_file_name );

		$insert_query .= '
			author_id = '.$qualities->{PROPERTY_ID}.',
			hash = "'.$hash.'",
			keywords = "'.$class_toolbox::escape( $metadata[METADATA_TYPE_KEYWORDS] ).'",
			height = '.( ! empty($photo_dimensions[1]) ? $photo_dimensions[1] : 0 ).',
			mime_type = "'.$file_extension.'",
			size = '.strlen( $file_contents ).',
			original_file_name = "'.$file_name.'",
			pht_rdf = '. (
					strlen( $rdf_contents )
				?
					'"'.$class_toolbox::escape( $rdf_contents ).'"'
				:
					'NULL'
			).',
			pht_status = '.PHOTOGRAPH_STATUS_VALID.',
			title = "'.$class_toolbox::escape( $metadata[METADATA_TYPE_TITLE] ).'",
			width = '.( ! empty($photo_dimensions[0] ) ? $photo_dimensions[0] : 0 ).',
		';

		$insert_query .= '
			bytes = "'.base64_encode($file_contents).'"
		';

		$insertion_result = $class_db::query($insert_query);
		
		if ( ! $insertion_result )
		
			throw new Exception($link->error);
		else

			return $insertion_result;
	}

	/**
	*  Save a query
	*
	* @param	string	$properties	properties
	* @return	integer	contact identifier
	*/
	public static function saveQuery($properties)
	{
		global $class_application;

		$class_db = $class_application::getDbClass();

		$class_dumper = $class_application::getDumperClass();

		if (!is_array($properties))
		{
			if (!is_array($properties))
			{
				$_properties = $properties;

				$properties = (array)$_properties->getProperties();
			}

			if (count($properties) == 0 || !isset($properties['value']))
	
				throw new Exception(EXCEPTION_INVALID_ARGUMENT);
		}

		$insert_query = '
			INSERT INTO '.TABLE_QUERY.' SET
				qry_value = "'.trim($properties['value']).'",
				qry_status = '.(
					isset($properties['status'])
				?
					$properties['status']
				:
					QUERY_STATUS_INACTIVE
				).',
				qry_type = '.(
					isset($properties['type'])
				?
					$properties['type']
				:
					QUERY_TYPE_UPDATE
				).'
		';

		$query_id = $class_db::query($insert_query);

		return $query_id;
	}

	/**
	* Save a snapshot
	*
	* @param	mixed	&$context		context
	* @param	integer	$storage_model	storage model
	* @return	integer	last inserted id
	*/
	public static function saveSnapshot( &$context, $storage_model = STORE_DATABASE )
	{
		$class_db = CLASS_DB;

		$class_entity = CLASS_ENTITY;

		$last_insertion_id = NULL;

		if (
			isset($context->{PROPERTY_STATE}) &&
			is_object($context->{PROPERTY_STATE})
		)
		{
			$entity_properties = $class_entity::fetchId(get_class($context->{PROPERTY_STATE}));
			
			if (is_object($entity_properties) && isset($entity_properties->{PROPERTY_ID}))
			
				$entity_type = $entity_properties->{PROPERTY_ID};
		}
		else
		
			$entity_type = CLASS_ENTITY;

		$field_prefix = PREFIX_TABLE_COLUMN_SNAPSHOT;

		$insert_snapshot = '
			INSERT INTO '.TABLE_SNAPSHOT.'
			SET
				'.$field_prefix.PROPERTY_TYPE.' = ?,
				'.$field_prefix.PROPERTY_STATE.' = ?
		';

		$link = $class_db::getConnection(TRUE);

		$pdo_statement = $link->prepare($insert_snapshot);

		$results = $pdo_statement->execute(
			array(
				$entity_type,
				serialize($context->{PROPERTY_STATE})
			)
		);

		if ($results)

			$last_insertion_id = $link->lastInsertId();

		$snapshot_properties = $class_entity::fetchId(get_class($context));
		
		if (is_object($snapshot_properties) && isset($snapshot_properties->{PROPERTY_ID}))
		
			$snapshot_id = $snapshot_properties->{PROPERTY_ID};
		else
		
			$snapshot_id = 0;

		// return the last insertion id for the logs
		return array(
			PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID => $snapshot_id,
			PREFIX_TABLE_COLUMN_ENTITY_TYPE.PROPERTY_ID => EVENT_TYPE_TAKE_SNAPSHOT,
			PREFIX_TABLE_COLUMN_EVENT.PROPERTY_SOURCE => $last_insertion_id,
			PREFIX_TABLE_COLUMN_EVENT.PROPERTY_TARGET => $entity_type
		);
	}

	/**
	*  Toggle the status of an entity
	*
	* @param	string	$entity_id		entity identifier
	* @param	string	$entity_type	entity type	
	* @param	string	$entity_status	entity status

	* @return	nothing
	*/
	public static function toggleStatus($entity_id, $entity_type = ENTITY_LINK, $entity_status = null)
	{
		$class_db = CLASS_DB;

		if (!is_numeric($entity_id))

			throw new Exception(EXCEPTION_INVALID_ARGUMENT);
		
		switch ($entity_type)
		{
			case ENTITY_EMAIL:

				$column_update_time = 'out_date_update';
				$column_status = 'out_status';
				$table = TABLE_OUTGOING;
				$where_clause = "\n".'out_id = '.$entity_id;

					break;

			case ENTITY_QUERY:

				$column_update_time = 'qry_date_update';
				$column_status = 'qry_status';
				$table = TABLE_QUERY;
				$where_clause = "\n".'qry_id = '.$entity_id;

					break;

			default:
			case ENTITY_LINK:

				$column_update_time = 'lnk_date_update';
				$column_status = 'lnk_status';
				$table = TABLE_LINK;
				$where_clause = "\n".'lnk_id = '.$entity_id;

					break;
		}

		$select_entity_status = '
			SELECT
				'.$column_status.' status
			FROM
				'.$table.'
			WHERE'.
				$where_clause
		;

		$update_entity_status_model = '
			UPDATE '.$table.' SET
				'.$column_status.' = {value},
				'.$column_update_time.' = NOW()
			WHERE'.
				$where_clause
		;
		
		$select_results = $class_db::query($select_entity_status);
		
		if ($select_results->num_rows)
		{
			$status = !($select_results->fetch_object()->status);

			$class_db::query(
				str_replace(
					'{value}',
					!isset($entity_status) ? $status : $entity_status,
					$update_entity_status_model
				)
			);
		}
	}

	/**
	* Update an insight
	*
	* @param	mixed	$context	context
	* @return	mixed	callback parameters
	*/
	public static function updateInsight($context)
	{
		$class_db = CLASS_DB;

		$callback_parameters = NULL;

		$conditions = $context->getProperties();

		$clause_assignments =
			PREFIX_TABLE_COLUMN_INSIGHT_NODE.
			PROPERTY_DATE_MODIFICATION.
			' = NOW()'.
			(
					count($conditions) > 0
				?
					','
				:
					''
			)
		;
		
		$clause_where = '
			WHERE
				'.PREFIX_TABLE_COLUMN_INSIGHT_NODE.PROPERTY_ID.' = '.$conditions->{PROPERTY_ID}
		;
		
		unset($conditions->{PROPERTY_ID});

		$_conditions = (array)$conditions;

		list($last_property) = each($_conditions);

		foreach ($conditions as $property => $value)
		
			$clause_assignments .=
				PREFIX_TABLE_COLUMN_INSIGHT_NODE.$property.
				' = '.
				( is_string($value) ? '"'.$value.'"' : $value ).
				( $property !== $last_property ? ',' : '')
			;

		$update_insight = '
			UPDATE '.TABLE_INSIGHT_NODE.'
			SET '.
			$clause_assignments.
			$clause_where
		;

		$class_db::query($update_insight);
	
		if (
			isset($_SESSION[SESSION_STORE_AFFORDANCE]) &&
			isset($_SESSION[SESSION_STORE_AFFORDANCE][AFFORDANCE_REMOVE_INSIGHT_NODE])
		)

			$callback_parameters = $_SESSION[SESSION_STORE_AFFORDANCE][AFFORDANCE_REMOVE_INSIGHT_NODE];

		return $callback_parameters;
	}
}
