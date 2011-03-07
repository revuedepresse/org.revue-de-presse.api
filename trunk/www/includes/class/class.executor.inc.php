<?php
/**
*************
* Changes log
*
*************
* 2011 03 07
*************
*
* Add the author CSS selectors to the stylesheet
* 
* EXECUTOR :: perform
* 
* (revision 588)
*
*/

/**
* Executor class
*
* Class to perform actions
* @package  sefi
*/
class Executor extends Processor
{
	/**
	* Execute a query
	*
	* @param	string	$query	query
	* @return 	mixed	results
	*/
	public static function executeQuery($query)
	{
		$class_db = CLASS_DB;

		// execute a query and return the results
		return $class_db::query($query);
	}

	/**
	* Perform an action
	*
	* @param	string	$affordance	affordance
	* @param	object	$properties	properties
	* @return 	nothing
	*/
	public static function perform( $affordance, $properties )
	{
		// set the application class name
		global $class_application;

		// set the database connection class name
		$class_db = $class_application::getDbClass();

		// set the dumper class name
		$class_dumper = $class_application::getDumperClass();

		// set the entity class name
		$class_entity = $class_application::getEntityClass();

		// set the executor class name
		$class_interceptor = $class_application::getInterceptorClass();
		
		$callback_parameters = array();

		// set the insight class name
		$class_insight = $class_application::getInsightClass();

		// set the member class name
		$class_member = $class_application::getMemberClass();

		// set the user handler class name
		$class_user_handler = $class_application::getUserHandlerClass();

		// set the stylesheet class name
		$class_stylesheet = $class_application::getStylesheetClass();

		// set the template engine class name
		$class_template_engine = $class_application::getTemplateEngineClass();

		// set the toolbox class name
		$class_toolbox =  $class_application::getToolboxClass();

		$affordance = $class_toolbox::translate_entity(
			$affordance,
			$to = ENTITY_AFFORDANCE,
			$from = ENTITY_URI
		);

		// get a link
		$link = $class_db::getLink();

		switch ( $affordance )
		{
			case AFFORDANCE_LOAD.".".ENTITY_STYLESHEET:

				$placeholders = $class_stylesheet::getPlaceholders();

				// declare a new instance of the template engine
				$template_engine = new $class_template_engine();

				// set the parameters
				$parameters = array(

					'classes' => array(
							ENTITY_ELEMENT."_".STYLE_CLASS_AUTHOR => array(
	
							substr(PREFIX_CLASS, 0, -1)."_".ENTITY_PROPERTY."_".STYLE_CLASS_AUTHOR =>
		
								ENTITY_PROPERTY."_".STYLE_CLASS_AUTHOR,
		
							substr(PREFIX_CLASS, 0, -1)."_".ENTITY_SEPARATOR."_".STYLE_CLASS_AUTHOR =>
		
								ENTITY_SEPARATOR."_".STYLE_CLASS_AUTHOR,
	
							substr(PREFIX_CLASS, 0, -1)."_".ENTITY_VALUE."_".STYLE_CLASS_AUTHOR =>
		
								ENTITY_VALUE."_".STYLE_CLASS_AUTHOR
						),

						ENTITY_ELEMENT."_".STYLE_CLASS_TITLE => array(
	
							substr(PREFIX_CLASS, 0, -1)."_".ENTITY_PROPERTY."_".STYLE_CLASS_TITLE =>
		
								ENTITY_PROPERTY."_".STYLE_CLASS_TITLE,
		
							substr(PREFIX_CLASS, 0, -1)."_".ENTITY_SEPARATOR."_".STYLE_CLASS_TITLE =>
		
								ENTITY_SEPARATOR."_".STYLE_CLASS_TITLE,
	
							substr(PREFIX_CLASS, 0, -1)."_".ENTITY_VALUE."_".STYLE_CLASS_TITLE =>
		
								ENTITY_VALUE."_".STYLE_CLASS_TITLE
						),

						ENTITY_ELEMENT."_".STYLE_CLASS_KEYWORDS => array(
	
							substr(PREFIX_CLASS, 0, -1)."_".ENTITY_PROPERTY."_".STYLE_CLASS_KEYWORDS =>
		
								ENTITY_PROPERTY."_".STYLE_CLASS_KEYWORDS,
		
							substr(PREFIX_CLASS, 0, -1)."_".ENTITY_SEPARATOR."_".STYLE_CLASS_KEYWORDS =>
		
								ENTITY_SEPARATOR."_".STYLE_CLASS_KEYWORDS,
	
							substr(PREFIX_CLASS, 0, -1)."_".ENTITY_VALUE."_".STYLE_CLASS_KEYWORDS =>
		
								ENTITY_VALUE."_".STYLE_CLASS_KEYWORDS
						)
					),

					'links' => array(

						STYLE_CLASS_ENABLED,

						STYLE_CLASS_DISABLED,

						STYLE_CLASS_LINK,

						STYLE_CLASS_POSITION_BOTTOM,

						STYLE_CLASS_POSITION_NEXT,
						
						STYLE_CLASS_POSITION_PREVIOUS,

						STYLE_CLASS_POSITION_TOP
					),

					'enabled' => STYLE_CLASS_ENABLED,

					'disabled' => STYLE_CLASS_DISABLED,

					'line_feed' => "\n"
				);

				if (
					is_array( $placeholders ) &&
					count( $placeholders )
				)

					while ( list( , $placeholder ) = each( $placeholders ) )

						$parameters[$placeholder->{PROPERTY_NAME}] =
							$placeholder->{PROPERTY_VALUE}
						;

				// loop on parameters
				foreach ( $parameters as $property => $value )

					// assign property
					$template_engine->assign( $property, $value );

				// send appropriate headers
				header("Content-type: ".MIME_TYPE_TEXT_CSS,';charset='.I18N_CHARSET_UTF8);

				// display the parameters through a template
				$template_engine->display( TPL_STYLESHEET );

				// clear all cache
				$template_engine->clear();
	
					break;

			case AFFORDANCE_REMOVE_INSIGHT_NODE:
			case AFFORDANCE_REMOVE:

				$entity_insight_node = $class_entity::getByName(ENTITY_INSIGHT_NODE);

				// check the affordance store
				if (
					!isset($_SESSION[SESSION_STORE_AFFORDANCE]) ||
					!isset($_SESSION[SESSION_STORE_AFFORDANCE][AFFORDANCE_CONFIRM])
				)

					// jump to the confirmation page
					$class_application::jumpTo(
						URI_AFFORDANCE_CONFIRM.'?'.
						GET_REFERER.'='.urlencode("/".
							$class_toolbox::translate_entity($affordance).'-'.
							$properties->{PROPERTY_IDENTIFIER}
						)
					);

				// check if the affordance store points to the current request URI
				else if (
					$_SERVER['REQUEST_URI'] == $_SESSION[SESSION_STORE_AFFORDANCE][AFFORDANCE_CONFIRM]
				)
				{
					// unset a session store
					unset($_SESSION[SESSION_STORE_AFFORDANCE][AFFORDANCE_CONFIRM]);				

					// check if an entity is bound to the current route
					if (!is_numeric($properties->{PROPERTY_ENTITY}))
					{
						// get the primal definition
						$primal_definition = $class_interceptor::getPrimalDefinition();
	
						// remove the primal definition
						$class_interceptor::forgetPrimalDefinition();
	
						if (
							isset($primal_definition) &&
							$primal_definition == CONTENT_TYPE_DOCUMENT ||
							$primal_definition == ACTION_DISPLAY_DOCUMENT
						)
						{
							// set a disabled status
							$disabled_status = DOCUMENT_STATUS_INACTIVE;
			
							// set the update document status query
							$update_document_status = '
								UPDATE '.TABLE_FEED.' SET
									fd_status = ?
								WHERE
									fd_id = ?
							';
		
							// prepare a statement
							$statement = $link->prepare($update_document_status);
						
							// bind parameters to a statement
							$statement->bind_param(
								MYSQLI_STATEMENT_TYPE_INTEGER.
									MYSQLI_STATEMENT_TYPE_INTEGER,
								$disabled_status,
								$properties->{PROPERTY_IDENTIFIER}
							);
						}
						else
						{
							// set a disabled status
							$disabled_status = PHOTOGRAPH_STATUS_DISABLED;
			
							// set the update photograph status query
							$update_photograph_status = "
								UPDATE ".TABLE_PHOTOGRAPH." SET
									pht_status = ?
								WHERE
									photo_id = ?
							";
		
							// prepare a statement
							$statement = $link->prepare($update_photograph_status);
						
							// bind parameters to a statement
							$statement->bind_param(
								MYSQLI_STATEMENT_TYPE_INTEGER.
									MYSQLI_STATEMENT_TYPE_INTEGER,
								$disabled_status,
								$properties->{PROPERTY_IDENTIFIER}
							);
						}
					
						// execute a statement
						$execution_result = $statement->execute();
	
						// check the fetched result
						if ($execution_result)
						{						
							// get the default border
							$default_border = $class_interceptor::getDefaultBorder();
		
							// get the previous position
							$previous_position = $class_interceptor::getPreviousPosition();
	
							if ($previous_position != URI_ACTION_DISPLAY_DOCUMENT)
							{
								if ($default_border != 1)
		
									// set the primal definition as the previously visited content identifier
									$previous_position .= '-'.($default_border - 1);
							}
							else if (
								isset($_SESSION[STORE_SEARCH_RESULTS]) && 
								isset($_SESSION[STORE_SEARCH_RESULTS]->items[$default_border])
							)
							{
								$keys = array_keys($_SESSION[STORE_SEARCH_RESULTS]->items);
	
								if (in_array($default_border, $keys))
								{
									if (isset($keys[array_search($default_border, $keys) + 1]))
	
										$previous_position =
											PREFIX_ROOT.
												str::rewrite(
													$_SESSION[STORE_SEARCH_RESULTS]->items[
														$keys[array_search($default_border, $keys) + 1]
													]->title
												).
												'-'.GET_DOCUMENT_IDENTIFIER_REWRITTEN.
													$keys[array_search($default_border, $keys) + 1];
	
									unset($_SESSION[STORE_SEARCH_RESULTS]->items[$default_border]);
								}
							}
	
							// forget the default border
							$class_interceptor::forgetDefaultBorder();
		
							// forget the previous position
							$class_interceptor::forgetPreviousPosition();	
	
							// check the previous position
							if (!empty($previous_position))
	
								// go to the destination
								$class_application::jumpTo($previous_position);							
							else
	
								// jump to the root
								$class_application::jumpTo(PREFIX_ROOT);
						}
					}
					else if (
						isset($properties->{PROPERTY_ENTITY}) &&
						is_object($entity_insight_node) &&
						isset($entity_insight_node->{PROPERTY_ID}) &&
						$properties->{PROPERTY_ENTITY} == $entity_insight_node->{PROPERTY_ID}
					)

						// process a request by taking advantage of the insight class
						return $class_insight::processRequest($properties);
					else
					
						// throw an operation failure exception
						throw new Exception(EXCEPTION_OPERATION_FAILURE_PROCESSING);
				}

					break;

			default:

				// check if a user is logged in
				if ( $class_user_handler::loggedIn() )
				{
					$id = $properties->{PROPERTY_IDENTIFIER};

					// set the select flag query
					$select_flag = "
						SELECT
							flg_status
						FROM
							".TABLE_FLAG."
						WHERE
							flg_target = ? AND
							flg_type = ? AND
							usr_id = ?
						ORDER BY 
							flg_id
						DESC							
						LIMIT 1
					";

					// set the insert flag query
					$insert_flag = "
						INSERT INTO ".TABLE_FLAG." (
							flg_status,
							flg_target,
							flg_type,
							usr_id
						) VALUES (
							?,
							?,
							?,
							?
						)
					";

					if (
						defined(
							strtoupper(
								ENTITY_FLAG.'_'.
									ENTITY_TYPE.'_'.
										$class_toolbox::translate_entity(
											$affordance,
											ENTITY_SMARTY_VARIABLE
										)
							)
						)
					)

						// set the flag type
						$flag_type = constant(
							strtoupper(
								ENTITY_FLAG.'_'.
									ENTITY_TYPE.'_'.
										$class_toolbox::translate_entity(
											$affordance,
											ENTITY_SMARTY_VARIABLE
										)
							)
						);
					else if (isset($properties->{PROPERTY_ENTITY}))

						// process a request
						return self::processRequest($affordance, $properties);
					else 

						throw new Exception('There is no flag type for '.$affordance);

					// get qualities
					$qualities = $class_member::getQualities();

					// set the member identifier
					$member_identifier = $qualities->{ROW_MEMBER_IDENTIFIER};

					// prepare a statement
					$insert_statement = $link->prepare($insert_flag);

					// prepare a statement
					$select_statement = $link->prepare($select_flag);

					// bind parameters to a selection statement
					$select_statement->bind_param(
						MYSQLI_STATEMENT_TYPE_INTEGER.
							MYSQLI_STATEMENT_TYPE_INTEGER.
								MYSQLI_STATEMENT_TYPE_INTEGER,
						$id,
						$flag_type,
						$member_identifier					
					);

					// bind results to a selection statement
					$select_statement->bind_result($_flag_status);

					// execute a statement
					$selection_result = $select_statement->execute();

					// check the selection result
					if ( $selection_result )
					{
						// get the result fetched from the selection statement
						$fetched_result = $select_statement->fetch();

						// check the fetched result
						if ( $fetched_result === TRUE )

							// set the flag status	
							$flag_status = !!!$_flag_status;
						else
	
							// set the flag status
							$flag_status = FLAG_STATUS_ENABLED;
					}
					else

						// set the flag status
						$flag_status = FLAG_STATUS_ENABLED;

					// bind parameters to a statement
					$insert_statement->bind_param(
						MYSQLI_STATEMENT_TYPE_INTEGER.
							MYSQLI_STATEMENT_TYPE_INTEGER.
								MYSQLI_STATEMENT_TYPE_INTEGER.
									MYSQLI_STATEMENT_TYPE_INTEGER,
						$flag_status,
						$id,
						$flag_type,
						$member_identifier						
					);

					// close the selection statement
					$select_statement->close();

					// execute a statement
					$execution_insertion_result = $insert_statement->execute();

					// close a statement
					$insert_statement->close();

					if ( $flag_type === FLAG_TYPE_SHARE )
					{
						$arc_id =

						$edge_destination_id =

						$edge_source_id = NULL;
						
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

						$database = DB_SEFI . '.';

						# insert new edge representing a photograph
						
						$query_insert_edge_source = '
							INSERT INTO ' . $database.  TABLE_EDGE . ' (
								`edg_id` ,
								`ety_id` ,
								`edg_status` ,
								`edg_key`
							)
							VALUES (
								NULL ,
								' . $concept_photograph_id . ',
								' . EDGE_STATUS_ACTIVE. ',
								' . $id . '
							)
						';
						
						# insert visibility arc
						
						$query_insert_arc_model = '
							INSERT INTO ' . $database . TABLE_ARC . '  (
								`arc_id` ,
								`arc_status` ,
								`arc_type` ,
								`arc_source` ,
								`arc_destination`
							)
							VALUES (
								NULL ,
								' . $arc_type_visibility . ',
								' . ARC_STATUS_ACTIVE . ' , 
								{edge_source_id},
								{edge_destination_id}
							)
						';

						/**
						*
						* FIXME
						*
						* Insert a new edge if the entity type at stake
						* is not bound to any
						*
						*/

						$query_select_arc_model = '
							SELECT
								' . PREFIX_TABLE_COLUMN_ARC . PROPERTY_ID .
									' ' . PROPERTY_ID . '
							FROM ' . $database . TABLE_ARC . '  
							WHERE
								'. PREFIX_TABLE_COLUMN_ARC . PROPERTY_TYPE . 
									' = ' . $arc_type_visibility . ' AND 
								'. PREFIX_TABLE_COLUMN_ARC . PROPERTY_SOURCE .
									' = {edge_source}  AND   
								'. PREFIX_TABLE_COLUMN_ARC . PROPERTY_DESTINATION .
									' = {edge_destination}
						';

						$query_select_edge_destination_model = '
							SELECT
								' . PREFIX_TABLE_COLUMN_EDGE . PROPERTY_ID . 
									' ' . PROPERTY_ID . '
							FROM
								' . $database . TABLE_EDGE . '
							WHERE
								' . PREFIX_TABLE_COLUMN_EDGE . PROPERTY_STATUS .
									' = ' . EDGE_STATUS_ACTIVE. ' AND
								' . PREFIX_TABLE_COLUMN_ENTITY. PROPERTY_ID .
									' = ' . $concept_entity_type_id . ' AND
								' . PREFIX_TABLE_COLUMN_EDGE . PROPERTY_KEY . 
									' = ' . $visibility_type_public
						;

						$query_select_edge_source_model = '
							SELECT
								' . PREFIX_TABLE_COLUMN_EDGE . PROPERTY_ID . 
									' ' . PROPERTY_ID . '
							FROM
								' . $database . TABLE_EDGE . '
							WHERE
								' . PREFIX_TABLE_COLUMN_EDGE . PROPERTY_STATUS .
									' = ' . EDGE_STATUS_ACTIVE. ' AND
								' . PREFIX_TABLE_COLUMN_ENTITY. PROPERTY_ID .
									' = ' . $concept_photograph_id . ' AND
								' . PREFIX_TABLE_COLUMN_EDGE . PROPERTY_KEY . 
									' = ' . $id
						;

						# update visibility arc

						$query_update_arc_model = '
							UPDATE ' . $database . TABLE_ARC . '
							SET
								arc_status = {' . PROPERTY_STATUS . '}
							WHERE 
								arc_id = {'. PROPERTY_ID .'}
						';
						
						$results_edge_destination = $class_db::query(
							$query_select_edge_destination_model
						);

						$results_edge_source = $class_db::query(
							$query_select_edge_source_model
						);

						if ( $results_edge_source->num_rows )
						{
							$edge_source = $results_edge_source
								->fetch_object()
							;
							
							$edge_source_id = $edge_source->{PROPERTY_ID};
						}

						if ( $results_edge_destination->num_rows )
						{
							$edge_destination = $results_edge_destination
								->fetch_object()
							;

							$edge_destination_id = $edge_destination->{PROPERTY_ID};
	
							$class_dumper::log(
								__METHOD__,
								array(
									'[destination edge id]',
									$edge_destination_id,
									'[source edge id]',
									$edge_source_id
								)
							);
							
							if (
								! is_null( $edge_source_id ) &&
								! is_null( $edge_destination_id )
							)
							{
								$query_select_arc = str_replace(
									array(
										'{edge_destination}',
										'{edge_source}'
									),
									array(
										$edge_destination_id,
										$edge_source_id
									),
									$query_select_arc_model
								);

								$results_arc = $class_db::query( $query_select_arc );

								$class_dumper::log(
									__METHOD__,
									array( $query_select_arc )
								);

								if ( $results_arc->num_rows )
								{
									$arc = $results_arc->fetch_object();
	
									$arc_id = $arc->{PROPERTY_ID};
								}
		
								$results_arc->free_result();
							}
						}

						$results_edge_destination->free_result();								
						
						$results_edge_source->free_result();

						$class_dumper::log(
							__METHOD__,
							array(
								'[arc id]',
								$arc_id
							)
						);

						if ( ! is_null( $arc_id ) )
						{
							$query_update_arc = str_replace(
								array(
									'{' . PROPERTY_ID . '}',
									'{' . PROPERTY_STATUS . '}'
								),
								array(
									$arc_id,
										$flag_status == FLAG_STATUS_ENABLED
									?
										ARC_STATUS_ACTIVE
									:
										ARC_STATUS_INACTIVE
								)
								,
								$query_update_arc_model
							);

							$class_db::query( $query_update_arc );
						}
						else 
						{
							if ( is_null( $edge_source_id ) )
							{
								$class_db::query( $query_insert_edge_source );

								$edge_source_id = $link->insert_id;								
							}
							
							if ( $flag_status == FLAG_STATUS_ENABLED )
							{
								$query_insert_arc = str_replace(
									array(
										'{edge_destination_id}',
										'{edge_source_id}'
									),
									array(
										$edge_destination_id,
										$edge_source_id
									),
									$query_insert_arc_model
								);

								$class_db::query( $query_insert_arc );
								
								$arc_id = $link->insert_id;	
							}
						}
					}

					// check the fetched result
					if ( $execution_insertion_result )
					{
						// get the previous position
						$previous_position = $class_interceptor::getPreviousPosition();

						// check the previous position
						if (!empty($previous_position))
						{
							// forget the previous position
							$class_interceptor::forgetPreviousPosition();

							// get the default border
							$default_border = $class_interceptor::getDefaultBorder();

							// forget the default border
							$class_interceptor::forgetDefaultBorder();

							// forget the previous position
							$class_interceptor::forgetPreviousPosition();
		
							// remove the primal definition
							$class_interceptor::forgetPrimalDefinition();

							if ($default_border != 1)
		
								// get the default border
								$previous_position .= '-'.$default_border;

							// go to the destination
							$class_application::jumpTo(
								$previous_position.
									"#".PREFIX_DOM_IDENTIFIER_IMAGE_PHOTOGRAPH.
										$id
							);
						}
						else

							// jump to the root 
							$class_application::jumpTo( PREFIX_ROOT );
					}
					else

						// throw an operation failure exception
						throw new Exception( EXCEPTION_OPERATION_FAILURE_REMOVAL );
				}
				else

					// jump to the root 
					$class_application::jumpTo( PREFIX_ROOT );

					break;
		}
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
	* Process a request
	*
	* @param	mixed	$request 	request
	* @param	mixed	$context	context
	* @return 	mixed
	*/
	public static function processRequest($request, $context)
	{
		global $class_application;

		$class_processor = $class_application::getProcessorClass();

		return $class_processor::processRequest($request, $context);
	}
}
