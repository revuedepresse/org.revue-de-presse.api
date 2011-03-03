<?php

/**
* Prover class
*
* @package  sefi
*/
class Prover extends Entity
{
	public static $logical_mechanics;

	/**
    * Check predicates
	*
	* @param	array	$predicates	containing predicates
	* @param	mixed	$identifier	identifier
    * @return	array			resolutions
	*/
	public static function checkPredicates($predicates, $identifier = null)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		// 	set empty arrays of
		// 	members
		//	results
		//	computing results
		$computing_results =
		$members =
		$results = 
		$processed_operands = array();

		// set a new instance of the standard class
		self::$logical_mechanics = new stdClass();

		// loop on predicates
		while (list($predicate_index, $predicate) = each($predicates))
		{
			// declare an empty array of members
			$members[$predicate_index] = array();

			// declare a predicate as an instance of the standard class
			$_predicate = new stdClass();

			// declare members as an instance of the standard class
			$_members = new stdClass();

			// set a resource
			$resource = array(
				RESOURCE_EXPRESSION => $predicate,
				RESOURCE_CONNECTOR => SYMBOL_IMPLIES
			);

			// prepare data
			$prepared_data = parser::prepare_data($resource, PARSING_TYPE_CONNECTOR);

			// set the left member of predicate
			$_members->{PROPERTY_LEFT_OPERAND} = $prepared_data[0];

			// set the right member of predicate
			$_members->{PROPERTY_RIGHT_OPERAND} = $prepared_data[1];

			// prepare members of expression
			parser::prepare_data($_members, PARSING_TYPE_EXPRESSION);

			// set the left member of predicate
			$_predicate = array(
				SYMBOL_IMPLIES => $_members
			);

			// set the parsed predidate
			$members[$predicate_index] = $_predicate;
		}

		// declare an empty array of globals
		self::$logical_mechanics->{STORE_GLOBALS} = array();

		// set the predicates
		self::$logical_mechanics->{STORE_PREDICATES} = $members;

		// display the logical mechanics
		$class_dumper::log(
			__METHOD__,
			array(self::$logical_mechanics),
			DEBUGGING_DISPLAY_LOGICAL_MECHANICS
		);

		// loop on predicates
		while (list($predicate_index, $predicate) = each(self::$logical_mechanics->{STORE_PREDICATES}))

			// resolve a predicate
			$resolutions[] = self::resolve($predicate, $identifier);

		// display the resolutions
		$class_dumper::log(
			__METHOD__,
			array(
				'resolutions',
				$resolutions
			),
			DEBUGGING_DISPLAY_RESOLUTIONS
		);

		// loop on resolutions
		while (list($predicate_index, $resolution) = each($resolutions))

			// process predicates
			$results[] = self::processResolution($resolution);

		// display the results
		$class_dumper::log(
			__METHOD__,
			array(
				'results',
				$results
			),
			DEBUGGING_DISPLAY_RESULTS
		);

		// loop on resolutions
		while (list($predicate_index, $result) = each($results))

			// process predicates
			$computing_results[] = self::computeResults($result);

		// display the computing results
		$class_dumper::log(
			__METHOD__,
			array(
				'computing results',
				$computing_results
			),
			DEBUGGING_DISPLAY_COMPUTING_RESULTS
		);

		// loop on computing results
		while (list($predicate_index, $operands) = each($computing_results))
		{
			// declare an empty array as operands store
			self::$logical_mechanics->{STORE_CONNECTORS} = array();

			// set a connector store
			$connector_store = &self::$logical_mechanics->{STORE_CONNECTORS};

			// process operands
			$processed_operands[] = self::processOperands($operands, $connector_store);
		}

		// display the computing results
		$class_dumper::log(
			__METHOD__,
			array(
				'processed operand',
				$processed_operands
			),
			DEBUGGING_DISPLAY_PROCESSED_OPERANDS
		);

		// loop on processed operands
		while (list($predicate_index, $predicate) = each($processed_operands))

			// process operands
			$predicate_resolutions[] = self::solvePredicate($predicate);

		// display the computing results
		$class_dumper::log(
			__METHOD__,
			array(
				'predicate resolutions',
				$predicate_resolutions
			),
			DEBUGGING_DISPLAY_PREDICATE_RESOLUTIONS
		);

