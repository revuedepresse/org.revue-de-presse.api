<?php

/**
* Data_Miner class
*
* @package  sefi
*/
class Data_Miner extends Content
{
	/**
	* Check accessor
	*
	* @param	array	$store					properties store
	* @param	string	$type					aggregation type
	* @param	string	$refresh				refreshment flag
	* @param	boolean	$preserve_whitespaces	after instruction end 
	* @return	mixed	aggregation results 
	*/		
	public static function aggregate(
		$store, $type = NULL, $refresh = FALSE, $preserve_whitespaces = TRUE
	)
	{
		global $class_application;

		$class_entity = $class_application::getEntityClass();

		$default_type =
			$class_entity::getDefaultType( NULL, ENTITY_AGGREGATION )
				->{PROPERTY_VALUE}
		;

		if ( is_null( $type ) ) $type = $default_type;

		$exception_invalid_function = sprintf(
			EXCEPTION_INVALID_PROPERTY, PROPERTY_FUNCTION
		);

		if ( ! isset( $store[PROPERTY_FUNCTION] ) )

			throw new Exception( $exception_invalid_function );

		else if ( is_string( $store[PROPERTY_FUNCTION] ) )

			$functions = array( $store[PROPERTY_FUNCTION] );

		else if ( is_array( $store[PROPERTY_FUNCTION] ) )

			$functions = $store[PROPERTY_FUNCTION];
		else
			throw new Exception( $exception_invalid_function );

		/**
		* extract variables
		*
		* @tparam	array	$summary
		* @tparam	array	$stream
		*/
		extract( self::checkAccessor(
			self::getFunctionCalls( $store, FALSE, $refresh )
		) );

		$aggregation_result = array();

		while ( list( $function, $properties ) = each( $summary ) )
		{
			if ( in_array( $function, $functions ) )
			{
				while (
					list( $index, $call ) =
						each( $properties[PROPERTY_OCCURRENCES] )
				)
				{
					$call_start =
					$token_index = $call[PROPERTY_START];

					do {
						if (
							! isset( $aggregation_result[$call_start] ) ||
							! is_array( $aggregation_result[$call_start] )
						)

							$aggregation_result[$call_start] = array();

						$aggregation_result[$call_start][$token_index] =
							$stream[$token_index]
						;

						if ( $token_index != $call_start )
	
							unset( $aggregation_result[$token_index] );
						else
						{
							unset( $aggregation_result[$token_index][0] );
							unset( $aggregation_result[$token_index][1] );
							unset( $aggregation_result[$token_index][2] );
						}

						// aggregate end instruction character
						// whenever it can be detected
						if (
							( $token_index === $call[PROPERTY_END] ) &&
							isset( $stream[$token_index + 1] ) &&
							( $end_instruction_index = $token_index + 1 ) &&
							$stream[$end_instruction_index] === ';'
						)
						{
							$aggregation_result[$call_start]
								[$end_instruction_index] =
									$stream[$end_instruction_index]
							;

							// aggregate whitespace
							// whenever it can be detected
							// after an end instruction character
							if (
								$preserve_whitespaces &&
								isset( $stream[$end_instruction_index + 1] ) && (
								$whitespace_index = $end_instruction_index + 1
								) && is_array( $stream[$whitespace_index] ) &&
								( $stream[$whitespace_index][0] === T_WHITESPACE )
							)
								$aggregation_result[$call_start]
									[$whitespace_index] =
										$stream[$whitespace_index]
								;
						}

						$token_index++;
					}
					while ( $token_index <= $call[PROPERTY_END] );
				}
			}
		}

		$accessor = array( PROPERTY_STREAM => $aggregation_result );

		return $accessor;
	}

