<?php
/**
*************
* Changes log
*
*************
* 2011 03 05
*************
* 
* Implement maximum uid retrieval
*
* method affected ::
*
* DATA FETCHER :: fetchMaxUid
*
* (branch 0.1 :: revision :: 570)
*
*/

/**
* Data Fetcher class
*
* Class for fetching data
* @package  sefi
*/
class Data_Fetcher extends Database
{

    /**
    * Load a photograph
    * 
    * @param    integer	$id identifier
    * @return   mixed
    */
	public static function fetchPhotograph( $id )
	{
		$class_db = CLASS_DB;

		$class_photograph = CLASS_PHOTO;

		$attributes = array();

		$exceptions = '';

        $select_photograph = '
            SELECT
				author_id,
				hash,
                height,
                keywords,
                mime_type,
                original_file_name,
                size,
                title,
                width,
                pht_date_creation,
                pht_date_last_modification,
                pht_status
            FROM
                '.TABLE_PHOTOGRAPH.'
            WHERE
                photo_id = '.$id.' AND
                pht_status != '.PHOTOGRAPH_STATUS_DISABLED
        ;

        $result_photograph = $class_db::query($select_photograph);

		if ($result_photograph->num_rows)
		{
			$photograph = $result_photograph->fetch_object();

			$attributes = new $class_photograph((int)$id);

			$dimensions = array((int)$photograph->width, (int)$photograph->height);

			try
			{
				$attributes->setDimensions($dimensions);
			}
			catch (Exception $setting_exception)
			{
				$exceptions .= $setting_exception;
			}
		
			try
			{
				$attributes->setHash($photograph->hash);
			}
			catch (Exception $setting_exception)
			{
				$exceptions .= $setting_exception;
			}

			try
			{
				$attributes->setKeywords($photograph->keywords);
			}
			catch (Exception $setting_exception)
			{
				$exceptions .= $setting_exception;
			}
			
			try
			{
				$attributes->setSize((int)$photograph->size);                    
			}
			catch (Exception $setting_exception)
			{
				$exceptions .= $setting_exception;
			}
			
			try
			{
				$attributes->setMimeType($photograph->mime_type);
			}
			catch (Exception $setting_exception)
			{
				$exceptions .= $setting_exception;
			}
			
			try
			{
				$attributes->setAuthor((int)$photograph->author_id);
			}
			catch (Exception $setting_exception)
			{
				$exceptions .= $setting_exception;
			}

			try
			{
				$attributes->setStatus((int)$photograph->pht_status);
			}
			catch (Exception $setting_exception)
			{
				$exceptions .= $setting_exception;
			}

			try
			{
				$attributes->setTitle($photograph->title);
			}
			catch (Exception $setting_exception)
			{
				$exceptions .= $setting_exception;
			}
			
			try {
				$attributes->setOriginalFileName($photograph->original_file_name);
			}
			catch (Exception $setting_exception)
			{
				$exceptions .= $setting_exception;
			}

			try
			{
				$attributes->setCreationDate($photograph->pht_date_creation);
			}
			catch (Exception $setting_exception)
			{
				$exceptions .= $setting_exception;
			}
			
			try
			{
				$attributes->setLastModificationDate($photograph->pht_date_last_modification);
			}
			catch (Exception $setting_exception)
			{
				$exceptions .= $setting_exception;
			}
		}

		return $attributes;
	}

	/***
	* Fetch photographs
	* 
	* @param	integer		$member_id			member id
	* @param	boolean		$accept_avatars 	avatars flag
	*
	* @return	resource	photographs results
	*/
	public static function fetchPhotographs(
		$member_id,
		$accept_avatars = FALSE
	)
	{
		global $class_application, $verbose_mode;

		$class_db = $class_application::getDbClass();

		$class_dumper = $class_application::getDumperClass();
		
		$callback_parameters = array();

		$arc_type_visibility = self::getEntityTypeValue(
			array(
				PROPERTY_NAME => ENTITY_VISIBILITY,
				PROPERTY_ENTITY => ENTITY_ARC
			)
		);

        /**
        *
        * Retrieve concepts ids
        * (previously called ids of entities, renamed here to clarify
        * "id of entity types" instead of "entity id of entity types")
        *
        */

        $concept_photograph_id = self::getEntityIdByName(
            ENTITY_PHOTOGRAPH
        );

        $concept_entity_type_id = self::getEntityIdByName(
            ENTITY_ENTITY_TYPE
        );

		$visibility_type_public = self::getEntityTypeId(
			array(
				PROPERTY_NAME => PROPERTY_PUBLIC,
				PROPERTY_ENTITY => ENTITY_VISIBILITY
			)
		);

		$class_dumper::log(
			__METHOD__,
            array(
                '[value of arc type with visibility as name]',
                $arc_type_visibility,
                '[id of public visibility as an entity type concept]',
                $visibility_type_public,
                '[photograph concept id]',
                $concept_photograph_id,
                '[entity type concept id]',
                $concept_entity_type_id                  
            )
		);

        $query_select_photo = '
            SELECT
				author_id,
                photo_id,
				hash,
                height,
                keywords,
                mime_type,
                original_file_name,
                size,
                title,
                width,
                pht_date_creation,
                pht_date_last_modification,
                pht_status
            FROM
                `'.TABLE_PHOTOGRAPH.'`
            WHERE
                ( ' .
                    ( $member_id ? ' author_id = '.$member_id : '' ) . ' OR
                    photo_id IN (
                        SELECT

                            ' . PREFIX_TABLE_COLUMN_EDGE .
								TABLE_ALIAS_PHOTOGRAPH . '.' .
                                    PREFIX_TABLE_COLUMN_EDGE . PROPERTY_KEY . '

                        FROM

                            '. TABLE_ARC .' ' . TABLE_ALIAS_ARC . ', 

                            '. TABLE_EDGE .' ' .
								PREFIX_TABLE_COLUMN_EDGE .
									TABLE_ALIAS_PHOTOGRAPH . ',

                            '. TABLE_EDGE .' ' .
								PREFIX_TABLE_COLUMN_EDGE .
									TABLE_ALIAS_ENTITY_TYPE . '

                        WHERE

                            # active arcs

                            ' . TABLE_ALIAS_ARC . '.' .
								PREFIX_TABLE_COLUMN_ARC . PROPERTY_STATUS .
								
									' = ' . ARC_STATUS_ACTIVE . ' AND

                            # arcs of type visiblity
                            
                            ' . TABLE_ALIAS_ARC . '.' . PREFIX_TABLE_COLUMN_ARC .
								PROPERTY_TYPE .
								
									' = ' . $arc_type_visibility . ' AND

                            # source edges are active

                            ' . PREFIX_TABLE_COLUMN_EDGE . TABLE_ALIAS_PHOTOGRAPH .
								'.' . PREFIX_TABLE_COLUMN_EDGE  . PROPERTY_STATUS .
								
									' = ' . EDGE_STATUS_ACTIVE . ' AND
                                
                            # source edges are of type photograph

                            ' . PREFIX_TABLE_COLUMN_EDGE . TABLE_ALIAS_PHOTOGRAPH . '.' .
								PREFIX_TABLE_COLUMN_EDGE  . PROPERTY_ID .
                                
									' = ' . TABLE_ALIAS_ARC . '.' .
										PREFIX_TABLE_COLUMN_ARC. PROPERTY_SOURCE . '  AND

                            ' . PREFIX_TABLE_COLUMN_EDGE . TABLE_ALIAS_PHOTOGRAPH . '.' .
								PREFIX_TABLE_COLUMN_ENTITY . PROPERTY_ID .
                                
									' = ' . $concept_photograph_id . '  AND

                            # destination edges are active

                            ' . PREFIX_TABLE_COLUMN_EDGE . TABLE_ALIAS_ENTITY_TYPE . '.' .
								PREFIX_TABLE_COLUMN_EDGE  . PROPERTY_STATUS .
                                
									' = ' . EDGE_STATUS_ACTIVE . ' AND

                            # destination edges are of type entity type

                            ' . PREFIX_TABLE_COLUMN_EDGE . TABLE_ALIAS_ENTITY_TYPE . '.' .
								PREFIX_TABLE_COLUMN_EDGE  . PROPERTY_ID .

								    ' =  ' . TABLE_ALIAS_ARC . '.' .
										PREFIX_TABLE_COLUMN_ARC . PROPERTY_DESTINATION . ' AND

                            ' . PREFIX_TABLE_COLUMN_EDGE . TABLE_ALIAS_ENTITY_TYPE . '.' .
								PREFIX_TABLE_COLUMN_ENTITY . PROPERTY_ID .
                                
								    ' = ' . $concept_entity_type_id . '  AND                                

                            # visibility level is public

                            ' . PREFIX_TABLE_COLUMN_EDGE . TABLE_ALIAS_ENTITY_TYPE . '.' .
								PREFIX_TABLE_COLUMN_EDGE  . PROPERTY_KEY .
                            
								    ' = ' . $visibility_type_public . '
                    )
                )
                AND pht_status != '.PHOTOGRAPH_STATUS_DISABLED.
                (
                    $accept_avatars === FALSE
                ?
                    ' AND pht_status != '.PHOTOGRAPH_STATUS_AVATAR
                :
                    ''
                ).'
            ORDER BY
                photo_id
            DESC
        ';

		$photographs_results = $class_db::query( $query_select_photo );
		
		
		if ( $author_id == 3 )
echo $query_select_photo ;		
		if (
			is_object( $photographs_results ) &&
			get_class( $photographs_results ) === CLASS_MYSQLI_RESULT
		)
		{
			if ( $photographs_count = $photographs_results->num_rows )

				for ( $k = $photographs_count ; $k > 0 ; $k-- )

					$callback_parameters[] = $photographs_results->fetch_object();

			$photographs_results->free_result();
		}
		$class_dumper::log(
			__METHOD__,
			array($callback_parameters),
			TRUE
		);
		return $callback_parameters;
	}

