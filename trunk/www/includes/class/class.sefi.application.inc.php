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
	* Application class
	*
	* @package  sefi
	*/
	class Application extends \Service_Manager
	{
		private $_resources;
		private $_templates;

		/**
		* Construct a new application 
		*
		* @param	integer	$page		representing a page
		* @param	integer	$handler_id	representing a field handler
		* @param	string	$block		containing a block name
		* @return 	object	representing an application
		*/	    
		private function __construct(
			$page = PAGE_UNDEFINED,
			$handler_id = FORM_UNDEFINED,
			$block = BLOCK_HTML
		)
		{
			$class_user_interface = self::getUserInterfaceClass();

			// set templates
			$this->_templates = $class_user_interface::get_templates(
				$page,
				$handler_id
			);

			// set resources
			$this->_resources = array( TEMPLATE_PAGE => $page );
		}
	
		/**
		* Get a string representing a source code
		*
		* @return  string	containing contents
		*/
		public function __toString()
		{
			return $this->beautify_source();
		}
	
		/**
		* Get the blanks of a view
	
		* @param	string	$block	containing a block name 
		* @return 	array	containing templates
		*/	
		public function &get_blanks($block = BLOCK_HTML)
		{
			// get templates
			$templates = &$this->get_templates();

			// return blanks property of the templates
			return $templates[BLOCK_HTML][PROPERTY_BLANKS];
		}
	
		/**
		* Get current block
		*
		* @return  string	containing the name of a block
		*/	
		public function &get_block()
		{
			// get resources			
			$resources = &$this->get_resources();

			// return the template block resource
			return $resources[TEMPLATE_BLOCK];
		}
	
		/**
		* Get current page
		*
		* @return  integer	representing a page
		*/	
		public function &get_page()
		{
			// get resources
			$resources = &$this->get_resources();

			// return the page resource
			return $resources[TEMPLATE_PAGE];
		}
	
		/**
		* Get current resources
		*
		* @return  array	containing resources
		*/	
		public function &get_resources()
		{
			// return the resources
			return $this->_resources;
		}
	
		/**
		* Get source
		*
		* @param	string	$block	containing a block name
		* @return 	string	containing a source
		*/	
		public function &get_source($block = BLOCK_HTML)
		{
			// get templates
			$templates = &$this->get_templates();	

			// return the template of the block argument
			return $templates[$block][TEMPLATE_SOURCE];
		}
	
		/**
		* Get templates
		*
		* @return  array	containing templates
		*/	
		public function &get_templates()
		{
			return $this->_templates;
		}
	
		/**
		* Get variables
		*
		* @return  array	containing variables
		*/	
		public function &get_variables()
		{
			// get resources
			$resources = &$this->get_resources();
	
			// check if the variables store has been initialized
			if (!isset($resources[RESOURCE_VARIABLES]))

				// set the variables
				$resources[RESOURCE_VARIABLES] = array();
	
			// return the variables
			return $resources[RESOURCE_VARIABLES];
		}
	
		/**
		* Add blanks to template
		*
		* @param	array	$blanks containing blanks
		* @param	string	$block	containing a block name 
		* @return	nothing
		*/	
		public function add_blanks($blanks, $block = BLOCK_HTML)
		{
			// get blanks
			$_blanks = &$this->get_blanks($block);

			// merge member blanks with newly collected ones
			$_blanks = array_merge($_blanks, $blanks);
		}

		/**
		* Beautify a snippet of code
		*
		* @param	string		$source 			a source
		* @param	boolean		$clean_source 		clean up flag
		* @param	boolean		$declare_doctype	doctype flag
		* @param	boolean		$plant_tree			XML root flag
		* @param	array		$config 			configuration
		* @return  	string		a beautified source
		*/	
		public function beautify_source(
			$source = NULL,
			$clean_source = VALIDATE_TIDY_SOURCE,
			$declare_doctype = VALIDATE_DOCTYPE_DECLARATION,
			$plant_tree = VALIDATE_TREE_PLANTING,
			$config = NULL
		)
		{
			$class_dumper = self::getDumperClass();

			$_config = array(
				TIDY_OPTION_INDENT => VALIDATE_TIDY_AUTO_INDENT,
				TIDY_OPTION_MARKUP => VALIDATE_TIDY_MARKUP,			
				TIDY_OPTION_OUTPUT_XHTML => VALIDATE_TIDY_OUTPUT_HTML,
				TIDY_OPTION_WRAP => VALIDATE_TIDY_WRAP,
				TIDY_OPTION_BODY_ONLY => TIDY_FLAG_BODY_ONLY
			);

			if (
				isset($config) &&
				is_array($config) &&
				count($config) != 0
			)

				// loop on option configuration argument
				foreach ($config as $option => $flag)

					$_config[$option] = $flag;

			if (!isset($source))
			{
				$templates = &$this->get_templates();
				$source = &$this->get_source();
			}
	
			if ($clean_source && function_exists(FUNCTION_TIDY_PARSE_STRING))
			{
				$tidy = tidy_parse_string($source, $_config, 'UTF8');

				if ( strlen(trim($source)) != 0 && strlen(trim($tidy->value)) == 0 )
				
					throw new \Exception(EXCEPTION_INVALID_SERVER_CONFIGURATION_INVALID_TIDY_EXTENSION);

				$source = $tidy->value;
			}

			if ($declare_doctype)

				$source = DOCTYPE_XHTML_TRANSITIONAL.$source;
	
			if ($plant_tree)

				$source = DOCUMENT_ROOT_XML.$source;

			return $source;        	
		}

		/**
		* Alias to the display_view method
		*
		* @param	integer	$page		page
		* @param	integer	$handler_id	field handler
		* @param	string	$block		block name 
		* @param	array	$variables	variables
		* @param	mixed	$informant	informant
		* @return  	nothing
		*/	
		public function displayView(
			$page = PAGE_UNDEFINED,
			$handler_id = FORM_ORDINARY,
			$block = BLOCK_HTML,
			$variables = null,
			$informant = null
		)
		{
			$this->display_view($page, $handler_id, $block, $variables, $informant);
		}

		/**
		* Display a view
		*
		* @param	integer	$page		page
		* @param	integer	$handler_id	field handler
		* @param	string	$block		block name 
		* @param	array	$variables	variables
		* @param	mixed	$informant	informant
		* @return  	nothing
		*/	
		public function display_view(
			$page = PAGE_UNDEFINED,
			$handler_id = FORM_ORDINARY,
			$block = BLOCK_HTML,
			$variables = NULL,
			$informant = NULL
		)
		{
			// send headers
			header(
				'Content-Type: '.
					MIME_TYPE_TEXT_HTML.
						'; charset='.I18N_CHARSET_UTF8
			);

			// return a member view
			echo $this->fetchMemberView(
				$page,
				$handler_id,
				$block,
				$variables,
				$informant
			);
		}

		/**
		* Get a view
		*
		* @param	integer	$page		representing a page
		* @param	integer	$handler_id	representing a field handler
		* @param	string	$block		containing a block name
		* @param	array	$variables	containing variables
		* @param	mixed	$informant		informant
		* @return  	string	containing a view
		*/	
		public function get_view(
			$page = PAGE_UNDEFINED,
			$handler_id = FORM_UNDEFINED,
			$block = BLOCK_HTML,
			$variables = null,
			$informant = null
		)
		{
			// get member variables
			$_variables = &$this->get_variables();
	
			// check if a page identifier has been passed as an argument
			$resources = $this->get_resources();

			$page = $resources[TEMPLATE_PAGE];

			// load the view of the selected block
			$_view = $this->load_view(
				$page,
				$handler_id,
				$_variables,
				$informant
			);

			// set private member resources
			$this->_resources = array(
				TEMPLATE_BLOCK => $block,
				TEMPLATE_PAGE => $page
			);

			// load variables into the selected block
			if ( isset( $variables ) )
	
				// beautify the view
				$view = $this->beautify_source(
					$this->load_variables(
						$variables,
						$block
					)
				);

			// check the member variables
			else if (
				isset( $_variables ) &&
				$block == BLOCK_HTML
			)

				// beautify the view
				$view = $this->beautify_source(
					$this->load_variables( $_variables, $block )
				);

			// check if the page is defined and the handler identifier is undefined
			else if (
				$handler_id != FORM_UNDEFINED &&
				$block == BLOCK_HTML
			)

				// set the view
				$view = $this;
			else

				// set the view			
				$view = $_view;

			// check the handler identifier
			switch ( $handler_id )
			{	
				default:

					// set a pattern
					$pattern =
						REGEXP_OPEN.
							REGEXP_ESCAPE.
							CHARACTER_BRACKET_START.
								REGEXP_ESCAPE.
								CHARACTER_DOLLAR.
								HTML_ELEMENT_BODY.
							REGEXP_ESCAPE.
							CHARACTER_BRACKET_END.
						REGEXP_CLOSE
					;

					// replace patterns in the view
					$view = preg_replace(
						$pattern,
						$_view,
						$view
					);	

						break;
			}

			if ( $block == BLOCK_HTML )

				// beautify the view
				$view = $this->beautify_source(
					$view,
					VALIDATE_TIDY_SOURCE,
					FALSE,
					FALSE
				);

			// add space between closing bracket of single tags 
			$view = preg_replace( '/\s*\/>/', ' />', $view );

			// return the view
			return $view;
		}

		/**
		* Load variables into a view
		*
		* @param	array	$variables	containing variables
		* @param	string	$block		containing a block name
		* @return  	string	containing a view loaded with variables
		*/
		public function load_variables($variables = null, $block = BLOCK_HTML)
		{
			$_blanks = &$this->get_blanks($block);
			$_resources = &$this->get_resources();
			$source = &$this->get_source($block);

			// check the variables
			if (!isset($variables))
			
				// get the member variables
				$variables = &$this->get_variables();

			// add smarty placeholders to the source code
			if (
				isset($variables) &&
				isset($_blanks) &&
				is_array($variables) &&
				is_array($_blanks) && 
				count($variables) > 0 &&
				count($_blanks) != 0
			)
			{
				// check the member blanks
				if (count($_blanks) != 1)

					// loop on the member blanks
					while (list($blank_index, $blank) = each($_blanks))
					{
						// set a pattern
						$pattern =
							CHARACTER_SLASH.
							CHARACTER_BACKSLASH.
							CHARACTER_BRACKET_START.
							CHARACTER_BACKSLASH.
							CHARACTER_DOLLAR.
							$blank.
							CHARACTER_BACKSLASH.
							CHARACTER_BRACKET_END.
							CHARACTER_SLASH;

						// check a blank variable
						if ( isset( $variables[$blank] ) )

							// replace the pattern with a variable value in the source
							$source = preg_replace(
								$pattern,
								$variables[$blank],
								$source
							);
					}
			}

			return $source;
		}
	
		/**
		* Load a view
		*
		* @param	integer	$page		representing a page
		* @param	integer	$handler_id	representing a field handler
		* @param	array	$variables	containing variables
		* @param	array	$informant	informant
		* @param	array	$assert		assertion flag
		* @return  	string	containing the source code of a view
		*/	
		public function load_view(
			$page = PAGE_UNDEFINED,
			$handler_id = FORM_ARBITRARY,		
			$variables = NULL,
			$informant = NULL,
			$assert = FALSE
		)
		{
			global $verbose_mode;

			// set the dumper class
			$class_dumper = self::getDumperClass();

			// set the form manager class name
			$class_form_manager = self::getFormManagerClass();

			// set the test case class
			$class_test_case = self::getTestCaseClass();

			// declare disclaimer context
			$disclaimers = CONTEXT_DISCLAIMERS;

			// declare field context
			$fields = CONTEXT_FIELDS;

			// the default current position is the first one
			$current_position = REQUEST_SINGLE_STEP;
	
			// check if the request URI contains some step parameter
			if (
				isset($_REQUEST[REQUEST_STEP]) &&
				$class_form_manager::is_multi_step($page)
			)

				// set the current position
				$current_position = (int) $_REQUEST[REQUEST_STEP];

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

			// set the roadmap
			$roadmap = self::fetch_roadmap( $context, $page );

			// check the roadmap
			if (
				count( $roadmap ) == 1 &&
				(
					isset($roadmap[PAGE_ANY]) &&
					$roadmap[PAGE_ANY] === HANDLER_STATUS_INACTIVE
				||
					isset($roadmap[$page]) &&
					$roadmap[$page] === HANDLER_STATUS_INACTIVE
				||
					isset($roadmap[$page]) &&
					$roadmap[$page] === PAGE_STATUS_ACTIVE
				)
			)
			{
				try
				{
					// set the alternative view
					$view = self::fetch_view( $context, $page );
				}
				catch (\Exception $exception)
				{
					$class_dumper::log(						
						__METHOD__,
						array(
							'An exception has been caught while calling '.
							'a p p l i c a t i o n  : :  f e t ch _ v i e w =>',
							$exception
						),
						DEBUGGING_DISPLAY_EXCEPTION,
						AFFORDANCE_CATCH_EXCEPTION
					);
				}

				// return the alternative view
				return $view;
			}

			// set the scripts
			list(
				$current_script,
				$default_script,
				$next_script
			) = self::fetch_scripts( $context, NULL, $page );
	
			// check the page
			if ( $page == PAGE_UNDEFINED )
			{
				// get member resources
				$resources = $this->get_resources();
	
				// get page
				$page = $resources[TEMPLATE_PAGE];
			}

			// get stores
			list(
				$data_submitted,
				$default_field_values,
				$errors,
				$field_handler,
				$field_names,
				$field_options,
				$field_values,
				$helpers,
				$labels,
				$template_models
			) = self::fetch_stores( $context, $page );

			$class_test_case::perform(
				DEBUGGING_FIELD_ERROR_HANDLING,
				$verbose_mode,
				$field_handler
			);

	        // get the configuration of the current field handler
	        $configuration = $field_handler->get_config();

			if ( ! empty( $_POST ) || $data_submitted )			

				$class_dumper::log(
					__METHOD__,
					array(
						'submitted data? ',
						$data_submitted,
						'field values? ',
						$field_values,
						'next condition? ',
						! empty( $configuration[PROPERTY_FORM_IDENTIFIER] ) &&
						$configuration[PROPERTY_FORM_IDENTIFIER] != AFFORDANCE_CONFIRM &&
						$configuration[PROPERTY_FORM_IDENTIFIER] != ACTION_CHALLENGE &&
						$configuration[PROPERTY_FORM_IDENTIFIER] != ACTION_SEARCH &&				
						count( $field_values )
					),
					DEBUGGING_FIELD_HANDLING,
					DEBUGGING_FIELD_HANDLING &&
					( ! empty( $_POST ) && ! $data_submitted )
				);

			// check the form identifier of the current field handler
			if (
				! empty( $configuration[PROPERTY_FORM_IDENTIFIER] ) &&
				$configuration[PROPERTY_FORM_IDENTIFIER] != AFFORDANCE_CONFIRM &&
				$configuration[PROPERTY_FORM_IDENTIFIER] != ACTION_CHALLENGE &&
				$configuration[PROPERTY_FORM_IDENTIFIER] != ACTION_SEARCH &&				
				count( $field_values )
			)

				// perform an action
				self::performAction( $context, $page );

			// check if the current field handler is about search
			else if (
				$configuration[PROPERTY_FORM_IDENTIFIER] == ACTION_SEARCH &&
				$data_submitted
			)

				self::displaySearchResults( $context, $page );

			if ( $data_submitted )			

				$class_dumper::log(
					__METHOD__,
					array(
						'context: ',
						$context,
						'page: ',
						$page
					),
					DEBUGGING_FIELD_HANDLING
					//, $data_submitted && DEBUGGING_FIELD_HANDLING
				);

			// route the current visitor
			self::route( $context, $page );

			try
			{
				// set the view
				$view = self::fetch_view($context, $page, $informant);
			}
			catch (\Exception $exception)
			{
				$class_dumper::log(						
					__METHOD__,
					array( $exception ),
					DEBUGGING_DISPLAY_EXCEPTION,
					AFFORDANCE_CATCH_EXCEPTION
				);
			}

			// return the view
			return $view;
		}
		
		/**
		* Set blanks
		*
		* @param	array	$blanks containing blanks
		* @return 	nothing
		*/	
		public function set_blanks($blanks)
		{
			$_blanks = &$this->get_blanks();
	
			if (isset($blanks) && is_array($blanks) && count($blanks) > 0)
				$_blanks = $blanks;
		}
	
		/**
		* Set block
		*
		* @param	string	$block	containing a name of block
		* @return 	nothing
		*/	
		public function set_block($block)
		{
			$_resources = &$this->get_resources();
			$_resources[TEMPLATE_BLOCK] = $block;
		}
	
		/**
		* Set page
		*
		* @param	integer	$page	representing a page
		* @return 	nothing
		*/	
		public function set_page($page)
		{
			$_resources = &$this->get_resources();
			$_resources[TEMPLATE_PAGE] = $page;
		}
	
		/**
		* Set variables
		*
		* @param	array	$variables containing variables
		* @return 	nothing
		*/	
		public function set_variables($variables)
		{
			$_variables = &$this->get_variables();
	
			if (isset($variables) && is_array($variables) && count($variables) > 0)
				$_variables = $variables;			
		}
	
		/**
		* Indicates in which section of the application a user is located
		*
		* @param	integer	$user_type		representing a user type
		* @param	integer	$user_action	representing a user action
		* @return	mixed	indicating a visitor location
		*/	 
		private static function where_am_i($user_type = USER_TYPE_VISITOR, $user_action = AFFORDANCE_VISIT)
		{
			$area = SECTION_FRONT_END;
	
			switch ($user_type)
			{
				case USER_TYPE_VISITOR:
	
					switch ($user_action)
					{
						case AFFORDANCE_VISIT:
	
							if (
								!preg_match(
									REGEXP_START_SHARP.
											TLD_DEV.
										REGEXP_END.
									REGEXP_END_SHARP.
										REGEXP_CASE_INSENSITIVE
									,
									$_SERVER['SERVER_NAME']
								) &&
								!preg_match(
									REGEXP_START_SHARP.
											TLD_PREPROD.
										REGEXP_END.
									REGEXP_END_SHARP.
										REGEXP_CASE_INSENSITIVE
									,
									$_SERVER['SERVER_NAME']
								) &&
								!preg_match(
									REGEXP_START_SHARP.
											TLD_REPOS.
										REGEXP_END.
									REGEXP_END_SHARP.
										REGEXP_CASE_INSENSITIVE
									,
									$_SERVER['SERVER_NAME']
								) &&
								!preg_match(
									REGEXP_START_SHARP.
											TLD_TRUNK.
										REGEXP_END.
									REGEXP_END_SHARP.
										REGEXP_CASE_INSENSITIVE
									,
									$_SERVER['SERVER_NAME']
								) &&
								self::am_i_a_visitor()
							)
								return true;						
							else
								return false;
	
								break;
	
						case AFFORDANCE_DEBUG:
	
							if (!self::am_i_a_visitor())
								return true;
							else
								return false;
	
								break;
	
					}
	
						break;
			}
	
			return $area;
		}

		/**
		* Alias to the toolbox beautifier  method
		*
		* @param	string		$source 			a source
		* @param	integer		$source_type		type of source
		* @param	boolean		$clean_source 		clean up flag
		* @param	boolean		$declare_doctype	doctype flag
		* @param	boolean		$plant_tree			XML root flag
		* @param	array		$config 			configuration
		* @return  	string		a beautified source
		*/	
		public static function beautifySource(
			$source = null,
			$source_type = NULL,
			$clean_source = VALIDATE_TIDY_SOURCE,
			$declare_doctype = VALIDATE_DOCTYPE_DECLARATION,
			$plant_tree = VALIDATE_TREE_PLANTING,
			$config = null
		)
		{
			return parent::beautifySource(
				$source,
				$source_type,
				$clean_source,
				$declare_doctype,
				$plant_tree,
				$config
			);
		}
	
		/**
		* Indicates if a visitor is driven by beta-testing purposefulness
		*
		* @return	boolean	indicating if a user visits a environment dedicated to unit testing
		*/
		public static function am_i_a_bug_slayer()
		{
			return self::where_am_i(USER_TYPE_VISITOR, AFFORDANCE_DEBUG);
		}
	
		/**
		* Indicates if a visitor is attending a environment of preproduction
		*
		* @return	boolean	indicating if a user visits a development environment
		*/
		public static function am_i_a_visitor()
		{
			if (
				! preg_match(
					REGEXP_START_SHARP.
							HOST_NAME_GOLDENMARKET.TLD_EU.
						REGEXP_END.
					REGEXP_END_SHARP.
						REGEXP_CASE_INSENSITIVE,
					$_SERVER['SERVER_NAME']
				) &&
				! preg_match(
					REGEXP_START_SHARP.
							TLD_GM.
						REGEXP_END.
					REGEXP_END_SHARP.
						REGEXP_CASE_INSENSITIVE,
					$_SERVER['SERVER_NAME']
				) &&
					! preg_match(
						REGEXP_OPEN.
						CHARACTER_COLUMN.
						REGEXP_CLOSE,
						$_SERVER['HTTP_HOST']
					)
				&&
					$_SERVER['SERVER_PORT'] == 80
			)
				return TRUE;

			return FALSE;
		}
	
		/**
		* Indicates if a visitor is located in a environment of preproduction
		*
		* @return	boolean	indicating if a user visits a environment of preproduction
		*/
		public static function am_i_irl()
		{
			return self::where_am_i();
		}
	
		/**
		* Check records
		*
		* @param	array	$predicates 		predicates
		* @param	integer	$handler_id			field handler
		* @param	integer	$page				page
		* @param	integer	$storage_model		storage model
		* @param	integer	$informant			informant
		* @return 	nothing
		*/
		public static function checkPredicates(
			$predicates,
			$handler_id = FORM_ARBITRARY,
			$page = PAGE_UNDEFINED,		
			$storage_model = STORE_DATABASE,
			$informant = null
		)
		{
			global $class_application;

	        $class_form_manager = $class_application::getFormManagerClass();

	        $class_prover = $class_application::getProverClass();

			$class_user_handler = $class_application::getUserHandlerClass();

			// set the default mismatching values
			$mismatching_values = array();

			// 	declare empty arrays of
			// 	attribute identifiers
			//	conditions
			//	domains
			//	operations
			// 	selections
			//	tables
			//	table identifiers 
			$attribute_identifiers =
			$conditions =			
			$domains =
			$operations =
			$resolutions =
			$selections =
			$selectors =
			$tables =
			$table_identifiers =
			$valuations = array();

			// declare the domain pattern
			$domain_pattern =
				REGEXP_OPEN_SHARP.
					SYMBOL_DOMAIN.
				REGEXP_CLOSE_SHARP
			;

			// get field values of the current field handler
			$field_values = $class_form_manager::getPersistentProperty(
				PROPERTY_FIELD_VALUES,
				$handler_id,
				ENTITY_FORM_MANAGER
			);

			// declare a function pattern
			$function_pattern =
				REGEXP_OPEN_SHARP.
					REGEXP_CATCH_START.
							REGEXP_WILDCARD_LITERAL_NUMERIC.REGEXP_ANY.																	
					REGEXP_CATCH_END.
					REGEXP_ESCAPE.SYMBOL_LIST_ARGUMENTS_START.
						REGEXP_CATCH_START.
							REGEXP_WILDCARD_MEMBER_ATTRIBUTE.REGEXP_ANY.
						REGEXP_CATCH_END.
					REGEXP_ESCAPE.SYMBOL_LIST_ARGUMENTS_END.
				REGEXP_CLOSE_SHARP
			;

			// set an array of operators
			$operators = array(
				SYMBOL_CUNJUNCTION,
				SYMBOL_NEGATION,
				SYMBOL_DISJUNCTION
			);

			// set an array of complementary operators
			$complementary_operators = array_reverse( $operators );

			// switch from the predicates
			switch ( $predicates )
			{
				case PREDICATE_LOGIN:
					
					$predicates = array(
						PREDICATE_CHECK_LOGIN,
						PREDICATE_CHECK_PASSWORD,								
					);
			}

			// check predicates
			$results = $class_prover::checkPredicates( $predicates, $handler_id );

			// check if the result is false
			if (
				is_bool( $results[0] ) &&
				$results[0] === FALSE
			)

				$mismatching_values[FIELD_NAME_LOGIN] = TRUE;

			// check if the result is an object
			else if (
				is_object( $results[0] ) &&
				isset( $results[0]->{PROPERTY_LEFT_OPERAND} ) &&
				$results[0]->{PROPERTY_LEFT_OPERAND} === TRUE
			)

				$mismatching_values[FIELD_NAME_LOGIN] = FALSE;

			if (
				is_bool($results[1]) &&
				$results[1] === FALSE
			)

				$mismatching_values[FIELD_NAME_PASSWORD] = TRUE;
			else 

				$mismatching_values[FIELD_NAME_PASSWORD] = FALSE;

			// check if the password mismatches
			if (
				! $mismatching_values[FIELD_NAME_PASSWORD] &&
				! $mismatching_values[FIELD_NAME_LOGIN]
			)
			{
				// check the member store in session
				if (
					isset( $_SESSION[STORE_MEMBER] ) &&
					is_object( $_SESSION[STORE_MEMBER] )
				)

					// log a member in
					$class_user_handler::logMemberIn(
						$results[0]->{PROPERTY_RIGHT_OPERAND}->{'member_id'}
					);
			}

			// return the matching values
			return $mismatching_values;
		}

		/**
		* Destroy a session
		*
		* @param	boolean		$administrator	administrator flag
		* @return 	nothing
		*/
		public static function destroySession( $administrator = FALSE )
		{	
			// check the administrator store
			if (
				isset( $_SESSION[STORE_ADMINISTRATOR] ) &&
				! $administrator
			)

				$store = $_SESSION[STORE_ADMINISTRATOR];

			// check the member store
			else if ( isset( $_SESSION[STORE_MEMBER] ) && $administrator )

				$store = $_SESSION[STORE_MEMBER];

			// check the administrator store
			if ( isset( $_SESSION[STORE_PAPER] ) )

				$store_paper = $_SESSION[STORE_PAPER];
				
			// destroy a session
			session_destroy();

			// check if an administrator has been backed up
			// Check the current session identifier and the headers already sent
			if (
				session_id() === '' &&
				! headers_sent()
			)
			{
				// start a session
				session_start();

				if ( isset( $store ) )
				{
					if ( ! $administrator )
	
						$_SESSION[STORE_ADMINISTRATOR] = $store;
					else
	
						$_SESSION[STORE_MEMBER] = $store;
				}

				if ( isset( $store_paper ) )

					$_SESSION[STORE_PAPER] = $store_paper;
			}
		}

		/**
		* Display an application view
		*
		* @param	integer	$page		a page
		* @param	integer	$handler_id	a field handler
		* @param	string	$block		a block name
		* @param	array	$variables	variables
		* @param	mixed	$informant	informant
		* @return 	nothing
		*/	
		public static function display(
			$page = NULL,
			$handler_id = NULL,
			$block = NULL,
			$variables = NULL,
			$informant = NULL
		)
		{
			global $class_application, $verbose_mode;

			$class_dumper = $class_application::getDumperClass();

			if ( is_null( $page ) )
			{
				// check if the constants have been declared
				if ( ! defined('PAGE_HOMEPAGE') )

					throw new \Exception( 'Sorry, invalid boostrap' );

				$page = PAGE_HOMEPAGE;
			}
			
			if ( is_null( $handler_id ) )
			
				$handler_id = FORM_UNDEFINED;

			if ( is_null( $block ) )
			
				$block = BLOCK_HTML;

			$application = self::shapeApplication(
				$page,
				$handler_id,
				$block,
				$variables
			);

			$application->display_view(
				$page,
				$handler_id,
				$block,
				$variables,
				$informant
			);
		}

		/**
		* Display content
		*
		* @param	integer		$content_id		content
		* @return 	nothing
		*/	
		public static function displayContent($content_id = PAGE_HOMEPAGE)
		{
			// display a content page
			self::display($page = PAGE_CONTENT, $content_id, BLOCK_HTML);
		}

		/**
		* Display a form
		*
		* @param	integer	$dialog_id		dialog 
		* @param	integer	$page			page
		* @param	string	$block			block name
		* @param	array	$variables		variables
		* @return 	nothing
		*/
		public static function displayDialog(
			$dialog_id,
			$page = PAGE_UNDEFINED,
			$block = BLOCK_HTML,
			$variables = null	
		)
		{
			global $class_application, $verbose_mode;

			$class_dumper = $class_application::getDumperClass();

			switch ( $dialog_id )
			{
				case AFFORDANCE_LOGOUT:

					$administration = false;

					// check the POST parameters
					if (isset($_POST[POST_ADMINISTRATION]))
					
						$administration = $_POST[POST_ADMINISTRATION];

					// logout the current logged in member
					self::destroySession($administration);

					if ( ! $administration )

						$authentication_form = PREFIX_ROOT;
					else

						$authentication_form = URI_ACTION_OVERVIEW;

					// display the sign in form
					self::jumpTo( $authentication_form );

						break;

				default:

					if ( is_string( $dialog_id ) )

						// display form
						$class_application::displayForm($dialog_id);

					else if (
						is_object( $dialog_id ) &&
						isset( $dialog_id->{PROPERTY_LEVELS} )
					)
					{
						$request_URI= "/".implode("/", $dialog_id->{PROPERTY_LEVELS});
						
						if ( $request_URI == URI_ACTION_PROVIDE_WITH_FEEDBACK )

							self::displayOverview( $dialog_id, PAGE_OVERVIEW );
					}
			}
		}

		/**
		* Display a form
		*
		* @param	string	$affordance		affordance
		* @param	integer	$administrator	administrator flag
		* @param	integer	$page			page
		* @param	string	$block			block name
		* @param	array	$variables		variables
		* @param	mixed	$informant		informant
		* @return 	nothing
		*/	
		public static function displayForm(
			$affordance,
			$administrator = false,
			$page = PAGE_UNDEFINED,
			$block = BLOCK_HTML,
			$variables = null,
			$informant = null
		)
		{
			// set the form manager class name
			$class_form_manager = CLASS_FORM_MANAGER;

			// get a handler identifier from the provided affordance
			$handler_id = $class_form_manager::getHandlerId(
				$affordance,
				$administrator
			);

			// display a view
			self::display(
				$page,
				$handler_id,
				$block,
				$variables,
				$informant
			);
		}

		/**
		* Display a feedback
		*
		* @param	mixed	$feedback	feedback
		* @param	mixed	$context	context
		* @return  	nothing
		*/	
		public static function displayFeedbackView(
			$feedback,
			$context = NULL
		)
		{
			// send headers
			header(
				'Content-Type: '.MIME_TYPE_TEXT_HTML.'; '.
				'charset='.I18N_CHARSET_UTF8
			);

			echo self::getFeedbackView( $feedback, $context );
		}

		/**
		* Display a media
		*
		* @param	integer		$media			media
		* @param	integer		$media_type		media_type
		* @return 	nothing
		*/	
		public static function displayMedia(
			$media,
			$media_type = MEDIA_TYPE_IMAGE
		)
		{
			$class_db = self::getDbClass();

			$class_dumper = self::getDumperClass();

			// construct a new instance of the standard class
			$properties = new \stdClass();

			// set the default key flag
			$properties->{PROPERTY_KEY} = false;

			if (isset($media->{PROPERTY_KEY}))
			{
				$select_key = "
					SELECT
						usr_id
					FROM
						".TABLE_USER."
					WHERE
						sha1(usr_id) = '".substr($media->{PROPERTY_KEY}, 40, 40)."' AND
						sha1(usr_user_name) = '".substr($media->{PROPERTY_KEY}, 80, 40)."'
				";

				$link = $class_db::getLink();
				
				$statement = $link->prepare($select_key);

				// execute a statement
				$statement->bind_result($member_id);
				
				// execute a statement
				$execution_result = $statement->execute();
		
				// fetch result of a statement
				$fetch_result = $statement->fetch();
				
				if ($fetch_result)
				{
					$properties->{PROPERTY_IDENTIFIER} = $member_id;

					$properties->{PROPERTY_KEY} = TRUE;
				}
			}

			// fetch a media
			$file_content = self::fetchMedia($media, $media_type, $properties);

			if (file_exists(CONFIGURATION_FILE_MAGIC_MGC))

				// construct a new instance of file information
				$finfo = new \finfo(FILEINFO_MIME, CONFIGURATION_FILE_MAGIC_MGC);
			else

				throw new \Exception(sprintf(EXCEPTION_INVALID_CONFIGURATION_FILE, CONFIGURATION_FILE_MAGIC_MGC));

			// get the raw contents mime    
			$file_info = $finfo->buffer($file_content, FILEINFO_MIME);

			// get the raw contents mime type
			$file_mime_type = $finfo->buffer($file_content, FILEINFO_MIME_TYPE);
			
			// check the key
			if ($properties->{PROPERTY_KEY} !== true && $file_mime_type != MIME_TYPE_PLAIN_TEXT)
			
				// send appropriate headers
				header("Content-type: {$file_mime_type}", true);

			// display a file content
			echo $file_content;
		}

		/**
		* Display an application overview
		*
		* @param	string	$route			route
		* @param	integer	$page			page
		* @param	integer	$handler_id		field handler
		* @param	string	$block			block name
		* @param	array	$variables		variables
		* @param	mixed	$informant		informant
		* @return 	nothing
		*/	
		public static function displayOverview(
			$route = NULL,
			$page = PAGE_HOMEPAGE,
			$handler_id = FORM_UNDEFINED,
			$block = BLOCK_HTML,
			$variables = NULL,
			$informant = NULL
		)
		{
			// send headers
			header(
				'Content-Type: '.
				MIME_TYPE_TEXT_HTML.
				'; charset='.I18N_CHARSET_UTF8
			);

			echo self::fetchOverview(
				$route,
				$page,
				$handler_id,
				$block,
				$variables,
				$informant			
			);
		}

		/**
		* Display tabs
		*
		* @param	string	$affordance		affordance
		* @param	integer	$page			page
		* @param	string	$block			block name
		* @param	array	$variables		variables
		* @return 	nothing
		*/	
		public static function displayTabs(
			$affordance,
			$page = PAGE_UNDEFINED,
			$block = BLOCK_HTML,
			$variables = null			
		)
		{
			// set the layout manager class name
			$class_layout_manager = CLASS_LAYOUT_MANAGER;

			// display tabs
			$class_layout_manager::displayTabs(
				$affordance,
				$page,
				$block,
				$variables
			);
		}

		/**
		* Display a media
		*
		* @param	integer	$media			media
		* @param	integer	$media_type		media_type
		* @param	object 	$properties		properties
		* @return 	nothing
		*/			
		public static function fetchMedia(
			$media,
			$media_type = MEDIA_TYPE_IMAGE,
			$properties = null
		)
		{
			global $class_application, $verbose_mode;

			$class_dumper = $class_application::getDumperClass();

			$class_media_manager = $class_application::getMediaManagerClass();
	
			$class_member = $class_application::getMemberClass();

			$class_user_handler = $class_application::getUserHandlerClass();

			// set the default file content
			$file_content = '';

			// check if the properties are empty
			if (empty($properties))
			{
				// construct a new instance of the standard class
				$properties = new \stdClass();
				
				// set the default key property
				$properties->{PROPERTY_KEY} = FALSE;
			}

			// switch from the media type
			switch ( $media_type )
			{
				case MEDIA_TYPE_IMAGE:

					// check if the content is a photograph
					if ( $media->content_type == CONTENT_TYPE_PHOTOGRAPH )
					{
						// check if a member is logged in
						if (
							$member_qualities =
								$class_user_handler::anybodyThere() ||
							$properties->{PROPERTY_KEY}
						)
						{
							// get qualities
							$qualities = $class_member::getQualities();

							if (
								! $properties->{PROPERTY_KEY} ||
								isset( $properties->{PROPERTY_IDENTIFIER} )	
							)

								$photos =
									$class_media_manager::loadPhotosByAuthorId(
										$qualities->{ROW_MEMBER_IDENTIFIER},
										FALSE,
										(
											! $properties->{PROPERTY_KEY}
										?
											$media
										:
											$properties->{PROPERTY_IDENTIFIER}
										)
									)
								;

							// check the media identifier
							if (
								isset( $media->{PROPERTY_IDENTIFIER} ) &&
								isset( $photos[$media->{PROPERTY_IDENTIFIER}] )
							)

								// get the file content
								$file_content =
									$photos[$media->{PROPERTY_IDENTIFIER}]
										->storeFileContent()
								;
						}
						else

							// jump to the root page
						    self::jumpTo( PREFIX_ROOT );
					}

					// check the member qualities					
					else if ( $qualities = $class_member::getQualities() )
					{
						// load photos by author identifier
						$photos = $class_media_manager::loadPhotosByAuthorId(
							$qualities->{ROW_MEMBER_IDENTIFIER},
							FALSE,
							$media->{PROPERTY_IDENTIFIER}
						);

						$key =
							sha1(COOKIE_MEMBER_IDENTIFER).
							sha1($qualities->{ROW_MEMBER_IDENTIFIER}).
							sha1($qualities->{ROW_MEMBER_USER_NAME})
						;

						$uri = 
							BASE_URL.URI_DISPLAY_PHOTOGRAPH."?".
							GET_IDENTIFIER."=".$media->{PROPERTY_IDENTIFIER}.
							"&".GET_WIDTH."=".
								$photos[$media->{PROPERTY_IDENTIFIER}]->getWidth().
							"&".GET_HEIGHT."=".
								$photos[$media->{PROPERTY_IDENTIFIER}]->getHeight().
							"&".GET_KEY."=".$key
						;

						// get contents
						$file_content = file_get_contents( $uri );
					}
					else

						// jump to the root page
					    $class_application::jumpTo( PREFIX_ROOT );

						break;
			}

			// return the file content
			return $file_content;
		}

		/**
		* Fetch an application overview
		*
		* @param	string	$route			route
		* @param	integer	$page			page
		* @param	integer	$handler_id		field handler
		* @param	string	$block			block name
		* @param	array	$variables		variables
		* @param	mixed	$informant		informant
		* @return 	nothing
		*/	
		public static function fetchOverview(
			$route = NULL,
			$page = PAGE_HOMEPAGE,
			$handler_id = FORM_UNDEFINED,
			$block = BLOCK_HTML,
			$variables = NULL,
			$informant = NULL
		)
		{
			$class_dumper = self::getDumperClass();

			$flowing_data_view = TRUE;

			$form = NULL;

			// check the route validity 
			if ( $route )
			{
				// prepare the current affordance
				$affordance = self::translate_entity(
					$route->{PROPERTY_AFFORDANCE},
					$to = ENTITY_AFFORDANCE,
					$from = ENTITY_URI
				);

				if ( isset( $route->{PROPERTY_FORM} ) )

					$form = $route->{PROPERTY_FORM};
			}
			else

				$affordance = NULL;

			// shape a new application
			$application = self::shapeApplication(
				$affordance ? $page : PAGE_OVERVIEW,
				$handler_id,
				$block,
				$variables
			);

			if ( $affordance )
			{
				// fetch a member view
				$view = $application->fetchMemberView(
					$page,
					$handler_id,
					$block,
					$variables,
					$informant
				);

				if (
					is_null( $form ) ||
					! is_numeric( $form ) ||
					! $form
				)
				
					$flowing_data_view = FALSE;

				if ( $affordance != ACTION_PROVIDE_WITH_FEEDBACK )
				{
					// the flowing data view flag provides with a route
					// otherwise a single affordance is offered
					$form_action = 
							! $flowing_data_view 
						?
							$affordance
						:
							$route
					;

					$arguments = array(
						$form_action,
						BLOCK_FORM, 
						FORM_UNDEFINED,
						NULL,
						TRUE,
						$informant
					);

					$element_form = call_user_func_array(
						array( 'self', 'fetchForm' ),
						$arguments
					);
				}
				else 

					// fetch a dialog
					$dialog = self::fetchDialog( $affordance );
			}
			else

				// return a form
				$view = $application->fetchMemberView(
					PAGE_OVERVIEW,
					$handler_id,
					$block,
					$variables,
					$informant
				);

			// return an overview embedding the active menu
			return str_replace(
				"{".PLACEHOLDER_BODY."}",
					!empty( $element_form )
				?
					$element_form
				:
				(
						!empty( $dialog )
					?
						$dialog
					:
						''
				),
				$view
			);
		}

		/**
		* Display a resource
		*
		* @param	string	$resource_key	resource key
		* @param	string	$resource_type	resource type
		* @return 	mixed	resource
		*/	
		public static function displayResource(
			$resource_key,
			$resource_type = RESOURCE_URI
		)
		{
			// check if the resource key is defined
			if (
				$resource_type == RESOURCE_URI && empty( $resource_key ) ||
				$resource_type == RESOURCE_FILE
			)
			{
				// set the default identifier
				$identifier = NULL;

				// check the resource type
				if ( $resource_type == RESOURCE_FILE && isset( $resource_key ) )

					// set the identifier				
					$identifier = $resource_key;

				// display a photograph
				self::displayPhotograph( $identifier );
			}
			else
			{
				// display a fetched resource
				$resource = self::fetchResource($resource_key, $resource_type);
	
				// construct a new instance of file information
				$finfo = new \finfo(FILEINFO_MIME, "/usr/share/misc/magic.mgc");
		
				// get the raw contents mime
				$file_info = $finfo->buffer( $resource, FILEINFO_MIME );

				// get the raw contents mime type
				$file_mime_type = $finfo->buffer( $resource, FILEINFO_MIME_TYPE );
	
				// send appropriate headers
				header( "Content-type: {$file_mime_type}", TRUE );

				// display the resource
				echo $resource;
			}
		}

		/**
		* Display a photograph
		*
		* @param	integer	$identifier	identifier
		* @param	object	$properties	properties
		* @return	nothing
		*/
		public static function displayPhotograph(
			$identifier = NULL,
			$properties = NULL
		)
		{
			// display a photograph
			echo self::fetchPhotograph( $identifier, $properties );
		}

		/**
		* Display search results
		*
		* @param	integer	$context	contextual parameters
		* @param	integer	$page 		page
		* @return  	nothing
		*/	
		public static function displaySearchResults(&$context, $page)
		{
			// send headers
			header(
				'Content-Type: '.MIME_TYPE_TEXT_HTML.
					'; charset='.I18N_CHARSET_UTF8
			);

			// return a fetched view
			echo self::getSearchResults($context, $page);
		}

		/**
		* Fetch a form
		*
		* @param	string	$form_action	form action 
		* @param	string	$block			block name
		* @param	integer	$page			page
		* @param	array	$variables		variables
		* @param	boolean	$administrator	administrator flag
		* @param	mixed	$informant		informant
		* @return 	string
		*/
		public static function fetchForm(
			$form_action,
			$block = BLOCK_HTML,
			$page = PAGE_UNDEFINED,
			$variables = NULL,
			$administrator = FALSE,
			$informant = NULL
		)
		{
			$class_dumper = self::getDumperClass();

			$form_view = NULL;

			if ( is_string( $form_action ) )
			{
				$handler_id = call_user_func_array(
					array(CLASS_APPLICATION, 'fetchFormAbstract'),
					array(
						$form_action,
						$block,
						$administrator,
						$informant
					)
				);

				// fetch a form view
				$form_view = self::fetchView(
					$page,
					$handler_id,
					$block,
					$variables
				);
			}
			else if ( is_object( $form_action ) )

				$form_view = self::fetchFlowingDataView(
					$form_action,
					$block,
					$administrator,
					$informant
			
				);
			else

				throw new \Exception( EXCEPTION_INVALID_ARGUMENT );
			
			return $form_view;
		}

		/**
		* Fetch flowing data view
		*
		* @param	string	$route			route
		* @param	string	$block			block name
		* @param	boolean	$administrator	administrator flag
		* @param	mixed	$informant		informant
		* @return 	string
		*/
		public static function fetchFlowingDataView(
			$route,
			$block = BLOCK_HTML,
			$administrator = FALSE,
			$informant = NULL
		)
		{
			return call_user_func_array(
				array(
					self::getViewBuilderClass(),
					'buildFlowingDataView'
				),
				func_get_args()
			);
		}

		/**
		* Fetch a form abstract
		*
		* @param	string	$affordance		affordance
		* @param	string	$block			block name
		* @param	boolean	$administrator	administrator flag
		* @param	boolean	$edition		edition flag
		* @param	mixed	$informant		informant
		* @return 	string
		*/
		public static function fetchFormAbstract(
			$affordance,
			$block = BLOCK_HTML,
			$administrator = FALSE,
			$edition = FALSE,
			$informant = NULL
		)
		{
			// set the form manager class name
			$class_form_manager = self::getFormManagerClass();

			// get a handler identifier from the provided affordance
			$handler_id = $class_form_manager::getHandlerId(
				$affordance,
				$administrator,
				$edition,
				$informant
			);

			return $handler_id;
		}

		/**
		* Fetch a dialog
		*
		* @param	string	$affordance		affordance
		* @param	boolean	$informant		informant
		* @return 	string
		*/
		public static function fetchDialog( $affordance, $informant = NULL )
		{
			// set the view builder class name
			$class_view_builder = self::getViewBuilderClass();

			$dialog = '';

			if (
				!empty($_SESSION[STORE_FEEDBACK]) &&
				is_array($_SESSION[STORE_FEEDBACK])
				&& count($_SESSION[STORE_FEEDBACK]) != 0
			)		
				if (
					!empty($_SESSION[STORE_FEEDBACK][PROPERTY_SUCCESS]) &&
					is_array($_SESSION[STORE_FEEDBACK][PROPERTY_SUCCESS]) &&
					count($_SESSION[STORE_FEEDBACK][PROPERTY_SUCCESS]) != 0
				)
				
					if (!empty($_SESSION[STORE_FEEDBACK][PROPERTY_SUCCESS][STORE_MESSAGE]))
					{
						$message =
								defined(
									strtoupper(
									LANGUAGE_PREFIX_DIALOG.
									$_SESSION[STORE_FEEDBACK][PROPERTY_SUCCESS][STORE_MESSAGE]
								))
							?
								array(
									constant(
										strtoupper(
											LANGUAGE_PREFIX_DIALOG.
											$_SESSION[STORE_FEEDBACK][PROPERTY_SUCCESS][STORE_MESSAGE]
										)
									)
								)
							:
								array()
						;

						if (!empty($_SESSION[STORE_FEEDBACK][PROPERTY_SUCCESS][AFFORDANCE_LINK_TO]))

							$links = array($_SESSION[STORE_FEEDBACK][PROPERTY_SUCCESS][AFFORDANCE_LINK_TO]);
						else

							$links = array();
							
						$dialog = $class_view_builder::buildDialog(
							array(
								STORE_MESSAGE => $message,
								STORE_LINKS => $links
							)
						);

						unset($_SESSION[STORE_FEEDBACK]);
					}

			return $dialog;
		}

		/**
		* Fetch a member view
		*
		* @param	integer	$page			page
		* @param	integer	$handler_id		field handler
		* @param	string	$block			block name 
		* @param	array	$variables		variables
		* @param	mixed	$informant		informant
		* @return  	nothing
		*/
		public function fetchMemberView(
			$page = PAGE_UNDEFINED,
			$handler_id = FORM_ORDINARY,
			$block = BLOCK_HTML,
			$variables = null,
			$informant = null
		)
		{
			// set member variables
			if ( isset( $variables ) )

				$this->set_variables( $variables );

			// get a view
			return $this->get_view(
				$page,
				$handler_id,
				$block,
				$variables,
				$informant
			);
		}

		/**
		* fetch a photograph
		*
		* @param	integer	$identifier	identifier
		* @param	object	$properties	properties
		* @param	boolean	$avatar		avatar dimensions
		* @return	nothing
		*/
		public static function fetchPhotograph(
			$identifier = NULL,
			$properties = NULL,
			$avatar = FALSE
		)
		{
			// set the db class name
			$class_db = self::getDbClass();

			// set the Dumper class name
			$class_dumper = self::getDumperClass();

			// set the template engine class name
			$class_template_engine = self::getTemplateEngineClass();

			// set the snapshots directory
			$dir_snapshots = dirname(__FILE__).'/../../'.DIR_SNAPSHOTS;

			$key = NULL;

			// set the default portrait flag
			$portrait = FALSE;

			// check the identifier
			if ( empty( $identifier ) && $_GET[GET_IDENTIFIER] )

				// set the identifier
				$identifier = $_GET[GET_IDENTIFIER];

			// construct a new instance of the template engine object
			$template_engine = new $class_template_engine();

			if (
				(
					empty( $_GET[GET_HEIGHT] ) ||
					empty( $_GET[GET_WIDTH] )
				)
				&& is_object( $properties )
			)
			{
				// check the width properties
				if ( ! empty( $properties->{PROPERTY_HEIGHT} ) )

					// set the height
					$height = $properties->{PROPERTY_HEIGHT};

				// check the $height properties
				if ( ! empty( $properties->{PROPERTY_HEIGHT} ) )

					// set the width
					$width = $properties->{PROPERTY_WIDTH};
			}

			// check the key
			if ( ! empty( $_GET[GET_KEY] ) )

				$key = $_GET[GET_KEY];

			// check the key property
			else if ( ! empty( $properties->{PROPERTY_KEY} ) )

				$key = $properties->{PROPERTY_KEY};

			// check the height
			if ( ! empty( $_GET[GET_HEIGHT] ) )

				// set the height
				$height = $_GET[GET_HEIGHT];

			// check the width
			if ( ! empty( $_GET[GET_WIDTH] ) )

				// set the width
				$width = $_GET[GET_WIDTH];

			if ( ! isset( $height ) || ! isset( $width ) )
			{
				$exception = new \Exception( EXCEPTION_INVALID_ARGUMENT );
				
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
				
				$height =
				
				$width = DIMENSION_MAXIMUM_AVATAR_LONG_EDGE;
			}

			// save the original height
			$_height = $height;

			// save the original width
			$_width = $width;

			if ( $height > $width )
			
				$portrait = TRUE;

			// set the long edge
			$long_edge = $height > $width ?  $height : $width;
	
			$maximum_long_edge =
					$avatar
				?
					DIMENSION_MAXIMUM_AVATAR_LONG_EDGE
				:
					DIMENSION_MAXIMUM_LONG_EDGE
			;

			// set the ratio
			$ratio = $long_edge / $maximum_long_edge;    

			// check the ratio
			if ( $ratio != 0 )
			{
				// check the portrait flag 
				if ( $height > $width )
				{
					// set the ratio and size
					$size = $ratio = $height / $width;

					// set the height	
					$height = $maximum_long_edge * $ratio;

					// set the width
					$width = $maximum_long_edge;
				}
				else
				{
					// set the size
					$size = $long_edge / $height;

					// set the new height
					$height =  $height / $ratio;

					// set the new width
					$width = $maximum_long_edge; 
				}
			}
			else
			
				// jump to the root index
				self::jumpTo( PREFIX_ROOT );

			$template_name = TPL_BLOCK_IMAGE;

			$cache_id =
				md5(
					serialize(
						array(
							DIMENSION_MAXIMUM_LONG_EDGE,
							DIMENSION_MAXIMUM_AVATAR_LONG_EDGE,
							$key.
							( (int) $avatar ).'_'.
							substr(
								'0000000000'.
									floor( $width ).'x'.
										floor( $height ),
								-10,
								10
							).'_'.
							substr(
								'0000000000'.$identifier,
								-10,
								10
							)
						)
					)
				)
			;

			if (
				! ( $cached =
					$template_engine->is_cached(
						$template_name,
						$cache_id
					)
				)
			);
			{
				switch ( $size )
				{
					case 2/3:				
					case 3/2:
	
						if ( $size > 1 )
	
							$proportions = "3x2";
						else 
	
							$proportions = "2x3";
	
							break;
	
					case 3/4:
					case 4/3:
	
						if ( $size > 1 )
	
							$proportions = "3x4";
						else 
	
							$proportions = "3x4";
	
							break;
	
					case 4/5:
					case 5/4:
	
						if ( $size > 1 )
	
							$proportions = "5x4";
						else
	
							$proportions = "4x5";
	
							break;
	
					case 9/16:	
					case 16/9:
	
						if ( $size > 1 )
	
							$proportions = "16x9";
						else
	
							$proportions = "9x16";
	
							break;
					
					case 1:
	
						$proportions = "1x1";
	
							break;
					
					default:

						// round the size
						if (
							round( $size, 2 ) == round( 3/2, 2 ) ||
							round( $size, 1 ) == round( 3/2, 1 )
						)

							$proportions = "3x2";
	
						else if (
							round( $size, 2 ) == round( 2/3, 2 ) ||
							round( $size, 1 ) == round( 2/3, 1 )
						)
	
							$proportions = "2x3";
	
						else if (
							round( $size, 2 ) == round( 4/3, 2 ) ||
							round( $size, 1 ) == round( 4/3, 1 )
						)
	
							$proportions = "4x3";
						
						else if (
							round( $size, 2 ) == round( 4/5, 2 ) ||
							round( $size, 1 ) == round( 4/5, 1 )
						)
	
							$proportions = "4x5";
	
						else if (
							round( $size, 2 ) == round( 5/4, 2 ) ||
							round( $size, 1 ) == round( 5/4, 1 )
						)
	
							$proportions = "5x4";
	
						else if (
							round( $size, 2 ) == round( 16/9, 2 ) ||
							round( $size, 1 ) == round( 16/9, 1 )
						)
	
							$proportions = "16x9";
	
						else if (
							round( $size, 2 ) == round( 9/16, 2 ) ||
							round( $size, 1 ) == round( 9/16, 1 )
						)
	
							$proportions = "9x16";						
				}
	
				// check if a directory file exists
				if (
					is_string( $proportions ) &&
					! \file_exists(
						$dir_snapshots."/".
							$maximum_long_edge."_".
								$proportions
					)
				)
	
					// make a new directory
					mkdir( $dir_snapshots."/".$maximum_long_edge."_".$proportions );
	
				// check if anybody there
				if (
					isset( $key ) &&
					! file_exists(
						$dir_snapshots."/".
							$maximum_long_edge."_".
								$proportions."/".
									$identifier
					)
				)
				{
					// construct new instances of the standard class
					$media = new \stdClass();
					$_properties = new \stdClass();
	
					$select_key = "
						SELECT
							usr_id
						FROM
							".TABLE_USER."
						WHERE
							sha1(usr_id) = '".substr($key, 40, 40)."' AND
							sha1(usr_user_name) = '".substr($key, 80, 40)."'
					";
	
					// get a link
					$link = $class_db::getLink();
	
					// prepare a statement				
					$statement = $link->prepare($select_key);
	
					// execute a statement
					$statement->bind_result($member_id);
	
					// execute a statement
					$execution_result = $statement->execute();
			
					// fetch result of a statement
					$fetch_result = $statement->fetch();
	
					// set the content type property of the media
					$media->{PROPERTY_CONTENT_TYPE} = CONTENT_TYPE_PHOTOGRAPH;
					
					// set the identifier property
					$media->{PROPERTY_IDENTIFIER} = $identifier;
	
					if ( $fetch_result )
					{
						$_properties->{PROPERTY_IDENTIFIER} = $member_id;
	
						$_properties->{PROPERTY_KEY} = TRUE;
					}
	
					// fetch a media
					$file_contents = self::fetchMedia(
						$media,
						MEDIA_TYPE_IMAGE,
						$_properties
					);
	
					// Resample
					$true_color = imagecreatetruecolor(
						floor( $width ),
						floor( $height )
					);

					if ( ! empty( $file_contents ) )
					{
						// create an image from jpeg
						$image = imagecreatefromstring( $file_contents );

						// copy the resampled image
						imagecopyresampled(
							$true_color,
							$image,
							0,
							0,
							0,
							0,
							floor( $width ),
							floor( $height ),
							$_width,
							$_height
						);
	
						// check that no file has been already generated
						if ( ! is_float( $proportions )  )
						{
							if (
								! file_exists(
									$dir_snapshots."/".
										$maximum_long_edge."_".
											$proportions."/".
												$identifier
								)
							)
	
								// Output
								imagejpeg(
									$true_color,
									$dir_snapshots."/".
										$maximum_long_edge."_".
											$proportions."/".
												$identifier,
									IMAGE_JPEG_QUALITY
								);
						}
						else
						
							throw new \Exception( EXCEPTION_INVALID_PROPORTIONS );
					}
				}
	
				// assign the value of an image src attribute
				$template_engine->assign(
					HTML_ATTRIBUTE_SRC,
					URI_LOAD_PHOTOGRAPH_REWRITTEN.
					$identifier
				);

				// assign the value of an image class attribute
				$template_engine->assign(
					HTML_ATTRIBUTE_STYLE,
					'height:'.floor($height).'px;'.
					'width:'.floor($width).'px'
				);

				if ( ! empty( $height ) )
				
					// assign the value of an image src attribute
					$template_engine->assign(
						HTML_ATTRIBUTE_HEIGHT,
						floor($height)
					);
				
				if ( ! empty( $width ) )

					// assign the value of an image src attribute
					$template_engine->assign(
						HTML_ATTRIBUTE_WIDTH,
						floor($width)
					);
	
				if ( ! $avatar )
	
					// assign the photograph id
					$template_engine->assign(
						PLACEHOLDER_PHOTOGRAPH_ID,
						PREFIX_DOM_IDENTIFIER_IMAGE_PHOTOGRAPH.$identifier
					);	
			}

			// display a template
			$photograph = $template_engine->fetch( $template_name, $cache_id );

			// clear all cache
			$template_engine->clear();

			// return a photograph
			return $photograph;
		}

		/**
		* Fetch a member view
		*
		* @param	integer	$page			page
		* @param	integer	$handler_id		field handler
		* @param	string	$block			block name 
		* @param	array	$variables		variables

		* @return  	nothing
		*/
		public static function fetchView(
			$page = PAGE_UNDEFINED,
			$handler_id = FORM_ORDINARY,
			$block = BLOCK_HTML,
			$variables = null
		)
		{
			// shape an application
			$application = self::shapeApplication($page, $handler_id, $block);

			// return a view
			return $application->fetchMemberView($page, $handler_id, $block, $variables);
		}

		/**
		* Fetch a anchor
		*
		* @param	array	$context	context
		* @param	integer	$page		page
		* @param	boolean	$next 		indicator 
		* @param	integer	$informant	informant
		* @return	string	internal anchor
		*/
		public static function fetch_anchor(
			$context,
			$page = PAGE_UNDEFINED,
			$next = false,
			$informant = null     		
		)
		{
			// declare a default internal anchor
			$anchor = CHARACTER_EMPTY_STRING;
	
			// set the default suffix instance
			$suffix_instance = CHARACTER_EMPTY_STRING;
	
			// check if the context argument is an non empty array
			if (
				is_array($context) &&
				count($context) > 0
			)
			{
				list(
					$handler_id,
					$coordinates				
				) = $context;
	
				// check the position instance context parameter
				if (
					! empty( $context[CONTEXT_INDEX_POSITION_INSTANCE] ) &&
					$context[CONTEXT_INDEX_POSITION_INSTANCE] !=
						CONTEXT_WITHOUT_POSITION_INSTANCE
				)
				{
	
					// set the position instance
					$position_instance = $context[CONTEXT_INDEX_POSITION_INSTANCE];
	
					// set the position instance suffix		
					$suffix_instance = CHARACTER_UNDERSCORE.$position_instance;
				}
			}
	
			// return an anchor
			return $anchor;
		}

		/**
		* Fetch contents
		* 
		* @param	string	$resource_key	resource key
		* @param	string	$resource_type	resource type
		* @return	string	contents
		*/
		public static function fetchResource($resource_key, $resource_type)
		{
			$class_data_fetcher = self::getDataFetcherClass();

			return $class_data_fetcher::fetchResource($resource_key, $resource_type);
		}

		/**
		* Fetch parameters
		* 
		* @param	array	$context	containing context parameters
		* @param	integer	$page		representing a page
		* @return	array	containing parameters
		*/
		public static function fetch_parameters(
			$context,
			$page = PAGE_UNDEFINED
		)
		{
			$callback_parameters = array();

			// set the dumper class name
			$class_dumper = self::getDumperClass();

			// set the form manager class name
			$class_form_manager = self::getFormManagerClass();

			// set the interceptor class
			$class_interceptor = self::getInterceptorClass();

			// set the user handler class name
			$class_user_handler = self::getUserHandlerClass();

			// set the view builder class name
			$class_view_builder = self::getViewBuilderClass();			

			// declare an empty array of form parameters
			$form_parameters = array();

			// set the default suffix instance
			$suffix_instance = CHARACTER_EMPTY_STRING;
	
			// check if the context argument is an non empty array
			if (
				is_array($context) &&
				count($context) > 0
			)
			{
				list(
					$handler_id,
					$coordinates				
				) = $context;
	
				// set the current position
				$current_position = $coordinates[COORDINATES_CURRENT_POSITION];
	
				// set the default stores
				$errors =
				$extra_parameters =
				$field_names =
				$field_options = 
				$field_values =
				$helpers =
				$labels = array();
	
				// check the position instance context parameter
				if (
					! empty(
						$context[CONTEXT_INDEX_POSITION_INSTANCE]
					) &&
					$context[CONTEXT_INDEX_POSITION_INSTANCE]
						!= CONTEXT_WITHOUT_POSITION_INSTANCE
				)
				{
					// set the position instance
					$position_instance = $context[CONTEXT_INDEX_POSITION_INSTANCE];
	
					// set the position instance suffix		
					$suffix_instance = CHARACTER_UNDERSCORE.$position_instance;
				}
	
				// check the data submission context parameter
				if ( isset( $context[CONTEXT_INDEX_DATA_SUBMISSION] ) )
		
					// set the data submission
					$data_submitted = $context[CONTEXT_INDEX_DATA_SUBMISSION];
	
				// check the disclaimer context parameter
				if ( isset( $context[CONTEXT_INDEX_DISCLAIMERS] ) )
		
					// set the disclaimers
					$disclaimers = $context[CONTEXT_INDEX_DISCLAIMERS];
	
				// check the defaut field values context parameter
				if ( isset( $context[CONTEXT_INDEX_DEFAULT_FIELD_VALUES] ) )
		
					// set the field values
					$default_field_values =
						$context[CONTEXT_INDEX_DEFAULT_FIELD_VALUES]
					;
	
				// check the errors context parameter
				if ( isset($context[CONTEXT_INDEX_ERRORS] ) )
		
					// set the errors
					$errors = $context[CONTEXT_INDEX_ERRORS];
	
				// check the field options context parameter
				if ( isset( $context[CONTEXT_INDEX_FIELD_OPTIONS] ) )
		
					// set the field options
					$field_options = $context[CONTEXT_INDEX_FIELD_OPTIONS];
	
				// check the field names context parameter
				if ( isset( $context[CONTEXT_INDEX_FIELD_NAMES] ) )
		
					// set the field names
					$field_names = $context[CONTEXT_INDEX_FIELD_NAMES];
	
				// check the field values context parameter
				if ( isset( $context[CONTEXT_INDEX_FIELD_VALUES] ) )
		
					// set the field values
					$field_values = $context[CONTEXT_INDEX_FIELD_VALUES];
		
				// check the helper context parameter
				if ( isset( $context[CONTEXT_INDEX_HELPERS] ) )
		
					// set the helpers
					$helpers = $context[CONTEXT_INDEX_HELPERS];
		
				// check the label context parameter
				if ( isset( $context[CONTEXT_INDEX_LABELS] ) )
		
					// set the labels
					$labels = $context[CONTEXT_INDEX_LABELS];
		
				// check the template models context parameter
				if (
					isset( $context[CONTEXT_INDEX_TEMPLATES] ) &&
					is_array( $context[CONTEXT_INDEX_TEMPLATES] ) &&
					isset(
						$context
							[CONTEXT_INDEX_TEMPLATES]
								[TEMPLATE_INDEX_MODELS]
					)
				)
		
					// set the template models
					$template_models =
						$context
							[CONTEXT_INDEX_TEMPLATES]
								[TEMPLATE_INDEX_MODELS]
					;
			}	

			// set the scripts
			list(
				$current_script,
				$default_script,
				$next_script
			) = self::fetch_scripts( $context, NULL, $page );

			// set the current anchor
			$current_anchor = self::fetch_anchor( $context, $page );

			if ( $page != PAGE_UNDEFINED )
			{
				// switch from the page
				switch ( $page )
				{
					case PAGE_CONTENT:

						if (
							$class_interceptor::getPrimalDefinition()
								!= ACTION_DISPLAY_DOCUMENT
						)
	
							// switch from the handler identifier
							switch ( $handler_id )
							{
								case ROUTE_DOCUMENT:
	
									// build a content view
									$parameters[ENTITY_CONTENT] =
										$class_view_builder::buildContent(
											$handler_id,
											ENTITY_CONTENT
										);

									// build the right column
									$parameters[PLACEHOLDER_COLUMN_RIGHT] =
										$class_view_builder::buildBlock(
											$page,
											BLOCK_RIGHT,
											array(
												ENTITY_DOCUMENT =>
													$handler_id
											)
										)
									;
	
										break;
	
								case ROUTE_SEARCH_RESULTS:

									if (
										! empty(
											$_SESSION[STORE_SEARCH_RESULTS]
										)
									)
									{
										$configuration =
											$_SESSION[STORE_SEARCH_RESULTS];

										$configuration->{ACTION_SEARCH} = TRUE;

										// set the default configuration border
										$configuration->{BORDER_DEFAULT} =
											$class_interceptor::getDefaultBorder()
										;

										$class_interceptor::forgetDefaultBorder();

										// build a content view
										$parameters[ENTITY_CONTENT] =
											$class_view_builder::buildResultPage(
												$configuration,
												$configuration->{BORDER_DEFAULT} - 1
											)
										;

										if ( $configuration !== NULL )	

											// build the right column
											$parameters[PLACEHOLDER_COLUMN_RIGHT] =
												$class_view_builder::buildBlock(
													$page,
													BLOCK_RIGHT,
													$configuration
												)
											;
										else
										
											throw new Exception(
												EXCEPTION_INVALID_CONFIGURATION
											);
									}

									break;
	
								case ROUTE_WANDERING:

									// route the visitor to the display document page
									self::jumpTo( URI_ACTION_DISPLAY_DOCUMENT );
	
								case ROUTE_WONDERING:

									// build a content view
									$parameters[ENTITY_CONTENT] =
										$class_view_builder::buildContent(
											$handler_id,
											ENTITY_ROUTE
										)
									;

									// build the right column
									$parameters[PLACEHOLDER_COLUMN_RIGHT] =
										$class_view_builder::buildBlock(
											$page,
											BLOCK_RIGHT
										)
									;
	
										break;
							}
	
						// check if the primal definition is of the display document type
						else if (
							$class_interceptor::getPrimalDefinition() ==
								ACTION_DISPLAY_DOCUMENT
						)
						{
							// get the primal definition
							$primal_definition =
								$class_interceptor::getPrimalDefinition();
	
							// build a content view
							$parameters[ENTITY_CONTENT] =
								$class_view_builder::buildContent(
									$handler_id,
									ENTITY_CONTENT,
									$primal_definition
								)
							;

							if (
								! empty(
									$_SESSION[STORE_SEARCH_RESULTS]
										->{BORDER_DEFAULT}
								)
							)

								unset(
									$_SESSION[STORE_SEARCH_RESULTS]
										->{BORDER_DEFAULT}
								);
							
							if ( ! empty( $_SESSION[STORE_SEARCH_RESULTS] ) )

								// build the right column
								$parameters[PLACEHOLDER_COLUMN_RIGHT] =
									$class_view_builder::buildBlock(
										$page,
										BLOCK_RIGHT,
										$_SESSION[STORE_SEARCH_RESULTS]
									)
								;
						}

							break;

					case PAGE_HOMEPAGE:
					case PAGE_OVERVIEW:

						// clean up the handler store if the visitor returns to the homepage
						if ( $page == PAGE_HOMEPAGE )
						{
							$handlers = $class_form_manager::getHandlerStatus();

							while ( list( $_handler_id ) = each( $handlers ) )
	
								// destroy the current field handler
								$class_form_manager::destroyHandler(
									$_handler_id,
									ACTION_SEARCH
								);
						}

						// set the form parameters
						$form_parameters = array(
							PLACEHOLDER_COLUMN_RIGHT =>
								$class_view_builder::buildBlock(
									$page,
									BLOCK_RIGHT
								),
							PLACEHOLDER_BODY_FOOTER =>
								$class_view_builder::buildBlock(
									$page,
									BLOCK_FOOTER
								),
							PLACEHOLDER_BODY_HEADER =>
								$class_view_builder::buildBlock(
									$page,
									BLOCK_HEADER
								)
						);

						// check the page before setting the next placeholder
						if ( $page == PAGE_OVERVIEW )

							$form_parameters[PLACEHOLDER_BODY] = TRUE;
						else

							$form_parameters[PLACEHOLDER_BODY] = FALSE;

						if (
							$class_user_handler::loggedIn() &&
							empty( $_SESSION[STORE_DIALOG] ) &&
							$page != PAGE_OVERVIEW
						)
						{
							if (
								! isset( $_SESSION[STORE_DIALOG] ) ||
								! is_array( $_SESSION[STORE_DIALOG] )
							)

								$_SESSION[STORE_DIALOG] = array();

							if ( SEFI_ARTICLES_BASE )
							{
								$_SESSION[STORE_DIALOG][] = ACTION_SEARCH;
	
								$form_parameters[PLACEHOLDER_MAIN] = self::getFormView(
									ACTION_SEARCH,
									BLOCK_FORM,
									PAGE_DIALOG
								);

								unset( $_SESSION[STORE_DIALOG] );
							}
							else if (
								$_SERVER['REQUEST_URI'] !==
									URI_PAGE_WONDERING
							)
							{
								unset( $_SESSION[STORE_DIALOG] );

								self::jumpTo( URI_PAGE_WONDERING );
							}
						}
						else 

							unset( $_SESSION[STORE_DIALOG] );

							break;
				}
			}

			// check if the form parameters are set
			if (
				is_array( $form_parameters ) &&
				count( $form_parameters ) != 0
			)
			{
				// add field options to the form variables 
				$form_parameters += $field_options;
		
				// add extra parameters to the form variables 
				$form_parameters += $extra_parameters;
			
				// review form parameters
				$class_dumper::log(
					__METHOD__,
					array(
						'form parameters:',
						$form_parameters
					)
				);

				// return the form parameters
				$callback_parameters = $form_parameters;
			}
			
			// check if the parameters are set
			else if ( isset( $parameters ) )

				$callback_parameters = $parameters;

			$callback_parameters[PLACEHOLDER_FOOTER] = self::getFooter();

			return $callback_parameters;
		}
	
		/**
		* Fetch a roadmap
		* 
		* @param	array	&$context	context parameters
		* @param	integer	$page		page
		* @return	array	containing a templates
		*/
		public static function fetch_roadmap(
			&$context,
			$page = PAGE_UNDEFINED
		)
		{
			// set the dumper class name
			$class_dumper = self::getDumperClass();

			// set the field handler class name
			$class_field_handler = self::getFieldHandlerClass();

			// set a default roadmap
			$roadmap = array();

			// check if the context argument is an non empty array
			if (
				is_array( $context ) &&
				count( $context ) 
			)			

				list( $handler_id ) = $context;

			// set the default handler status
			$handler_inactive = false;
	
			// get the current handlers
			$handlers = $class_field_handler::get_handlers();

			if (
				$handler_id == FORM_UNDEFINED &&
				$page != PAGE_UNDEFINED ||
				$page == PAGE_CONTENT
			)
			{
				// set the roadmap
				$roadmap = array(
					$page => PAGE_STATUS_ACTIVE
				);

				// set templates
				list(
					$template,
					$template_model,
					$template_pattern,
					$prefix_template
				) = self::fetch_templates( $context, $page );
			}

			// check the status of the current handler
			if (
				is_array( $handlers ) &&
				count( $handlers ) &&
				! empty( $handlers[$handler_id] ) &&
				$handlers[$handler_id] == HANDLER_STATUS_INACTIVE
			)
			{
				// switch from the handler identifier
				switch ($handler_id)
				{
					default:
	
						// set the roadmap
						$roadmap = array(
							$page => HANDLER_STATUS_INACTIVE
						);
				}

				// set templates
				list(
					$template,
					$template_model,
					$template_pattern,
					$prefix_template
				) = self::fetch_templates( $context, $page );
	
				// return the roadmap
				return $roadmap;
			}
	
			// check the page
			if (
				$page != PAGE_CONTENT && ! isset( $roadmap )
				|| ! count( $roadmap )
			)

				// set the roadmap
				$roadmap = array( $page => REQUEST_SINGLE_STEP );

			// set the roadmap context parameter
			$context[CONTEXT_INDEX_ROADMAP] = $roadmap;
	
			// check if a non empty roadmap has been declared
			if (
				isset( $roadmap ) &&
				is_array( $roadmap ) &&
				count( $roadmap )
			)
			{
				// set the next internal anchor
				$next_anchor = CHARACTER_EMPTY_STRING;
				
				// get information about the next position
				self::get_next_position( $context, $next_anchor );
		
				// set the current anchor
				$current_anchor = self::fetch_anchor( $context );
			}
	
			// get the default requests of position and next position instances 
			list(
				$position_instance,
				$suffix_instance
			) = self::get_position_instances( $context, $next_anchor );

			// return the roadmap
			return $roadmap;
		}
	
		/**
		* Fetch scripts
		* 
		* @param	array	$context	containing context parameters
		* @param	integer	$index		representing an index
		* @param	integer	$page		representing a page
		* @return	mixed
		*/
		public static function fetch_scripts(
			&$context,
			$index = NULL,
			$page = PAGE_UNDEFINED
		)
		{
			// 	declare
			//	the default beginning of a request
			//	the default requests of position and next position instances
			// 	the default next position		
			//	the default scripts
			$default_instance =
			$next_instance = 
			$request_start = 
			$next_position = CHARACTER_EMPTY_STRING;

			// declare the default array of scripts to be returned
			$scripts = self::build_space( 3 );
	
			// check if the context argument is an non empty array
			if ( is_array( $context ) && count( $context ) )
			{
				// set a reference to the coordinates
				$coordinates = &$context[CONTEXT_INDEX_COORDINATES];
	
				// check the default request of position instance
				if (
					! empty(
						$context[CONTEXT_INDEX_DEFAULT_POSITION_INSTANCE_REQUEST]
					)
				)
	
					// set the default request of position instance			
					$default_instance =
						$context[CONTEXT_INDEX_DEFAULT_POSITION_INSTANCE_REQUEST]
					;

				// check the default request of position instance
				if (
					! empty(
						$context[CONTEXT_INDEX_NEXT_POSITION_INSTANCE_REQUEST]
					)
				)
	
					// set the default request of next position instance			
					$next_instance =
						$context[CONTEXT_INDEX_NEXT_POSITION_INSTANCE_REQUEST];
	
				// set the handler identifier
				list( $handler_id ) = $context;
			}

			// set the default current URI
			$current_URI = $_SERVER['PHP_SELF'];

			// check the current rewritten URI
			if ( ! empty( $_SERVER['REQUEST_URI'] ) )

				// set the current URI
				$current_URI = $_SERVER['REQUEST_URI'];

			// set the current action script
			$scripts[SCRIPT_INDEX_CURRENT] =

			// set the next step action script
			$scripts[SCRIPT_INDEX_NEXT] = $current_URI;
	
			// check if an index has been passed as an argument
			if ( ! isset( $index ) )
	
				// return scripts
				return $scripts;
			else 	
	
				// return a script
				return $scripts[$index];
		}
	
		/**
		* Fetch stores
		* 
		* @param	array	$context	containing context parameters
		* @param	integer	$page		representing a page
		* @param	string	$store_type	containing a store type
		* @param	mixed	$informant	informant
		* @param	boolean	$assert		assertion flag
		* @return	mixed
		*/
		public static function fetch_stores(
			&$context,
			$page = PAGE_UNDEFINED,
			$store_type = STORE_FIELDS,
			$informant = NULL,
			$assert = FALSE
		)
		{
			// set the Dumper class name
			$class_dumper = self::getDumperClass();

			// set the Form Manager class name
			$class_form_manager = self::getFormManagerClass();

			// declare stores
			$default_field_values =
			$errors =
			$field_names =
			$field_options =
			$field_values =
			$helpers =
			$labels = array();
	
			// declare data submission status
			$data_submitted = FALSE;
	
			// set the scripts
			list(
				$current_script,
				$default_script,
				$next_script
			) = self::fetch_scripts( $context, NULL, $page );
	
			// get options
			$options = self::get_options( $page, $context );

			// declare the default stores
			$stores = self::build_space( 10 );
	
			// declare default arrays of stores and template models
			$template_models = array();

			// set templates
			list(
				$template,
				$template_model,
				$template_pattern,
				$prefix_template
			) = self::fetch_templates( $context, $page );
	
			// check if the context argument is an non empty array
			if (
				is_array( $context ) &&
				count( $context )
			)
			{
				// get the handler identifier, the coordinates, the position instance
				list(
					$handler_id,
					$coordinates
				) = $context;
		
				// set the current position
				$current_position = $coordinates[COORDINATES_CURRENT_POSITION];
		
				// check the children count context parameter
				if (isset($context[CONTEXT_INDEX_CHILDREN_COUNT]))
		
					// set the children count
					$children_count = $context[CONTEXT_INDEX_CHILDREN_COUNT];
		
				// check the field context parameter
				if (isset($context[CONTEXT_INDEX_FIELDS]))
		
					// set the fields
					$fields = &$context[CONTEXT_INDEX_FIELDS];
		
				// check the marital status context parameter
				if (isset($context[CONTEXT_INDEX_MARITAL_STATUS]))
		
					// set the marital status
					$marital_status = $context[CONTEXT_INDEX_MARITAL_STATUS];
		
				// check the position context parameter
				if (!empty($context[CONTEXT_INDEX_POSITION_INSTANCE]))
		
					// set the position instance
					$position_instance = $context[CONTEXT_INDEX_POSITION_INSTANCE];
		
				// check the roadmap context parameter
				if (!empty($context[CONTEXT_INDEX_ROADMAP]))
		
					// set the roadmap
					$roadmap = $context[CONTEXT_INDEX_ROADMAP];
		
				// check the template context parameter
				if (
					isset($context[CONTEXT_INDEX_TEMPLATES]) &&
					is_array($context[CONTEXT_INDEX_TEMPLATES])
				)
		
					// set the template pattern
					$template_pattern = $context[CONTEXT_INDEX_TEMPLATES][TEMPLATE_INDEX_PATTERN];
			}

			$properties = array(
				$handler_id,						
				$current_script,
				$coordinates,
				$roadmap,
				PROTOCOL_HTTP_METHOD_POST
			); 

			// deserialize a form manager from properties
			$field_handler = &$class_form_manager::deserialize(
				$properties,
				ENTITY_FIELD_HANDLER
			);

			$class_dumper::log(
				__METHOD__,
				array(
					'field handler from deserialization: ',
					$field_handler
				)
			);

			// get fields
			self::get_fields( $page, $context );

			// check if some fields have been provided to the field handler
			if ( isset( $fields ) )

				list(
					$labels,
					$field_handler,
					$field_names,
					$field_options,
					$default_field_values,
					$helpers
				) = $field_handler->build_form(
					$fields,
					$options
				);

			else
			{
				// redirect to a debugging script if no field is set
				if ( DEBUGGING_FORM_BUILDING )

					// route the visitor to the default script
					self::jumpTo( $default_script );

				// check the current development cycle
				if ( self::am_i_irl() )

					// terminate the script interpretation
					exit();
				else

					// throw an exception
					throw new \Exception( EXCEPTION_UNDEDINED_FIELDS );
			}

			// call the field values controller
			list(
				$errors,
				$field_values,
				$data_submitted
			) = $field_handler->check_fields(
				$fields,
				$options,
				$current_position,
				NULL,
				$handler_id
			);

			$class_dumper::log(
				__METHOD__,
				array(
					'submission: ',
					$data_submitted,
					'errors: ',
					$errors,
					'field values: ',
					$field_values
				),
				DEBUGGING_FIELD_HANDLING,
				! empty( $_POST ) &&
				! $data_submitted
			);
		
			// set the stores
			$stores = array(
				$data_submitted,
				$default_field_values,
				$errors,
				$field_handler,
				$field_names,
				$field_options,
				$field_values,
				$helpers,
				$labels,
				$template_models
			);
	
			// set the data submission context parameter				
			$context[CONTEXT_INDEX_DATA_SUBMISSION] = $data_submitted;
	
			// set the default field values context parameter
			$context[CONTEXT_INDEX_DEFAULT_FIELD_VALUES] = $default_field_values;
	
			// set the error context parameter
			$context[CONTEXT_INDEX_ERRORS] = $errors;
	
			// set the field handler context parameter				
			$context[CONTEXT_INDEX_FIELD_HANDLER] = $field_handler;
	
			// set the field names context parameter				
			$context[CONTEXT_INDEX_FIELD_NAMES] = $field_names;
	
			// set the field options context parameter				
			$context[CONTEXT_INDEX_FIELD_OPTIONS] = $field_options;
	
			// set the field values context parameter				
			$context[CONTEXT_INDEX_FIELD_VALUES] = $field_values;
	
			// set the helpers context parameter		
			$context[CONTEXT_INDEX_HELPERS] = $helpers;
	
			// set the labels context parameter
			$context[CONTEXT_INDEX_LABELS] = $labels;
	
			// set the template models context parameter
			$context[CONTEXT_INDEX_TEMPLATES][TEMPLATE_INDEX_MODELS] = $template_models;
	
			// check field values
			$class_dumper::log(
				__METHOD__,
				array(
					'check field values to be passed to the view:',
					$field_values,
					'check errors to be passed to the view:',
					$errors
				),
				DEBUGGING_DISPLAY_SUBMISSION_ERRORS
			);

			// return the stores
			return $stores;
		}
	
		/**
		* Fetch templates
		* 
		* @param	array	$context	containing context parameters
		* @param	integer	$page		representing a page
		* @return	array	containing a templates
		*/
		public static function fetch_templates(
			&$context,
			$page = PAGE_UNDEFINED
		)
		{
			// set the dumper class name
			$class_dumper = self::getDumperClass();

			// set the template engine class name
			$class_template_engine = self::getTemplateEngineClass();

			// set the default position and suffix instance
			$position_instance =

			$suffix_instance = CHARACTER_EMPTY_STRING;

			// construct a new Smarty object
			$template = new $class_template_engine;

			// declare a template store
			$template_store = array();
	
			// append the newly constructed Smarty object to the template store
			$template_store[] = $template;
	
			// append default template model, pattern and prefix to the template store
			$template_store = array_merge(
				$template_store,
				self::build_space(2)
			);
	
			// declare the template prefix path
			$template_store[TEMPLATE_INDEX_PREFIX] =
				dirname(__FILE__).
				DIR_PARENT_DIRECTORY.
				DIR_PARENT_DIRECTORY.
				DIR_TEMPLATES.
				CHARACTER_SLASH
			;
	
			// check if the context argument is an non empty array
			if (is_array($context) && count($context) > 0)
			{
				list(
					$handler_id,
					$coordinates
				) = $context;
	
				// check if the context position instance parameter
				if (!empty($context[CONTEXT_INDEX_POSITION_INSTANCE]))
	
					// set the position instance
					$position_instance = $context[CONTEXT_INDEX_POSITION_INSTANCE];
			}

			// check the handler and page identifiers
			if (
				$handler_id == FORM_UNDEFINED &&
				$page != PAGE_UNDEFINED
			)
			{
				$constants = get_defined_constants( TRUE );
				
				$keys = array_keys( $constants['user'], $page );

				$pattern =
					REGEXP_OPEN.
						REGEXP_START.
						strtoupper(PREFIX_PAGE).
						REGEXP_CATCH_START.
							REGEXP_WILDCARD.REGEXP_ANY.
						REGEXP_CATCH_END.
					REGEXP_CLOSE
				;

				// loop on keys
				foreach ( $keys as $index => $key )
				{
					$match = preg_match ($pattern, $key, $matches );

					if ($match)
					
						$reversed_constant = $matches[1];	
				}

				if (
					defined(
						strtoupper(PREFIX_TEMPLATE).
						strtoupper(PREFIX_PAGE).
						$reversed_constant
					)
				)

					$template_store[TEMPLATE_INDEX_MODEL] = constant(
						strtoupper(PREFIX_TEMPLATE).
						strtoupper(PREFIX_PAGE).
						$reversed_constant
					);
			}

			// check if the current page is a content page
			else if ( $page == PAGE_CONTENT )

				$template_store[TEMPLATE_INDEX_MODEL] = constant(
					strtoupper(
						PREFIX_TEMPLATE.
						PREFIX_PAGE.
						ENTITY_CONTENT
					)
				);

			// check if a template pattern has been set
			if (
				isset($template_store) &&
				is_array($template_store) &&
				count($template_store) != 0
			)
	
				// set the template context parameter
				$context[CONTEXT_INDEX_TEMPLATES] = $template_store;

			// return template, pattern and model
			return $template_store;
		}
	
		/**
		* Fetch variables
		* 
		* @param	array	$context	containing context parameters
		* @param	integer	$page		representing a page
		* @return	array	containing variables
		*/
		public static function fetch_variables(&$context, $page = PAGE_UNDEFINED)
		{
			// set the default suffix instance and span element
			$span_element = 
			$suffix_instance = CHARACTER_EMPTY_STRING;
	
			// set the default variables
			$variables = array();
	
			// check if the context argument is an non empty array
			if (is_array($context) && count($context) > 0)
			{
				list(
					$handler_id,
					$coordinates
				) = $context;
	
				// set the current position
				$current_position = $coordinates[COORDINATES_CURRENT_POSITION];
	
				// check the data submission context parameter
				if (isset($context[CONTEXT_INDEX_DATA_SUBMISSION]))
		
					// set the data submission
					$data_submitted = $context[CONTEXT_INDEX_DATA_SUBMISSION];
	
				// check the position instance context parameter
				if (
					!empty($context[CONTEXT_INDEX_POSITION_INSTANCE]) &&
					$context[CONTEXT_INDEX_POSITION_INSTANCE] != CONTEXT_WITHOUT_POSITION_INSTANCE &&
					$current_position != REQUEST_STEP_3
				)
				{
	
					// set the position instance
					$position_instance = $context[CONTEXT_INDEX_POSITION_INSTANCE];
	
					// set the position instance suffix		
					$suffix_instance = CHARACTER_UNDERSCORE.$position_instance;
				}
			}

			switch ($page)
			{
				case PAGE_UNDEFINED:
	
					if (
						is_array($variables) &&
						count($variables) == 0
					)
						$variables = array(
							HTML_ELEMENT_BODY
						);
	
					break;	
			}

			// return variables
			return $variables;
		}
	
		/**
		* Fetch view
		* 
		* @param	array	$context	context parameters
		* @param	integer	$page		page
		* @param	mixed 	$informant	informant
		* @return	string	containing a view
		*/
		public function fetch_view(
			&$context,
			$page,
			$informant = NULL
		)
		{
			// set the dumper class name
			$class_dumper = self::getDumperClass();

			// set the entity class name
			$class_entity = self::getEntityClass();

			// set the flag Manager class name
			$class_flag_manager = self::getFlagManagerClass();

			// set the form manager class name
			$class_form_manager = self::getFormManagerClass();

			// set the insight class name
			$class_insight = self::getInsightClass();

			// set the interceptor class name
			$class_interceptor = self::getInterceptorClass();

			// set the media manager class name
			$class_media_manager = self::getMediaManagerClass();

			// set the member class name
			$class_member = self::getMemberClass();

			// set the user handler class name
			$class_user_handler = self::getUserHandlerClass();

			// set the view builder class name
			$class_view_builder = self::getViewBuilderClass();

			$cache_id = NULL;

			// set the default view	
			$view = '';

			// check if the context argument is an non empty array
			if ( is_array( $context ) && count( $context ) )
			{
				list( $handler_id ) = $context;

				if (
					isset( $context[CONTEXT_INDEX_TEMPLATES] ) &&
					is_array( $context[CONTEXT_INDEX_TEMPLATES] ) &&
					count( $context[CONTEXT_INDEX_TEMPLATES] )
				)
				{
					// check the template object
					if (
						is_object(
							$context
								[CONTEXT_INDEX_TEMPLATES]
									[TEMPLATE_INDEX_OBJECT]
						)
					)
	
						// set the template object
						$template_engine =
							$context
								[CONTEXT_INDEX_TEMPLATES]
									[TEMPLATE_INDEX_OBJECT]
						;
	
					// check the template pattern
					if (
						is_string(
							$context
								[CONTEXT_INDEX_TEMPLATES]
									[TEMPLATE_INDEX_MODEL]
						)
					)
	
						// set the template pattern
						$template_model =
							$context
								[CONTEXT_INDEX_TEMPLATES]
									[TEMPLATE_INDEX_MODEL]
						;
	
					// check the template pattern
					if (
						is_string(
							$context
								[CONTEXT_INDEX_TEMPLATES]
									[TEMPLATE_INDEX_PATTERN]
						)
					)
		
						// set the template pattern
						$template_pattern =
							$context
								[CONTEXT_INDEX_TEMPLATES]
									[TEMPLATE_INDEX_PATTERN]
						;
	
					// check the template prefix
					if (
						is_string(
							$context
								[CONTEXT_INDEX_TEMPLATES]
									[TEMPLATE_INDEX_PREFIX]
						)
					)
	
						// set the template prefix
						$template_prefix =
							$context
								[CONTEXT_INDEX_TEMPLATES]
									[TEMPLATE_INDEX_PREFIX]
						;
				}

				// check the template model
				if ( empty( $template_model ) && $page != PAGE_DIALOG )
				{
					// get the current form identifier
					$form_identifier =
						$class_form_manager::get_persistent_property(
							PROPERTY_FORM_IDENTIFIER,
							$handler_id
						)
					;

					// get the current parent
					$parent =
						$class_form_manager::get_persistent_property(
							PROPERTY_PARENT,
							$handler_id
						)
					;

					// set the template model
					$template_model =
						PREFIX_LANGUAGE_wd_WD.
						PREFIX_FORM.
						$form_identifier.
						EXTENSION_TPL
					;
				}
				else if ( $page == PAGE_DIALOG )
				{
					// get the current form identifier
					$form_identifier =
						$class_form_manager::get_persistent_property(
							PROPERTY_FORM_IDENTIFIER,
							$handler_id
						)
					;

					// get the current parent
					$parent =
						$class_form_manager::get_persistent_property(
							PROPERTY_PARENT,
							$handler_id
						)
					;
				}
			}

			// get member variables
			$_variables = &$this->get_variables();
	
			// set the default variables
			$default_variables = self::fetch_variables( $context, $page );

			// check if the template exists
			if (
				! empty( $template_prefix ) &&
				! empty( $template_model ) &&
				file_exists( $template_prefix.$template_model ) &&
				! is_dir( $template_prefix.$template_model )
			)
			{
				$template_engine->cache_lifetime = 0;

				$_context = $context;

				$member_identifier =
					$class_member::getIdentifier( FALSE, FALSE );

				$administrator_identifier =
					$class_member::getIdentifier( TRUE, FALSE );

				$context_caching = array(
					$_context,
					$page,
					$member_identifier,
					$administrator_identifier
				);

				if ( $page == PAGE_CONTENT )
				{
					$default_border = $class_interceptor::getDefaultBorder();

					unset( $_context[CONTEXT_INDEX_TEMPLATES] );

					$context_caching[] = $default_border;

					if ( $handler_id == ROUTE_WONDERING )
					{
						$flags = $class_flag_manager::getFlags(
							array( 'usr_id' => $member_identifier )
						);
						
						// load photos by author identifier
						$photos = 
							$class_media_manager::loadPhotosByAuthorId(
								$member_identifier,
								FALSE,
								array(
									PROPERTY_START =>
										(
											$default_border =
												$class_interceptor::getDefaultBorder()
										) *
										PAGINATION_COUNT_PER_PAGE_PHOTOGRAPH -
											PAGINATION_COUNT_PER_PAGE_PHOTOGRAPH,
									PROPERTY_LENGTH =>
										PAGINATION_COUNT_PER_PAGE_PHOTOGRAPH
								)
							)
						;

						while ( list( $id, ) = each( $photos ) )
						
							$context_caching[] = $class_insight::loadThread(
								$id,
								$class_entity::getByName( CLASS_PHOTOGRAPH )
									->{PROPERTY_ID}
							);

						reset( $photos );

						$context_caching[] = $flags;
					}
					else

						$template_engine->cache_lifetime = 3600 * 24 * 365 * 10;
				}

				$cache_id = md5( serialize( $context_caching ) );

				if (
					! (
						$cached = $template_engine->is_cached(
							$template_prefix.$template_model,
							$cache_id
						)
					)
				)
				{
					// set the paramaters
					$parameters = self::fetch_parameters( $context, $page );

					// check the parameters
					if (is_array($parameters) && count($parameters) != 0)
					
						// loop on parameters
						while (
							list( $variable_name, $variable_value ) =
								each( $parameters )
						)
		
							// assign form view parameters to a template			
							$template_engine->assign(
								$variable_name,
								$variable_value
							);
				}

				// set the form view
				$view_form = $template_engine->fetch(
					$template_prefix.$template_model,
					$cache_id
				);

				// clear all cache
				$template_engine->clear();

				// check if the page is a content page
				if ( $page == PAGE_CONTENT )

					$view = $class_view_builder::buildBlock(
						PAGE_HOMEPAGE,
						BLOCK_HEADER
					).$view_form;
			}
			// check if the page is undefined
			else if (
				$page == PAGE_UNDEFINED ||
				$page == PAGE_DIALOG
			)
			{
				// check the form identifier
				if ( isset( $form_identifier ) )
				
					// switch from the form identifier
					switch ( $form_identifier )
					{
						case AFFORDANCE_SIGN_IN:

							// check if a user is logged in
							if ( $class_user_handler::loggedIn() )
							{
								// Deactivate the active handler
								$class_form_manager::deactivateHandler(
									$form_identifier
								);

								// return a dialog 
								return $class_view_builder::buildDialog(
									$form_identifier
								);
							}

								break;

						// As reminders
						case ACTION_CHALLENGE:
						case ACTION_SIGN_UP:
						case AFFORDANCE_CONFIRM:
						case AFFORDANCE_EDIT.'.'.self::translate_entity(
							DIALOG_MEMBER_ACCOUNT,
							ENTITY_AFFORDANCE,
							ENTITY_CONSTANT
						):
						default:

							// check if a user is logged in or
							// whether a challenge is offered to the visitor
							if (
								$authorization_granted =
									$class_user_handler::authorizedUser(
										$form_identifier
									) ||
								(
									$class_user_handler::loggedIn() &&
									$parent != ROUTE_OVERVIEW &&
									$parent != ROUTE_UNDEFINED									
								) ||
								in_array(
									$form_identifier,
									array(
										ACTION_SIGN_UP,
										ACTION_CHALLENGE 
									)		 
								) ||
								(
									$class_user_handler::loggedIn( TRUE ) &&
									$parent != ROUTE_ROOT &&
									$parent != ROUTE_UNDEFINED
								) 
							)

								// return a dialog 
								return $class_view_builder::build( $context );
							else

								self::jumpTo( PREFIX_ROOT );
					}

				// return a view
				return $class_view_builder::build( $context );
			}
			else

				// throw an exception
				throw new \Exception( EXCEPTION_MISSING_RESOURCE );
	
			// set member variables to default variables
			$this->set_variables( $default_variables );

			// check the variables	
			if (
				! isset( $variables ) &&
				isset( $_variables ) &&
				is_array( $_variables ) &&
				count( $_variables )
			)

				// get member variables
				$variables = $_variables;

			// check the variables
			else if (
				isset( $variables ) &&
				is_array( $variables ) &&
				count( $variables )
			)

				// set member variables
				$_variables = $variables;

			// check the view
			if ( ! empty( $view ) )

				// return the view
				return $view;
			else

				// return a form view
				return $view_form;
		}

		/**
		* Build an application
		*
		* @param	integer	$page		representing a page
		* @param	integer	$handler_id	representing a field handler
		* @param	string	$block		containing a block name
		* @return 	object	application
		*/
		public static function shapeApplication( $page, $handler_id, $block )
		{
			// return an instance of the application class
			return new self( $page, $handler_id, $block );
		}

		/**
		* Get a feedback view
		* 
		* @param	mixed	$feedback	feedback
		* @param	mixed	$context	context
		* @return  	string	view
		*/	
		public static function getFeedbackView(
			$feedback,
			$context = NULL
		)
		{
			global $class_application, $verbose_mode;

			$class_view_builder = $class_application::getViewBuilderClass();
			
			return $class_view_builder::buildDialog(
				ENTITY_FEEDBACK,
				array(
					ENTITY_MESSAGE => $feedback,
					ENTITY_LAYOUT => TPL_DEFAULT_XHTML_TRANSITIONAL
				),
				$context
			);
		}

		/**
		* Alias to the fetch form method
		*
		* @param	string	$affordance		affordance
		* @param	string	$block			block name
		* @param	integer	$page			page
		* @param	array	$variables		variables
		* @param	array	$administrator	administrator flag
		* @return  	string		form view
		*/	
		public static function getFormView(
			$affordance,
			$block = BLOCK_FORM,
			$page = PAGE_UNDEFINED,
			$variables = null,
			$administrator = false
		)
		{
			// fetch a form view
			return self::fetchForm(
				$affordance,
				BLOCK_FORM,
				$page,
				$variables,
				$administrator
			);
		}

		/**
		* Alias to the View_Builder::getFooter method
		*
		* @return  	string	footer
		*/	
		public static function getFooter()
		{
			global $verbose_mode;
			
			$class_view_builder = self::getViewBuilderClass();

			return $class_view_builder::getFooter();
		}

		/**
		* Get the current language
		*
		* @return	string	containing the current language code
		*/
		public static function get_current_language()
		{
			$language = I18N_LANGUAGE_EN;
	
			if (
				isset( $_SESSION[STORE_I18N] ) &&
				is_array( $_SESSION[STORE_I18N] ) &&
				count( $_SESSION[STORE_I18N] ) != 0 &&
				isset( $_SESSION[STORE_I18N][SECTION_FRONT_END] )
			)

				$language = $_SESSION[STORE_I18N][SECTION_FRONT_END];
	
			return $language;
		}
	
		/**
		* Get field review
		* 
		* @param	integer	$page		representing a page
		* @param	array	$context	containing context parameters
		* @return	array	containing field stores
		*/
		public static function get_field_review($page, &$context)
		{
			$class_dumper = self::getDumperClass();

			$class_field_handler = self::getFieldHandlerClass();

			// declare an array of template models
			$template_models = array();
	
			// check if the context argument is an non empty array
			if (
				is_array( $context ) &&
				count( $context ) 
			)
			{
				list(
					$handler_id,
					$local_coordinates,
					$position_instance,
					$default_instance,
					$next_instance,
					$disclaimers,
					$fields,
					$options,
					$current_script,
					$roadmap,
					$children_count,
					$marital_status,
					$templates
				) = $context;
	
				$template_pattern = $templates[TEMPLATE_INDEX_PATTERN];
			}
	
			// set the current position
			$current_position = $local_coordinates[COORDINATES_CURRENT_POSITION];
	
			// declare an array of store arrays
			$store = array(
				STORE_DEFAULT_VALUES => array(),
				STORE_FIELD_HANDLERS => array(),
				STORE_FIELDS => array(),
				STORE_HELPERS => array(),
				STORE_LABELS => array(),
				STORE_NAMES => array(),
				STORE_OPTIONS => array()
			);
	
			// initialize the page index
			$page_index = PAGE_SIGN_UP_STEP_0;
	
			// initialize the template model index		
			$template_model_index = REQUEST_STEP_0;
	
			// initialize the instance index
			$instance_index = null;
	
			// loop on position until the current step is reached
			while ($page_index < $page)
			{
				// declare the local submit button label
				$local_submit_label = CHARACTER_EMPTY_STRING;
	
				// set the context position
				$context
					[CONTEXT_INDEX_COORDINATES]
						[COORDINATES_CURRENT_POSITION] =
							$template_model_index
				;
	
				// check if the current position is different from the candidate step
				if ($template_model_index != REQUEST_STEP_1)
	
					// set the following position index				
					$next_local_position = $template_model_index + 1;
				else
	
					// by default, the address step follows 
					$next_local_position = 4;
	
				// construct a field handler
				$field_handler = new \Field_Handler(
					$current_script,
					$local_coordinates,
					$roadmap,
					$handler_id
				);
	
				// set the local next anchor
				$local_next_anchor = strtolower(constant("FORM_LABEL_CURRENT_STEP_".$next_local_position));
	
				// set the submit button label for the first step index and indexes strictly greater than the third
				if (
					$next_local_position != REQUEST_STEP_2 &&
					$next_local_position != REQUEST_STEP_3 &&
					$next_local_position != REQUEST_STEP_4
				)
					$local_submit_label = ucfirst($local_next_anchor);
		
				// set the label of a local submit button option
				$options['submit_button'] = $local_submit_label;
	
				// get fields
				$context_set = self::get_fields( $page_index, $context, FALSE );

				// expand the field store
				$store
					[STORE_FIELDS]
						[$template_model_index] =
					$context_set[CONTEXT_INDEX_FIELDS]
				;
	
				try
				{	
					// build a form
					list(
						$local_labels,
						$local_field_handler,
						$local_field_names,
						$local_field_options,
						$local_default_values,
						$local_helpers
					) = $field_handler->build_form(
						$store[STORE_FIELDS][$template_model_index],
						$options
					);
				}
				catch (\Exception $exception)
				{
					$class_dumper::log(
						__METHOD__,
						array($exception),
						DEBUGGING_DISPLAY_EXCEPTION,
						AFFORDANCE_CATCH_EXCEPTION
					);
				}
	
				// store field default values
				$store[STORE_DEFAULT_VALUES][$template_model_index] = $local_default_values;
				$default_field_values[$template_model_index] = $local_default_values;
	
				// set the current field handler
				$field_handler =
	
				// store field handlers			
				$store[STORE_FIELD_HANDLERS][$template_model_index] = $local_field_handler;
	
				// store helpers
				$store[STORE_HELPERS][$template_model_index] = $local_helpers;
				$helpers[$template_model_index] = $local_helpers;
	
				// store labels
				$store[STORE_LABELS][$template_model_index] = $local_labels;
				$labels[$template_model_index] = $local_labels;
	
				// store field names
				$store[STORE_NAMES][$template_model_index] = $local_field_names;
				$field_names[$template_model_index] = $local_field_names;
	
				// store field options
				$store[STORE_OPTIONS][$template_model_index] = $local_field_options;
	
				// set field options
				while (list($option_index, $option) = each($local_field_options))
					$field_options[$option_index] = $option;

				// check if multiple instances of the same position were submitted
				if ($instance_index == null)
	
					// get field values
					$field_values[$template_model_index] =
						$class_field_handler::get_field_values(
							NULL,
							$template_model_index,
							$instance_index,
							$handler_id
						)
					;
	
				else
	
					// get field values				
					$field_values[$template_model_index][$instance_index] =
						$class_field_handler::get_field_values(
							NULL,
							$template_model_index,
							$instance_index,
							$handler_id
						)
					;
	
				// initialize the instance index for the children step
				if ($page_index == PAGE_SIGN_UP_STEP_2)
					$instance_index = 1;
	
				// set the template models for the review step
				$template_models[$template_model_index] = preg_replace(
					$template_pattern,
					$template_model_index,
					TPL_FORM_VIEW_MODEL
				);
	
				// check if there are multiple instances of the same position
				if (
					$page_index != PAGE_SIGN_UP_STEP_3 ||
					$children_count > 0 &&
					$instance_index >= $children_count
				)
				{
					if ($page_index == PAGE_SIGN_UP_STEP_3)
						$instance_index = null;
	
					$page_index++;
					$template_model_index++;					
				}
	
				// case when instances of the same position should be retrieved				
				else if (
					$children_count > 0 &&
					$instance_index < $children_count &&					
					$page_index == PAGE_SIGN_UP_STEP_3
				)
					$instance_index++;
	
				// case when there are no children
				else
				{
					$page_index++;			
					$template_model_index++;
				}
			}
	
			// restore the original context position
			$context
				[CONTEXT_INDEX_COORDINATES]
					[COORDINATES_CURRENT_POSITION] =
						REQUEST_STEP_5
			;
	
			// check the template models
			$class_dumper::log(
				__METHOD__,
				array(
					'template models:',
					$template_models
				)
			);
	
			// return field stores
			return array(
				$default_field_values,
				$field_handler,
				$field_names,
				$field_options,
				$field_values,
				$labels,
				$helpers,
				$store,
				$template_models
			);
		}
		
		/**
		* Get fields
		* 
		* @param	integer	$page			representing a page
		* @param	array	$context		containing context parameters
		* @param	boolean	$update_context	indicating if the context should be updated
		* @param	integer	$informant		representing an informant
		* @return	array	containing context parameters
		*/
		public static function get_fields(
			$page,
			&$context,
			$update_context = TRUE,
			$informant = NULL
		)
		{
			$class_dumper = self::getDumperClass();

			$class_form_manager = self::getFormManagerClass();

			// check if the context argument is an non empty array
			if ( is_array( $context ) && count( $context ) )
			{
				list(
					$handler_id,
					$coordinates
				) = $context;
	
				// check the defaut field values context parameter
				if ( isset( $context[CONTEXT_INDEX_DEFAULT_FIELD_VALUES] ) )
		
					// set the field values
					$default_field_values = $context[CONTEXT_INDEX_DEFAULT_FIELD_VALUES];
	
				// check the disclaimer context parameter
				if ( isset( $context[CONTEXT_INDEX_DISCLAIMERS] ) )
		
					// set the disclaimers
					$disclaimers = &$context[CONTEXT_INDEX_DISCLAIMERS];
	
				// check the field context parameter
				if ( isset( $context[CONTEXT_INDEX_FIELDS] ) )
		
					// set the fields
					$fields = &$context[CONTEXT_INDEX_FIELDS];
	
				// check the default request of position instance
				if ( ! empty( $context[CONTEXT_INDEX_NEXT_POSITION_INSTANCE_REQUEST] ) )
	
					// set the default request of next position instance			
					$next_instance = $context[CONTEXT_INDEX_NEXT_POSITION_INSTANCE_REQUEST];
	
				// check the position instance context parameter
				if ( ! empty( $context[CONTEXT_INDEX_POSITION_INSTANCE] ) )
	
					// set the position instance
					$position_instance = $context[CONTEXT_INDEX_POSITION_INSTANCE];
				else
	
					// set the position position 
					$context[CONTEXT_INDEX_POSITION_INSTANCE] = NULL; 
			}

			// get the persistent fields
			if (
				! is_array( $fields ) ||
				! count( $fields )
			)

				$fields = $class_form_manager::getPersistentProperty(
					PROPERTY_FIELDS,
					$handler_id
				);

			if ( $update_context )
			{
				$context[CONTEXT_INDEX_DISCLAIMERS] = $disclaimers;
				$context[CONTEXT_INDEX_FIELDS] = $fields;
			}
	
			return array(
				$context[CONTEXT_INDEX_HANDLER_IDENTIFIER],
				$context[CONTEXT_INDEX_COORDINATES],
				$context[CONTEXT_INDEX_POSITION_INSTANCE],
				CONTEXT_INDEX_DISCLAIMERS => $disclaimers,
				CONTEXT_INDEX_FIELDS => $fields
			);
		}

		/**
		* Get jumpers
		*
		* @param	array	$context	containing context parameters
		* @param	integer	$page		representing a page
		* @param	integer	$informant	representing an informant
		* @return 	array	containing jumpers
		*/
		public static function get_jumpers(&$context, $page = PAGE_UNDEFINED, $informant = null)
		{
			$jumpers = self::build_space(2);

			// check if the context argument is an non empty array
			if (is_array($context) && count($context) > 0)

				// get the context parameters
				list(
					$handler_id,
					$coordinates
				) = $context;

			return $jumpers;
		}

		/**
		* Provide the next step the data submission process of an application
		*
		* @param	array	$context		containing context parameters
		* @param	string	&$next_anchor	containing an internal anchor
		* @param	object	$field_handler	representing a field handler
		* @param	integer	$informant		representing an informant
		* @return 	nothing
		*/
		public static function get_next_position(
			&$context,
			&$next_anchor,
			$field_handler = null,
			$informant = null
		)
		{
			$class_dumper = self::getDumperClass();

			// check if the context argument is an non empty array
			if (is_array($context) && count($context) > 0)
			{
				list(
					$handler_id,
					$coordinates
				) = $context;

				// get jumpers
				list($children_count, $marital_status) = self::get_jumpers($context);
			}
	
			// set the current position
			$current_position = &$coordinates[COORDINATES_CURRENT_POSITION];
	
			// get the next position
			$next_position = &$coordinates[COORDINATES_NEXT_POSITION];
	
			// check if the field handler argument has been passed to the current method
			if (!isset($field_handler))
			{
				// set the next position
				$coordinates[COORDINATES_NEXT_POSITION] = $next_position;
	
				return;
			}
	
			// check if the field handler argument is an object
			else if (
				is_object($field_handler) &&
				get_class($field_handler) == CLASS_FIELD_HANDLER
			)
			{
				// get the current position
				$current_position =	$field_handler->get_position(COORDINATES_CURRENT_POSITION, $handler_id);
	
				// check the informant
				if ($informant == 'informant value')
	
					// check the next position
					$class_dumper::log(
						__METHOD__,
						array(
							'next position:',
							$next_position
						)
					);
	
				// check the next anchor
				
				if (defined("FORM_LABEL_CURRENT_STEP_".$next_position))
	
					// set the next anchor	
					$next_anchor = strtolower(constant("FORM_LABEL_CURRENT_STEP_".$next_position));
	
				// set the next position in roadmap
				$field_handler->set_position($next_position, COORDINATES_NEXT_POSITION, $handler_id);
			}
		}
	
		/*
		* Get options
		*
		* @param	integer	$page		representing a page
		* @param	array	$context	containing context parameters
		* @return	array	containing option elements
		*/
		public static function get_options( $page = null, $context )
		{
			$class_form_manager = self::getFormManagerClass();
			
			// declare the default label of the submit button
			$next_anchor =
			$next_position =
			$submit_button_label = CHARACTER_EMPTY_STRING;
	
			// declare the default array of options
			$options = array();
	
			// check if the context argument is an non empty array
			if ( is_array( $context ) && count( $context ) > 0 )

				list(
					$handler_id,
					$coordinates
				) = $context;
	
			// get the current position from coordinates
			$current_position = $coordinates[COORDINATES_CURRENT_POSITION];
	
			// get the current position from coordinates
			$next_position = $coordinates[COORDINATES_NEXT_POSITION];
	
			// get information about the next position
			self::get_next_position( $context, $next_anchor );

			if (
				! is_array( $options ) ||
				! count( $options ) 
			)
			{
				// set the required options indicator
				$required_options = false;
	
				$fields = $class_form_manager::get_persistent_property(
					PROPERTY_FIELDS,
					$handler_id
				);
	
				// loop on fields
				while (
					list( $field_index, $field_attributes ) =
						each( $fields )
				)
	
					// check the field option attributes
					if (
						! empty(
							$field_attributes[AFFORDANCE_PROVIDE_WITH_OPTIONS]
						)
					)
					{
						// set the required options indicator
						$required_options = TRUE;

						break;
					}
	
				// check the required options indicator
				if ( $required_options )
	
					// check the required options indicator
					$options = $class_form_manager::get_persistent_property(
						PROPERTY_OPTIONS,
						$handler_id
					);
			}

			// return option elements
			return $options;
		}
	
		/*
		* Get position instances
		* 
		* @param	array	&$context	containing context parameters
		* @param	string	&$next_anchor	containing an internal anchor
		* @param	integer	$informant	representing an informant
		* @return	array	containing instances
		*/
		public static function get_position_instances(
			&$context,
			&$next_anchor,
			$informant = null
		)
		{
			// 	declare the default requests of position and next position instances
			$default_instance =
			$next_instance = CHARACTER_EMPTY_STRING;
	
			// set the default instances
			$instances = self::build_space(2);
	
			// check if the context argument is an non empty array
			if (is_array($context) && count($context) > 0)
			{
				// get the active handler identifier
				list($handler_id) = $context;
				
				// check the coordinates
				if (isset($context[CONTEXT_INDEX_COORDINATES]))
	
					// set a reference to the coordinates
					$coordinates = &$context[CONTEXT_INDEX_COORDINATES];			
			}

			switch ( $handler_id )
			{
				case FORM_LOGIN:
	
					// 	set
					// 	the default request of position request
					//	the default request of next position instance
					//  the position instance
					$context[CONTEXT_INDEX_DEFAULT_POSITION_INSTANCE_REQUEST] =
					$context[CONTEXT_INDEX_NEXT_POSITION_INSTANCE_REQUEST] = 
					$context[CONTEXT_INDEX_POSITION_INSTANCE] = CHARACTER_EMPTY_STRING;
	
						break;
			}
	
			// return instances
			return $instances;
		}

		/**
		* Get properties from a CSV file
		*
		* @param	string		$file_base	containing a file base name
		* @param	string		$separator 	containinga separator
		* @param	string		$directory 	containinga directory name
		* @return	array	containing properties and values
		*/
		public static function get_properties(
			$file_base,
			$separator = CHARACTER_COMMA,
			$directory = ''
		)
		{
			$properties = array();
	
			$file_path_model =
				dirname(__FILE__).
				DIR_PARENT_DIRECTORY.
				DIR_PARENT_DIRECTORY.
				$directory.
				CHARACTER_SLASH.
				DIR_CSV.CHARACTER_SLASH.
				"{file_name}".
				EXTENSION_CSV
			;
	
			$file_path = preg_replace("/\{file_name\}/", $file_base, $file_path_model);
	
			$handle = fopen($file_path, 'r');
			$contents = fread($handle, filesize($file_path));
			fclose($handle);
	
			$rows = explode("\n", $contents);
	
			while (list($line, $row) = each($rows))
			{
				list($property, $value) = explode($separator, $row);
	
				$properties[] = array(
					$property,
					$value
				);
			}
	
			return $properties;
		}

		/**
		* Get trackers
		*
		* @return	nothing
		*/
		public static function get_trackers()
		{
			// check if the application runs in production
			if (self::am_i_irl())
	
				// require the tracker scripts
				require_once(DIR_JAVASCRIPT.CHARACTER_SLASH.SCRIPT_TRACKERS);
		}

		/**
		* Get the current language
		*
		* @param	boolean		$return		return flag 
		* @return  	array		languages
		*/	
		public static function getLanguage($return = true)
		{
			$language = I18N_DEFAULT_LANGUAGE; 
	
			// check the current i18n session store
			if (
				isset($_SESSION[STORE_I18N]) &&
				is_array($_SESSION[STORE_I18N]) &&
				count($_SESSION[STORE_I18N]) != 0 &&
				isset($_SESSION[STORE_I18N][SECTION_FRONT_END])
			)
	
				$language = $_SESSION[STORE_I18N][SECTION_FRONT_END];
	
			else 
			{
				// check the current i18n session store
				if (
					!isset($_SESSION[STORE_I18N]) ||
					!is_array($_SESSION[STORE_I18N])
				)
	
					// set the current i18n session store
					$_SESSION[STORE_I18N] = array();
	
				// check the current i18n front end session store
				if (
					count($_SESSION[STORE_I18N]) == 0 ||
					!isset($_SESSION[STORE_I18N][SECTION_FRONT_END])
				)
	
					// set the current i18n front end session store
					$_SESSION[STORE_I18N][SECTION_FRONT_END] = $language;
			}
	
			// check the return flag
			if ($return)
	
				// return the current language for the front end
				return $language;
	
			else
			
				// display the current language
				echo $language;
		}

		/**
		* Get search results
		*
		* @param	array	$context	context parameters
		* @param	integer	$page		page
		* @return	nothing
		*/	
		public static function getSearchResults(&$context, $page)
		{
			$class_content_manager = self::getContentManagerClass();

			$class_content_manager::getSearchResults($context, $page);
		}

		/**
		* Jump to a URI
		*
		* @param	string		$URI			URI
		* @param	integer		$HTTP_code		HTTP code
		* @param	boolean		$replace		replace flag
		* @param	mixed		$informant		informant
		* @param	boolean		$assert			assertion flag
		* @return	nothing
		*/
		public static function jumpTo(
			$URI,
			$HTTP_code = 302,
			$replace = TRUE,
			$informant = NULL,
			$assert = FALSE
		)
		{
			if ( isset( $_SESSION[ENTITY_FEEDBACK] ) )

				setCookie(
					ENTITY_FEEDBACK,
					serialize( $_SESSION[ENTITY_FEEDBACK] )
				);

			header( 'Location: '.$URI, $replace, $HTTP_code );

			if ( DEBUGGING_ROUTING )
			{
				$class_exception_handler = self::getExceptionHandlerClass();

				$class_exception_handler::logTrace( NULL, $informant, $assert );
			}	

			exit();
		}

		/**
		* Perform an action
		*
		* @param	array		$context	context parameters
		* @param	integer		$page		page
		* @param	mixed		$informant	informant
		* @return	nothing
		*/
		public static function performAction( &$context, $page, $informant = NULL )
		{
			// set the form manager class
			$class_form_manager = self::getFormManagerClass();

			// serialize the current form
			$class_form_manager::serialize( $context, $page, $informant );
		}

		/**
		* Route a visitor
		*
		* @param	array		$context	containing context parameters
		* @param	integer		$page		representing a page
		* @return	nothing
		*/
		public static function route(
			&$context = NULL,
			$page = PAGE_UNDEFINED
		)
		{
			// declare the router class name
			$class_router = self::getRouterClass();

			// get a route
			$class_router::getRoute( $context, $page );
		}
	
		/**
		* Set language
		* 
		* @param	string		$uri	containing a URI
		* @return	nothing
		*/
		public static function set_language($uri = null)
		{
			$class_i18n = CLASS_I18N;

			// check if the language request is set
			if (isset($_POST[POST_LANGUAGE]))
			{
				if (!isset($_SESSION[STORE_I18N]))
					$_SESSION[STORE_I18N] = array();
				
				if (!is_array($_SESSION[STORE_I18N]))
					$_SESSION[STORE_I18N] = array(SECTION_FRONT_END => LANGUAGE_CODE_ENGLISH);		
		
				$_SESSION[STORE_I18N][SECTION_FRONT_END] = $_POST[POST_LANGUAGE];
	
				// load the current language store
				$class_i18n::load_store();
			}
			else
	
				// load the current language store
				$class_i18n::load_store(I18N_STORE_FORM);
	
			if (!empty($uri))
	
				// refresh the current page
				self::jumpTo($uri);			
		}

		/**
		* Spawn a form view
		*
		* @param	string 	$affordance	form affordance
		* @param	array	$search		items to be found
		* @param	array	$replace	items used for replacement
		* @param	string	$block		block type
		* @param	boolean	$edition	edition flag
		* @return 	string	view
		*/
		public static function spawnFormView(
			$affordance,
			$search,
			$replace,
			$block = BLOCK_FORM,
			$edition = FALSE
		)
		{
			global $class_application;

			$class_dumper = $class_application::getDumperClass();

			$class_form_manager = $class_application::getFormManagerClass();

			$handler_id = self::fetchFormAbstract(
				$affordance,
				BLOCK_HTML,
				FALSE,
				$edition
			);

			$form = str_replace(
				$search,
				$replace,
				self::fetchView( PAGE_UNDEFINED, $handler_id, BLOCK_FORM, NULL )
			);

			$class_form_manager::destroyHandler($handler_id);

			return $form;
		}
	}

/// @cond DOC_SKIP

}

/// @endcond
