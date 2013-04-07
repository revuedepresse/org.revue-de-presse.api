<?php

/**
* Flag manager class
*
* Class for flag management
* @package  sefi
*/
class Flag_Manager extends Flag
{
	/**
	* Set a sharing flag
	*
	* @param	mixed	$properties 	properties
	* @return	mixed
	*/
	public static function flagAsShared( $properties )
	{
		global $class_application, $verbose_mode;

		$class_serializer = $class_application::getSerializerClass();

		if ( ! is_array( $properties ) || ! count( $properties ) )

			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		return $class_serializer::flagAsShared( $properties );
	}

	/**
	* Get flags
	*
	* @param	array	$properties	properties
	* @param	integer	$limit		limit
	* @return	object	flag
	*/
	public static function getFlags( $properties, $limit = 0 )
	{		
		$class_db = self::getDbClass();

		$class_dumper = self::getDumperClass();

		$class_flag = self::getFlagClass();

		// get a link
		$link = $class_db::getLink();

		// declare the default flags, parameters and well known parameters as empty arrays
		$flags =
		$parameters =
		$well_known_parameters = array();

		// check the limit
		if ( ! empty( $limit ) && is_numeric( $limit ) )
		
			// set the limit clause
			$limit_clause = "LIMIT $limit";
		else

			// declare the limit clause as an empty string
			$limit_clause = '';

		// check the count of properties
		if ( count( $properties ) )
		{
			// get the unique property
			list( $first_property ) = each( $properties );
			$latest_property = $first_property;
			reset( $properties );
		}
		else 
		{
			// get the latest property
			end( $properties );
			list( $latest_property ) = each( $properties );
			reset( $properties );
		}

		// set the order clause
		$order_by_clause = "
			ORDER BY
				flg_id\n".
			(
				isset( $first_property ) && $first_property == 'usr_id'
			?
				"	ASC\n"
			:
				"	DESC\n"
			)
		;

		// declare the default parameters types
		$parameters_types = '';

		// declare the default where clause
		$where_clause = "	WHERE\n";

		// set the select clause
		$select_clause = "
			SELECT
				flg_date_creation,
				flg_id,
				flg_status,
				flg_target,
				flg_type,
				usr_id
			FROM
				".TABLE_FLAG."\n
		";

		// loop on properties
		while ( list( $property ) = each( $properties ) )
		{
			// append a parameter type to the store 
			$parameters_types .= MYSQLI_STATEMENT_TYPE_INTEGER;

			// append a property value to the store
			$parameters[] = &$properties[$property];

			// switch from the property
			switch ( $property )
			{
				case 'flg_date_creation':

					$well_known_property = PROPERTY_DATE_CREATION;

						break;

				case 'flg_id':

					$well_known_property = PROPERTY_IDENTIFIER;

						break;

				case 'flg_status':

					$well_known_property = PROPERTY_STATUS;

						break;

				case 'flg_target':

					$well_known_property = PROPERTY_TARGET;

						break;

				case 'flg_type':

					$well_known_property = PROPERTY_TYPE;

						break;

				case 'usr_id':

					$well_known_property = PROPERTY_OWNER;

						break;
			}

			// set the well_known_parameters
			$well_known_parameters[$well_known_property] =  $properties[$property];

			// set the where clause
			$where_clause .=
				"		$property = ?".
				(
					$property != $latest_property
				?
					" AND"
				:
					''
				)
				."\n"
			;
		}

		// reset the properties
		reset( $properties );
	
		// unshift parameters
		array_unshift( $parameters, $parameters_types );

		// set the query
		$query = $select_clause.$where_clause.$order_by_clause.$limit_clause;

		// prepare a statement
		$statement = $link->prepare( $query );

		// set a reference to the parameters
		$_parameters = &$parameters;

		// prepare a statement
		call_user_func_array(
			array( $statement, 'bind_param' ), $_parameters
		);

		// bind results to the statement
		$statement->bind_result(
			$flag_date_creation,
			$flag_identifier,
			$flag_status,
			$flag_target,
			$flag_type,
			$member_identifier
		);

        // execute a statement
        $execution_result = $statement->execute();

		$fetched_result = $statement->fetch();

		// loop on fetched result
		do 
		{
			// construct a new instance of the flag class
			$flag = new $class_flag;

			// check the fetched result
			if ( $fetched_result )
			{
				// set the flag properties
				$flag_properties = array(
					PROPERTY_DATE_CREATION => $flag_date_creation,
					PROPERTY_IDENTIFIER => $flag_identifier,
					PROPERTY_OWNER => $member_identifier,
					PROPERTY_STATUS => $flag_status,
					PROPERTY_TARGET => $flag_target,
					PROPERTY_TYPE => $flag_type
				);
	
				// set properties
				$flag->setProperties( $flag_properties );

				// check the count of properties
				if ( isset( $first_property ) )
				{
					if ( $first_property == 'usr_id' )
					{
						// check the second level flag store 
						if (
							! isset( $flags[$flag_type] ) ||
							! is_array( $flags[$flag_type] )
						)

							$flags[$flag_type] = array();

						// check the flag status
						if ( $flag_status === FLAG_STATUS_ENABLED )

							// append a flag to the store
							$flags[$flag_type][$flag_target] = $flag;

						// check if the flag is defined
						else if ( $flags[$flag_type][$flag_target] )
		
							// append a flag to the store
							unset( $flags[$flag_type][$flag_target] );
					}
				}
				else
	
					// append a flag to the store
					$flags[] = $flag;
			}
			else
			{
				// set default properties
				$_properties = $flag->setProperties(
					array(
						PROPERTY_DATE_CREATION,
						PROPERTY_IDENTIFIER,
						PROPERTY_OWNER,
						PROPERTY_STATUS,
						PROPERTY_TARGET,
						PROPERTY_TYPE
					)
				);
	
				// set properties
				$flag->setProperties( $well_known_parameters );

				// append a flag to the store
				$flags[-1] = $flag;
			}

		// loop on statement
		} while ( $fetched_result = $statement->fetch() );

		// close the statement
		$statement->close();

		// return a flag
		return $flags;
	}

	/**
	* Get flags
	*
	* @param	integer	$owner_identifier	owner identifier
	* @return	array	flags
	*/
	public static function getFlagsByOwner($owner_identifier)
	{
		// returned flags
		return self::getFlags( array( 'usr_id' => $owner_identifier ) );
	}
}

/**
*************
* Changes log
*
*************
* 2011 03 08
*************
* 
* Implement items sharing with flags
* 
* methods affected ::
*
* EXECUTOR :: perform
* FLAG MANAGER :: flagAsShared
* SERIALIZER :: flagAsShared
* 
* (revision 590)
*
*/