    /**
    * Fetch the properties of insight node
    * 
    * @param	mixed	$context		context
    * @param	mixed	$entity_type	kind of entity
    * @param	boolean	$wrap			wrap flag
    * @param	boolean	$verbose		verbose mode
    * @param	mixed	$checksum 		checksum
    * @return	mixed
	*/
	public static function &fetchProperties(
		$context,
		$entity_type = NULL,
		$wrap = FALSE,		
		$verbose = FALSE,
		$checksum = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$class_db = $class_application::getDbClass();

		$meta_properties = 
		$original_aliases =
		$properties = array();

		switch ($entity_type)
		{
			case CLASS_ENTITY:

				if ( is_numeric($context) && !empty($context) )
				{
					$select_id = '
						SELECT
							'.PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_NAME.' '.PROPERTY_ENTITY_NAME.'
						FROM
							'.TABLE_ENTITY.'
						WHERE
							'.PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID.' = '.$context
					;

					$results = $class_db::query($select_id, TRUE);

					if ($results->rowCount() == 1)

						$properties = $results->fetchObject();
				}
				else if ( is_array( $context ) && count( $context ) )
				{
					$clause_where = SQL_ANYWHERE;

					while ( list( $name, $value ) = each($context) )

						// prevent meta properties like namespace to be used
						if ( ! is_array( $value ) )

							$clause_where .=
								' '.SQL_AND.' '.
								PREFIX_TABLE_COLUMN_ENTITY.$name.
								' = '.
								( is_numeric($value) ? $value : '"'.$value.'"' )
							;
						else

							$meta_properties[$name] = $value;

					$column_entity_id =
						PREFIX_TABLE_COLUMN_ENTITY.
							PROPERTY_ID
					;

					$column_entity_name =
						PREFIX_TABLE_COLUMN_ENTITY.
							PROPERTY_NAME
					;

					$alias_entity_id = PROPERTY_ID;

					$alias_entity_name = PROPERTY_ENTITY_NAME;

					$select_matching_items = '
						SELECT
							'.$column_entity_id.' '.$alias_entity_id.',
							'.$column_entity_name.' '.$alias_entity_name.'
						FROM
							'.TABLE_ENTITY.'
						WHERE
							'.$clause_where
					;

					$class_dumper::log(
						__METHOD__,
						array(
							'select matching items ',
							$select_matching_items
						)
					);

					$results = $class_db::query($select_matching_items, TRUE);

					if ( $results->rowCount() )
					{
						if ($results->rowCount() == 1)
						
							$properties = $results->fetchObject();
						else 

							while ($result = $results->fetchObject())
	
								$properties[] = $result;
					}
					
					if ( count($meta_properties) )
					
						$properties =
							(object)
							array_merge(
									is_object($properties)
								?
									(array) $properties
								:
									$properties
								,
								$meta_properties
							)
						;
				}

					break;
			
			default:

				if (
					! is_null( $entity_type ) &&
					class_exists( $entity_type )
				)

					$configuration = $entity_type::getConfiguration();
				else
				
					throw new Exception(
						EXCEPTION_DEVELOPMENT_BEHAVIORAL_DEFINITION_MISSING
					);

				if (
					isset($configuration[ENTITY_COLUMN_PREFIX]) && 
					isset($configuration[ENTITY_TABLE])
				)
				{
					$clause_where = SQL_ANYWHERE;

					$clause_group_by =
					$clause_select = '';

					$column_prefix = $configuration[ENTITY_COLUMN_PREFIX];

					$table_alias = substr($configuration[ENTITY_COLUMN_PREFIX], 0, -1);

					$table = $configuration[ENTITY_TABLE];

					if (
						isset($context[SQL_SELECT]) &&
						is_array($context[SQL_SELECT]) &&
						( $columns = $context[SQL_SELECT] ) != FALSE
					)
					{
						end($columns);
						list($last_alias) = each($columns);
						reset($columns);

						while ( list( $alias, $column ) = each( $columns ) )
						{
							$_column =

							$_parameter = NULL;

							if ( is_numeric( $alias ) )
							{
								$index = $alias;
								$alias = $column;
							}
							else if (isset($index))

								$index = $alias;

							// prevent MySQL keywords from being used as aliases
							if (
								in_array(
									strtolower( $alias ),
									$class_db::getSQLKeywords()
								)
							)
							{
								$original_aliases[$alias.'_'] = $alias;

								$alias = $alias.'_';
							}

							if (
								is_array( $column ) &&
								(
									isset( $column[PROPERTY_FOREIGN_KEY] ) &&
									( $_column = $column[PROPERTY_FOREIGN_KEY] )
									||
									isset( $column[PROPERTY_RETURN] ) &&
									( $_return = $column[PROPERTY_RETURN] )
								)
							)
							{
								// check is a parameter is to be passed
								// to a function
								if (
									isset( $column[PROPERTY_RETURN] ) &&
									( $_return = ' '.$column[PROPERTY_RETURN] ) &&
									isset( $column[PROPERTY_PARAMETER] ) &&
									( $_parameter = $column[PROPERTY_PARAMETER] )
								)

									$selected_column =
										$_return.'('.
											$table_alias.'.'.
												$column_prefix.
													$_parameter.
										')';

								// set a foreign key column selection
								if (
									isset( $column[PROPERTY_FOREIGN_KEY] ) &&
									( $_column = $column[PROPERTY_FOREIGN_KEY] )
								)
								{
									// select a foreign key
									$selected_column = $table_alias.'.'.$_column;
									
									if (
										isset( $column[PROPERTY_RETURN] ) &&
										( $_return = $column[PROPERTY_RETURN] ) &&
										! isset( $column[PROPERTY_PARAMETER] )
									)

										// pass a foreign key as a parameter
										$selected_column =
											$_return.'('.$table_alias.'.'.$_column.')';
								}
								else if ( ! isset( $column[PROPERTY_PARAMETER] ) )

									// pass a native table column as parameter
									// to a function
									$selected_column =
										$_return.'('.
											$table_alias.'.'.
											$column_prefix.$alias.
										')'
									;
							}
							else
							
								$selected_column = $table_alias.'.'.$column_prefix.$column;

							$clause_select .=
								$selected_column.' '.$alias.
								(
									(
										isset($index)
									?
										$index
									:
										$alias
									) !== $last_alias
								?
									', '."\n"
								:
									''
								)
							;
						}

						$selection = $context[SQL_SELECT];

						unset($context[SQL_SELECT]);
					}
					else if (
						is_array( $context ) &&
						isset( $context[SQL_ANY] )
					)
					{
						$clause_select = SQL_ANY;

						$selection = $context[SQL_ANY];

						unset($context[SQL_ANY]);
					}

					if (
						is_array( $context ) &&
						isset( $context[SQL_GROUP_BY]) 
					)
					{
						if (
							! is_array( $context[SQL_GROUP_BY] ) ||
							! count( $context[SQL_GROUP_BY] )
						)
						
							unset( $context[SQL_GROUP_BY] );
						else
						{
							while (
								list( $index )
								   = each( $context[SQL_GROUP_BY] )
							)
							{
								if (
									! is_array( $context[SQL_GROUP_BY][$index] )
								)
	
									$context[SQL_GROUP_BY][$index] =
										$table_alias.'.'.
											$column_prefix.
												$context[SQL_GROUP_BY][$index];
								else if (
									is_array( $context[SQL_GROUP_BY][$index] ) &&
									(
										list( $_value, $_name) =
										each( $context[SQL_GROUP_BY][$index] )
									) &&
									$_name == PROPERTY_FOREIGN_KEY &&
									trim( strlen( $_value ) ) > 0
								)
								{
									$context[SQL_GROUP_BY][$index] =
										$table_alias.'.'.$_value;
								}
								

							}

							$clause_group_by .= ' '.SQL_GROUP_BY.' '.implode(
								','."\n",
								$context[SQL_GROUP_BY]
							);
			
							unset( $context[SQL_GROUP_BY] );
						}
					}	

					if (
						is_array( $context ) &&
						( $conditions = $context ) !== FALSE
					)
					{
						while ( list( $name, $value ) = each( $conditions ) )
						{
							if (
								$name != PROPERTY_FOREIGN_KEY &&
								! is_array( $value ) 
							)

								$clause_where .=
									SQL_AND.
									$table_alias.'.'.$column_prefix.$name.' = '.
									( is_numeric($value) ? $value : '"'.$value.'"' )
								;

							else if (
								is_array( $value ) &&
								( FALSE !== ( list($_name, $_value) = each( $value ) ) )
							)
	
								$clause_where .=
									SQL_AND.
									$table_alias.'.'.
									
									// check if a condition of type IN is defined
									(
											$_name !== SQL_IN 
										?
										
											// integrate a foreign key condition
											$_name.' = '.
											(
													is_numeric($_value)
												?
													$_value
												:
													'"'.$_value.'"'
											)
										:
										(
											// integrate a condition of type IN
											
												is_array( $_value) &&
												count( $_value )
											?
												$column_prefix.$name.' '.
												SQL_IN.
												' ( '.
													implode( ',', $_value ).
												' ) '
											:
												''
										)
									);

								;
						}
					}

					$select_entity = 
						SQL_SELECT.' '.
							(
								$clause_select != SQL_ANY
							?
								'"'.
								// prevent backslashes from being escaped
								preg_replace(
									'/[\\\\](?![\\\\])/',
									'\\\\\\\\',
									$entity_type
								).'" '.
								PROPERTY_ENTITY_NAME.
								(
									!empty($clause_select)
								?
									','
								:
									''
								)
							:
								''
							).
							$clause_select.' '.
						SQL_FROM.' '.
							$table.' '.$table_alias.' '.
						SQL_WHERE.' '.
							$clause_where.
							
							$clause_group_by
					;

					$class_dumper::log(
						__METHOD__,
						array($select_entity)
					);

					$results = $class_db::query($select_entity);

					$restore_aliases = function (&$container, $aliases)
					{
						if (count($aliases) > 0)
						
							while (list($temporary, $original) = each($aliases))
							{
								$container->$original = $container->$temporary;
					
								unset($container->$temporary);
							}
	
						reset($aliases);
					};

					if ( $results->num_rows )
					{
						if ( $results->num_rows > 1 )
						{
							// check if a primary key could be used for indexation
							if (
								isset( $selection ) &&
								is_array( $selection) &&
								isset( $selection[PROPERTY_ID] )
							)

								while ($result = $results->fetch_object())
								{
									$restore_aliases($result, $original_aliases);
							
									$properties[$result->{PROPERTY_ID}] = $result;
								}
							else 
	
								while ($result = $results->fetch_object())
								{
									$restore_aliases($result, $original_aliases);
							
									$properties[] = $result;
								}
						}
						else
						{
							$properties = $results->fetch_object();

							$restore_aliases( $properties, $original_aliases );
						}
					}
				}
		}

		return $properties;
	}

	/**
	* Call magically a non-declared static method 
	*
	* @param	string	$name			name of magic static method
	* @param	array	$arguments		arguments
	* @return	nothing
	*/	
	public static function __callStatic($name, $arguments)
	{
		$callback_parameters = NULL;

		$class_dumper = CLASS_DUMPER;

		$class_entity = CLASS_ENTITY;

		$class_parser = CLASS_PARSER;

		$exception_details = '';

		self::checkStaticParameters($entity_type);

		$operation_get_entity_type = str_replace('.', '', ACTION_GET_ENTITY_TYPE);

		if ( FALSE !== strpos(strtolower($name), $operation_get_entity_type) )
		{
			$property = substr($name, strlen($operation_get_entity_type));

			if (
				is_array($arguments) &&
				isset($arguments[0]) &&
				is_array($arguments[0])
			)
			{
				array_unshift($arguments, strtolower($property));

				$method_name = $class_parser::translate_entity(
					ACTION_GET_TYPE.'.'.PROPERTY_PROPERTY,
					ENTITY_NAME_METHOD
				);

				if (in_array($method_name, get_class_methods($class_entity)))
	
					$callback_parameters = call_user_func_array(
						array(
							$entity_type,
							$method_name
						),
						$arguments
					);
				else

					$exception_details .=
						': '.
						sprintf(
							EXCEPTION_DEVELOPMENT_CLASS_METHOD_REQUIRED,
							$method_name,
							$entity_type
						)
					;
			}
			else
			
				throw new Exception(
					EXCEPTION_INVALID_ARGUMENT.': '.
					EXCEPTION_EXPECTATION_ARRAY
				);
		}

		if ( ! isset( $callback_parameters ) )

			return $class_entity::__callStatic(
				str_replace(CLASS_ENTITY, '', $name),
				$arguments
			);

		return $callback_parameters;
	}

    /**
    * Check credentials
    *
    * @param	array	$proofs				credentials
    * @param	mixed	$credential_type	type of credentials
    * @return	boolean	creentials validity
    */
	public static function checkCredentials(
		$proofs = NULL,
		$credential_type = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_db = $class_application::getDbClass();;
		
		if ( ! is_array( $proofs ) || count( $proofs ) != 2 )

			throw new Exception(
				EXCEPTION_RIGHTS_MANAGEMENT_CREDENTIALS_INSUFFICIENT
			);

		$select_credentials = '
			SELECT
				usr_id,
				usr_user_name
			FROM
				'.TABLE_PRIVILEGE.' p
			LEFT JOIN
				'.TABLE_USER.' u
			ON
				sha1( u.usr_id ) = p.prv_hash
			WHERE
				p.usr_passwd = "'.$proofs['response'].'" AND
				sha1( u.usr_user_name ) = "'.$proofs['identity'].'"
		';
		
		$results = $class_db::query( $select_credentials );
		
		if ( $results->num_rows )

			return $results->fetch_object();
		else

			return false;
	}

    /**
    * Check a link
    * 
    * @param	string	$link			link
    * @param	boolean	$query_attached	query attached
    * @return	array	link properties
	*/
	public static function checkLink($link, $query_attached = TRUE)
	{
		$class_db = CLASS_DB;

		$select_link_properties = '
			SELECT
				lnk_id link_id,
				lnk_status link_status,
				qry_id query_id,
				qry_value query
			FROM
				'.TABLE_LINK.'
			LEFT JOIN
				'.TABLE_QUERY.'
			USING
				(qry_id)
			WHERE
				TO_DAYS(lnk_date_creation) - TO_DAYS(NOW()) <= 1 AND
				lnk_value LIKE "%'.$link.'%" AND
				qry_status = '.QUERY_STATUS_INACTIVE.'
		';

		$results = $class_db::query($select_link_properties);
		
		if ($results->num_rows)

			return $results;
		else
		
			return false;
	}