	/**
	* Check accessor
	*
	* @param	array	$accessor $accessor
	* @return	nothing
	*/		
	public static function checkAccessor( $accessor )
	{
		if ( ! isset( $accessor[PROPERTY_STREAM] ) )

			throw new Exception(
				sprintf( EXCEPTION_INVALID_PROPERTY, PROPERTY_STREAM )
			); 

		if ( ! isset( $accessor[PROPERTY_SUMMARY] ) )

			throw new Exception(
				sprintf( EXCEPTION_INVALID_PROPERTY, PROPERTY_SUMMARY )
			); 

		if ( ! isset( $accessor[PROPERTY_SUMMARY_NATIVE] ) )

			throw new Exception(
				sprintf( EXCEPTION_INVALID_PROPERTY, PROPERTY_SUMMARY_NATIVE )
			);

		return $accessor;
	}

	/**
	* Count delimiters
	*
	* @param	array	$tokens				tokens
	* @param	boolean	$restore_delimiters	restore delimiters
	* @return	mixed	delimiters counts
	*/
	public static function countDelimiters(
		&$tokens = NULL, $restore_delimiters = TRUE
	)
	{
		global $class_application, $verbose_mode;
		$class_dumper = $class_application::getDumperClass();

		$callback_parameters = NULL;

		$closing_delimiters =
		$mapping_delimiters =
		$opening_delimiters = 
		$index_last = array();

		$syntax_error = FALSE;

		$type_closing = DELIMITER_TYPE_CLOSING;
		$type_opening = DELIMITER_TYPE_OPENING;
		
		$count_tokens = count( $tokens );

		for ( 
			$token_index = 0;
			$token_index < $count_tokens;
			$token_index++
		)
		{
			if ( isset( $tokens[$token_index][PROPERTY_TYPE] ) )
			{
				$delimiter_type = $tokens[$token_index][PROPERTY_TYPE];
	
				if (
					( $delimiter_type === $type_opening ) &&
					(
						! isset( $mapping_delimiters[$token_index] ) ||
						( $mapping_delimiters[$token_index] === $token_index )
					)
				)
				{
					$mapping_delimiters[$token_index] = $token_index;
				
					// encountering an opening delimiter
					$opening_delimiters[$token_index] = $token_index;
				
					$index_last[] = array( 
						PROPERTY_TYPE => $type_opening,
						PROPERTY_VALUE => $token_index
					);
				}
				else if ( 
					( $delimiter_type === $type_closing ) &&
					! in_array( $token_index, $mapping_delimiters )
				)
				{
					// encountering a closing delimiter.    
					$closing_delimiters[$token_index] = $token_index;
				
					$previous_token_index = count( $index_last ) - 1;
				
					if (
						( $previous_token_index > 0 ) && 
						(
							$index_last[$previous_token_index]
								[PROPERTY_TYPE] === $type_opening
						)
					)
					{
						$index_value =
							$index_last[$previous_token_index][PROPERTY_VALUE]
						;
						$mapping_delimiters[$index_value] = $token_index;
						array_pop( $index_last );
						array_pop( $opening_delimiters );
						array_pop( $closing_delimiters );
					}
					else
						$index_last[] = array( 
							PROPERTY_TYPE => $type_closing,
							PROPERTY_VALUE => $token_index
						);

				}	
			}

			$new_loop =
				( $token_index === ( $count_tokens - 1 ) ) &&
				(
					count( $opening_delimiters ) ===
						count( $closing_delimiters )
				) &&
				( count( $closing_delimiters ) > 0 )
			;

			if ( $new_loop ) $token_index = 0;
		}

		$count_closing = count( $closing_delimiters );
		$count_opening = count( $opening_delimiters );

		if ( $count_opening != $count_closing ) $syntax_error = TRUE;

		if ( $syntax_error )
		{
			$context = array(
				'[program termination at line # ' . __LINE__ . ']',
				'[closing delimiters]', $closing_delimiters,
				'[opening delimiters]', $opening_delimiters,
				'[closing delimiters count]', $count_closing, 
				'[opening delimiters count]', $count_opening,
				'[mapping delimiters]', $mapping_delimiters,
				'[stream]', $tokens
			);
			
			if ( INTROSPECTION_VERBOSE )
			{
				$class_dumper::log(
					__METHOD__,
					array(
						'[closing delimiters count]', $count_closing,
						'[opening delimiters count]', $count_opening,
					), $syntax_error
				);
	
				fprint( $context, TRUE, TRUE );
			}
			else error_log( date( 'Ymd_Hi' ) . print_r( $context, TRUE ) );
		}
		
		if ( $restore_delimiters )

			// restore native delimiters tokens
			while(
				list( $opening_delimiter, $closing_delimiter ) =
					each( $mapping_delimiters )
			)
			{
				$tokens[$opening_delimiter] = '(';
				$tokens[$closing_delimiter] = ')';
			}

		$callback_parameters = array(
			PROPERTY_DELIMITERS_CLOSING => $closing_delimiters,
			PROPERTY_DELIMITERS_MAPPING => $mapping_delimiters,
			PROPERTY_DELIMITERS_OPENING => $opening_delimiters,
			PROPERTY_ERROR_SYNTAX => $syntax_error
		);

		return $callback_parameters;
	}

