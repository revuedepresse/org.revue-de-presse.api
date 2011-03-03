<?php

/// @cond DOC_SKIP

namespace sefi
{

/// @endcond

	/**
	* Sefi namespace built for the project Weaving the Web
	*
	* @package  sefi
	*/

	/**
	* Form class
	*
	* @package  sefi
	*/
	class Form extends \Controller
	{
		/**
		* Get a store bound to a form
		*
		* @return	nothing
		*/
		public function getForeignObjects()
		{
			global $class_application, $verbose_mode;

			$class_data_fetcher  = $class_application::getDataFetcherClass();

			$class_dumper = $class_application::getDumperClass();

			$class_store = $class_application::getStoreClass();

			$class_store_item = $class_application::getStoreItemClass();

			$store_item_type_properties = array(
				PROPERTY_NAME => ENTITY_QUERY,
				PROPERTY_ENTITY => ENTITY_STORE
			);

			// fetch the store item of type query
			$store_item_type_query = $class_store_item::getTypeValue(
				$store_item_type_properties
			);

			$results_form = $class_data_fetcher::fetchForeignObjects(
				$this,
				array( PROPERTY_STORE => CLASS_STORE )
			);

			if (
				isset( $results_form[$this->{PROPERTY_ID}] ) &&
				isset(
					$results_form[$this->{PROPERTY_ID}]->{
						'(object) '.
						CLASS_STORE_ITEM
					}
				)
			)
			{
				$store_items =
					&$results_form[$this->{PROPERTY_ID}]->
					{
						'(object) '.
						CLASS_STORE_ITEM
					};
	
				$store_item = $store_items[count($store_items) - 1];
		
				$store_item_types =
					$class_data_fetcher::fetchEntityTypes(
						ENTITY_STORE_ITEM,
						PROPERTY_VALUE
					);

				if (
					isset( $store_item->{PROPERTY_TYPE} ) &&
					isset( $store_item_types[$store_item->{PROPERTY_TYPE}] )
				)
				{
					$store_item_type = $store_item_types[$store_item->{PROPERTY_TYPE}];

					if (
						$store_item_type->{PROPERTY_NAME} == ENTITY_STORE &&
						isset( $store_item->{PROPERTY_KEY} ) &&
						$store_item->{PROPERTY_KEY}
					)
					{
						$store = $class_store::getById($store_item->{PROPERTY_KEY});

						$store_item->{PROPERTY_KEY} = array(
							PROPERTY_ID => $store_item->{PROPERTY_KEY},
							PROPERTY_REFERENCE => $store
						);

						if (
							isset(
								$results_form[$this->{PROPERTY_ID}]->{
									'(object) '.
									CLASS_STORE
								}
							) &&
							is_array(
								$results_form[$this->{PROPERTY_ID}]->{
									'(object) '.
									CLASS_STORE
								}								
							)
						)
						{
							$results_form[$this->{PROPERTY_ID}]->{
									'(object) '.
									CLASS_STORE
							}[] = $store;

							$results_store =
								$class_data_fetcher::fetchForeignObjects(
									$store,
									NULL,
									array(
										CLASS_QUERY =>
											PREFIX_TABLE_COLUMN_STORE_ITEM.
												PROPERTY_TYPE.
													' = '.
														$store_item_type_query
									)
								);

							list( $id, $collection ) = each( $results_store );
							reset( $results_store );

							$results = &$results_form[$this->{PROPERTY_ID}];

							// merge Store results and Form results
							while (
								list( $type, $instances ) = each( $collection )
							)
							{
								if ( $type != PROPERTY_ID )
								{
									list( $_index, $instance) = each( $instances );
	
									if (
										! isset( $results->$type )
									)

										$results->$type = array();
									
									if ( ! in_array( $instance, $results->$type ) )
	
										$results->{$type}[] = $instance;
								}
							}
						}
					}
				}
			}

			return $results;
		}

		/**
		* Display the rendering of a form
		*
		* @param	string	$form_identifier	form identifier
		* @return	nothing
		*/
		public static function displayRendering( $form_identifier )
		{
			global $class_application, $verbose_mode;
		
			$class_form_manager = $class_application::getFormManagerClass();

			// send headers
			header(
				'Content-Type: '.MIME_TYPE_TEXT_HTML.'; '.
				'charset='.I18N_CHARSET_UTF8
			);

			echo self::getRendering( $form_identifier );

			// serialize data
			$class_form_manager::getPersistentFieldHandler(
					$class_form_manager::getHandlerId( $form_identifier )
			)->serialize();

			return self::getFieldValues(
				$class_form_manager::getHandlerId( $form_identifier )
			);
		}