    /**
    * Check predicates
    *
    * @param	array		$records 		values
    * @param	array		$mapping_cue 	mapping cue
    * @param	array		$data_types 	data types
    * @param	array		$selected_rows 	rows to be selected
    * @param	object		$domain	domain
    * @return	array		results
	*/
	public static function checkPredicates(
		$records,
		$mapping_cue,
		$data_types = null,
		$selected_rows,
		$domain = null
	)
	{
		global $class_application;

		$class_lsql = $class_application::getLsqlClass();

		$results = array();

		// declare the default select clause 
		$select_clause = SQL_ANY;

		// set the query model
		$select_query_model = "
			SELECT
				{select_clause}
			FROM
				{table}
			WHERE
				{where_clause}
		";

		// declare the default where clause
		$where_clause = SQL_ANYWHERE;

		// check the mapping cue
		if (
			is_array($mapping_cue) &&
			count($mapping_cue) != 0
		)
		{
			// set an empty where clause
			$where_clause = CHARACTER_EMPTY_STRING;

			if (
				is_array($selected_rows) &&
				count($selected_rows) != 0
			)
			{
				// set an empty select clause
				$select_clause = CHARACTER_EMPTY_STRING;

				// get the last item of the select rows
				end($selected_rows);
				list($last_row_alias, $last_row_name) = each($selected_rows);
				reset($selected_rows);

				while (list($row_alias, $row_name) = each($selected_rows))
				{
					$select_clause .= $row_name;

					if (is_string($row_alias))
						$select_clause .= CHARACTER_BLANK.SQL_ALIAS.CHARACTER_BLANK.$row_alias;

					if ($row_name != $last_row_name)
						$select_clause .= CHARACTER_COMMA.CHARACTER_NEW_LINE;
				}				
			}

			while (list($table, $rows) = each($mapping_cue))
			{
				if (
					is_array($rows) &&
					count($rows) != 0
				)
				{
					// set the default delimiters
					$end_delimiter = 
					$start_delimiter = CHARACTER_EMPTY_STRING;

					// check the records
					if (
						!is_array($records) &&
						count($records) == 0 ||
						count($records) != count($rows)
					)
						throw new Exception(EXCEPTION_INCONSISTENT_RECORDS);

					// check the data types
					if (
						!is_array($data_types) ||
						count($data_types) == 0 ||
						count($data_types) != count($rows)
					)

						// throw an exception
						throw new Exception(EXCEPTION_UNDEFINED_DATA_TYPES);

					// get the last item of the mapping cue
					end($rows);
					list($last_target, $last_value_name) = each($rows);
					reset($rows);

					// loop on the rows
					while (list($target, $value_name) = each($rows))
					{
						// check the data types
						switch ($data_types[$target])
						{
							case DATA_TYPE_LITERAL: 

								$end_delimiter = 
								$start_delimiter = SQL_QUOTE;							

									break;
						}

						// append a string to the where clause
						$where_clause .=
							$target.
								CHARACTER_BLANK.SQL_EQUAL.CHARACTER_BLANK.
							$start_delimiter.$records[$value_name].$end_delimiter;

						if ($target != $last_target)
							$where_clause .= CHARACTER_BLANK.SQL_AND.CHARACTER_NEW_LINE;
					}
				}

				$patterns =	array(
						REGEXP_OPEN.
							REGEXP_ESCAPE.CHARACTER_BRACKET_START.
							"select_clause".
							REGEXP_ESCAPE.CHARACTER_BRACKET_END.
						REGEXP_CLOSE,
						REGEXP_OPEN.
							REGEXP_ESCAPE.CHARACTER_BRACKET_START.
							"where_clause".
							REGEXP_ESCAPE.CHARACTER_BRACKET_END.
						REGEXP_CLOSE,
						REGEXP_OPEN.
							REGEXP_ESCAPE.CHARACTER_BRACKET_START.
							"table".
							REGEXP_ESCAPE.CHARACTER_BRACKET_END.
						REGEXP_CLOSE						
					);

					// check the domain argument
					if (
						isset($domain) &&
						(
							is_object($domain) ||
							is_array($domain)
						)
					)
					{
						// check if the domain argument is an array
						if (is_array($domain))
						{
							$scope[0] = SQL_ANY;
							$scope[1] = "";
							$scope[2] = $table;
	
							end($domain);
							list($last_domain_index, $last_domain) = each($domain);
							reset($domain);
	
							// loop on the domain argument
							while (list($domain_index, $domain_object) = each($domain))
							{
								$scope[1] .=
									$domain_object->{PROPERTY_DOMAIN}." ".SQL_EQUAL." ".
										(
											is_string($domain_object->{PROPERTY_IMAGE})
										?
											SQL_QUOTE
										:
											''
										).
										$domain_object->{PROPERTY_IMAGE}.
										(
											is_string($domain_object->{PROPERTY_IMAGE})
										?
											SQL_QUOTE
										:
											''
										).										
										(
											$domain_index != $last_domain_index
											?
												" ".SQL_OR." \n"
											:
												''
										)
								;
							}

							// replace patterns in the select query model
							$select_scope = preg_replace(
								$patterns,
								$scope,
								$select_query_model
							);
						}
					}

				// check the selected scope 
				if (isset($select_scope))

					// set the table
					$table = "(".$select_scope.") ".SQL_ALIAS." SCOPE";

				// set the replacements
				$replacements = array(
					$select_clause,
					$where_clause,
					$table
				);

				// replace patterns in the select query model
				$select_query = preg_replace(
					$patterns,
					$replacements,					
					$select_query_model
				);

				// set results
				$results[$table] = $class_lsql::query(
					$select_query,
					false,
					DEBUGGING_DISPLAY_QUERY_CHECK_PREDICATES,
					false,
					__METHOD__
				);
			}
		}

		return $results;
	}

    /**
    * Check records
    *
    * @param	mixed 	$value				value
    * @param	string	$data_type			data type
    * @param	string 	$record_references	record references
    * @param	integer	$key				key
    * @return	array	results
	*/
	public static function checkRecords(
		$value,
		$data_type,
		$record_references,
		$key = NULL
	)
	{
		$class_member = CLASS_MEMBER;

		$class_user_handler = CLASS_USER_HANDLER;

		$pattern =
			REGEXP_OPEN.
				SHORTHAND_DATABASE.
				REGEXP_ESCAPE.'.'.
				REGEXP_CATCH_START.
				REGEXP_WILDCARD_LITERAL.REGEXP_ANY.
				REGEXP_CATCH_END.
				REGEXP_ESCAPE.'.'.
				REGEXP_CATCH_START.
				REGEXP_WILDCARD_LITERAL.REGEXP_ANY.
				REGEXP_CATCH_END.						
			REGEXP_CLOSE
		;

		$match = preg_match(
			$pattern,
			$record_references,
			$matches
		);

		// set the select comparison query model
		$select_comparison_model = "
			SELECT
				count(*) AS ".PROPERTY_MATCH."
			FROM
				{table}
			WHERE
				{primary_key} = {key} AND
				{value} = {column}
		";

		// check  the match
		if (
			$match &&
			!empty($matches[1]) &&
			!empty($matches[2])
		)
		{
			// get the database
			$database = DB_SEFI;

			// set the field name		
			$field_name = $matches[2];

			// set the store name
			$store_name = $matches[1];

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

			// set a table name
			$table = constant(strtoupper(PREFIX_TABLE.$store_name));

			// get table constraints
			$constraints = self::getTableConstraints($table, $database);

			// switch from the data type	
			switch (strtolower($data_type))
			{
				case FIELD_TYPE_EMAIL:
				case FIELD_TYPE_TEXT:
					
					$value = "'".$value."'";

						break;

				case FIELD_TYPE_PASSWORD:

					$value = "'".sha1($value)."'";

						break;
			}

			// check if a user is logged in
			if (empty($key))
			{
				$search = array(
					'{column}',
					'{primary_key}',
					'{table}',
					'{value}',
					'{key}'
				);

				$replace = array(
					$column,
					$constraints[0]->{PROPERTY_TARGET},
					$table,
					$value
				);

				if ($class_user_handler::loggedIn())
				{
					// get the qualities of the logged in member 
					$qualities = $class_member::getQualities();
					
					$replace[] = $qualities->{ROW_MEMBER_IDENTIFIER};
				}
				else

					$replace[] = $constraints[0]->{PROPERTY_TARGET};


				// set the select comparison query
				$select_comparison = str_replace(
					$search,
					$replace,
					$select_comparison_model
				);
			}

			// execute the select comparison query
			$results = DB::query($select_comparison);

			// check the results
			if ($results->num_rows)
			{
				$comparison = $results->fetch_object();
				
				return $comparison->{PROPERTY_MATCH};
			}
			else
			
				return false;
		}
		else
			return false;
	}

    /**
    * Get a document
    *
    * @param	integer	$document_type	document type
    * @param	integer	$id				identifier
    * @param	string	$parent			parent
    * @param	integer	$start			starting value
    * @param	integer	$limit			limit
    * @param	boolean	$next			next flag
    * @param	boolean	$last_attempt	last attempt flag
    * @return 	nothing
    */
	public static function fetchDocument(
		$document_type = DOCUMENT_TYPE_RDF,
		$id = NULL,
		$parent = NULL,
		$start = 0,
		$limit = PAGINATION_COUNT_PER_PAGE_DOCUMENT,
		$next = FALSE,
		$last_attempt = FALSE
	)
	{
		// set the application class name
		global $class_application;

		// set the db class name
		$class_db = $class_application::getDbClass();

		// set the optimizer class name
		$class_optimizer = $class_application::getOptimizerClass();

		// set the default document content
		$document = '';

		switch ( $document_type )
		{
			case DOCUMENT_TYPE_RDF:

				// get relative resources
				$get_relative_resources = TRUE;				
				if (
					$id == NULL &&
					isset( $_GET[GET_DOCUMENT_INDEX_REWRITTEN] )
				)

					// set a feed identifier
					$feed_identifier = $_GET[GET_DOCUMENT_INDEX_REWRITTEN];

				else if ( $id != NULL )

					// set a feed identifier
					$feed_identifier = $id;
				else 
				
					// set the default feed identifier
					$feed_identifier = 1;

				// set a statement
				$select_contents = "
					SELECT
						sn_contents
					FROM
						".TABLE_SERIALIZATION."
					WHERE
						sn_id = ?
				";
				
				// get a SQL link
				$link = $class_db::getLink();
				
				// prepare a statement    
				$statement = $link->prepare( $select_contents );
				
				// bind parameters to a statement
				$statement->bind_param(
					MYSQLI_STATEMENT_TYPE_INTEGER,
					$feed_identifier
				);
				
				// bind parameters to a statement
				$statement->bind_result($contents);
				
				// execute a statement
				$execution_result = $statement->execute();
				
				// fetch result of a statement
				$fetch_result = $statement->fetch();
				
				// close the current statement
				$statement->close();

					break;

			case DOCUMENT_TYPE_XHTML:

				$active_status = DOCUMENT_STATUS_ACTIVE;

				// get relative resources
				$get_relative_resources = TRUE;
				
				if ( isset( $_GET[GET_DOCUMENT_IDENTIFIER_REWRITTEN] ) )

					// set the default feed identifier
					$feed_identifier = $id;

				else if (
					$id == NULL &&
					isset( $_GET[GET_DOCUMENT_INDEX_REWRITTEN] )
				)
				
					// set a feed identifier
					$feed_index = $_GET[GET_DOCUMENT_INDEX_REWRITTEN];
				
				// check the id argument
				else if (
					$id != NULL &&
					$parent != NULL &&
					is_numeric( $parent )
				)
				
					// set the default feed identifier
					$feed_index = $id;

				// check the id argument
				else if ( $id != NULL )

					// set the default feed identifier
					$feed_identifier = $id;

				else if ( $parent == NULL )

					// set the default feed identifier
					$feed_index = 1;


				// check if a parent has been defined				
				if ( $parent == NULL && isset( $_GET[GET_PARENT_IDENTIFIER_REWRITTEN] ) )
				
					// set a feed identifier
					$feed_parent = rawurldecode( $_GET[GET_PARENT_IDENTIFIER_REWRITTEN] );
				
				// check the parent argument
				else if ( $parent !== NULL && is_integer( $parent ) )
				
					// set the default feed identifier
					$feed_parent = $parent;

				else if ( is_integer( $parent ) )
					
					// set the default feed identifier
					$feed_parent = 'my-items-all';

				// check the next and last attempt flag 
				if ( $next && $last_attempt )

					$class_application::jumpTo( PREFIX_ROOT );

				else if ( $next )
				{
					if ( isset( $feed_index ) )
	
						$where_clause = '
							WHERE
								fd_status = ? AND
								fd_index => ? AND
								fd_parent_id = ?
						';
	
					else if ( $parent == NULL )
	
						$where_clause = '
							WHERE
								fd_status = ? AND
								fd_id > ?
						';

					else if ( is_string( $parent ) )

						$where_clause = '
							WHERE
								fd_status = ? AND
								fd_id = ?
						';
				}
				else
				{
					if ( isset( $feed_index ) )
	
						$where_clause = '
							WHERE
								fd_status = ? AND
								fd_index = ? AND
								fd_parent_id = ?
						';
	
					else if ( $parent == NULL )
	
						$where_clause = '
							WHERE
								fd_status = ?
						';

					else if ( is_string( $parent ) )

						$where_clause = '
							WHERE
								fd_status = ? AND
								fd_id = ?
						';					
				}

				// set a statement
				$select_contents = '
					SELECT
						SQL_CACHE
						fd_contents
					FROM
						'.TABLE_FEED
				;

				if ( ! is_string( $parent ) )

					// set the limit clause
					$limit_clause = '
						LIMIT '.$start.','.$limit.'
					';

				else
					$limit_clause = '';
 
				// get a SQL link
				$link = $class_db::getLink();
		
				// prepare a statement    
				$statement = $link->prepare(
					$select_contents.$where_clause.$limit_clause
				);

				if ( isset( $feed_index ) )

					// bind parameters to a statement
					$statement->bind_param(
						MYSQLI_STATEMENT_TYPE_INTEGER.
							MYSQLI_STATEMENT_TYPE_INTEGER.
								MYSQLI_STATEMENT_TYPE_STRING,
						$active_status,
						$feed_index,
						$feed_parent
					);

				else if ( $parent == NULL )

					// bind parameters to a statement
					$statement->bind_param(
						MYSQLI_STATEMENT_TYPE_INTEGER,
						$active_status
					);

				else if ( $feed_identifier && is_string( $parent ) )

					// bind parameters to a statement
					$statement->bind_param(
						MYSQLI_STATEMENT_TYPE_INTEGER.
							MYSQLI_STATEMENT_TYPE_INTEGER,
						$active_status,
						$feed_identifier
					);

				// bind parameters to a statement
				$statement->bind_result( $document );

				$class_optimizer::startTimer();

				// execute a statement
				$execution_result = $statement->execute();

				// fetch result of a statement
				$fetch_result = $statement->fetch();

				if ( ! $fetch_result && ! $next )
				{
					list( $_document_type, $_id ) = func_get_args();					

					$document = call_user_func(
						__METHOD__,
						$_document_type,
						$_id,
						$parent,
						$start,
						$limit,
						true,
						false
					);
				}
				else if ( ! $fetch_result && $next )
				{
					list( $_document_type, $_id ) = func_get_args();					
					
					$document = call_user_func(
						__METHOD__,
						$_document_type,
						$_id,
						$parent,
						$start,
						$limit,
						true,
						true
					);
				}

				// close the current statement
				$statement->close();
				$class_optimizer::logResults(true);
				
					break;
		}

		return $document;
	}