	/**
	* Check if a token might be a function call
	*
	* @param	integer	$target_index	target index
	* @param	mixed	$target			target
	* @param	array	&$tokens			tokens
	* @return	boolean	indicator
	*/
	public static function functionCallToken( $target_index, $target, &$tokens )
	{
		$function_call_alike =
			( $target === 'T_STRING' ) && isset( $tokens[$target_index + 1] ) &&
			( $tokens[$target_index + 1] === '(' ) &&
			(
				! isset( $tokens[$target_index - 2] ) || ! in_array(
					$tokens[$target_index - 2],
					array( 'T_NEW', 'T_FUNCTION' )
				)
			) && (
				! isset( $tokens[$target_index - 3] ) || ! in_array(
					$tokens[$target_index - 3],
					array( 'T_NEW', 'T_FUNCTION' )
				) 
			)
		;

		return $function_call_alike;
	}

	/**
	* Get parameters passed when calling a function
	*
	* @param	integer	$first_opening_parenthesis	first opening parenthesis
	* @param	integer	$last_closing_parenthesis	last closing parenthesis
	* @param	integer	$target_index				target index
	* @param	array	&$stream					stream
	* @return	array	properties
	*/
	public static function getFunctionCallProperties(
		$first_opening_parenthesis,
		$last_closing_parenthesis,
		$target_index,
		&$stream
	)
	{
		$arguments =
		$comma_indexes = array();

		$argument_index = 0;		
		$cursor_index = $first_opening_parenthesis;
		$first_argument_index = NULL;

		$remove_empty_strings = TRUE;

		$triple = $stream[$target_index];
		$function = $triple[1];

		if ( $last_closing_parenthesis !== $first_opening_parenthesis + 1 )
		{
			do {
				$cursor_index++;
	
				if (
					is_array( $stream[$cursor_index] )
					|| is_string( $stream[$cursor_index] )
				)
				{
					$separator = FALSE;
	
					if ( is_array( $stream[$cursor_index] ) )
	
						$argument = $stream[$cursor_index][1];
	
					else if (
						is_string( $stream[$cursor_index] ) &&
						$stream[$cursor_index] === ','
					)
					{
						$arguments[$argument_index][PROPERTY_ARGUMENT] = implode(
							$arguments[$argument_index][PROPERTY_VALUE]
						);
						$argument_index++;
						$argument = $stream[$cursor_index];
						$comma_indexes[] = $cursor_index;
						$separator = TRUE;
					}
					else
						$argument = $stream[$cursor_index];
	
					$argument_value = (
						( strlen( trim( $argument ) ) === 0 )
						? (
							$remove_empty_strings
							? ''
							: '"' . $argument . '"'
						)
						: $argument
					);
	
					$argument_properties = array(
						PROPERTY_INDEX => $cursor_index,
						PROPERTY_VALUE => $argument_value
					);
	
					if ( ! $separator )
					{
						$arguments[$argument_index][PROPERTY_VALUE][] =
							$argument_properties[PROPERTY_VALUE]
						;					
						$arguments[$argument_index][PROPERTY_TOKEN][] =
							$argument_properties
						;
					}
				}
	
			} while(
				$cursor_index < $last_closing_parenthesis - 1
			);
	
			$arguments[count( $arguments ) - 1][PROPERTY_ARGUMENT] =
				implode( $arguments[count( $arguments ) - 1][PROPERTY_VALUE] )
			;
		}

		$properties = array(
			PROPERTY_ARGUMENTS => $arguments,
			PROPERTY_END => $last_closing_parenthesis,
			PROPERTY_START => $target_index,
			PROPERTY_NAME => $function,
			PROPERTY_PARENTHESIS_CLOSING => $last_closing_parenthesis,
			PROPERTY_PARENTHESIS_OPENING => $first_opening_parenthesis
		);
		
		return $properties;
	}