		/**
		* Get a form by providing its id
		*
		* @param	integer	$id	identifier
		* @return	object	Form
		*/
		public static function getById($id)
		{
			if ( ! is_numeric( $id  ) )
				
				throw new \Exception(EXCEPTION_INVALID_ARGUMENT);

			return self::getByProperty(
				$id,
				PROPERTY_ID,
				array(
					PROPERTY_ID,
					PROPERTY_IDENTIFIER,
					PROPERTY_PRIVILEGE =>
						array(
							PROPERTY_FOREIGN_KEY =>
								PREFIX_TABLE_COLUMN_PRIVILEGE.PROPERTY_ID
						),					
					PROPERTY_ROUTE =>
						array(
							PROPERTY_FOREIGN_KEY =>
								PREFIX_TABLE_COLUMN_ROUTE.PROPERTY_ID
						),					
					PROPERTY_STATUS,
					PROPERTY_STORE =>
						array(
							PROPERTY_FOREIGN_KEY =>
								PREFIX_TABLE_COLUMN_STORE.PROPERTY_ID
						),
					PROPERTY_TITLE
				),
				NAMESPACE_SEMANTIC_FIDELITY.'\\'.CLASS_FORM,
				TRUE
			);	
		}

		/**
		* Get field values
		*
		* @param	integer	$handler_id		handler id
		* @param	integer	$page			page
		* @return	mixed	values
		*/
		public static function getFieldValues(
			$handler_id,
			$page = PAGE_UNDEFINED
		)
		{
			global $class_application, $verbose_mode;

			$class_dumper = $class_application::getDumperClass();

			$class_form_manager = $class_application::getFormManagerClass();

			// the default current position is the first one
			$current_position = REQUEST_SINGLE_STEP;

			// declare disclaimer context
			$disclaimers = CONTEXT_DISCLAIMERS;

			$field_values = NULL;
			
			// declare field context
			$fields = CONTEXT_FIELDS;
			
			// declare the next position
			$next_position = NULL;
						
			// set the coordinates argument of the field handler
			$coordinates = array(
				COORDINATES_CURRENT_POSITION => &$current_position,
				COORDINATES_NEXT_POSITION => &$next_position
			);

			// set the context argument to be passed to the get field method
			$context = array(
				$handler_id,
				&$coordinates,
				CONTEXT_INDEX_DISCLAIMERS => &$disclaimers,
				CONTEXT_INDEX_FIELDS => &$fields
			);

			// set the scripts
			list(
				$current_script,
				$default_script,
				$next_script
			) = $class_application::fetch_scripts(
				$context,
				NULL,
				$page
			);

			// set the roadmap
			$roadmap = $class_application::fetch_roadmap(
				$context,
				$page
			);

			$properties = array(
				$handler_id,						
				$current_script,
				$coordinates,
				$roadmap,
				PROTOCOL_HTTP_METHOD_POST
			); 

			$context[CONTEXT_INDEX_FIELD_HANDLER] = 

			// deserialize a form manager from properties
			$field_handler = &$class_form_manager::deserialize(
				$properties,
				ENTITY_FIELD_HANDLER
			);

			// get fields
			$class_application::get_fields( $page, $context );
			
			list(
				$labels,
				$field_handler,
				$field_names,
				$field_options,
				$default_field_values,
				$helpers
			) = $field_handler->build_form(
				$fields,
				(
					$options =
						$class_application::get_options(
							$page,
							$context
						)
				)
			);
			
			// call the field values controller
			list(
				$context[CONTEXT_INDEX_ERRORS],
				$context[CONTEXT_INDEX_FIELD_VALUES],
				$context[CONTEXT_INDEX_DATA_SUBMISSION]
			) = $field_handler->check_fields(
				$fields,
				$class_application::get_options( $page, $context ),
				$current_position,
				NULL,
				$handler_id
			);
			
			$class_dumper::log(
				__METHOD__,
				array(
					'field handler: ',
					$field_handler,
					'options: ',
					$options,
					'$fields',
					$fields,
					'context: ',
					$context
				)
			);

			//self::checkSubmittedData( $context, $page );

			return $context[CONTEXT_INDEX_FIELD_VALUES];
		}