    /**
    * Fetch emails
    * 
    * @param	integer	$email_type	email type
    * @return	array	emails
	*/
	public static function fetchEmails( $email_type = EMAIL_TYPE_UNSENT )
	{
		global $class_application, $verbose_mode;

		$class_db = $class_application::getDbClass();

		$emails = array();

		$select_emails = '
			SELECT
				out_id id,
				out_value serialization
			FROM
				'.TABLE_OUTGOING.'
			WHERE
				TO_DAYS( NOW() ) - TO_DAYS( out_date_creation ) <= 1 AND
				out_status = '.$email_type
		;
		
		$email_results = $class_db::query( $select_emails );
		
		if ( $email_results->num_rows )

			while ( $email = $email_results->fetch_object() )

				$emails[$email->id] = unserialize(
					base64_decode($email->serialization)
				);
		
		return $emails;
	}

	/**
	* Get the default type of an entity
	*
	* @param	string	$entity_type			type of entity
	* @return	mixed	default type
	*/	
	public static function fetchEntityDefaultType( $entity_type = NULL )
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$default_entity_type = NULL;

		$entity_types = self::fetchEntityTypes( $entity_type );

		if ( is_array( $entity_types) && count( $entity_types ) > 0 )
		{
			list($id, $default_entity_type) = each($entity_types);

			while (
				!$default_entity_type->{PROPERTY_DEFAULT.'_'.PROPERTY_VALUE} &&
				(list($id, $default_entity_type) = each($entity_types))
			);
		}