	/**
	* Get the functions calls in a PHP script
	*
	* @param	string	$store		store
	* @param	boolean	$sorted_out	sorting flag
	* @param	string	$refresh	refreshment flag	
	* @return	mixed	function calls
	* @todo		check validity of detected functions 
	* @todo		build dependencies mapping 
	*/
	public static function getFunctionCalls(
		$store = NULL, $sorted_out = FALSE, $refresh = FALSE
	)
	{
		global $class_application;

		$ns = NAMESPACE_CID;

		$class_dumper = $class_application::getDumperClass();
		$class_memento = $class_application::getMementoClass();
		$class_tokens_stream = $class_application::getTokensStreamClass( $ns );

		$accessor =
		$function_calls = array();

		if ( ! arr_valid( $store ) )

			throw new Exception(
				EXCEPTION_INVALID_ARGUMENT .
				' (' . str_replace( '_', ' ', PROPERTY_STORE ) . ')'
			);

		$key = md5( serialize( $store ) );

		if (
			is_null( $value = $class_memento::remind( $key ) ) ||
			( $refresh === TRUE )
		)
		{
			if ( isset( $store[PROPERTY_URI_REQUEST] ) )
				$request_uri = $store[PROPERTY_URI_REQUEST];
	
			$properties = array(
				PROPERTY_MODE_ACCESS => FILE_ACCESS_MODE_READ_ONLY,
				PROPERTY_URI_REQUEST => $request_uri
			);
	
			$token_stream = $class_tokens_stream::shape( $properties );
	
			$stream = $token_stream->{PROPERTY_TOKEN};
			$tokens_count = count( $stream );
			$tokens = $class_tokens_stream::extractTokens( $stream );

			while ( list( $token_index , $token ) = each( $tokens ) )
			{
				if ( self::functionCallToken( $token_index, $token, $tokens ) )
				{
					$previous_opening_delimiter =
						self::getPreviousOpeningDelimiter(
							$token_index, $stream
						)
					;

					$next_instruction_delimiter =
						self::getNextInstructionSeparator(
							$token_index, $previous_opening_delimiter, $stream
						)
					;

					$first_opening_parenthesis =
						self::getNextOpeningDelimiter( $token_index, $stream )
					;

					$last_closing_parenthesis =
						self::getLastClosingDelimiter(
							$first_opening_parenthesis, $stream
						)
					;		

					$properties = self::getFunctionCallProperties(
						$first_opening_parenthesis, $last_closing_parenthesis,
						$token_index,
						$stream
					);
	
					$function = $properties[PROPERTY_NAME];
	
					if ( ! isset( $function_calls[$function] ) )
					{
						$function_calls[$function][PROPERTY_COUNT] = 1;
						$function_calls[$function][PROPERTY_OCCURRENCES] =
							array( $properties )
						;	
					}
					else
					{
						$function_calls[$function][PROPERTY_COUNT]++ ;
						$function_calls[$function][PROPERTY_OCCURRENCES][]
							= $properties
						;
					}
				}
			}

			$function_calls_as_is = $function_calls;

			if ( $sorted_out )
				self::sortFunctionCallsByFirstParameters( $function_calls );

			$accessor = array(
				PROPERTY_STREAM => &$stream,
				PROPERTY_SUMMARY => $function_calls,
				PROPERTY_SUMMARY_NATIVE => $function_calls_as_is
			);

			$class_memento::write(
				array(
					PROPERTY_KEY => $key,
					PROPERTY_INPUT => $store,
					PROPERTY_OUTPUT => $accessor,
					PROPERTY_STREAM => $token_stream
				),
				TRUE
			);
		}
		else if ( isset( $value[PROPERTY_OUTPUT] ) )

			$accessor = $value[PROPERTY_OUTPUT];

		return $accessor;
	}