		// return the predicate resolutions
		return $predicate_resolutions;
	}

	/**
    * Compute results
	*
	* @param	mixed	$results	results
	* @param	integer	$checksum	checksum 		
    * @return	mixed
	*/		
	public static function computeResults($results, $checksum = null)
	{
		// check the resolutions store
		if (
			!isset(self::$logical_mechanics->{STORE_OPERATIONS}) ||
			!is_array(self::$logical_mechanics->{STORE_OPERATIONS})
		)

			// declare of new empty static array of resolutions
			self::$logical_mechanics->{STORE_OPERATIONS} = array();
		
		// check the results
		if (is_object($results) || is_array($results))
		
			// get the results
			list($connector, $result) = each($results);

		// set a new instance of the standard class
		$computation = new stdClass();

		// check the connector
		if (isset($connector))

			switch ($connector)
			{
				case SYMBOL_ASSIGNMENT:

					// check if the result can be solved
					if (is_int($result))

						// get a resolution
						$computation->{SYMBOL_TRIPLE_EQUAL.$connector} =
							self::getResolution($result);
					else
					{
						// loop on result
						while (list($member, $_result) = each($result))
						{
							// check the result 
							if (is_numeric($_result))
	
								// get a resolution
								$computation->{$member.SYMBOL_TRIPLE_EQUAL.$connector} =
									self::getResolution($_result);
							else
							{
		
								// set the current member computation
								$_computation = self::computeResults($_result, $checksum);

								// check the private computation
								if (
									is_object($_computation) &&
									count(get_object_vars($_computation)) == 1
								)
								{
									// get a private result
									list($_connector, $_operands) = each($_computation);
	
									// get a resolution
									$computation->{$member.SYMBOL_TRIPLE_EQUAL.$connector} = $_operands;
								}
	
								// look for some disjunction symbol
								else if (
									is_object($_computation) &&
									count(get_object_vars($_computation)) != 1 &&
									isset($_computation->{PROPERTY_LENGTH})
								)
								{
									// get a private operands
									while (list($operand_index, $_operands) = each($_computation))
									{
										if ($operand_index != PROPERTY_LENGTH)
	
											while (list($_operand_index, $_operand) = each($_operands))
	
												// get a resolution
												$computation->{$member.SYMBOL_TRIPLE_EQUAL.$connector.$_operand_index} = $_operand;
									}
								}
	
								// check the commputation properties
								else if (count(get_object_vars($computation)) == 0)
	
									// set the computation
									$computation = $_computation;
								else
	
									// set a private resolution public
									$computation->{$member.SYMBOL_TRIPLE_EQUAL.$connector} = $_computation;							
							}
						}
					}

						break;
	
				case SYMBOL_DISJUNCTION:

					// set a new instance of the standard class
					$operands = new stdClass();

					// loop on result
					while (list($member, $_result) = each($result))
					{
						// set the current member computation
						$_computation = self::computeResults($_result);

						// get a private result
						list($_connector, $_operands) = each($_computation);

						$operands->{
							PROPERTY_OPERAND.SYMBOL_MEMBER_ATTRIBUTE.
								$member.
									SYMBOL_TRIPLE_EQUAL.$connector
						} = $_operands;
					}

					// reset the result
					reset($result);

					// set the operands
					$computation->{$connector} = $operands;

					// set the current computation length
					$computation->{PROPERTY_LENGTH} = count(get_object_vars($computation));

						break;
	
				case SYMBOL_DOMAIN:

					// set a new instance of the standard class
					$operands = new stdClass();

					// loop on result
					while (list($member, $_result) = each($result))
					{
						// set the current member computation
						$_computation = self::computeResults($_result);

						// check the private computation
						if (
							is_object($_computation) &&
							count(get_object_vars($_computation)) == 1
						)
						{
							// get a private result
							list($_connector, $_operands) = each($_computation);

							// get a resolution
							$computation->{$member.SYMBOL_TRIPLE_EQUAL.$connector} = $_operands;
						}
						else if (
							is_object($_computation) &&
							count(get_object_vars($_computation)) != 1 &&
							isset($_computation->{PROPERTY_LENGTH})
						)
						{
							// get a private operands
							while (list($operand_index, $_operands) = each($_computation))

								if ($operand_index != PROPERTY_LENGTH)

									while (list($_operand_index, $_operand) = each($_operands))

										// get a resolution
										$computation->{
											$member.
												SYMBOL_TRIPLE_EQUAL.
													$connector.
														$_operand_index} =
										$_operand;
						}
						else

							// set the current member computation
							$computation->{$member.SYMBOL_TRIPLE_EQUAL.$connector} = self::computeResults($_result);
					}

					// reset the result
					reset($result);

						break;
	
				case SYMBOL_EQUAL:

					// loop on result
					while (list($member, $_result) = each($result))
					{
						// check the result 
						if (is_numeric($_result))

							// get a resolution
							$computation->{$member.SYMBOL_TRIPLE_EQUAL.$connector} =
								self::getResolution($_result);
						else
						{
	
							// set the current member computation
							$_computation = self::computeResults($_result, $checksum);

							// check the private computation
							if (
								is_object($_computation) &&
								count(get_object_vars($_computation)) == 1
							)
							{
								// get a private result
								list($_connector, $_operands) = each($_computation);

								// get a resolution
								$computation->{$member.SYMBOL_TRIPLE_EQUAL.$connector} = $_operands;
							}

							// look for some disjunction symbol
							else if (
								is_object($_computation) &&
								count(get_object_vars($_computation)) != 1 &&
								isset($_computation->{PROPERTY_LENGTH})
							)
							{
								// get a private operands
								while (list($operand_index, $_operands) = each($_computation))
								{
									if ($operand_index != PROPERTY_LENGTH)

										while (list($_operand_index, $_operand) = each($_operands))

											// get a resolution
											$computation->{$member.SYMBOL_TRIPLE_EQUAL.$connector.$_operand_index} = $_operand;
								}
							}

							// check the commputation properties
							else if (count(get_object_vars($computation)) == 0)

								// set the computation
								$computation = $_computation;
							else

								// set a private resolution public
								$computation->{$member.SYMBOL_TRIPLE_EQUAL.$connector} = $_computation;
						}
					}

					// reset the result
					reset($result);

						break;
	
				case SYMBOL_EXISTS:
				case SYMBOL_EXISTS_ONE_ONLY:

					// set a new instance of the standard class
					$operands = new stdClass();

					// loop on result
					while (list($member, $_result) = each($result))
					{
						// set the current member computation
						$_computation = self::computeResults($_result);

						// check the private computation
						if (
							is_object($_computation) &&
							count(get_object_vars($_computation)) == 1
						)
						{
							// get a private result
							list($_connector, $_operands) = each($_computation);

							// get a resolution
							$operands->{
								PROPERTY_OPERAND.SYMBOL_MEMBER_ATTRIBUTE.
									$member.
										SYMBOL_TRIPLE_EQUAL.$connector
							} = $_operands;
						}
					}

					// set the operands
					$computation->{$connector} = $operands;

					// set the current computation length
					$computation->{PROPERTY_LENGTH} = count(get_object_vars($computation));

					// reset the results
					reset($result);

						break;
	
				case SYMBOL_IMPLIES:

					// set a new instance of the standard class
					$operands = new stdClass();

					// loop on result
					while (list($member, $_result) = each($result))
					{
						$_computation = self::computeResults($_result);

						if (
							is_object($_computation) &&
							count(get_object_vars($_computation)) == 1
						)
						{
							// get a private result
							list($_connector, $_operands) = each($_computation);

							while (list($_operand_index, $_operand) = each($_operands))

								// get a resolution
								$operands->{$_operand_index} = $_operand;

							// set the current member computation	
							$computation->{$member.SYMBOL_TRIPLE_EQUAL.$connector} = $operands;
						}
						else 

							// set the current member computation	
							$computation->{$member.SYMBOL_TRIPLE_EQUAL.$connector} = $_computation;
					}

					// reset the result
					reset($result);

						break;
	
				case SYMBOL_LEFT_RIGHT_ARROW_FROM_BAR:

					// get a resolution
					$computation->{SYMBOL_TRIPLE_EQUAL.$connector} =
						self::getResolution($result);

						break;

				case SYMBOL_MEMBER_ATTRIBUTE:

					// get a resolution
					$computation->{SYMBOL_TRIPLE_EQUAL.$connector} =
						self::getResolution($result);

						break;
			}

		// return some computation
		return $computation;
	}

	/**
    * Get a resolution
	*
	* @param	integer		$index		index
    * @return	mixed
	*/		
	public static function getResolution($index)
	{
		// check the index argument
		if (
			isset(self::$logical_mechanics->{STORE_RESOLUTIONS}) &&
			is_array(self::$logical_mechanics->{STORE_RESOLUTIONS}) &&
			!empty(self::$logical_mechanics->{STORE_RESOLUTIONS}[$index])
		)

			// return a resolution
			return self::$logical_mechanics->{STORE_RESOLUTIONS}[$index];
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
    * Process operands
	*
	* @param	mixed 	$operands			operands
	* @param	mixed	$connector_store	connector store
	* @param	mixed	$store				store
	* @param	integer	$checksum	checksum
    * @return	mixed
	*/
	public static function processOperands(
		$operands,
		&$connector_store,
		&$store = NULL,
		$checksum = NULL
	)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		// declare an empty array of processed operands
		$processed_operands = array();

		// check the index argument
		if (
			!isset(self::$logical_mechanics->{STORE_OPERANDS}) ||
			!is_array(self::$logical_mechanics->{STORE_OPERANDS})
		)

			// declare an empty array as operands store
			self::$logical_mechanics->{STORE_OPERANDS} = array();

		$operand_store = &self::$logical_mechanics->{STORE_OPERANDS};

		$exit =
		$track =
		$tracker =
		$tracking_mode = false;

		// get the first operand
		list($first_operand_index) = each($operands);
		reset($operands);

		// get the latest operand
		end($operands);
		list($latest_operand_index) = each($operands);
		reset($operands);

		// check the operands
		if (
			is_array($operands) ||
			is_object($operands) &&
			!isset($operands->{PROPERTY_CLASS}) &&
			!isset($operands->{PROPERTY_IDENTIFIER}) &&
			!isset($operands->{PROPERTY_IMAGE}) &&
			!isset($operands->{PROPERTY_TABLE}) &&
			!isset($operands->{PROPERTY_VALUE})
		)
		{
			// set the operand pattern
			$operand_pattern =
				REGEXP_OPEN.
					$class_application::translate_entity(PROPERTY_LEFT_OPERAND, ENTITY_PATTERN).
					SYMBOL_TRIPLE_EQUAL.
					REGEXP_CATCH_START.
						REGEXP_WILDCARD.REGEXP_FACULTATIVE.
					REGEXP_CATCH_END.
					REGEXP_CATCH_START.
						PROPERTY_OPERAND.
						REGEXP_ESCAPE.SYMBOL_MEMBER_ATTRIBUTE.
						REGEXP_CATCH_START.
							REGEXP_EXPRESSION_START.
								REGEXP_NOT.SYMBOL_TRIPLE_EQUAL.
							REGEXP_EXPRESSION_END.REGEXP_ANY.
						REGEXP_CATCH_END.
						SYMBOL_TRIPLE_EQUAL.REGEXP_FACULTATIVE.
						REGEXP_CATCH_START.
							REGEXP_EXPRESSION_START.
								REGEXP_NOT.SYMBOL_TRIPLE_EQUAL.
							REGEXP_EXPRESSION_END.REGEXP_ANY.
						REGEXP_CATCH_END.REGEXP_FACULTATIVE.
					REGEXP_CATCH_END.REGEXP_FACULTATIVE.
					REGEXP_OR.
					$class_application::translate_entity(PROPERTY_RIGHT_OPERAND, ENTITY_PATTERN).
					SYMBOL_TRIPLE_EQUAL.
					REGEXP_CATCH_START.
						REGEXP_WILDCARD.REGEXP_FACULTATIVE.
					REGEXP_CATCH_END.
					REGEXP_CATCH_START.
						PROPERTY_OPERAND.
						REGEXP_ESCAPE.SYMBOL_MEMBER_ATTRIBUTE.
						REGEXP_CATCH_START.
							REGEXP_EXPRESSION_START.
								REGEXP_NOT.SYMBOL_TRIPLE_EQUAL.
							REGEXP_EXPRESSION_END.REGEXP_ANY.
						REGEXP_CATCH_END.
						SYMBOL_TRIPLE_EQUAL.REGEXP_FACULTATIVE.
						REGEXP_CATCH_START.
							REGEXP_EXPRESSION_START.
								REGEXP_NOT.SYMBOL_TRIPLE_EQUAL.
							REGEXP_EXPRESSION_END.REGEXP_ANY.
						REGEXP_CATCH_END.REGEXP_FACULTATIVE.
					REGEXP_CATCH_END.REGEXP_FACULTATIVE.					
				REGEXP_CLOSE.REGEXP_MODIFIER_UNICODE
			;

			// loop on operands
			while (list($operand_index, $operand) = each($operands))
			{
				// dump the operands to be processed
				$class_dumper::log(
					__METHOD__,
					array(
						'tracked operand',
						$operands,
						'operand store',
						$operand_store,
						'private store',
						$store
					),
					FALSE
				);

				// dump the first top level operands to be processed
				$class_dumper::log(
					__METHOD__,
					array(
						'first round trip',
						'tracked operand',
						$operands,
						'operand store',
						$operand_store,
						'private store',
						$store
					),
					FALSE,
					'exit'
				);

				// set the default operator
				$operator =
				$operand_type =
				$sub_operator =
				$sub_operand_type = null;

				// set the default right match flag
				$right_match = false;

				// look for operand matches
				$operand_match = preg_match($operand_pattern, $operand_index, $operand_matches);

				$class_dumper::log(
					__METHOD__,
					array(
						'pattern',
						$operand_pattern,
						'operand index',
						$operand_index,
						'operand matches',
						$operand_matches
					),
					DEBUGGING_DISPLAY_OPERAND_MATCHES
				);

				// check the operand matches
				if (!empty($operand_matches[1]))
				{
					$operator = $operand_matches[1];
					$operand_type = PROPERTY_LEFT_OPERAND;

					// check if the operand store has been properly initialized
					if (
						$operator == SYMBOL_IMPLIES &&
						is_array($operand_store) &&
						count($operand_store) != 0
					)

						// declare an empty array as operands store
						self::$logical_mechanics->{STORE_OPERANDS} = array();

					// check the connector store					
					if (!isset($connector_store[$operator]))
					{
						// append the current connector to a store
						$connector_store[$operator] = new stdClass();

						// set the operand property
						$connector_store[$operator]->{PROPERTY_OPERAND} = PROPERTY_LEFT_OPERAND;
					}

					// set the sub operations property						
					$connector_store[$operator]->{PROPERTY_SUB_OPERATIONS} = false;

					$class_dumper::log(
						__METHOD__,
						array(
							'operand store',
							$operand_store,
							'operand matches',
							$operand_matches
						),
						false
					);

					if (!empty($operand_matches[4]))
					{
						$sub_operand = PROPERTY_LEFT_OPERAND;						
						$sub_connector = $operand_matches[4];
						$sub_operand_type = $operand_matches[3];

						// toggle the sub operations property						
						$connector_store[$operator]->{PROPERTY_SUB_OPERATIONS} = true;
					}
				}
				else if (!empty($operand_matches[5]))
				{
					$class_dumper::log(
						__METHOD__,
						array(
							'operand store:',
							$operand_store,
							'operand matches:',
							$operand_matches
						),
						false
					);
					
					$operator = $operand_matches[5];
					$operand_type = PROPERTY_RIGHT_OPERAND;					

					// set the sub operations property						
					$connector_store[$operator]->{PROPERTY_SUB_OPERATIONS} = false;

					// set the connector store
					$connector_store[$operator]->{PROPERTY_OPERAND} = $operand_type;

					if (!empty($operand_matches[8]))
					{
						$sub_operand = PROPERTY_RIGHT_OPERAND;
						$sub_connector = $operand_matches[8];
						$sub_operand_type = $operand_matches[7];

						// toggle the sub operations property						
						$connector_store[$operator]->{PROPERTY_SUB_OPERATIONS} = true;
					}

					// check the connector store
					else if (isset($connector_store[$operator]))

						// toggle the right match flag
						$right_match = true;
				}

				// check the operand store
				if (
					!isset($store) &&
					!empty($operator) &&
					(
						!isset($operand_store[$operator]) ||
						!is_array($operand_store[$operator])
					)
				)

					// set an empty array of operand types
					$operand_store[$operator] = array();

				// check the current operator
				if (isset($sub_operand_type))
				{
					// set an empty array of sub-operand types
					$sub_operator = array();

					// append the current processed operand to the operands store
					$sub_operator[$operand_type.$sub_connector.$sub_operand_type] =
						self::processOperands($operand, $connector_store);						
				}

				// check the current operator
				if (!isset($sub_operator) && !empty($operand_type))
				{
					if (!isset($store))
					{
						if (
							is_array($operand_store) &&
							!isset($operand_store[$operator][$operand_type])
						)

							// declare an empty array for the current operand type
							$operand_store[$operator][$operand_type] = array();

						// set a store
						$store = &$operand_store[$operator][$operand_type];

						// check the connector store
						if (
							isset($connector_store[$operator]) &&
							is_object($connector_store[$operator]) &&
							!isset($connector_store[$operator]->{PROPERTY_STORE}) &&
							isset($connector_store[$operator]->{PROPERTY_SUB_OPERATIONS}) &&
							$connector_store[$operator]->{PROPERTY_SUB_OPERATIONS} === false &&
							count($connector_store) == 1
						)
	
							// set a reference to the connector store
							$connector_store[$operator]->{PROPERTY_STORE} = &$operand_store;

						// append the current processed operand to the operands store
						$operand_store[$operator][$operand_type] =
							self::processOperands($operand, $connector_store, $store);
					}
					else
					{
						if (
							is_array($store) &&
							(
								!isset($store[$operator]) ||
								!isset($store[$operator][$operand_type])
							)
						)
						{
							// check the connector store
							if (
								!isset($connector_store[$operator]) ||
								is_object($connector_store[$operator]) &&
								$connector_store[$operator]->{PROPERTY_SUB_OPERATIONS} === true ||
								!isset($connector_store[$operator]->{PROPERTY_STORE})
							)
							{
								// check the store for the current operand type
								if (!isset($store[$operator][$operand_type]))

									// declare an empty array
									$store[$operator][$operand_type] = array();

								// declare a reference
								$_store = &$store[$operator][$operand_type];
							}
							else
							{
								// get the parent store
								$store = &$connector_store[$operator]->{PROPERTY_STORE};

								// check the store for the current operand type
								if (!isset($store[$operator][$operand_type]))
								
									// declare an empty array
									$store[$operator][$operand_type] = array();

								$_store = &$store[$operator][$operand_type];
							}

							// check if the current operand is the latest one
							if ($latest_operand_index != $operand_index)

								// append the current processed operand to the operands store
								$store[$operator][$operand_type] = self::processOperands(
									$operand,
									$connector_store,
									$_store,
									$checksum
								);

							else
							{
								// append the current processed operand to the operands store
								$carrier = self::processOperands(
									$operand,
									$connector_store,
									$_store,
									$checksum
								);

								if (is_array($carrier) && count($carrier) != 0)
								{
									// get the operand instance
									list($sub_operator_instance, $operand_instance) = each($carrier);
	
									// get the sub operand instance								
									list($sub_sub_operator_instance, $sub_sub_operand_instance) = each($operand_instance);
	
									// get the first operator properties
									list($first_operator, $properties) = each($connector_store);
									reset($connector_store);

									// check the current properties to match with the very first operator handled
									if (
										isset($sub_operator_instance) &&
										$first_operator == $sub_operator_instance &&
										is_object($properties) &&
										isset($properties->{PROPERTY_OPERAND}) &&
										$properties->{PROPERTY_OPERAND} == PROPERTY_RIGHT_OPERAND
									)
									{
										// reset the static array of resolutions
										self::$logical_mechanics->{STORE_OPERATIONS} = array();
	
										// set the return value
										$processed_operands = $operand_store;
									}
	
									else if (isset($sub_sub_operand_instance))
	
										// return the operator store
										$processed_operands = $sub_sub_operand_instance;
								}
								else if (is_object($carrier))
								{
									// get the first operator properties
									list($first_operator, $properties) = each($connector_store);
									reset($connector_store);

									// dump the operand store a some level
									$class_dumper::log(
										__METHOD__,
										array(
											'carrier',
											$carrier,
											'operand store',
											$operand_store,
											'operand',
											$operator,
											'private store before carrier',
											$store
										),
										false
									);

									if (
										isset($store[$operator]) &&
										is_array($store[$operator]) &&
										isset($store[$operator][$operand_type]) &&
										is_array($store[$operator][$operand_type]) &&
										count($store[$operator][$operand_type]) == 0
									)

										$store[$operator][$operand_type] = $carrier;

									// check the current properties to match with the very first operator handled
									if (
										$first_operator == $operator &&
										$operand_type == PROPERTY_RIGHT_OPERAND
									)

										$processed_operands = $operand_store;
									else 

										$processed_operands = $store;
								}
							}

							$class_dumper::log(
								__METHOD__,
								array(
									'private store',
									$store,
									'right after the latest',
									'private store',
									$store,
									'operand_store',
									$operand_store,				
									'operand type',
									$operand_type,
									'operand',
									$operand,
									$store[$operator][$operand_type],
									'latest operand',
									$latest_operand_index,
									'current operand',
									$operand_index										
								),
								false // toggled
							);
						}
						else

							$processed_operands = $store;
					}
				}

				// check the sub operand type
				else if (isset($sub_operand_type))
				{
					// check the store argument
					if (!isset($store))
					{
						// set a store
						$store = &$operand_store[$operator][$operand_type];

						// append the current processed operand to the operands store
						$operand_store[$operator] = array_merge($operand_store[$operator], $sub_operator);
					}

					// check the store argument
					else if (
						isset($store) &&
						is_array($store)
					)
					{
						// check the operand store for the current operator
						if (!isset($store[$operator]))

							$store[$operator] = array();

						// append the current processed operand to the operands store
						$store[$operator] = array_merge($store[$operator], $sub_operator);

						// check if the current operand is the latest one
						if ($operand_index == $latest_operand_index)

							$processed_operands = $operand_store;
					}
					else

						$processed_operands = $store;
				}
			}

			// reset the operands
			reset($operands);
		}
		else 

			// set the processed operands
			$processed_operands = $operands;

		$class_dumper::log(
			__METHOD__,
			array(
				'returned value',
				$processed_operands
			),
			false
		);

		// return the processed operands
		return $processed_operands;
	}

	/**
    * Process resolution
	*
	* @param	mixed		$resolution	resolution
    * @return	mixed
	*/		
	public static function processResolution($resolution)
	{
		// check the resolution
		if (is_object($resolution) || is_array($resolution))
		{
			// check the operand
			if (
				is_object($resolution) &&
				get_class($resolution) == CLASS_STANDARD_CLASS &&
				(
					!empty($resolution->{PROPERTY_IDENTIFIER}) ||
					!empty($resolution->{PROPERTY_CALLBACK_METHOD}) ||
					!empty($resolution->{PROPERTY_TABLE})
				)
			)
			{
				// check the resolutions store
				if (
					!isset(self::$logical_mechanics->{STORE_RESOLUTIONS}) ||
					!is_array(self::$logical_mechanics->{STORE_RESOLUTIONS})
				)

					// declare of new empty static array of resolutions
					self::$logical_mechanics->{STORE_RESOLUTIONS} = array();

				// append some resolutions to the resolutions store
				self::$logical_mechanics->{STORE_RESOLUTIONS}[] = $resolution;

				// return the length of the resolutions store
				return count(self::$logical_mechanics->{STORE_RESOLUTIONS}) - 1;
			}

			// get operands
			list($connector, $operands) = each($resolution);
		}

		// set an instance of the standard class as processing results
		$processing_results = new stdClass();

		// switch from a connector
		switch ($connector)
		{
			case SYMBOL_ASSIGNMENT:
			case SYMBOL_DISJUNCTION:
			case SYMBOL_EXISTS:
			case SYMBOL_EXISTS_ONE_ONLY:
			case SYMBOL_LEFT_RIGHT_ARROW_FROM_BAR:
			case SYMBOL_MEMBER_ATTRIBUTE:	

				// check the resolutions store
				if (
					!isset(self::$logical_mechanics->{STORE_RESOLUTIONS}) ||
					!is_array(self::$logical_mechanics->{STORE_RESOLUTIONS})
				)
	
					// declare of new empty static array of resolutions
					self::$logical_mechanics->{STORE_RESOLUTIONS} = array();
		}

		// switch from a connector
		switch ($connector)
		{
			case SYMBOL_ASSIGNMENT:

				if (count(get_object_vars($operands)) == 1)
				{
					// append some resolutions to the resolutions store
					self::$logical_mechanics->{STORE_RESOLUTIONS}[] = $operands;

					// set the processing results
					$processing_results = count(self::$logical_mechanics->{STORE_RESOLUTIONS}) - 1;
				}
				else

					// loop on operands 
					while (list($operand_index, $operand) = each($operands))
	
						// process the current operand resolution
						$processing_results->{$operand_index} = self::processResolution($operand);

					break;

			case SYMBOL_DISJUNCTION:

				// declare a new instance of the standard class
				$_resolution = new stdClass();

				// loop on operands
				while (list($operand_index, $operand) = each($operands))
				{
					if (is_string($operand))
					{
						// append an item to the resolutions store
						self::$logical_mechanics->{STORE_RESOLUTIONS}[] = $operand;

						// set the current private resolution property
						$_resolution->$operand_index = count(self::$logical_mechanics->{STORE_RESOLUTIONS}) - 1;
					}
					else if (is_array($operand))

						// set the current private resolution property
						$_resolution->$operand_index = self::processResolution($operand);
				}

				// return the private resolutions
				$processing_results = $_resolution;

					break;

			case SYMBOL_DOMAIN:

				// loop on operands 
				while (list($operand_index, $operand) = each($operands))

					// process the current operand resolution				
					$processing_results->{$operand_index} = self::processResolution($operand);

					break;

			case SYMBOL_EQUAL:

				// loop on operands 
				while (list($operand_index, $operand) = each($operands))

					// process the current operand resolution
					$processing_results->{$operand_index} = self::processResolution($operand);

					break;

			case SYMBOL_EXISTS:
			case SYMBOL_EXISTS_ONE_ONLY:

				// set a new instance of the standard class
				$_resolution = new stdClass();

				if (
					is_object($operands) &&
					count(get_object_vars($operands)) != 1
				)
				{
					// loop on operands
					while (list($operand_index, $operand) = each($operands))

						if (is_string($operand))
						{
							
							// append an item to the resolutions store
							self::$logical_mechanics->{STORE_RESOLUTIONS}[] = $operand;
	
							// set the current private resolution property
							$_resolution->$operand_index = count(self::$logical_mechanics->{STORE_RESOLUTIONS}) - 1;
						}
						else if (is_array($operand))

							// set the current private resolution property
							$_resolution->$operand_index = self::processResolution($operand);

					// set the processing results
					$processing_results = $_resolution;
				}
				else
				{
					// append an item to the resolutions store
					self::$logical_mechanics->{STORE_RESOLUTIONS}[] = $operands;

					// set the current private resolution property
					$processing_results = count(self::$logical_mechanics->{STORE_RESOLUTIONS}) - 1;
				}

					break;

			case SYMBOL_IMPLIES:

				// loop on operands 
				while (list($operand_index, $operand) = each($operands))

					// process the current operand resolution
					$processing_results->{$operand_index} = self::processResolution($operand);

					break;

			case SYMBOL_LEFT_RIGHT_ARROW_FROM_BAR:

				// append some resolutions to the resolutions store
				self::$logical_mechanics->{STORE_RESOLUTIONS}[] = $operands;

				// set the processing results
				$processing_results = count(self::$logical_mechanics->{STORE_RESOLUTIONS}) - 1;

					break;

			case SYMBOL_MEMBER_ATTRIBUTE:

				// append some resolutions to the resolutions store
				self::$logical_mechanics->{STORE_RESOLUTIONS}[] = $operands;

				// set the processing results
				$processing_results = count(self::$logical_mechanics->{STORE_RESOLUTIONS}) - 1;

					break;
		}

		// check the connector
		if (!empty($connector) && is_string($connector))

			// return the processing results
			return array($connector => $processing_results);
	}

	/**
    * Resolve an expression
	*
	* @param	mixed		$expression	expression
	* @param	mixed		$identifier	identifier
    * @return	mixed
	*/	
	public static function resolve($expression, $identifier = null)
	{
		// set an empty array of resolutions
		$resolutions = new stdClass();

		// check if the expression is an array
		if (is_array($expression))

			// get the current predicate operands
			list($connector, $operands) = each($expression);

		else if (is_string($expression))

			// set a value property
			$resolutions->{PROPERTY_IDENTIFIER} = $expression;

		// check the connector
		if (isset($connector))

			// switch from the connector
			switch ($connector)
			{
				case SYMBOL_ASSIGNMENT:

					// set the assignment property
					$resolutions->{PROPERTY_IDENTIFIER} = $operands->{PROPERTY_OPERAND};

						break;

				case SYMBOL_DISJUNCTION:

					$operand_collection = $operands->{PROPERTY_OPERANDS};

					// loop on operands
					while (list($operand_index, $_expression) = each($operand_collection))

						// set the resolution for the current operand
						$resolutions->{$operand_index} = self::resolve($_expression, $identifier);

						break;

				case SYMBOL_DOMAIN:

					// loop on the operands
					while (list($property, $value) = each($operands))

						// resolve the current value
						$resolutions->$property = self::resolve($value, $identifier);

						break;

				case SYMBOL_EQUAL:
	
					// loop on the operands
					while (list($property, $value) = each($operands))
					{
						// set the current resolution
						$_resolution = self::resolve($value, $identifier);
				
						// check the current resolution
						if (is_string($_resolution))
						{
							// declare a new instance of the standard class
							$resolution	= new stdClass();

							// set a value property
							$resolution->{PROPERTY_IDENTIFIER} = $_resolution;

							// set the private resolution
							$_resolution = $resolution;
						}

						// check if the private resolution is an array						
						else if (is_array($_resolution))
						{
							list($_connector, $_value) = each($_resolution);
							reset($_resolution);

							// check if the connector is an assignment symbole
							if ($_connector == SYMBOL_ASSIGNMENT)

								$connector = SYMBOL_ASSIGNMENT;
						}
						
						// resolve the current value
						$resolutions->$property = $_resolution;
					}

						break;

				case SYMBOL_EXISTS:
				case SYMBOL_EXISTS_ONE_ONLY:

					// declare an empty array 					
					$spawn = new stdClass();

					// get private resolutions
					$_resolution = self::resolve($operands->{PROPERTY_OPERAND}, $identifier);

					// get the current operand					
					list($operator, $operand) = each($operands->{PROPERTY_OPERAND});

					// set the identifier property 
					$spawn->{PROPERTY_IDENTIFIER} = $operand->{PROPERTY_RIGHT_OPERAND};

					// set the existence property
					$resolutions->{PROPERTY_ENTITY} = array(SYMBOL_ASSIGNMENT => $spawn);

					// set the existence property
					$resolutions->{PROPERTY_VALUE} = $_resolution;

						break;

				case SYMBOL_IMPLIES:

					// loop on the operands
					while (list($property, $value) = each($operands))

						// resolve the current value
						$resolutions->$property = self::resolve($value, $identifier);
	
						break;

				case SYMBOL_LEFT_RIGHT_ARROW_FROM_BAR:

					// declare an empty array of arguments
					$arguments = array();

					// resolve the arguments property
					$arguments[] = self::resolve($operands->{PROPERTY_ARGUMENTS}, $identifier);

					// resolve the callee property
					$callee = $operands->{PROPERTY_CALLEE};

					// check the arguments length
					if (is_array($arguments[0]))
					{
						// get the first argument
						list($operator, $argument) = each($arguments[0]);

						// check the argument properties
						if (count(get_object_vars($argument)) == 1)
	
							// append a new item to the resolutions
							$resolutions->{PROPERTY_IMAGE} = $callee($argument->{PROPERTY_VALUE});
					}

						break;

				case SYMBOL_MEMBER_ATTRIBUTE:

					// set the function call pattern
					$function_call_pattern = "/\(\)/";

					// check the right operand
					$function_call = preg_match($function_call_pattern, $operands->{PROPERTY_RIGHT_OPERAND});

					if ($operands->{PROPERTY_LEFT_OPERAND} == SYMBOL_SELF_REFERENCE)
					{
						$class_form_manager = CLASS_FORM_MANAGER;
	
						// get field values of the current field handler
						$field_values = $class_form_manager::getPersistentProperty(
							PROPERTY_FIELD_VALUES,
							$identifier,
							ENTITY_FORM_MANAGER
						);
	
						// set a field value
						$field_value = $field_values[$operands->{PROPERTY_RIGHT_OPERAND}];
	
						// return a field value	
						$resolutions->{PROPERTY_VALUE} = $field_value;
					}

					// check if the right operand does not contain some function call					
					else if (!$function_call)
					{
						// check if the left operand is defined as a column prefix
						if (
							defined(
								strtoupper(
									PREFIX_PREFIX.
										PREFIX_TABLE.
											PREFIX_COLUMN.
												$operands->{PROPERTY_LEFT_OPERAND}
								)
							)
						)

							// set a column prefix
							$column_prefix = constant(strtoupper(
								PREFIX_PREFIX.
									PREFIX_TABLE.
										PREFIX_COLUMN.
											$operands->{PROPERTY_LEFT_OPERAND}
								)
							);

						// check if the left operand is defined as a table name
						if (
							defined(
								strtoupper(
									PREFIX_PREFIX.
										PREFIX_TABLE.
											$operands->{PROPERTY_LEFT_OPERAND}
								)
							)
						)

							// set a table name
							$table_name = PREFIX_TABLE_CURRENT_APPLICATION.constant(strtoupper(
								PREFIX_PREFIX.
									PREFIX_TABLE.
										$operands->{PROPERTY_LEFT_OPERAND}
								)
							);

						// check the table name and column prefix
						if (!empty($table_name) && !empty($column_prefix))
						{
							// set the table property
							$resolutions->{PROPERTY_TABLE} = $table_name;

							// set the column property							
							$resolutions->{PROPERTY_COLUMN} = $column_prefix.$operands->{PROPERTY_RIGHT_OPERAND};	
						}
					}

					// check if the right operand contains some function call
					else if ($function_call)
					{
						// check if the left operand is defined as a module
						if (
							defined(
								strtoupper(
									PREFIX_PREFIX.
										PREFIX_MODULE.
											$operands->{PROPERTY_LEFT_OPERAND}
								)
							)
						)
						{
							// set a class constant
							$class_constant = constant(strtoupper(
								PREFIX_PREFIX.
									PREFIX_MODULE.
										$operands->{PROPERTY_LEFT_OPERAND}
								)
							);

							// set a class name
							$class = constant(strtoupper($class_constant));

							// set a method
							$method = substr(
								$operands->{PROPERTY_RIGHT_OPERAND},
								0,
								count($operands->{PROPERTY_RIGHT_OPERAND}) - 3
							);

							// check if a method exists for the selected class
							if (method_exists($class, $method))
							{
								// set a class
								$resolutions->{PROPERTY_CLASS} = $class;

								// set a call back method
								$resolutions->{PROPERTY_CALLBACK_METHOD} = $method;
							}
						}
					}

						break;
			}

		// check the current connector
		if (isset($connector))

			// return the resolutions
			return array($connector => $resolutions);
		else 

			// return the resolutions
			return $resolutions;
	}

	/**
    * Solve a predicate
	*
	* @param	mixed		$expression		expression
	* @param	string		$sup_operand	sup operand
	* @param	integer		$checksum		checksum
    * @return	mixed
	*/	
	public static function solvePredicate($expression, $sup_operand = null, $checksum = null)
	{
		global $class_application;

		$class_db = $class_application::getDbClass();

		$class_data_fetcher = $class_application::getDataFetcherClass();

		$class_dumper = $class_application::getDumperClass();

		$class_exception_handler = $class_application::getExceptionHandlerClass();

		// set the globals
		$globals = &self::$logical_mechanics->{STORE_GLOBALS};

		// escape meta characters
		$pattern =
			REGEXP_OPEN.
				REGEXP_CATCH_START.
					regexp_escape(PROPERTY_LEFT_OPERAND).
				REGEXP_OR.
					regexp_escape(PROPERTY_RIGHT_OPERAND).
				REGEXP_CATCH_END.								
				REGEXP_CATCH_START.
					REGEXP_WILDCARD.
				REGEXP_CATCH_END.REGEXP_FACULTATIVE.
				REGEXP_CATCH_START.
					REGEXP_WILDCARD.REGEXP_ANY.
				REGEXP_CATCH_END.REGEXP_FACULTATIVE.			
			REGEXP_CLOSE.REGEXP_MODIFIER_UNICODE
		;

		// set an empty array of resolutions
		$predicate = new stdClass();

		// check if the expression is an array
		if (is_array($expression))

			// get the current predicate operands
			list($connector, $operands) = each($expression);

		// check if the expression is an object
		else if (is_object($expression))

			// check if the expression has a table property
			if (isset($expression->{PROPERTY_TABLE}))

				// return a prepared query
				return $class_data_fetcher::prepareQuery($expression);

			// check if the expression has an image property
			else if ($expression->{PROPERTY_IMAGE})
			
				return $expression;

		switch ($connector)
		{
			case SYMBOL_ASSIGNMENT:
			case SYMBOL_EQUAL:

				// loop on operands
				while (list($operand_type, $operand) = each($operands))
				{
					// look for the pattern in the operand type
					$match = preg_match($pattern, $operand_type, $matches);

					$class_dumper::log(
						__METHOD__,
						array($matches),
						FALSE
					);

					// check if the current operand type is the left operand one
					if ($matches[1] == PROPERTY_LEFT_OPERAND)
					{
						// check if the left operand is shared
						if (empty($matches[2]))
						{
							// check if the current operand is an array
							if (!is_array($operand))

								// set the left operand holder to the current operand
								$predicate->{PROPERTY_LEFT_OPERAND} = $operand;
							else
							
								// solve an operand detected as a predicate
								$predicate->{PROPERTY_LEFT_OPERAND} = self::solvePredicate(
									$operand,
									(
										 !empty($sup_operand)
									?
										(string)$sup_operand.SYMBOL_MEMBER_ATTRIBUTE
									:
										''
									).
									$connector
								);
						}
						else

							// switch from the sub operator match
							switch ($matches[2])
							{
								case SYMBOL_EXISTS:
								default:

									// get the property value pair of the current operand	
									while (list($property, $value) = each($operand))
									{	
										// check the left operand
										if (
											!isset($predicate->{PROPERTY_LEFT_OPERAND}) ||
											!is_array($predicate->{PROPERTY_LEFT_OPERAND})
										)
										{
											$properties = new stdClass();
	
											$properties->$property = $value;
	
											// set the current property-value pair of the left operand 
											$predicate->{PROPERTY_LEFT_OPERAND} = array(
												$matches[2] => array(
													$matches[3] => $properties
												)
											);
										}
										else
										{
											if (
												!isset(
													$predicate->{PROPERTY_LEFT_OPERAND}
														[$matches[2]]
															[$matches[3]]
												) ||
												!is_object(
													$predicate->{PROPERTY_LEFT_OPERAND}
														[$matches[2]]
															[$matches[3]]
												)
											)
											{
												$predicate->{PROPERTY_LEFT_OPERAND}
													[$matches[2]]
														[$matches[3]] = array();
	
												$properties = new stdClass();
	
												$properties->$property = $value;
	
												$predicate->{PROPERTY_LEFT_OPERAND}
													[$matches[2]]
														[$matches[3]] = $properties
												;
											}
											else

												$predicate->{PROPERTY_LEFT_OPERAND}
													[$matches[2]]
														[$matches[3]]->$property = $value
												;	

										}
									}
	
										break;
							}					
					}

					// check if the current operand type is the right operand one
					else if ($matches[1] == PROPERTY_RIGHT_OPERAND)
					{
						// check if the right operand is shared
						if (empty($matches[2]))
						{
							// check if the current operand is an array
							if (!is_array($operand))

								// set the right operand holder to the current operand
								$predicate->{PROPERTY_RIGHT_OPERAND} = $operand;
							else
							{
								// solve an operand detected as a predicate
								$predicate->{PROPERTY_RIGHT_OPERAND} = self::solvePredicate(
									$operand,
									(
										 !empty($sup_operand)
									?
										(string)$sup_operand.SYMBOL_MEMBER_ATTRIBUTE
									:
										''
									).
									$connector,
									$checksum
								);
							}

							if ($checksum == 2048)
							{
								$class_dumper::log(
									__METHOD__,
									array($predicate),
									FALSE
								);
							}

							if ($checksum == 1024)
							{
								$class_dumper::log(
									__METHOD__,
									array($operand),
									FALSE
								);
							}
						}
						else

							// switch from the sub operator match
							switch ($matches[2])
							{
								case SYMBOL_DISJUNCTION:
								default:

									$class_dumper::log(
										__METHOD__,
										array($operand),
										FALSE
									);

									// get the property value pair of the current operand	
									while (list($property, $value) = each($operand))
									{	
										// check the left operand
										if (
											!isset($predicate->{PROPERTY_RIGHT_OPERAND}) ||
											!is_array($predicate->{PROPERTY_RIGHT_OPERAND})
										)
										{
											$properties = new stdClass();
	
											$properties->$property = $value;
	
											// set the current property-value pair of the left operand 
											$predicate->{PROPERTY_RIGHT_OPERAND} = array(
												$matches[2] => array(
													$matches[3] => $properties
												)
											);
										}
										else
										{
											if (
												!isset(
													$predicate->{PROPERTY_RIGHT_OPERAND}
														[$matches[2]]
															[$matches[3]]
												) ||
												!is_object(
													$predicate->{PROPERTY_RIGHT_OPERAND}
														[$matches[2]]
															[$matches[3]]
												)
											)
											{
												$predicate->{PROPERTY_RIGHT_OPERAND}
													[$matches[2]]
														[$matches[3]] = array();
	
												$properties = new stdClass();
	
												$properties->$property = $value;
	
												$predicate->{PROPERTY_RIGHT_OPERAND}
													[$matches[2]]
														[$matches[3]] = $properties
												;
											}
											else

												$predicate->{PROPERTY_RIGHT_OPERAND}
													[$matches[2]]
														[$matches[3]]->$property = $value
												;	

										}
									}

										break;								
							}
					}					
				}

				end($predicate);
				list($latest_operand_type) = each($predicate);
				reset($predicate);

				if ($checksum == 1024)

					$class_dumper::log(
						__METHOD__,
						array(
							'predicate',
							$predicate
						),
						false
					);

				if ($checksum == 2048)
				
					$class_dumper::log(
						__METHOD__,
						array(
							'predicate',
							$predicate),
						false
					);

				// loop on predicates
				while (list($operand_type, $operand) = each($predicate))
				{
					// check if a private operand has been set
					if (!isset($_operand))

						$_operand = &$predicate->{PROPERTY_RIGHT_OPERAND};

					// check if the current operand is an array
					if (is_array($operand))
					{
						$resolution = self::solvePredicate($operand);

						$class_dumper::log(
							__METHOD__,
							array(
								'property',
								PROPERTY_RESOLUTION,
								'resolution value',
								$resolution,
								'operand count',
								count($predicate->$operand_type),
								'predicate',
								$predicate,
								'operand type',
								$operand_type,
								'operand',
								$operand,
								'test'
							),
							false
						);

						// check if the resolution is a string
						if (is_object($resolution) && is_array($_operand))

							$_operand[PROPERTY_RESOLUTION][$operand_type] = $resolution;

						$class_dumper::log(
							__METHOD__,
							array(
								'predicate',
								$predicate,
								'array as operand',
								$operand
							),
							false
						);
					}

					// check if the current operand is an object
					else if (is_object($operand))

						// check if the private operand is an array	
						if (is_array($_operand))

							$_operand[PROPERTY_RESOLUTION][$operand_type] = $operand;

						// check if the private operand is an object
						else if (
							is_object($_operand) &&
							isset($_operand->{PROPERTY_TABLE})
						)
							// check if the left operand property is an object
							if (
								is_object($predicate->{PROPERTY_LEFT_OPERAND}) &&
								isset($predicate->{PROPERTY_LEFT_OPERAND}->{PROPERTY_IDENTIFIER})
							)
							{
								// set an identifier
								$identifier = $predicate->{PROPERTY_LEFT_OPERAND}->{PROPERTY_IDENTIFIER};

								// check the globals
								if (isset($globals[$identifier]))
								{
									// construct a new standard object
									$parameters = new stdClass();

									// set the sql conditions parameter
									$parameters->{SQL_CONDITIONS} = $_operand;

									// set the globals
									$container = $globals[$identifier];
			
									// set a where parameter
									$parameters->{SQL_PARAM_WHERE} = $container->$identifier;

									$class_dumper::log(
										__METHOD__,
										array(
											'parameters',
											$parameters,
											'globals',
											$globals,
											'private operand',
											$_operand,
											'predicate',
											$predicate
										),
										false
									);

									// return the parameters
									return $parameters;
								}	
							}

					// check if the current operand type is the latest one
					if ($operand_type == $latest_operand_type)
					{
						// check if the private operand has a resolution property
						if (
							$_operand &&
							is_array($_operand) &&
							isset($_operand[PROPERTY_RESOLUTION])
						)
						{
							if (
								isset($_operand[PROPERTY_RESOLUTION][PROPERTY_LEFT_OPERAND]) &&
								isset($_operand[PROPERTY_RESOLUTION][PROPERTY_LEFT_OPERAND]->{PROPERTY_VALUE})
							)
							{
								$class_dumper::log(
									__METHOD__,
									array($_operand),
									false
								);

								if (
									isset($_operand[PROPERTY_RESOLUTION][PROPERTY_RIGHT_OPERAND]) &&
									isset($_operand[PROPERTY_RESOLUTION][PROPERTY_RIGHT_OPERAND]->{'0'})
								)
								{
									list($_connector) = each($_operand);
									reset($_operand);

									if (
										isset($_operand[$_connector][0]) &&
										is_object($_operand[$_connector][0])	&&
										isset($_operand[$_connector][0]->{PROPERTY_TABLE})
									)
									{
										// get a mysqli link
										$link = $class_db::getLink();
	
										// declare an empty array of statements
										$statements = array();
	
										// set the where clause
										$where_clause = &$_operand[PROPERTY_RESOLUTION][PROPERTY_LEFT_OPERAND]->{PROPERTY_VALUE};

										// loop	the right resolution operand
										while (
											list(
												$occurence_index,
												$occurence
											) = each(
												$_operand[PROPERTY_RESOLUTION][PROPERTY_RIGHT_OPERAND])
										)
										{
											$statements[$occurence_index] = $link->prepare($occurence);
											
											// check if there is a string in the where clause 
											if (is_string($where_clause))
	
												$type = MYSQLI_STATEMENT_TYPE_STRING;
	
											// check if there is a numeric in the where clause
											else if (is_numeric($where_clause))
	
												$type = MYSQLI_STATEMENT_TYPE_INTEGER;

											// bind parameters to a statement
											$statements[$occurence_index]->bind_param($type, $where_clause);
										}
									}

									// loop on statements
									while (list($statement_index, $statement) = each($statements))
									{
										// bind result to a statement 
										$statement->bind_result($bind_result);

										// execute a statement
										$execution_result = $statement->execute();

										// fetch result of a statement
										$fetch_result = $statement->fetch();

										// check the fetch result
										if ($fetch_result)
										{
											switch (mb_substr($sup_operand, 0, 1, I18N_CHARSET_UTF8))
											{
												case SYMBOL_ASSIGNMENT:

													$bind = new stdClass();

													$bind->{SQL_CLAUSE_WHERE} =
														$_operand
															[$_connector]
																[$statement_index]->{PROPERTY_COLUMN}.
																	" = ".
																		SQL_PARAM_WILDCARD;
																		
													$bind->{SQL_PARAM_WHERE} = $bind_result;

													return $bind;

														break;

												default:

													return true;

														break;													
											}
										}
									}
								}									
							}
						}

						// check if the connector is an assignment symbol
						else if ($connector == SYMBOL_ASSIGNMENT)
						{
							// check if the left operand has an identifier property
							if (
								isset($predicate->{PROPERTY_LEFT_OPERAND}) &&
								is_object($predicate->{PROPERTY_LEFT_OPERAND}) &&
								isset($predicate->{PROPERTY_LEFT_OPERAND}->{PROPERTY_IDENTIFIER})
							)
							{
								// construct a new instance of the standard class
								$returned_value = new stdClass();

								// set the identifier property 
								$property = $predicate->{PROPERTY_LEFT_OPERAND}->{PROPERTY_IDENTIFIER};

								// set the property of a value to be returned
								$returned_value->$property = $operand;

								// append the value to be returned to the array of globals 
								$globals[$property] = $returned_value;

								$class_dumper::log(
									__METHOD__,
									array(
										'operand',
										$operand,
										'returned value',
										$returned_value,
										'globals',
										$globals
									),
									FALSE
								);

								// return a value
								return $returned_value;
							}
						}
						else if (
							is_string($_operand) &&
							isset($predicate->{PROPERTY_LEFT_OPERAND}) &&
							is_object($predicate->{PROPERTY_LEFT_OPERAND}) &&
							isset($predicate->{PROPERTY_LEFT_OPERAND}->{PROPERTY_IMAGE})
						)
						{
							// set a reference to the left operand image property
							$image = &$predicate->{PROPERTY_LEFT_OPERAND}->{PROPERTY_IMAGE};
							
							// check if the left operand equals the right operand
							if ($image == $_operand)
							
								return true;
							
							else
							
								return false;
						}

						return false;
					}
				}

				$class_dumper::log(
					__METHOD__,
					array(
						'predicate',
						$predicate
					),
					false
				);

					break;

			case SYMBOL_DISJUNCTION:

				$class_dumper::log(
					__METHOD__,
					array($operands),
					false
				);

				// loop on operands
				while (list($operand_index, $operand) = each($operands))
				{
					// check if the current operand is an object
					if (is_object($operand))

						$predicate->$operand_index = self::solvePredicate($operand);
				}

					break;

			case SYMBOL_DOMAIN:

				// loop on operands
				while (list($operand_type, $operand) = each($operands))
				{
					// look for the pattern in the operand type
					$match = preg_match($pattern, $operand_type, $matches);

					$class_dumper::log(
						__METHOD__,
						array(
							$operand,
							$operand_type						
						),
						false
					);

					if ($match)

						// check if the current operand type is the left operand one
						if ($matches[1] == PROPERTY_LEFT_OPERAND)
						{
							// check if the left operand is shared
							if (empty($matches[2]))
							{
								// check if the current operand is an array
								if (!is_array($operand))

									// set the left operand holder to the current operand
									$predicate->{PROPERTY_LEFT_OPERAND} = $operand;
								else
								
									// solve an operand detected as a predicate
									$predicate->{PROPERTY_LEFT_OPERAND} = self::solvePredicate(
										$operand,
										(
											 !empty($sup_operand)
										?
											(string)$sup_operand.SYMBOL_MEMBER_ATTRIBUTE
										:
											''
										).
										$connector
									);
							}
							else

								// switch from the sub operator match
								switch ($matches[2])
								{
									case SYMBOL_EXISTS:
									default:


									// get the property value pair of the current operand	
									while (list($property, $value) = each($operand))
									{	
										// check the left operand
										if (
											!isset($predicate->{PROPERTY_LEFT_OPERAND}) ||
											!is_array($predicate->{PROPERTY_LEFT_OPERAND})
										)
										{
											$properties = new stdClass();
	
											$properties->$property = $value;
	
											// set the current property-value pair of the left operand 
											$predicate->{PROPERTY_LEFT_OPERAND} = array(
												$matches[2] => array(
													$matches[3] => $properties
												)
											);
										}
										else
										{
											if (
												!isset(
													$predicate->{PROPERTY_LEFT_OPERAND}
														[$matches[2]]
															[$matches[3]]
												) ||
												!is_object(
													$predicate->{PROPERTY_LEFT_OPERAND}
														[$matches[2]]
															[$matches[3]]
												)
											)
											{
												$predicate->{PROPERTY_LEFT_OPERAND}
													[$matches[2]]
														[$matches[3]] = array();
	
												$properties = new stdClass();
	
												$properties->$property = $value;
	
												$predicate->{PROPERTY_LEFT_OPERAND}
													[$matches[2]]
														[$matches[3]] = $properties
												;
											}
											else

												$predicate->{PROPERTY_LEFT_OPERAND}
													[$matches[2]]
														[$matches[3]]->$property = $value
												;
										}
									}

											break;
								}					
						}

						// check if the current operand type is the right operand one
						else if ($matches[1] == PROPERTY_RIGHT_OPERAND)
						{
							// check if the right operand is shared
							if (empty($matches[2]))
							{
								// check if the current operand is an array
								if (!is_array($operand))

									// set the right operand holder to the current operand
									$predicate->{PROPERTY_RIGHT_OPERAND} = $operand;
								else
								
									// solve an operand detected as a predicate
									$predicate->{PROPERTY_RIGHT_OPERAND} = self::solvePredicate(
										$operand,
										(
											 !empty($sup_operand)
										?
											(string)$sup_operand.SYMBOL_MEMBER_ATTRIBUTE
										:
											''
										).
										$connector,
										$checksum == 1024 ? 2048 : null
									);

								if ($checksum == 1024)
								{
									$class_dumper::log(
										__METHOD__,
										array(
											$predicate,
											$operand
										),
										false // to be toggled again
									);
								}
							}
							else

								// get the property value pair of the current operand	
								while (list($property, $value) = each($operand))
								{	
									// check the left operand
									if (
										!isset($predicate->{PROPERTY_RIGHT_OPERAND}) ||
										!is_array($predicate->{PROPERTY_RIGHT_OPERAND})
									)
									{
										$properties = new stdClass();

										$properties->$property = $value;

										// set the current property-value pair of the left operand 
										$predicate->{PROPERTY_RIGHT_OPERAND} = array(
											$matches[2] => array(
												$matches[3] => $properties
											)
										);
									}
									else
									{
										if (
											!isset(
												$predicate->{PROPERTY_RIGHT_OPERAND}
													[$matches[2]]
														[$matches[3]]
											) ||
											!is_object(
												$predicate->{PROPERTY_RIGHT_OPERAND}
													[$matches[2]]
														[$matches[3]]
											)
										)
										{
											$predicate->{PROPERTY_RIGHT_OPERAND}
												[$matches[2]]
													[$matches[3]] = array();

											$properties = new stdClass();

											$properties->$property = $value;

											$predicate->{PROPERTY_RIGHT_OPERAND}
												[$matches[2]]
													[$matches[3]] = $properties
											;
										}
										else

											$predicate->{PROPERTY_RIGHT_OPERAND}
												[$matches[2]]
													[$matches[3]]->$property = $value
											;	

									}
								}
						}

						$class_dumper::log(
							__METHOD__,
							array(
								$predicate
							),
							FALSE
						);					
				}

				if (
					is_bool($predicate->{PROPERTY_RIGHT_OPERAND}) &&
					$predicate->{PROPERTY_RIGHT_OPERAND} === true
				)

					return true;

				else if (
					is_object($predicate->{PROPERTY_LEFT_OPERAND}) &&
					isset($predicate->{PROPERTY_LEFT_OPERAND}->{PROPERTY_TABLE})
				)
				{
					if (
						isset($predicate->{PROPERTY_RIGHT_OPERAND}) &&						
						is_object($predicate->{PROPERTY_RIGHT_OPERAND}) &&
						isset($predicate->{PROPERTY_RIGHT_OPERAND}->{SQL_CLAUSE_WHERE})
					)
					{
						// check the where parameter 
						if (isset($predicate->{PROPERTY_RIGHT_OPERAND}->{SQL_PARAM_WHERE}))
						
							$where_param = $predicate->{PROPERTY_RIGHT_OPERAND}->{SQL_PARAM_WHERE};
						else

							$where_param = SQL_ANYWHERE;

						// contruct parameters as a new instance of the standard class
						$parameters = new stdClass();

						$parameters->{SQL_CLAUSE_WHERE} = $predicate->{PROPERTY_RIGHT_OPERAND}->{SQL_CLAUSE_WHERE};

						// get a query to be prepared 
						$prepared_query = $class_data_fetcher::prepareQuery(
							$predicate->{PROPERTY_LEFT_OPERAND},
							$parameters
						);

						// get a mysqli link
						$link = $class_db::getLink();

						// prepare a query
						$statement = $link->prepare($prepared_query);

						// check if there is a string in the where clause 
						if (is_string($where_param))

							$type = MYSQLI_STATEMENT_TYPE_STRING;

						// check if there is a numeric in the where clause
						else if (is_numeric($where_param))

							$type = MYSQLI_STATEMENT_TYPE_INTEGER;

						// bind parameters to a statement
						$statement->bind_param($type, $where_param);										

						// bind result to a statement 
						$statement->bind_result($bind_result);

						// execute a statement
						$execution_result = $statement->execute();

						// fetch result of a statement
						$fetch_result = $statement->fetch();

						$link->close();

						// check the fetch result
						if ($fetch_result)

							return $bind_result;
					}
					else if (
						is_object($predicate->{PROPERTY_RIGHT_OPERAND}) &&
						isset($predicate->{PROPERTY_RIGHT_OPERAND}->{SQL_CONDITIONS}) &&
						is_object($predicate->{PROPERTY_RIGHT_OPERAND}->{SQL_CONDITIONS})
					) 
					{
						// set the parameters as a reference
						$parameters = &$predicate->{PROPERTY_RIGHT_OPERAND};

						// check the left operand
						if (
							is_object($predicate->{PROPERTY_LEFT_OPERAND}) &&
							isset($predicate->{PROPERTY_LEFT_OPERAND}->{PROPERTY_TABLE}) &&
							isset($parameters->{SQL_CONDITIONS}->{PROPERTY_TABLE}) &&
							$predicate->
								{PROPERTY_LEFT_OPERAND}->
									{PROPERTY_TABLE} ==
							$parameters->
								{SQL_CONDITIONS}->
									{PROPERTY_TABLE}
						)
						{
							// set the sql select clause parameter
							$parameters->{SQL_CLAUSE_SELECT} = 
								$predicate->
									{PROPERTY_LEFT_OPERAND}->
										{PROPERTY_COLUMN};

							// check the sql where param
							if (
								!empty($parameters->{SQL_PARAM_WHERE}) &&
								$parameters->{SQL_PARAM_WHERE}
							)

								// set the where clause parameter
								$where_param = $parameters->{SQL_PARAM_WHERE};
							else
							
								// set the where clause parameter
								$where_param = SQL_ANYWHERE;

							// prepare a query
							$prepared_query = $class_data_fetcher::prepareQuery(
								$parameters->{SQL_CONDITIONS},
								$parameters
							);

							// get a mysqli link
							$link = $class_db::getLink();

							// prepare a query
							$statement = $link->prepare($prepared_query);
	
							// check if there is a string in the where clause 
							if (is_string($where_param))
	
								$type = MYSQLI_STATEMENT_TYPE_STRING;
	
							// check if there is a numeric in the where clause
							else if (is_numeric($where_param))
	
								$type = MYSQLI_STATEMENT_TYPE_INTEGER;

							// bind parameters to a statement
							$statement->bind_param($type, $where_param);										
	
							// bind result to a statement 
							$statement->bind_result($bind_result);
	
							// execute a statement
							$execution_result = $statement->execute();
	
							// fetch result of a statement
							$fetch_result = $statement->fetch();
	
							// check the fetch result
							if ($fetch_result)

								// return the bind result
								return $bind_result;
						}
					}
					else

						// return false
						return false;
				}
				else

					// return false
					return false;

					break;

			case SYMBOL_IMPLIES:

				// check if the right operand is an object
				if (is_object($operands[PROPERTY_RIGHT_OPERAND]))
				
					$checksum = 1024;

				// loop on operands
				while (list($operand_type, $operand) = each($operands))
				{
					if ($operand_type == PROPERTY_LEFT_OPERAND)
					{
						// check if the current operand is an array
						if (!is_array($operand))

							// check if the current operand is an array
							$predicate->{PROPERTY_LEFT_OPERAND} = $operand;
						else 

							// solve a predicate
							$predicate->{PROPERTY_LEFT_OPERAND} = self::solvePredicate($operand, null, $checksum);

						$class_dumper::log(
							__METHOD__,
							array(
								$predicate,
								$operand
							),
							false
						);
					}
					else if (
						$operand_type == PROPERTY_RIGHT_OPERAND
					)
					{
						// check if the left operand is false
						if (
							!isset($predicate->{PROPERTY_LEFT_OPERAND}) ||
							$predicate->{PROPERTY_LEFT_OPERAND} === false
						)

							return false;
						
						// check if the current operand is an array
						if (!is_array($operand))

							$predicate->{PROPERTY_RIGHT_OPERAND} = $operand;
						else 

							// solve a predicate
							$predicate->{PROPERTY_RIGHT_OPERAND} = self::solvePredicate($operand, null, $checksum);
					}
				}

				// check the left operand
				if (
					isset($predicate->{PROPERTY_LEFT_OPERAND}) &&
					is_bool($predicate->{PROPERTY_LEFT_OPERAND}) &&
					$predicate->{PROPERTY_LEFT_OPERAND} === true &&
					isset($predicate->{PROPERTY_RIGHT_OPERAND}) &&
					is_object($predicate->{PROPERTY_RIGHT_OPERAND}) &&
					isset($predicate->{PROPERTY_RIGHT_OPERAND}->{PROPERTY_CLASS}) &&
					isset($predicate->{PROPERTY_RIGHT_OPERAND}->{PROPERTY_CALLBACK_METHOD})
				)
				{
					// set a class
					$class = $predicate->{PROPERTY_RIGHT_OPERAND}->{PROPERTY_CLASS};

					// set a method
					$method = $predicate->{PROPERTY_RIGHT_OPERAND}->{PROPERTY_CALLBACK_METHOD};

					// call a static class method
					call_user_func(array($class, $method));
				}

				$class_dumper::log(
					__METHOD__,
					array(
						'implication predicate:',
						$predicate
					),
					false
				);

					break;
		}

		return $predicate;
	}
}