		/**
		* Get rendering 
		*
		* @param	string	$form_identifier	form identifier
		* @return	mixed	rendering
		*/
		public static function getRendering( $form_identifier )
		{
			global $class_application;
	
			$class_tag_form = $class_application::getTagFormClass();
			
			$form_rendering = $class_tag_form::render( $form_identifier );

			return $form_rendering;			
		}

		/**
		* Get the class signature
		*
		* @param	boolean	$namespace	namespace flag
		* @return	string	signature
		*/
		public static function getSignature( $namespace = FALSE )
		{
			$_class = __CLASS__;

			if ( ! $namespace )
			{
				list( $_namespace, $_class ) = explode( '\\', __CLASS__ );

				self::$namespace = $_namespace;
			}

			return $_class;
		}

		/**
		* Make an instance of the Form class
		*
		* @return	object	Form
		*/
		public static function make()
		{
			global $class_application;
			
			$class_dumper = $class_application::getDumperClass();

			$arguments = func_get_args();

			$settings = NULL;

			// check if a form identifier is defined
			if ( isset( $arguments[0] ) )

				$name = $arguments[0];
			else

				throw new Exception( EXCEPTION_INVALID_ARGUMENT );

			// check if a route is defined
			if ( isset( $arguments[1] ) )

				$route = $arguments[1];
			else

				throw new Exception( EXCEPTION_INVALID_ARGUMENT );

			// check if an action list is defined
			if ( ! isset( $arguments[2] ) )

				$action_list = NULL;
			else
			
				$action_list = $arguments[2];

			// check if a configuration is defined
			if ( ! isset( $arguments[3] ) )

				$configuration = NULL;
			else

				$configuration = $arguments[3];

			if ( is_numeric( $route ) )

				$route_id = $route;
			else

				throw new \Exception( EXCEPTION_INVALID_ARGUMENT );

			if ( is_numeric( $action_list ) )

				$store_id = $action_list;

			if ( is_string( $name ) )

				$identifier = $class_application::translate_entity(
					$name,
					ENTITY_ACTION,
					ENTITY_NAME
				);
			else
				
				throw new \Exception( EXCEPTION_INVALID_ARGUMENT );

			$form_type = 

			// 	fetch the default form type: management 
			$default_form_type = self::getDefaultType();

			if ( ! is_null( $configuration ) && is_object( $configuration ) )
			{
				if ( isset( $configuration->{PROPERTY_TYPE} ) )
				{
					$type = $configuration->{PROPERTY_TYPE};
					
					$properties = array(
						PROPERTY_NAME => $type,
						PROPERTY_ENTITY => ENTITY_FORM
					);
	
					// fetch the form type
					$form_type = self::getTypeValue( $properties );
				}

				if ( isset( $configuration->{PROPERTY_SETTINGS} ) )

					$settings = $configuration->{PROPERTY_SETTINGS};
			}

			if (
				! isset( $store_id ) &&
				( is_null( $settings ) || strlen( trim( $settings ) ) === 0 )
			)

				throw new \Exception( EXCEPTION_INVALID_ARGUMENT );

			else if ( ! isset( $store_id ) )

				$properties[
					PREFIX_TABLE_COLUMN_STORE.
					PROPERTY_SHORTHAND_CONFIGURATION
				] = array(
					PROPERTY_FOREIGN_KEY => $settings
				);				

			$properties = array(
				PREFIX_TABLE_COLUMN_ROUTE.PROPERTY_ID => array(
					PROPERTY_FOREIGN_KEY => $route_id
				),
				PROPERTY_IDENTIFIER => $identifier,
				PROPERTY_STATUS => ENTITY_STATUS_ACTIVE,
				PROPERTY_TITLE => $name,
				PROPERTY_TYPE => $form_type
			);

			if ( isset( $store_id ) )

				$properties[PREFIX_TABLE_COLUMN_STORE.PROPERTY_ID] = array(
					PROPERTY_FOREIGN_KEY => $store_id
				);

			return self::add( $properties );
		}
	}

/// @cond DOC_SKIP

}

/// @endcond