	/**
	* Get the substream of functions calls in a PHP script
	*
	* @param	string	$store		store
	* @param	string	$refresh	refreshment flag	
	* @return	mixed	function calls
	*/
	public static function getFunctionCallsSubstream(
		$store = NULL, $refresh = FALSE
	)
	{
		$accessor =
		$unset_tokens = array();

		/**
		* extract variables
		*
		* @tparam	array	$native_summary
		* @tparam	array	$summary
		* @tparam	array	$stream
		*/
		extract( self::checkAccessor(
			self::getFunctionCalls( $store, TRUE, $refresh )
		) );

		if ( is_array( $summary ) && count( $summary ) )

			foreach( $summary as $function => $function_summary )
			{
				if (
					is_array( $function_summary ) &&
					count( $function_summary )
				)
					foreach(
						$function_summary[PROPERTY_OCCURRENCES]
							as $index => $properties
					)
					{
						$function_ends_at =
							$native_summary[$function][PROPERTY_OCCURRENCES]
								[$index][PROPERTY_END]
						;

						$function_starts_now_at = $properties[PROPERTY_START];

						$function_starts_at =
							$native_summary[$function][PROPERTY_OCCURRENCES]
								[$index][PROPERTY_START]
						;

						if ( $function_starts_at !== $function_starts_now_at )
						{				
							$token_index = $function_starts_at;
							$replacement_index = $function_starts_now_at;

							do {
								$unset_tokens[$token_index] = $stream[$token_index];

								if (
									! isset( 
										$unset_tokens[$replacement_index]
									)
								)
									$stream[$token_index] =
										$stream[$replacement_index]
									;
								else
									$stream[$token_index] =
										$unset_tokens[$replacement_index]
									;

								$replacement_index++;
								$token_index++;
							}
							while( $token_index <= $function_ends_at );
						}
					}
			}

		$updated_accessor = array(
			PROPERTY_STREAM => $stream
		);

		return $updated_accessor;
	}

	/**
	* Get the last closing delimiter in a stream
	*
	* @param	integer	$first_opening_delimiter	first opening delimiter index
	* @param	array	&$stream					stream
	* @return	integer	token index	
	*/
	public static function getLastClosingDelimiter(
		$first_opening_delimiter, &$stream
	)
	{
		/**
		* Extract variables
		*
		* @tparam	array	$closing_delimiters
		* @tparam	array	$mapping_delimiters
		* @tparam	array	$opening_delimiters
		* @tparam	boolean	$syntax_error
		*/
		extract( self::countDelimiters( $stream ) );
	
		if ( $syntax_error )
			throw new Exception ( 
				sprintf( EXCEPTION_INVALID_ENTITY, ENTITY_SYNTAX )
			);
	
		if ( isset( $mapping_delimiters[$first_opening_delimiter] ) )
			$last_closing_delimiter =
				$mapping_delimiters[$first_opening_delimiter]
			;
		else
			throw new Exception ( 
				sprintf( EXCEPTION_INVALID_OPERATION, ENTITY_MAPPING )
			);
		
		return $last_closing_delimiter;
	}