		return $default_entity_type;
	}

	/**
	* Get the default type of an entity
	*
	* @param	string	$entity_type	type of entity
	* @param	string	$index			index	
	* @return	mixed	default type
	*/	
	public static function fetchEntityTypes(
		$entity_type = CLASS_ENTITY,
		$index = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$class_entity_type = $class_application::getEntityTypeClass();

		$entity_types = array();

		$properties = self::fetchProperties(
			array(
				SQL_SELECT => array(
					PROPERTY_DEFAULT.'_'.PROPERTY_VALUE => PROPERTY_DEFAULT,
					PROPERTY_DESCRIPTION,
					PROPERTY_ID,
					PROPERTY_NAME,
					PROPERTY_VALUE
				),
				PROPERTY_STATUS => ENTITY_TYPE_STATUS_ACTIVE,
				PROPERTY_FOREIGN_KEY => array(
					PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID =>
						self::getByName( $entity_type )->{PROPERTY_ID}
				)
			),
			$class_entity_type
		);

		if ( is_object( $properties ) )
		{
			if ( is_null( $index ) && isset( $properties->{PROPERTY_ID} ) )

				$entity_types[$properties->{PROPERTY_ID}] = $properties;

			else if ( ! is_null($index) && isset( $properties->$index ) )

				$entity_types[$properties->$index] = $properties;
		}
		else if ( is_array( $properties ) && is_null( $index ) )

			$entity_types = $properties;

		else if ( is_array( $properties ) )

			while ( list( $name, $property) = each( $properties ))

				$entity_types[$property->$index] = $property; 

		return $entity_types;
	}
	
    /**
    * Fetch field value
    * 
    * @param	string		$field_name		field name
    * @param	string		$store_name		store name
    * @param	integer		$storage_model	storage model
    * @return	string
	*/
	public static function fetchFieldValue(
		$field_name,
		$store_name,
		$storage_model = STORE_DATABASE
	)
	{
		global $class_application, $verbose_mode;

		// set the Db class name
		$class_db = $class_application::getDbClass();

		// set the member class name
		$class_member = $class_application::getMemberClass();

		// set the user handler class name
		$class_user_handler = $class_application::getUserHandlerClass();

		// set the default field value
		$field_value = '';

		// swith from the storage model
		switch ( $storage_model )
		{
			case STORE_DATABASE:

				// check if a user is logged in
				if ( $class_user_handler::loggedIn() )
				{
					// get a link
					$link = $class_db::getLink();

					// get the qualities of the logged in member 
					$qualities = $class_member::getQualities();

					// set a column name
					$column_name =
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

					// set a table name
					$table_name = constant(
						strtoupper( PREFIX_TABLE.$store_name )
					);
	
					// set a select query
					$select_query = "
						SELECT
							$column_name
						FROM
							$table_name
						WHERE
							usr_id = ?
					";

					// prepare a query
					$statement = $link->prepare( $select_query );
				
					// bind parameters to a statement
					$statement->bind_param(
						MYSQLI_STATEMENT_TYPE_INTEGER,
						$qualities->{ROW_MEMBER_IDENTIFIER}
					);

					// bind results to the statement			
					$statement->bind_result($field_value);		

					// execute a statement
					$execution_result = $statement->execute();
			
					// fetch result of a statement
					$fetch_result = $statement->fetch();
				}

					break;
		}

		// return a field value		
		return $field_value;
	}

	/**
	* Fetch foreign objects
	* 
	* @param	object	$instance	reference instance
	* @param	array	$properties	dereferencing properties
	* @param	array	$conditions	dereferencing conditions
	* @return	mixed
	*/
	public static function fetchForeignObjects(
		$instance,
		$properties = NULL,
		$conditions = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$class_weaver = $class_application::getWeaverClass();

		$object = $class_weaver::dereference(
			$instance,
			$properties
		);

		$query = '';

		switch ( get_class( $object ) )
		{
			case CLASS_STORE:

				// Prepare instantiation of classes
				// Store, Store Item and Query

				$query = '
					SELECT
						'.TABLE_ALIAS_STORE.'.'.PREFIX_TABLE_COLUMN_STORE.PROPERTY_ID.'
							'.PROPERTY_ID.',					
						'.TABLE_ALIAS_STORE.'.'.
							PREFIX_TABLE_COLUMN_STORE.PROPERTY_ID.' '.CLASS_STORE.' ,
						'.TABLE_ALIAS_STORE_ITEM.'.'.
							PREFIX_TABLE_COLUMN_STORE_ITEM.PROPERTY_ID.' '.CLASS_STORE_ITEM.', '.

						// if a store item is of type query,
						// retrieve the corresponding foreign object

				'
						IF (
							'.
							TABLE_ALIAS_STORE_ITEM.'.'.
								$conditions[CLASS_QUERY].', '.
							TABLE_ALIAS_QUERY.'.'.
								PREFIX_TABLE_COLUMN_QUERY.PROPERTY_ID.',
							NULL
						) '.CLASS_QUERY.'
					FROM
						'.TABLE_STORE.' '.TABLE_ALIAS_STORE.'
					LEFT JOIN
						'.TABLE_STORE_ITEM.' '.TABLE_ALIAS_STORE_ITEM.'
					USING
						( '.PREFIX_TABLE_COLUMN_STORE.PROPERTY_ID.' )
					LEFT JOIN
						'.TABLE_QUERY.' '.TABLE_ALIAS_QUERY.'
					ON
						(
							'.PREFIX_TABLE_COLUMN_STORE_ITEM.
								PROPERTY_KEY.' = '.
									PREFIX_TABLE_COLUMN_QUERY.PROPERTY_ID.'
						)
					WHERE
						'.TABLE_ALIAS_STORE.'.'.
							PREFIX_TABLE_COLUMN_STORE.PROPERTY_ID.' = '.
								$object->{PROPERTY_ID}
				;

					break;
	
			case NAMESPACE_SEMANTIC_FIDELITY.'\\'.CLASS_FORM:

				// Prepare instantiation of classes
				// Form, Store and Store Item

				$query = '
					SELECT
						'.TABLE_ALIAS_FORM.'.'.PREFIX_TABLE_COLUMN_FORM.PROPERTY_ID.'
							'.PROPERTY_ID.',
						'.TABLE_ALIAS_STORE.'.'.
							PREFIX_TABLE_COLUMN_STORE.PROPERTY_ID.' '.CLASS_STORE.' ,
						'.TABLE_ALIAS_STORE_ITEM.'.'.
							PREFIX_TABLE_COLUMN_STORE_ITEM.PROPERTY_ID.' '.CLASS_STORE_ITEM.' 
					FROM
						'.TABLE_FORM.' '.TABLE_ALIAS_FORM.'
					LEFT JOIN
						'.TABLE_STORE.' '.TABLE_ALIAS_STORE.'
					USING
						( '.PREFIX_TABLE_COLUMN_STORE.PROPERTY_ID.' )
					LEFT JOIN
						'.TABLE_STORE_ITEM.' '.TABLE_ALIAS_STORE_ITEM.'
					USING
						( '.PREFIX_TABLE_COLUMN_STORE.PROPERTY_ID.' )
					WHERE
						'.TABLE_ALIAS_FORM.'.'.
							PREFIX_TABLE_COLUMN_FORM.PROPERTY_ID.' = '.
								$object->{PROPERTY_ID}
				;

						break;
		}

		return self::parse( $query );
	}

	/**
	* Fetch a form configuration
	* 
	* @param	string	$identifier	form identifier
	* @return	mixed	form configuration
	*/
	public static function fetchFormConfiguration( $identifier )
	{
		global $class_application, $verbose_mode;

		$class_db = $class_application::getDbClass();

		$class_dumper = $class_application::getDumperClass();

		$callback_parameters = FALSE;

		if (
			is_string( $identifier ) &&
			strlen( $identifier )
		)
		{
			if ( ( FALSE != strpos( $identifier, '_' ) ) )
			
				$identifier = str_replace( '_', '.', $identifier );
	
			else if ( preg_match( '/\s+/', $identifier ) )
			
				$identifier = preg_replace('/[\s+]/', '.', $identifier);

			$select_form = '
				SELECT
					frm_id id,
					frm_identifier form_identifier,
					frm_title title,
					frm_config configuration,
					prv_id	privilege_level,
					rte_id route,
					rte_uri form_uri
				FROM
					'.TABLE_FORM.'
				LEFT JOIN
					'.TABLE_ROUTE.'
				USING
					(rte_id)
				WHERE
					frm_status = '.FORM_STATUS_ACTIVE.' AND
					frm_identifier LIKE "'.$identifier.'"
			';
			
			$results_form = $class_db::query( $select_form );

			if ( is_object( $results_form ) && $results_form->num_rows )
	
				$callback_parameters = $results_form->fetch_object();
		}

		return $callback_parameters;
	}

	/**
	* Fetch i18n items
	* 
	* @param	array	$properties	properties
	* @param	array	$container 	container
	* @return	nothing
	*/
	public static function fetchI18nItems($properties, &$container)
	{
		$class_db = CLASS_DB;

		$items = array();

		if (empty($properties[PROPERTY_NAMESPACE]))

			throw new Exception(EXCEPTION_INVALID_ARGUMENT);
		else
		
			$namespace = $properties[PROPERTY_NAMESPACE];

		if (empty($properties[PROPERTY_LANGUAGE]))

			$language_code = LANGUAGE_CODE_ENGLISH;
		else
		
			$language_code = $properties[PROPERTY_LANGUAGE];

		$select_items = '
			SELECT
				SQL_CACHE
				lgi_id id,
				lgi_name name,
				lgi_value value
			FROM
				'.TABLE_LANGUAGE_ITEM.' lgi
			LEFT JOIN
				'.TABLE_NAMESPACE.' nsp
			ON
				nsp_name = LOWER(TRIM("'.$namespace.'"))
			LEFT JOIN
				'.TABLE_LANGUAGE.' lang
			ON
				lang_code = LOWER(TRIM("'.$language_code.'"))
			WHERE
				lgi.lang_id = lang.lang_id AND
				lgi.nsp_id = nsp.nsp_id AND
				nsp.nsp_status = '.ENTITY_STATUS_ACTIVE.' AND
				lang.lang_status = '.ENTITY_STATUS_ACTIVE.' AND
				lgi.lgi_status = '.ENTITY_STATUS_ACTIVE
		;

		$results_items = $class_db::query($select_items);
		
		if ($items_count = $results_items->num_rows)
		
			while ($item = $results_items->fetch_object())

				$container[$item->id] = $item;
	}

    /**
    * Fetch the id of an insight node
    * 
    * @param	mixed	$context		context
    * @param	mixed	$entity_type	kind of entity	
    * @return	mixed
	*/
	public static function fetchId($context, $entity_type = CLASS_ENTITY)
	{
		$class_db = CLASS_DB;

		$id = 0;

		switch ($entity_type)
		{
			case CLASS_ENTITY:

				if (is_string($context) && !empty($context))
				{
					$select_id = '
						SELECT
							'.PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID.' '.PROPERTY_ID.'
						FROM
							'.TABLE_ENTITY.'
						WHERE
							'.PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_NAME.' = LOWER("'.$context.'")
					';

					$results = $class_db::query($select_id, TRUE);
					
					if ($results->rowCount() == 1)
					
						$id = $results->fetchObject();
				}

					break;
		}

		return $id;
	}

	/**
	* Fetch insights
	*
	* @param	object	$conditions	conditions
	* @return	mixed
	*/
	public static function fetchInsights($conditions)
	{
		$class_db = CLASS_DB;

		$clause_where = SQL_ANYWHERE;

		$insights = array();

		if (is_array($conditions))
		{

			while (list($name, $value) = each($conditions))
			
				$clause_where .=
					SQL_AND.
					TABLE_ALIAS_INSIGHT.'.'.PREFIX_TABLE_COLUMN_INSIGHT.$name.
					' = '.( !is_numeric($value) ? '"'.$value.'"' : $value ) 
				;

			$select_insights = '
				SELECT
					'.PREFIX_TABLE_COLUMN_INSIGHT.PROPERTY_ID.' '.PROPERTY_ID.',
					'.PREFIX_TABLE_COLUMN_INSIGHT.PROPERTY_TARGET.' '.PROPERTY_TARGET.',
					'.PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID.' '.PROPERTY_TYPE.',
					'.PREFIX_TABLE_COLUMN_INSIGHT.PROPERTY_DATE_CREATION.' '.PROPERTY_DATE_CREATION.',
					'.PREFIX_TABLE_COLUMN_INSIGHT.PROPERTY_DATE_MODIFICATION.' '.PROPERTY_DATE_MODIFICATION.'
				FROM
					'.TABLE_INSIGHT.' '.TABLE_ALIAS_INSIGHT.'
				WHERE
					'.$clause_where
			;

			$results_insights = $class_db::query($select_insights);

			if ($results_insights->num_rows)
				
				while ($insight = $results_insights->fetch_object())
				
					$insights[$insight->{PROPERTY_ID}] = $insight;
		}

		return $insights;
	}

    /**
    * Fetch an insight node
    * 
    * @param	string	$conditions	conditions
    * @return	mixed
	*/
	public static function fetchInsightNodes($conditions)
	{
		global $class_application;

		$class_db = $class_application::getDbClass();

		$clause_where = SQL_ANYWHERE;

		$constraints = self::getTableConstraints(TABLE_INSIGHT_NODE, DB_SEFI);

		$nodes = array();

		if (is_array($constraints) && count($constraints) != 0)
		{
			list(, $constraint) = each($constraints);

			if (
				is_array($conditions) &&
				count($conditions) != 0 &&
				is_object($constraint) &&
				isset($constraint->{PROPERTY_TARGET})
			)
			{
				$primary_key = $constraint->{PROPERTY_TARGET};

				$position_end_root = strpos($primary_key, PROPERTY_ID);

				$name_prefix = substr($primary_key, 0, $position_end_root);

				while (list($index, $condition) = each($conditions))
				
					$clause_where .= ' '.SQL_AND.' '.TABLE_ALIAS_INSIGHT_NODE.'.'.(
							$index == PROPERTY_ID
						?
							$primary_key
						:
						(
								$index != PREFIX_TABLE_COLUMN_INSIGHT.PROPERTY_ID
							?
								$name_prefix.$index	
							:
								$index
						)
					).' = '.$condition;

				$entity_id_insight_node = self::getByName(CLASS_INSIGHT_NODE)->{PROPERTY_ID};

				$entity_id_member = self::getByName(CLASS_MEMBER)->{PROPERTY_ID};

				$arc_type_ownership = self::getEntityTypeValue(
					array(
						PROPERTY_NAME => ARC_MODEL_OWNERSHIP,
						PROPERTY_ENTITY => ENTITY_ARC
					)
				);

				$select_node = '
					SELECT
						'.TABLE_ALIAS_INSIGHT_NODE.'.'.$primary_key.' '.PROPERTY_ID.',
						'.PREFIX_TABLE_COLUMN_USER.'.'.PREFIX_TABLE_COLUMN_EDGE.PROPERTY_KEY.' '.PROPERTY_OWNER.',
						'.PREFIX_TABLE_COLUMN_INSIGHT.PROPERTY_ID.' '.ENTITY_THREAD.',
						'.TABLE_ALIAS_INSIGHT_NODE.'.'.$name_prefix.PROPERTY_PARENT.' '.PROPERTY_PARENT.',
						'.TABLE_ALIAS_INSIGHT_NODE.'.'.$name_prefix.PROPERTY_BODY.' '.PROPERTY_BODY.'
					FROM
						'.TABLE_INSIGHT_NODE.' '.TABLE_ALIAS_INSIGHT_NODE.',
						'.TABLE_ARC.' '.TABLE_ALIAS_ARC.',
						'.TABLE_EDGE.' '.PREFIX_TABLE_COLUMN_INSIGHT_NODE.',
						'.TABLE_EDGE.' '.PREFIX_TABLE_COLUMN_USER.'
					WHERE '.

						// restrict the selection to active arcs
						TABLE_ALIAS_ARC.'.'.PREFIX_TABLE_COLUMN_ARC.PROPERTY_STATUS.' = '.ARC_STATUS_ACTIVE.SQL_AND.

						// restrict the selection to ownership arcs
						TABLE_ALIAS_ARC.'.'.PREFIX_TABLE_COLUMN_ARC.PROPERTY_TYPE.' = '.$arc_type_ownership.SQL_AND.

						// match the arc destination with existing edges
						TABLE_ALIAS_ARC.'.'.PREFIX_TABLE_COLUMN_ARC.PROPERTY_DESTINATION.' = '.PREFIX_TABLE_COLUMN_INSIGHT_NODE.'.'.PREFIX_TABLE_COLUMN_EDGE.PROPERTY_ID.SQL_AND.

						// orient the source edge so that it represents a member
						PREFIX_TABLE_COLUMN_USER.'.'.PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID.' = '.$entity_id_member.SQL_AND.

						// match the arc source with an edge
						PREFIX_TABLE_COLUMN_USER.'.'.PREFIX_TABLE_COLUMN_EDGE.PROPERTY_ID.' = '.TABLE_ALIAS_ARC.'.'.PREFIX_TABLE_COLUMN_ARC.PROPERTY_SOURCE.SQL_AND.

						// orient the destination edge so that it represents an insight node 
						PREFIX_TABLE_COLUMN_INSIGHT_NODE.'.'.PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID.' = '.$entity_id_insight_node.SQL_AND.

						// match the insight node edge with an insight node
						PREFIX_TABLE_COLUMN_INSIGHT_NODE.'.'.PREFIX_TABLE_COLUMN_EDGE.PROPERTY_KEY.' = '.TABLE_ALIAS_INSIGHT_NODE.'.'.$primary_key.SQL_AND.

						// check the user edge status
						PREFIX_TABLE_COLUMN_USER.'.'.PREFIX_TABLE_COLUMN_EDGE.PROPERTY_STATUS.' = '.EDGE_STATUS_ACTIVE.SQL_AND.

						// check the insight node edge status
						PREFIX_TABLE_COLUMN_INSIGHT_NODE.'.'.PREFIX_TABLE_COLUMN_EDGE.PROPERTY_STATUS.' = '.EDGE_STATUS_ACTIVE.SQL_AND.

						$clause_where
				;

				$node_results = $class_db::query($select_node);

				if ($node_results->num_rows == 1)
				{
					$node = $node_results->fetch_object();

					$nodes[$node->{PROPERTY_ID}] = $node;
				}
				else if ($node_results->num_rows > 1)
				{
					$nodes = array();
					
					while ($node = $node_results->fetch_object())
					
						$nodes[$node->{PROPERTY_ID}] = $node;
				}
				else

					// considering the case when the root node has to be fetched
					$nodes[INSIGHT_TYPE_PARENT_ROOT] = new stdClass();
			}
		}

		return $nodes;
	}

	/**
	* Get the maximum uid for provided search criteria
	* 
	* @param	string	$criteria	criteria
	* @param	mixed	$type		uid type
	* @return	mixed	uid 
	*/
	public static function fetchMaxUid( $criteria, $type = NULL )
	{
		global $class_application, $verbose_mode;

		$class_db = $class_application::getDbClass();

		$max_uid = NULL;

		/**
		*
		* Case when IMAP uid are to be fetched
		*
		*/

		if ( is_null( $type ) )
		{
			$select_last_uid_recorded = '
				SELECT
					max(
						' .PREFIX_TABLE_COLUMN_HEADER . PROPERTY_IMAP_UID . '
					) AS ' . PROPERTY_LAST_UID_RECORDED . '
				FROM
					' . DB_SEFI . '.' . TABLE_HEADER . '
				WHERE
					' . PREFIX_TABLE_COLUMN_HEADER . PROPERTY_KEYWORDS . 
						' LIKE "%' . $criteria . '%"
			';

			$resource = $class_db::query( $select_last_uid_recorded );

			$results = $resource->fetch_object();
			
			$max_uid = (int) $results->{PROPERTY_LAST_UID_RECORDED};
		}
		
		return $max_uid;
	}

	/**
	* Get a list of entities
	* 
	* @param	string	$entity		document type
	* @param	integer	$start		start
	* @param	integer	$limit		limit	
	* @param	integer	$max		maximum flag
	* @return	object	list
	*/
	public static function fetchList(
		$entity = DOCUMENT_TYPE_XHTML,
		$start = 0,
		$limit = PAGINATION_COUNT_PER_PAGE_DOCUMENT_XHTML,
		$max = false
	)	
	{
		$limit_clause =
		$where_clause = '';

		$list = new stdClass();

		$list->items = array();
		
		if (is_object($entity))
		
			$entity_type = $entity->{PROPERTY_TYPE};
		else
		
			$entity_type = $entity;

		switch ($entity_type)
		{
			case DOCUMENT_TYPE_XHTML:

				if (!empty($limit))

					$select_clause = '
						SELECT
							fd_id id,
							fd_title title,
							fd_date_publication publication_date,
							fd_hash hash
					';
				else if (!$max)

					$select_clause = '
						SELECT
							count(*) AS count
					';
				else 

					$select_clause = '
						SELECT
							MAX(fd_id) AS max
					';

				$from_clause = '
					FROM
						'.TABLE_FEED.'
				';
					
				$where_clause = '
					WHERE
						fd_status = '.DOCUMENT_STATUS_ACTIVE
				;

				if ($limit != 0)	
				{
					$where_clause .= ' AND
						fd_id > '.$start.'
					';
					
					$limit_clause .= '
						LIMIT
							'.$limit
					;
				}
				
					break;

			case CONTENT_TYPE_SEARCH_RESULTS:
				
				$keywords = $entity->{PROPERTY_VALUE};

				// set the select contents query 
				$select_entities = '
					SELECT
					DISTINCT
						fd_id id,
# 						CONVERT(fd_title USING utf8) title,
						fd_title title,
						fd_date_publication publication_date,
						fd_hash hash,
						MATCH (
							fd_title,
							fd_source
						)
						AGAINST
							("'.$keywords.'") AS score
					FROM
						'.TABLE_FEED.'
					WHERE
						fd_status = '.DOCUMENT_STATUS_ACTIVE.' AND
					MATCH (
						fd_title,
						fd_source
					) AGAINST
						("'.$keywords.'")
					GROUP BY
						title
					ORDER BY
						score
					DESC
				';

					break;
		}

		if (!empty($from_clause))

			$select_entities = $select_clause.$from_clause.$where_clause.$limit_clause;

		$result = DB::query($select_entities);

		if (is_object($result) && $result->num_rows && !empty($limit))
		
			while ($_entity = $result->fetch_object())

				$list->items[$_entity->id] = $_entity;

		// check if the limit argument is empty
		else if (empty($limit) && is_object($result) && $result->num_rows && !$max)

			$list->count = $result->fetch_object()->count;

		else if (is_object($result) && $max)

			$list->max = $result->fetch_object()->max;
		else if (is_object($result) && $result->num_rows)

			while ($_entity = $result->fetch_object())

				$list->items[$_entity->id] = uf8_decode($_entity);

		return $list;
	}

    /**
    * Fetch the id of a member
    *
    * @param	mixed	$id				member id
    * @param	boolean	$administrator	administration flag
    * @return 	mixed
    */
	public static function fetchUserName( $id, $administrator = FALSE )
	{
		global $class_application;		

		$class_db = $class_application::getDbClass();

		$class_dumper = $class_application::getDumperClass();

		$qualities = array($id, NULL, NULL);

		$results = NULL;

		$select_clause = '
			SELECT
				'.TABLE_ALIAS_USER.'.'.PREFIX_TABLE_COLUMN_USER.'user_name,
				'.TABLE_ALIAS_USER.'.grp_id
		';
	
		$from_clause = '
			FROM
				'.TABLE_USER.' '.TABLE_ALIAS_USER
		;
		
		if ( $administrator )
		
			$from_clause .= '
				LEFT JOIN
					'.TABLE_PRIVILEGE.' p
				ON
					sha1( usr_id ) = p.prv_hash
			';

		$where_clause = '
			WHERE
				'.TABLE_ALIAS_USER.'.'.PREFIX_TABLE_COLUMN_USER.'id = ? AND
				'.TABLE_ALIAS_USER.'.'.PREFIX_TABLE_COLUMN_USER.'status = '.
					USER_STATUS_ACTIVE
		;

		$select_qualities = $select_clause.$from_clause.$where_clause;

		$link = $class_db::getLink();
			
		$statement = $link->prepare( $select_qualities );

		$statement->store_result();

		// bind variables to the statement parameters
		$statement->bind_param(
			MYSQLI_STATEMENT_TYPE_INTEGER,
			$qualities[0]
		);
		
		// bind variables to the statement results
		$statement->bind_result(
			$qualities[2],
			$qualities[1]
		);

		// execute a statement
		$execution_result = $statement->execute();

		// fetch result of a statement
		$success = $statement->fetch();

		if ( $success )

			return $qualities;
		else

			return $results;
	}

    /**
    * Fetch the qualities of a member
    *
    * @param	array	$conditions 	conditions
    * @return 	mixed
    */
	public static function fetchMemberQualities( $conditions )
	{
		global $class_application, $verbose_mode;

		$class_db = $class_application::getDbClass();

		$member_qualities = array();

		$select_qualities = '
			SELECT
				usr_email email,
				usr_id 	id,
				usr_user_name user_name,
				usr_avatar avatar
			FROM
				'.TABLE_USER.'
			WHERE
				1
		';

		if (is_array($conditions) && count($conditions) > 0)
		{
			$where_clause = 'AND'."\n";

			while (list($column, $value) = each($conditions))
			{
				$value =
					is_string($value)
				?
					'\''.$value.'\''
				:
					$value
				;
						
				if ($column == 'usr_user_name')
				{
					$column = 'TRIM(LOWER('.$column.'))';

					$value = 'TRIM(LOWER('.$value.'))';
				}

				$where_clause .=
					' '.$column.' = '.$value.
					(next($conditions) ? ' AND'."\n" : '')
				;
			}

			$select_qualities .= $where_clause;
		}		

		$results_qualities = $class_db::query($select_qualities);

		if ($results_qualities->num_rows)
		
			while ($qualities = $results_qualities->fetch_object())
				
				$member_qualities[$qualities->id] = $qualities;
		
		return $member_qualities;
	}

    /**
    * Fetch resource
    * 
    * @param	string	$resource_key	resource
    * @param	string	$resource_type	resource type
    * @return	mixed
	*/
	public static function fetchResource($resource_key, $resource_type = RESOURCE_URI)
	{
		// switch from the resource type
		switch ($resource_type)
		{
			case RESOURCE_URI:

				if (User_Handler::anybodyThere())
				{
					// get the qualities of the current member
					$qualities = Member::getQualities();
	
					$key =
						sha1(COOKIE_MEMBER_IDENTIFER).
						sha1($qualities->{ROW_MEMBER_IDENTIFIER}).
						sha1($qualities->{ROW_MEMBER_USER_NAME})
					;

					// get a file content
					$resource = file_get_contents($resource_key."?".GET_KEY."=".$key);
				}
				else

					// jump to the root 
					Application::jumpTo(PREFIX_ROOT);

					break;
		}

		return $resource;
	}

    /**
    * Fetch a thread
    * 
    * @param	integer		$target			target
    * @param	integer		$target_type	target type
    * @param	integer		$schema			schema type
    * @return	mixed
	*/
	public static function fetchThread(
		$target,
		$target_type,
		$schema = SCHEMA_TYPE_ARRAY
	)
	{
		$class_db = CLASS_DB;

		$class_entity = CLASS_ENTITY;

		$arc_type_ownership = self::getEntityTypeValue(
			array(
				PROPERTY_NAME => ARC_MODEL_OWNERSHIP,
				PROPERTY_ENTITY => ENTITY_ARC
			)
		);

		$insight_node_type_local = self::getEntityTypeValue(
			array(
				PROPERTY_NAME => PROPERTY_LOCAL,
				PROPERTY_ENTITY => ENTITY_INSIGHT_NODE
			)
		);

		$entity_id_insight_node = $class_entity::getByName(CLASS_INSIGHT_NODE)->{PROPERTY_ID};

		$entity_id_member = $class_entity::getByName(CLASS_MEMBER)->{PROPERTY_ID};

		$threads =
		$parents = array();

		$select_thread_model = '

			SELECT DISTINCT

				isn.isn_id '.PROPERTY_ID.',
				isg.isg_id '.PROPERTY_THREAD.',
				'.PREFIX_TABLE_COLUMN_USER.'.'.PREFIX_TABLE_COLUMN_EDGE.PROPERTY_KEY.' '.PROPERTY_OWNER.',
				isn.isn_parent '.PROPERTY_PARENT.',
				isn.isn_body '.PROPERTY_BODY.',
				if (
					isn.isn_date_creation > isn_date_modification,
					isn.isn_date_creation,
					isn.isn_date_modification
				) '.PROPERTY_DATE_MODIFICATION.',
				isn.isn_date_creation '.PROPERTY_DATE_CREATION.',
				isg.isg_date_creation thread_date_creation

			FROM

				'.TABLE_INSIGHT.' '.TABLE_ALIAS_INSIGHT.'

			LEFT JOIN

				'.TABLE_INSIGHT_NODE.' '.TABLE_ALIAS_INSIGHT_NODE.'

			USING

				(isg_id)

			LEFT JOIN

				'.TABLE_EDGE.' '.PREFIX_TABLE_COLUMN_INSIGHT_NODE.'

			ON ('.

				// match the insight node edge with an insight node
				PREFIX_TABLE_COLUMN_INSIGHT_NODE.'.'.PREFIX_TABLE_COLUMN_EDGE.PROPERTY_KEY.' = '.TABLE_ALIAS_INSIGHT_NODE.'.'.PREFIX_TABLE_COLUMN_INSIGHT_NODE.PROPERTY_ID.SQL_AND.

				// orient the destination edge so that it represents an insight node 
				PREFIX_TABLE_COLUMN_INSIGHT_NODE.'.'.PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID.' = '.$entity_id_insight_node.SQL_AND.

				// check the insight node edge status
				PREFIX_TABLE_COLUMN_INSIGHT_NODE.'.'.PREFIX_TABLE_COLUMN_EDGE.PROPERTY_STATUS.' = '.EDGE_STATUS_ACTIVE.'
			)

			LEFT JOIN

				'.TABLE_ARC.' '.TABLE_ALIAS_ARC.'

			ON ('.

				// match the arc destination with existing edges
				TABLE_ALIAS_ARC.'.'.PREFIX_TABLE_COLUMN_ARC.PROPERTY_DESTINATION.' = '.
				
					PREFIX_TABLE_COLUMN_INSIGHT_NODE.'.'.PREFIX_TABLE_COLUMN_EDGE.PROPERTY_ID.SQL_AND.

				// restrict the selection to active arcs
				TABLE_ALIAS_ARC.'.'.PREFIX_TABLE_COLUMN_ARC.PROPERTY_STATUS.' = '.ARC_STATUS_ACTIVE.SQL_AND.

				// restrict the selection to ownership arcs
				TABLE_ALIAS_ARC.'.'.PREFIX_TABLE_COLUMN_ARC.PROPERTY_TYPE.' = '.$arc_type_ownership.'

			)
			LEFT JOIN

				'.TABLE_EDGE.' '.PREFIX_TABLE_COLUMN_USER.'

			ON ('.

				// orient the source edge so that it represents a member
				PREFIX_TABLE_COLUMN_USER.'.'.PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID.' = '.$entity_id_member.SQL_AND.

				// match the arc source with an edge
				PREFIX_TABLE_COLUMN_USER.'.'.PREFIX_TABLE_COLUMN_EDGE.PROPERTY_ID.' = '.TABLE_ALIAS_ARC.'.'.PREFIX_TABLE_COLUMN_ARC.PROPERTY_SOURCE.SQL_AND.

				// check the user edge status
				PREFIX_TABLE_COLUMN_USER.'.'.PREFIX_TABLE_COLUMN_EDGE.PROPERTY_STATUS.' = '.EDGE_STATUS_ACTIVE.'				
			)

			WHERE

				'.TABLE_ALIAS_INSIGHT.'.isg_target = {target} AND
				'.TABLE_ALIAS_INSIGHT.'.ety_id = {target_type} AND
				'.TABLE_ALIAS_INSIGHT.'.isg_status = {insight_status} AND
				'.TABLE_ALIAS_INSIGHT_NODE.'.isn_status = {insight_node_status} AND
				'.TABLE_ALIAS_INSIGHT_NODE.'.isn_type = {insight_type} 

			ORDER BY

				parent,
				date_creation,
				date_modification

		';

		$select_thread = str_replace(
			array(
				'{target}',
				'{target_type}',
				'{insight_status}',
				'{insight_node_status}',
				'{insight_type}'
			),
			array(
				$target,
				$target_type,
				INSIGHT_STATUS_ACTIVE,
				INSIGHT_NODE_STATUS_ACTIVE,
				$insight_node_type_local
			),
			$select_thread_model
		);

		$results_thread = $class_db::query($select_thread);

		/**
		*
		* 	Depending on the schema type a parent-oriented or
		* 	children-oriented thread can be build.
		* 	
		*	In the first case, parents ids are keys of a collection of objects
		*	
		*	In the second case, children ids are keys of a collection of objects
		*	
		*/

		if ($results_thread->num_rows)

			while ($thread = $results_thread->fetch_object())
			{
				if ($schema == SCHEMA_TYPE_PARENTS)
				{
					if (!isset($threads[$thread->{PROPERTY_PARENT}]))
	
						$threads[$thread->{PROPERTY_PARENT}] = array();
	
					$threads[$thread->{PROPERTY_PARENT}][$thread->{PROPERTY_ID}] = $thread;
				}
				else
				{
					if (!isset($threads[$thread->{PROPERTY_ID}]))
	
						$threads[$thread->{PROPERTY_ID}] = array();
	
					$threads[$thread->{PROPERTY_ID}][$thread->{PROPERTY_PARENT}] = $thread;					
				}
			}

		/**
		* Remove children as objects from an array and replace them with arrays with embedded integers as accessing keys
		*
		* @tparam	mixed	$value	an array value
		* @tparam 	mixed	$index	an accessing key
		* @return 	nothing
		*/
		$remove_children = function (&$value, $index)
		{
			$_value = array();

			while (list($node_id, $node) = each($value))
			
				$_value[$node_id] = $node_id;

			$value = $_value;
		};

		switch ($schema)
		{
			case SCHEMA_TYPE_CHILDREN:

				$children_ids = $threads;
	
				array_walk($children_ids, $remove_children);
			
				// loop on the nodes of a collection of threads to add missing children where they might be missing

				while (list($child_id, $parent_id) = each($threads))
				{
					list($node_id, $parent) = each($parent_id);

					if (in_array(array($node_id => $node_id), $children_ids))
					{
						$_child_id = array_search(array($node_id => $node_id), $children_ids);

						if (isset($threads[$node_id]))
						{
							while (list($_parent_id, $parent) = each($threads[$node_id]))

								$threads[$child_id][$_parent_id] = $parent;
						
							reset($threads[$node_id]);
						}
					}
				}

					break;

			case SCHEMA_TYPE_PARENTS:

				$direct_parents =
				$parent_ids = $threads;
	
				array_walk($parent_ids, $remove_children);
	
				end($threads);
				list($last_index) = each($threads);
				reset($threads);
	
				$history = $parent_ids;
	
				$k = 0;

				// loop on the nodes of a collection of threads to add missing parents where they might be missing

				while ($k < count($threads))
				{
					while (list($parent, $children) = each($threads))
					{
						while (list($child_index, $child) = each($children))
						{
							while (list($id, $node) = each($direct_parents))
							{
								list($child_id, $_child) = each($node);
	
								// check if the child of a parent node has been seen before as being a parent

								if ($child_index == $id)
								{
									$threads[$parent][$child_id] = $_child;
					
									if (!isset($history[$parent]))
									
										$history[$parent] = array();
	
									$history[$parent][$child_id] = $child_id;
								}

								// do some cleaning for children nodes
								// which are not parents themselves from the history of the process

								else if (
									$last_index == $id &&
									!isset($parent_ids[$child_index])
								)
								{
									while (list($_parent, $_children) = each($history))
									{
										if (in_array($child->{PROPERTY_PARENT}, $_children))
										{
											$key = array_search($child->{PROPERTY_PARENT}, $_children);
	
											if (!isset($threads[$_parent][$child->{PROPERTY_ID}]))
											{
												$threads[$_parent][$child->{PROPERTY_ID}] = $child;
	
												$history[$_parent][$child->{PROPERTY_ID}] = $child->{PROPERTY_ID};
											}
										}
									}
									
									reset($history);
								}
	
								reset($node);
							}
		
							reset($direct_parents);
						}
		
						reset($children);
					}
	
					reset($threads);
	
					$k++;
				}

					break;
		}
	
		return $threads;
	}

	/**
    * Get package
    *
    * @param	integer	$type			representing a package type
    * @param	array	$properties 	containing properties
    * @param	boolena	$second_guess	second matching guess flag
    * @param	integer	$handler_id		handler id 
    * @return  	array	containing a package
	*/	
	public static function get_package(
		$type,
		$properties = NULL,
		$second_guess = NULL,
		$handler_id = FORM_ORDINARY
	)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$class_lsql = $class_application::getLsqlClass();

		// declare an empty package
		$package = array();

		switch ( $type )
		{
			case PACKAGE_I18N:

				// switch from the handler identifier
				switch ($handler_id)
				{
					default:
	
						$package[I18N_IDENTIFIER_PREFIX] = LANGUAGE_PREFIX_FORM;			
				}

					break;

			case PACKAGE_ROUTE:

				$class_serializer = $class_application::getSerializerClass();

				$select_parent_route_clause =
				$join_route_clause = '';

				$multilevel_route = FALSE;

				$route_type_content = self::getTypeValue(
					array(
						PROPERTY_NAME => ENTITY_CONTENT,
						PROPERTY_ENTITY => ENTITY_ROUTE
					)
				);

				list(
					$match,
					$submatch,
					$matches,
					$submatches
				) = $class_serializer::checkRequest( $properties );

				// check the match
				if ( $match )

					$properties =
							$submatch && is_null( $second_guess )
						?
							$submatches[1]
						:
							$matches[1]
					;
				else
				{
					// profile changes confirmation pattern for
					// email and user name updates
					$confirmation_pattern = 
						REGEXP_OPEN.
							REGEXP_ESCAPE.PREFIX_ROOT.
							AFFORDANCE_CONFIRM.'\?([^=]*)=(.*)'.
						REGEXP_CLOSE
					;

					$confirmation_match = preg_match(
						$confirmation_pattern,
						$properties,
						$submatches
					);

					if ( $confirmation_match )
					{
						if ( $submatches[1] == GET_PROFILE_CHANGES )
						{
							$link_results = self::checkLink( $properties );
					
							if (
								isset( $link_results ) &&
								is_object( $link_results )
							)
							
								while (
									$link_properties =
										$link_results->fetch_object()
								)
		
									if (
										isset($link_properties->link_status) &&
										$link_properties->link_status ==
											LINK_STATUS_INACTIVE
									)
									{
										$results =
											$class_serializer::executeQuery(
												$link_properties->query
											);
			
										// toggle the link status right after
										// executing the associated query
										if ( $results )
										{
											$class_serializer::toggleStatus(
												$link_properties->link_id,
												ENTITY_LINK
											);
			
											$class_serializer::toggleStatus(
												$link_properties->query_id,
												ENTITY_QUERY
											);
										}
		
										$_SESSION[ENTITY_FEEDBACK] =
											DIALOG_LINK_VALID;
									}
									else if (
										! isset( $link_properties->link_status )
									)
			
										$_SESSION[ENTITY_FEEDBACK] =
											DIALOG_LINK_INVALID
										;
	
									else if (
										$link_properties->link_status ==
											LINK_STATUS_ACTIVE
									)
			
										$_SESSION[ENTITY_FEEDBACK] =
											DIALOG_LINK_ALREADY_USED
										;
						}
						else

							throw new Exception(
								EXCEPTION_FURTHER_IMPLEMENTATION_REQUIRED
							);

						if ( isset( $_SESSION[ENTITY_FEEDBACK] ) )

							return $_SESSION[ENTITY_FEEDBACK];
						else

							return $package;
					}
				}

				if ( FALSE !== strpos( $properties, SEPARATOR_LEVEL ) )
				{
					$levels = explode( SEPARATOR_LEVEL, $properties );
					
					if ( ! empty( $levels[1] ) )
					{
						$multilevel_route = true;

						$properties = $levels[0].SEPARATOR_LEVEL;
					}
				}

				if ( $properties == SEPARATOR_LEVEL )
				
					$properties = '';

				if (
					$multilevel_route &&
					count( $levels ) &&
					! empty( $properties )
				)
				
					$properties = $levels[count( $levels ) - 1];

				if (
					FALSE === strpos(
						substr(
							$properties,
							strlen( $properties ) - 2,
							2
						),
						'-'
					)
				)
				{
					$alias_column_form_id = ENTITY_FORM;

					$alias_table_form = TABLE_ALIAS_FORM;

					$alias_table_route = TABLE_ALIAS_ROUTE;

					$column_form_id =
						TABLE_ALIAS_FORM.'.'.
							PREFIX_TABLE_COLUMN_FORM.
								PROPERTY_ID
					;

					// declare the select clause 
					$select_clause = '
						SELECT 
							'.$alias_table_route.'.rte_id AS id,
							'.$alias_table_route.'.rte_type AS type,
							'.$alias_table_route.'.rte_parent_hub AS folder,
							'.$alias_table_route.'.'.
								PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID.' entity,
							'.TABLE_ALIAS_CONTENT.'.cty_id AS content_type,
							COALESCE( '.$column_form_id.', 0 ) '.
								$alias_column_form_id
					;
	
					// declare the from clause 
					$from_clause = '
						FROM
							'.TABLE_ROUTE.' '.$alias_table_route.'
					';
	
					// declare the join content clause 
					$join_clause = '
						LEFT JOIN
							'.TABLE_CONTENT_TYPE.' '.TABLE_ALIAS_CONTENT.'
						ON
							'.TABLE_ALIAS_CONTENT.'.cty_id =
								'.$alias_table_route.'.cty_id 
						LEFT JOIN
							'.TABLE_FORM.' '.$alias_table_form.'
						ON (
							'.$alias_table_route.'.'.
								PREFIX_TABLE_COLUMN_ROUTE.PROPERTY_ID.' =
									'.$alias_table_form.'.'.
										PREFIX_TABLE_COLUMN_ROUTE.PROPERTY_ID.
							' AND '.
								$alias_table_form.'.'.
									PREFIX_TABLE_COLUMN_FORM.PROPERTY_STATUS.' = '.
										ENTITY_STATUS_ACTIVE.'
						)
					';
	
					// declare the where clause
					$where_clause = '
						WHERE
							'.$alias_table_route.'.rte_status = '.ROUTE_OPENED.
							' AND '.$alias_table_route.'.rte_uri LIKE "'.
							(strlen($properties) != 0 ? '%' : '').
							((!isset($levels) || count($levels) <= 1) ? '/' : '').
							$properties.(strlen($properties) != 0 ? '%' : '').'"
					';
	
					if (
						isset( $levels ) &&
						count( $levels ) &&
						! empty( $levels[count( $levels ) - 1] ) )
					{
						// declare the select parent route clause 
						$select_clause .= ',
							p.rte_id AS parent_id
						';
	
						// declare the join route clause 
						$join_clause .= '
							LEFT JOIN
								'.TABLE_ROUTE.' p
							ON
								'.$alias_table_route.'.rte_parent_hub = p.rte_id
						';
	
						// append a condition to the where clause
						$where_clause .= '
							AND p.rte_uri LIKE "/'.
								$levels[count($levels) - 2].'%"
						';
					}
				}
				else
				{
					if ( ! empty( $matches[2] ) && is_numeric( $matches[2] ) )

						$identifier = $matches[2];
					else 

						throw new Exception( EXCEPTION_INVALID_IDENTIFIER );

					$identifier_type = substr(
						$properties,
						strlen( $properties ) - 1,
						1
					);

					if (
						! empty( $identifier_type ) &&
						is_string( $identifier_type )
					)

						switch ($identifier_type)
						{
							case GET_DOCUMENT_IDENTIFIER_REWRITTEN:

								$join_clause = '';
	
								$select_clause = '
									SELECT
										fd_title title,
										fd_hash hash,
										fd_date_publication publication_date
								';
								
								$from_clause = '
									FROM
										'.TABLE_FEED.'
								';

								$where_clause = '
									WHERE
										fd_id = '.$identifier
								;

									break;
						}
				}

				// set the select route query
				$select_route =
					$select_clause.
					$from_clause.
					$join_clause.
					$where_clause
				;

				// execute the select route query
				$results = $class_lsql::query(
					$select_route,
					FALSE,
					DEBUGGING_DISPLAY_QUERY_CHECK_ROUTE,
					FALSE,
					__CLASS__,
					__FUNCTION__
				);

				// check the results
				if ( $results->num_rows )
				{
					// set the package
					$package[] = $results->fetch_object();

					if ( $match )
					{

						// check that the current route does not lead to a folder
						// or that the route lead explicitly to a folder
						// (ending with a separator "/")

						if (
							! empty( $package[count($package) - 1]
								->{PROPERTY_TYPE}
							) &&
							(
								$package[count($package) - 1]
									->{PROPERTY_TYPE} != ROUTE_TYPE_FOLDER ||
								substr(
									$properties,
									strlen( $properties ) - 1,
									1
								) == SEPARATOR_LEVEL
							)
						)
						{
							
							// assign the affordance property
							if (
								empty( $submatches[1] ) &&
								(
									! empty( $matches[1] ) ||
									! empty( $matches[4] )
								)
							)
	
								// set the affordance property
								$package[count($package) - 1]
									->{PROPERTY_AFFORDANCE} =
										!empty($matches[1])
								?
										$matches[1]
								:
										$matches[4]
									;
							else

								// set the affordance property
								$package[count($package) - 1]
									->{PROPERTY_AFFORDANCE} = $submatches[1]
								;

							if (
								$package[count($package) - 1]
									->{PROPERTY_ID} == ROUTE_SEARCH_RESULTS &&

								isset(
									$package[count($package) - 1]
										->{PROPERTY_AFFORDANCE}
								)
							)

								$package[count($package) - 1]
									->{PROPERTY_ACTION} =
										$package[count($package) - 1]
											->{PROPERTY_AFFORDANCE}
								;

							// check identifiers of an entity to be updated
							if (
								!empty( $matches[2] ) ||
								!empty( $matches[5] ) ||
								!empty( $submatches[2] )
							)
							{
								if ( empty( $submatches[2] ) )

									// set the identifier property
									$package[count( $package ) - 1]
										->{PROPERTY_IDENTIFIER} =
										! empty($matches[2])
									?
										$matches[2]
									:
										$matches[5]
									;
								else

									$package[count( $package ) - 1]
										->{PROPERTY_IDENTIFIER} =
										$submatches[2]
									;
							}
	
							// check the third match
							if (
								! empty( $matches[3] ) ||
								! empty( $matches[6] )
							)
	
								// set the key property
								$package[count( $package ) - 1]->{PROPERTY_KEY} =
									!empty( $matches[3] )
								?
									$matches[3]
								:
									$matches[6]
								;
	
							if ( $multilevel_route )
							{
								if ( empty( $levels[count($levels) - 1] ) )
								
									unset( $levels[count($levels) - 1] );
	
								// check if the number of levels is greater than one
								else if (
									count( $levels ) > 1 &&
									! empty( $levels[count( $levels ) - 1] )
								)
								
									$package[count($package) - 1]
										->{PROPERTY_AFFORDANCE} =
											$levels[count($levels) - 1]
									;
	
								// set the key property
								$package[count($package) - 1]
									->{PROPERTY_LEVELS} =
										$levels
								;
							}
						}
						else if ( ! empty($package[count($package) - 1]->title ) )
						{
							$package[count($package) - 1]
								->{PROPERTY_ACTION} =
									ACTION_DISPLAY_DOCUMENT
							;

							$package[count($package) - 1]
								->{PROPERTY_IDENTIFIER} =
									$identifier
							;	

							$package[count($package) - 1]
								->{PROPERTY_TYPE} =
									$route_type_content
							;
						}
					}
					else

						$package = array();
				}
				else if ( $match && is_null( $second_guess ) )
				{
					$arguments = func_get_args();

					$arguments[2] = TRUE;

					$package = call_user_func_array(
						array( __CLASS__, __METHOD__ ),
						$arguments
					);
				}

					break;
		}

		// return the selected package
		return $package;
	}

	/**
    * Get Attributes of an entity
    *
    * @param	string	$conditions		conditions
    * @param	string	$entity_type	entity type
    * @return  	string	constraints 
	*/	
	public static function getAttributes($conditions, $entity_type = ENTITY_ROUTE)
	{
		$class_db = CLASS_DB;

		switch ($entity_type)
		{
			case ENTITY_ROUTE:

				$where_clause = '
					WHERE
						rte_status = '.ROUTE_OPENED
				;
				
				if (is_string($conditions))
				
					$where_clause .= ' AND rte_uri LIKE "%'.$conditions.'%"';

				// check if the conditions are passed as an array
				else if (is_array($conditions) && count($conditions) > 0)
				{
					while (list($property, $value) = each($conditions))
					
						$where_clause .= ' AND '.
							$property.
							(
								is_numeric($value)
							?
								' = '.$value
							:
								' LIKE "%'.$value.'%"'
							)
						; 
				}
	
				$select_entity = '
					SELECT
						'.ROW_PARENT_HUB.',
						'.PREFIX_TABLE_COLUMN_ENTITY.PROPERTY_ID.' '.PROPERTY_ENTITY.'
					FROM
						'.TABLE_ROUTE.'
					'.$where_clause
				;

				$results = $class_db::query($select_entity);

				$attributes = $results->num_rows ? $results->fetch_object() : new stdClass();

					break;
		}

		return $attributes;
	}

	/**
    * Get a member email
    *
    * @param	integer	$member_id 
    * @return  	string	email
	*/	
	public static function getMemberEmail($member_id)
	{
		$select_member_email = '
			SELECT
				usr_email email
			FROM
				'.TABLE_USER.'
			WHERE
				usr_id = '.$member_id
		;

		$result = DB::query($select_member_email);
		
		if ($result->num_rows)

			return $result->fetch_object()->email;
		else
		
			return false;
	}

	/**
    * Alias to the get_package mehthod
    *
    * @param	integer	$type		representing a package type
    * @param	array	$properties containing properties
    * @param	integer	$handler_id	representing a field handler
    * @return  	array	containing a package
	*/	
	public static function getPackage(
		$type,
		$properties = null,
		$handler_id = FORM_ORDINARY
	)
	{
		return self::get_package( $type, $properties, $handler_id );
	}

	/**
    * Get an entity by providing some of its properties
    *
	* @param	mixed	$value		value
    * @param	string	$name		name
	* @param	mixed	$property	property
    * @param	boolean	$verbose	verbose mode	
    * @param	mixed	$informant	informant
    * @return  	mixed	entity
	*/
	public static function getEntityByProperty(
		$value,
		$name,
		$property = NULL,
		$verbose = FALSE,
		$informant = NULL
	)
	{
		// the additional property argument has to be passed as an array
		if (
			! is_null( $property ) && ! is_array( $property )
		)

			$entity = NULL;
		else

			$entity = self::getByProperty(
				$value,
				$name,
				$property,
				CLASS_ENTITY,
				$verbose,
				$informant
			);

		return $entity;
	}

	/**
    * Get the id of an entity from its name
    *
	* @param	mixed	$name			name
	* @param	mixed	$properties		properties
	* @param	string	$entity_type	type of entity
	* @param	mixed	$verbose		verbose
	* @param	mixed	$checksum		checksum
    * @return  	mixed	id
	*/
	public static function getEntityIdByName(
		$name = NULL,
		array $properties  = NULL,
		$entity_type = CLASS_ENTITY,
		$verbose = FALSE,
		$checksum = NULL
	)
	{
		$entity_id = NULL;

		$entity = self::getEntityByName(
			$name,
			$properties,
			$entity_type,
			$verbose,
			$checksum
		);

		if (is_object($entity) && !empty($entity->{PROPERTY_ID}))
		
			$entity_id = $entity->{PROPERTY_ID};

		return $entity_id;
	}

	/**
    * Get the value of an entity type property from its name and context
    *
    * @param	string	$name		property name
    * @param	array	$context	context
    * @param	boolean	$verbose	verbose mode
    * @param	mixed	$checksum	checksum
    * @return  	mixed	id
	*/
	public static function getEntityTypeProperty(
		$name = PROPERTY_ID,
		$context = NULL,
		$verbose = FALSE,
		$checksum = NULL
	)
	{
		return parent::getTypeProperty($name, $context, $verbose, $checksum);
	}

	/**
    * Get an entity type from properties
    *
    * @param	string	$properties		properties
    * @param	boolean	$verbose		verbose mode
    * @param	mixed	$checksum		checksum
    * @return  	object	property
	*/
	public static function getEntityType($properties, $verbose = FALSE, $checksum = NULL)
	{
		return parent::getType($properties, $verbose, $checksum);
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
    * Get the constraints of a table in a database
    *
    * @param	string	$table				table name
    * @param	string	$database			database
    * @param	string	$constraint_type	constraint type
    * @param	boolean	$verbose			verbose mode
    * @param	mixed	$checksum			checksum
    * @return  	string	constraints 
	*/	
	public static function getTableConstraints(
		$table,
		$database,
		$constraint_type = NULL,
		$verbose = FALSE,
		$checksum = NULL
	)
	{
		global $class_application;

		// get the dumper class name		
		$class_dumper = $class_application::getDumperClass();

		// get the database connection class name
		$class_db = $class_application::getDbClass();

		// declare the default constraints to be returned
		$constraints = null;

		if (is_null($constraint_type))

			$constraint_type = self::getEntityTypeValue(
				array(
					PROPERTY_NAME => PROPERTY_PRIMARY_KEY,
					PROPERTY_ENTITY => ENTITY_CONSTRAINT
				)
			);

		$column_type_index = self::getEntityTypeValue(
			array(
				PROPERTY_NAME => PROPERTY_INDEX,
				PROPERTY_ENTITY => ENTITY_COLUMN
			)
		);

		// set the select primary key query model
		$select_constraints_model = '
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
				t.constraint_type="{constraint}" AND
				t.table_schema="{database}" AND
				t.table_name="{table}"
		';

		if ($constraint_type == $column_type_index)

			$select_constraints_model = '
				SELECT
					column_name AS target
				FROM
					information_schema.COLUMNS
				WHERE
					TABLE_NAME LIKE "{table}" AND
					COLUMN_KEY LIKE "%MUL%"
			';

		// set the select primary key query
		$select_constraint = str_replace(
			array(
				'{constraint}',
				'{database}',
				'{table}'
			),
			array(
				strtoupper($constraint_type),
				$database,
				$table
			),
			$select_constraints_model
		);

		// execute the select primary key query 
		$constraint_results = $class_db::query($select_constraint);

		// check the results of the primary key constraints
		if ($constraint_results->num_rows)
		{
			if ($constraint_results->num_rows == 1)

				// get the constraints
				$constraints[0] = $constraint_results->fetch_object();
			else
			
				// loop on constraint results 
				while ($columns = $constraint_results->fetch_object())

					$constraints[$columns->{ENTITY_TARGET}] = $columns->{ENTITY_TARGET};
		}

		// return constraints
		return $constraints;
	}

	/**
    * Get a template
    *
    * @param	string	$action	action
    * @return  	string	email
	*/	
	public static function getTemplate( $action )
	{
		$class_db = CLASS_DB;

		list($template_type, $template_block) = explode('.', $action);

		if (defined('TEMPLATE_TYPE_'.strtoupper($template_type)))

			$select_template = '
				SELECT
					tpl_contents contents
				FROM
					'.TABLE_TEMPLATE.'
				WHERE
					tpl_block = "'.$template_block.'" AND
					tpl_status = '.TEMPLATE_STATUS_ACTIVE.' AND
					tpl_type = '.constant('TEMPLATE_TYPE_'.strtoupper($template_type)) .' AND
					lang_id = '.LANGUAGE_ID_DEFAULT			
			;

		$result = $class_db::query($select_template);
		
		if ($result->num_rows)

			return $result->fetch_object()->contents;
		else
		
			return false;
	}
	
	/*
    * Prepare a query
    *
    * @param	object	$conditions		conditions
    * @param	array 	$parameters		parameters
    * @param	integer	$query_type		query type
    * @return	string			query
	*/
	public static function prepareQuery(
		$conditions,
		$parameters = NULL,
		$query_type = NULL
	)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		// 	set the default
		// 	select clause
		//	where clause
		$select_clause =
		$where_clause = NULL;

		if (is_null($query_type))

			$query_type = self::getTypeValue(
				array(
					PROPERTY_NAME => ACTION_SELECT,
					PROPERTY_ENTITY => ENTITY_QUERY
				)				   
			);

		// check the parameters
		if (is_object($parameters))
		{
			// check the select clause parameter			
			if (!empty($parameters->{SQL_CLAUSE_SELECT}))

				// set the select clause
				$select_clause = $parameters->{SQL_CLAUSE_SELECT};

			// check the where clause parameter
			if (!empty($parameters->{SQL_CLAUSE_WHERE}))

				// set the where clause		
				$where_clause = $parameters->{SQL_CLAUSE_WHERE};
		}

		// set a select query model
		$select_query_model = "
			SELECT
				{select}
			FROM
				{from}
			WHERE
				{where}
		";

		// check the select clause 
		if (empty($select_clause))

			$select_query_model = preg_replace(
				"/\{select\}/",
				"{column}",
				$select_query_model
			);
		else 
			$select_query_model = preg_replace(
				"/\{select}/",
				$select_clause,
				$select_query_model
			);

		// check the where clause 
		if (empty($where_clause))

			$select_query_model = preg_replace(
				"/\{where\}/",
				"{column} = ?",
				$select_query_model
			);
		else 
			$select_query_model = preg_replace(
				"/\{where\}/",
				$where_clause,
				$select_query_model
			);

		// replace placeholders with condition arguments
		$select_query = preg_replace(
			array(
				"/\{column\}/",
				"/\{from\}/"
			),
			array(
				$conditions->column,
				$conditions->table
			),
			$select_query_model
		);

		// return a select query
		return $select_query;
	}
}