	/**
	* Get the next instruction separator
	*
	* @param	integer	$token_index				token index
	* @param	integer	$previous_opening_delimiter previous opening delimiter	
	* @param	array	&$stream					stream
	* @return	integer	token index	
	*/
	public static function getNextInstructionSeparator(
		$token_index, $previous_opening_delimiter, &$stream
	)
	{
		$instruction_separator_index = $token_index;
		$closing_delimiters =
		$opening_delimiters = array();

		if (
			is_string( $stream[$previous_opening_delimiter] )
			&& ( $stream[$previous_opening_delimiter] === '(' )
		)
			$instruction_separator_index = $previous_opening_delimiter;

		do {
			if ( $stream[$instruction_separator_index] === '(' )
			{
				$stream[$instruction_separator_index] = array(
					PROPERTY_TYPE => DELIMITER_TYPE_OPENING,
					PROPERTY_VALUE =>
						$stream[$instruction_separator_index]
				);
				$opening_delimiters[$instruction_separator_index] =
					$instruction_separator_index
				;
			}
			else if ( $stream[$instruction_separator_index] === ')' )
			{
				$stream[$instruction_separator_index] = array(
					PROPERTY_TYPE => DELIMITER_TYPE_CLOSING,
					PROPERTY_VALUE =>
						$stream[$instruction_separator_index]
				);
				$closing_delimiters[$instruction_separator_index] =
					$instruction_separator_index
				;				
			}

			$instruction_separator_index++;
		} while ( (
				! is_string( $stream[$instruction_separator_index] ) ||
				( $stream[$instruction_separator_index] !== ';' )
			) && (
				( count( $opening_delimiters ) === 0 ) ||
				( count( $opening_delimiters ) > 0 ) && (
					count( $opening_delimiters ) !==
						( count( $closing_delimiters ) )
				)
		) );

		return $instruction_separator_index;
	}

	/**
	* Get the next opening delimiter in a stream
	*
	* @param	integer	$target_index	target index
	* @param	array	&$stream			stream
	* @return	integer	token index	
	*/
	public static function getNextOpeningDelimiter( $target_index, &$stream )
	{
		$opening_parenthesis_token_index = $target_index + 1;

		return $opening_parenthesis_token_index;
	}

	/**
	* Get the previous opening delimiter in a stream
	*
	* @param	integer	$target_index	target index
	* @param	array	&$stream			stream
	* @return	integer	token index	
	*/
	public static function getPreviousOpeningDelimiter( $target_index, &$stream )
	{
		$previous_opening_delimiter = $target_index;
	
		do { $previous_opening_delimiter--; }
		while (
			(
				! is_string(
					$stream[$previous_opening_delimiter]
				) ||
				! in_array(
					$stream[$previous_opening_delimiter],
					array( '(', ')' )
				)
			) &&
			( $previous_opening_delimiter > 0 )
		);
		
		return $previous_opening_delimiter;
	}

	/**
	* Get the class signature
	*
	* @param	boolean	$namespace	namespace flag
	* @return	string	signature
	*/
	public static function getSignature( $namespace = TRUE )
	{
		$_class = __CLASS__;

		if ( ! $namespace )

			list( $_namespace, $_class ) = explode( '\\', __CLASS__ );

		return $_class;
	}

	/**
	* Sort function calls by parameters names
	*
	* @param	array	$a	left member
	* @param	array	$b	right member
	* @return	integer	sorting value
	*/
	public static function sortByParameterName( $a, $b )
	{
		$result = 0;
		
		if (
			! isset( $a[PROPERTY_NAME] ) ||
			! isset( $b[PROPERTY_NAME] )
		)
			throw new Exception(
				EXCEPTION_INVALID_ARGUMENT .
				' ('. PROPERTY_NAME . ')'
			);

		if (
			! isset( $a[PROPERTY_ARGUMENTS] ) ||
			! isset( $b[PROPERTY_ARGUMENTS] )
		)
			throw new Exception(
				EXCEPTION_INVALID_ARGUMENT .
				' (' . PROPERTY_ARGUMENTS . ')'
			);

		if ( (
				isset( $a[PROPERTY_ARGUMENTS][0] ) &&
				isset( $a[PROPERTY_ARGUMENTS][0][PROPERTY_ARGUMENT] )
			) && ( (
					isset( $b[PROPERTY_ARGUMENTS][0] ) &&
					isset( $b[PROPERTY_ARGUMENTS][0][PROPERTY_ARGUMENT] ) &&
					( $a[PROPERTY_ARGUMENTS][0][PROPERTY_ARGUMENT] >
						$b[PROPERTY_ARGUMENTS][0][PROPERTY_ARGUMENT] )
			) || ! isset( $b[PROPERTY_ARGUMENTS][0] ) )
		)
			$result = 1;
		
		else if ( (
				isset( $b[PROPERTY_ARGUMENTS][0] ) &&
				isset( $b[PROPERTY_ARGUMENTS][0][PROPERTY_ARGUMENT] ) 
			) && ( (
					isset( $a[PROPERTY_ARGUMENTS][0] ) &&
					isset( $a[PROPERTY_ARGUMENTS][0][PROPERTY_ARGUMENT] ) &&
					( $a[PROPERTY_ARGUMENTS][0][PROPERTY_ARGUMENT] <
						$b[PROPERTY_ARGUMENTS][0][PROPERTY_ARGUMENT] )
			) || ! isset( $a[PROPERTY_ARGUMENTS][0] ) )
		)
			$result = -1;

		return $result;
	}
	/**
	* Sort function calls by first parameters
	*
	* @param	array	&$function_calls 	function calls
	* @return 	nothing
	*/
	public static function sortFunctionCallsByFirstParameters( &$function_calls )
	{
		if ( is_array( $function_calls ) )
	
			foreach ( $function_calls as $function => $properties )

				usort(
					$function_calls[$function][PROPERTY_OCCURRENCES],
					array( __CLASS__, 'sortByParameterName' )
				);
	}
}

/**
*************
* Changes log
*
*************
* 2011 10 11
*************
*
* project :: wtw ::
*
* development :: introspection ::
* 
* Start implementing the Data_Miner class
*
* methods affected ::
*
* 	DATA_MINER::getFunctionCalls
* 
* (branch 0.1 :: revision :: 705)
*
*************
* 2011 10 13
* 2011 10 16
*************
*
* project :: wtw ::
*
* development :: introspection ::
* 
* Implement function calls extraction
*
* methods affected ::
*
*	DATA_MINER::countDelimiters
*	DATA_MINER::functionCallToken
*	DATA_MINER::getLastClosingDelimiter
*	DATA_MINER::getFunctionCallArguments
*	DATA_MINER::getFunctionCalls
*	DATA_MINER::getFunctionCallsSubstream
*	DATA_MINER::getNextInstructionSeparator
*	DATA_MINER::getNextOpeningDelimiter
*	DATA_MINER::getPreviousOpeningDelimiter
*	DATA_MINER::sortByParameterName
*	DATA_MINER::sortFunctionCallsByFirstParameters
*
* (branch 0.1 :: revision :: 710)
* (branch 0.2 :: revision :: 390)
*
*************
* 2011 10 13
* 2011 10 16
*************
*
* project :: wtw ::
* 
* development :: introspection ::
* 
* Implement tokens aggregation within a substream
*
* methods affected ::
*
*	DATA_MINER::aggregate
*
* (branch 0.1 :: revision :: 722)
* (branch 0.2 :: revision :: 393)
*
*************
* 2012 05 05
*************
*
* project :: wtw ::
* 
* development :: introspection ::
* 
* Implement removal of trailing whitespaces 
*
* methods affected ::
*
*	DATA_MINER::aggregate
*
* (branch 0.1 :: revision :: 898)
*
*/