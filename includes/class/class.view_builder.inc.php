<?php

/**
* View builder class
*
* Class for view building
* @package  sefi
*/
class View_Builder extends User_Interface
{
    protected $_dom;
    protected $_i18n;
    protected $_inhabitants;
    protected $_stylesheet;
    static protected $blackboard;
    static protected $configuration;
    static protected $placeholder;
    static protected $properties;

    /**
    * Construct a View Builder
    *
    * @return  object   representing a View Builder instance
    */
    public function __construct()
    {
		global $class_application, $verbose_mode;

        // get the stylesheet member
        $_stylesheet = &$this->getStylesheet();

        // declare a empty stylesheet array
        $_stylesheet =
		
		// initialize the context of a test case
		$context = array();

        // set the dumper class name
		$class_dumper = $class_application::getDumperClass();

        // set the field handler class name
        $class_field_handler = $class_application::getFieldHandlerClass();

        // set the form manager class name
        $class_form_manager = $class_application::getFormManagerClass();

        // set the test case class name
        $class_test_case = $class_application::getTestCaseClass();

        // declare the default data submission status
        $data_submitted = TRUE;

		// get the current handler identifier
        $handler_id = $class_field_handler::get_active_handler();

        // get the persistent handler store
        $persistent_field_handler =
			$class_form_manager::getPersistentFieldHandler( $handler_id )
		;

		// get a persistent property
		$persistent_store = &$class_form_manager::getPersistentProperty(
			PROPERTY_STORE,
			$handler_id
		);

        // set a blackboard as a new instance of the standard class
        $blackboard = new stdClass();

		// set the blackboard check property
		$blackboard->{PROPERTY_CHECK} = &$persistent_field_handler->getStore(
			$persistent_field_handler->getPosition(
				COORDINATES_CURRENT_POSITION,
				$handler_id
			),
			$handler_id
		);

		// set the blackboard dashboard property
		$blackboard->{PROPERTY_DASHBOARD} =
			&$persistent_field_handler->getDashboard()
		;

		// set the blackboard store property
		$blackboard->{PROPERTY_STORE} = &$persistent_store;

        // get the configuration of the current field handler
        $configuration = $persistent_field_handler->get_config();

        // get errors of the current field handler
        $store =
			$persistent_field_handler->getControlDashboard(
				$handler_id
		);

		$class_test_case::perform(
			DEBUGGING_FIELD_ERROR_HANDLING,
			$verbose_mode && DEBUGGING_FIELD_ERROR_HANDLING,
			array(
				$blackboard,
				$persistent_field_handler,
				__METHOD__,
				__LINE__
			)
		);

		/**
		*
		* context for test case of revision 561
		*
		* Revise field links controller
		*
		*/

		$context = array_merge(
			$context,
			array(
				PROPERTY_DASHBOARD => $blackboard->{PROPERTY_DASHBOARD},
				PROPERTY_REPOSITORY => $blackboard->{PROPERTY_CHECK},
				PROPERTY_STORE => $store,
				PROPERTY_HANDLER => $handler_id
			)
		);

        // get field values of the current field handler
        $field_values =
			$class_form_manager::getPersistentProperty(
				PROPERTY_FIELD_VALUES,
				$handler_id,
				ENTITY_FORM_MANAGER
			)
		;

        // set the i18n context
        $this->_i18n = array( PROPERTY_NAMESPACE => LANGUAGE_PREFIX_FORM );

        // declare an array of inhabitants
        $this->_inhabitants = array();

		// set the DOM member
        $this->_dom[ENTITY_DOCUMENT] = new DOMDocument();

        // check the data submission status
        if (
            isset( $store[SESSION_STATUS] ) &&
            $store[SESSION_STATUS] == SESSION_STATUS_NO_SUBMISSION
        )

            // set the data submission status
            $data_submitted = FALSE;

		// check the context field parameter 
		if ( isset( $configuration[CONTEXT_FIELDS] ) )

			// get the current fields
			$fields = $configuration[CONTEXT_FIELDS];

		// set the blackboard
		self::setBlackboard( $blackboard );

		// set the configuration
		self::setConfiguration( $configuration );

        // check the data submission status
        if ( $data_submitted )

            // loop on items of the control dashboard 
            while ( list( $field_name, $element ) = each( $store ) )

                $class_dumper::log(
                    __METHOD__,
                    array(
                        'field name',
                        $field_name,
                        'element',
                        $element
                    )
                );

        // check the fields
        if ( isset( $fields ) && is_array( $fields ) && count( $fields ) )

            // loop on the fields
            while ( list( $field_index, $field ) = each( $fields ) )
            {
				self::settleOptions(
					$field,
					array( $configuration, $handler_id )
				);

                // set an inhabitant                        
                $this->_inhabitants[$field[HTML_ATTRIBUTE_NAME]] = $field;

                // check the value of the current field 
                if ( ! empty( $field_values[$field[HTML_ATTRIBUTE_NAME]] ) )

                    // set a field value
                    $this->_inhabitants
						[$field[HTML_ATTRIBUTE_NAME]]
							[HTML_ATTRIBUTE_VALUE] =
								$field_values
									[$field[HTML_ATTRIBUTE_NAME]]
					;
            }

		$class_test_case::perform(
			DEBUGGING_FIELD_HANDLING_LINK_FIELDS,
			$verbose_mode,
			$context
		);
    }

	/**
    * Display a view
    *
    * @param	mixed	$view		view
    * @param	mixed	$view_type	type of view 
    * @return   mixed
    */
	public static function display( $view, $view_type = NULL )
	{
		if ( ! is_null( $view_type ) )
		{
			switch ( $view_type )
			{
				case VIEW_TYPE_INJECTION:
	
					$callback_parameters = self::getView( $view, $view_type );
					
					list( , $view ) = each( $callback_parameters );

						break;
			}
		}

		// send headers
		header(
			'Content-Type: '.
			MIME_TYPE_TEXT_HTML.
			'; charset='.
			I18N_CHARSET_UTF8
		);

		echo $view;		
	}

	/**
    * Display a form
    *
    * @param	mixed	$context		context
    * @param	mixed	$entity_type	type of entity
    * @param	boolean	$edition		edition flag
    * @param	boolean	$standalone		standalone flag
    * @return   mixed
    */	
	public static function displayForm(
		$context,
		$entity_type,
		$edition = FALSE,
		$standalone = TRUE
	)
	{
		return self::displayView(
			$context,
			$entity_type,
			$edition,
			$standalone
		);
	}

	/**
    * Display a preview
    *
    * @param	mixed	$context		context
    * @param	mixed	$entity_type	type of entity
    * @param	boolean	$edition		edition flag
    * @return   mixed
    */	
	public static function displayPreview(
		$context,
		$entity_type,
		$edition = NULL
	)
	{
		$callback_parameters = self::getPreview(
			$context,
			$entity_type,
			$edition
		);

		list( $action, $preview ) = each( $callback_parameters );

		self::display( $preview );

		return array( PROPERTY_ACTION, $action );
	}

	/**
    * Display a view
    *
    * @param	mixed	$context		context
    * @param	mixed	$entity_type	type of entity
    * @param	boolean	$edition		edition flag
    * @param	boolean	$standalone		stand-alone flag
    * @return   mixed
    */
	public static function displayView(
		$context,
		$entity_type,
		$edition = FALSE,
		$standalone = TRUE
	)
	{
		$class_dumper = self::getDumperClass();

		$callback_parameters = self::getView(
			$context,
			$entity_type,
			$edition,
			$standalone
		);

		list( $action, $view ) = each( $callback_parameters );

		self::display( $view );

		return array( PROPERTY_ACTION, $action );
	}

    /**
    * Get the static blackboard
    *
    * @return   mixed
    */	
	public static function &getBlackboard()
	{
		return self::$blackboard;
	}

    /**
    * Get the DOM member 
    *
    * @return  object   DOMDocument
    */
    public function &getDOM()
    {
        // return the DOMDocument member 
        return $this->_dom;        
    }

    /**
    * Get the i18n member
    *
    * @return  mixed
    */
    public function &getI18n()
    {
        // return the i18n member
        return $this->_i18n;
    }

    /**
    * Get the static configuration
    *
    * @return   mixed
    */	
	public static function &getConfiguration()
	{
		return self::$configuration;
	}

    /**
    * Get a persistent property
    *
    * @param	mixed	$name	name
    * @return   nothing
    */
	public static function &getPersistentProperty( $name )
	{
		if (empty($_SESSION[STORE_SERIALIZATION]))

			$_SESSION[STORE_SERIALIZATION] = array();

		else if (
			! isset( $_SESSION[STORE_SERIALIZATION][CLASS_VIEW_BUILDER] ) ||
			! is_object(
				$_SESSION[STORE_SERIALIZATION][CLASS_VIEW_BUILDER]
			) ||
			get_class(
				$_SESSION[STORE_SERIALIZATION][CLASS_VIEW_BUILDER]
			) != CLASS_STANDARD_CLASS
			
		)

			$_SESSION[STORE_SERIALIZATION][CLASS_VIEW_BUILDER] = new stdClass();

		return $_SESSION[STORE_SERIALIZATION][CLASS_VIEW_BUILDER]->$name;
	}

    /**
    * Get the static placeholder
    *
    * @return   mixed
    */	
	public static function &getPlaceholder()
	{
		return self::$placeholder;
	}

    /**
    * Get a property
    *
    * @param	mixed	$name	name
    * @return   nothing
    */
	public static function &getProperty( $name )
	{
		$_persistent_property = self::getPersistentProperty($name);

		if (!is_object(self::$properties))

			self::$properties = new stdClass();

		if ($_persistent_property !== null && !isset(self::$properties->$name))

			self::$properties->$name = $_persistent_property;

		return self::$properties->$name;
	}

    /**
    * Get the stylesheet member
    *
    * @return  mixed
    */
    public function &getStylesheet()
    {
        // return the stylesheet member
        return $this->_stylesheet;
    }

    /**
    * Build a field view
    *
    * @param    string      $tag      			tag name
    * @param    string  	$resources			resources
    * @param	mixed		$static_parameters	static parameters
    * @return   object      DOMNode
    */
    public function buildDOMNode( $tag, $resources, $static_parameters = NULL )
    {
		global $class_application, $verbose_mode;

		$class_dumper = self::getDumperClass();

		$class_data_fetcher = self::getDataFetcherClass();

		$class_element_html = $class_application::getElementHtmlClass();

		$class_test_case = self::getTestCaseClass();

		$edition = NULL;

		$select_option =

		$static_mode = FALSE;

		// initialize the context of a test case 
		$context = array();

		if ( ! is_null( $static_parameters ) )
		{		
			$static_mode = TRUE;

			list(
				$form_identifier,
				$settings,
				$_i18n,
				$document,
				$elements
			) = $static_parameters;
			
			if ( isset( $elements[HTML_ELEMENT_INPUT] ) )

				$element = $elements[HTML_ELEMENT_INPUT];

			else if ( isset( $elements[HTML_ELEMENT_FORM] ) )
			
				$element = $elements[HTML_ELEMENT_FORM];

			else if ( isset( $elements[HTML_ELEMENT_OPTION] ) )

				$element = $elements[HTML_ELEMENT_OPTION];

			else if ( isset( $elements[HTML_ELEMENT_SELECT] ) )

				$element = $elements[HTML_ELEMENT_SELECT];

			else if ( isset( $elements[HTML_ELEMENT_TEXTAREA] ) )

				$element = $elements[HTML_ELEMENT_TEXTAREA];
				
			if ( isset( $elements[HTML_ELEMENT_DIV] ) )
			
				$element_div = $elements[HTML_ELEMENT_DIV];

			if ( isset( $elements[HTML_ELEMENT_LABEL] ) )
			
				$element_label = $elements[HTML_ELEMENT_LABEL];

			if ( isset( $elements[HTML_ELEMENT_SPAN] ) )
			
				$element_span = $elements[HTML_ELEMENT_SPAN];
		}

		// check if the field handler resource can be used as properties
        if ( isset( $resources[CONTEXT_INDEX_FIELD_HANDLER] ) )
        {
            // check the field handler context parameter            
            $properties = $resources[CONTEXT_INDEX_FIELD_HANDLER];

            // check the error context parameter
            if ( isset( $resources[CONTEXT_INDEX_ERRORS] ) )
            {
				// set the context resources property
				$context[PROPERTY_RESOURCES] = $resources;

                // get the error context parameter
                $errors = $resources[CONTEXT_INDEX_ERRORS];

                // declare an empty array
                $stylesheet = array();

                // loop on errors
                while ( list( $name, $error ) = each( $errors ) )

                    // set the current field stylesheet
                    $stylesheet[$name] = STYLE_CLASS_ERROR;

                // set the stylesheet
                self::setStylesheet( $stylesheet );
            }
        }
        else

            // set the resources as properties
            $properties = $resources;

		if ( ! $static_mode )
		{
			// get the check
			$_check = $this->readSigns( PROPERTY_CHECK );
	
			// get the configuration
			$_configuration = self::getConfiguration();
	
			// check the form identifier
			if ( ! empty( $_configuration[PROPERTY_FORM_IDENTIFIER] ) )
			
				// set the form identifier
				$prefix_affordance = $class_application::translate_entity(
					$_configuration[PROPERTY_FORM_IDENTIFIER],
					ENTITY_CSS_CLASS
				)."_";
	
			// get the dasboard signs
			$_dashboard = &$this->readSigns( PROPERTY_DASHBOARD );
	
			// get the DOM member
			$_dom = &$this->getDOM();
	
			// get the inhabitants member
			$_inhabitants = $this->getInhabitants();
	
			// get the i18n member
			$_i18n = &$this->getI18n();
	
			// get the store signs
			$_store = &$this->readSigns( PROPERTY_STORE );

			// get the member DOMDocument object
			$document = $_dom[ENTITY_DOCUMENT];
		}
		else
		{
			$_check =

			$_configuration =

			$_dashboard = 
			
			$_inhabitants =
			
			$_store = NULL;

			$prefix_affordance = $class_application::translate_entity(
				$form_identifier,
				ENTITY_CSS_CLASS
			)."_";			
		}

		// Read the HTML elements from the blackboard
		$html_elements = &self::readSigns( PROPERTY_HTML_ELEMENTS );

		// Read the instances properties from the blackboard
		$instances_properties = &self::readSigns( PROPERTY_ELEMENTS_PROPERTIES );

		if ( is_object( $html_elements ) )
		
			$class_dumper::trace( TRUE );

        // Set a default array of attributes
        $attributes =

		// Declare an empty collection of HTML elements of type input
		$html_elements[HTML_ELEMENT_INPUT] = 

		// Declare the default empty collection of properties
		$instances_properties[HTML_ELEMENT_INPUT] = array();

		// check if the edition flag is set
		if ( isset( $_configuration[PROPERTY_EDITION] ) )
		
			$edition = $_configuration[PROPERTY_EDITION];

        // declare the default properties validity flag
        $valid_properties = TRUE;

        // switch from the tag argument
        switch ( $tag )
        {
            case HTML_ELEMENT_FORM:

                // check the properties
                if (
					! $static_mode &&
					(
						! is_object( $properties ) ||
						get_class( $properties ) != CLASS_FIELD_HANDLER
					)
				)

                    // toggle the properties validity flag
                    $valid_properties = FALSE;

                    break;

            case HTML_ELEMENT_INPUT:
            case HTML_ELEMENT_OPTION:
            case HTML_ELEMENT_SELECT:
            case HTML_ELEMENT_TEXTAREA:

                // check the properties
                if (
					! $static_mode &&
					(
						! is_array( $properties ) ||
						! count( $properties )
					)
				)

                    // toggle the properties validity flag
                    $valid_properties = FALSE;

                // list the properties of an input element
                list( $field_type, $field_name ) = $properties;

				// get the link suffix length
				$suffix_start = strpos( $field_name, SUFFIX_LINK );

				$suffix_length = strlen( SUFFIX_LINK );
		
				// check if the current field is linked to another field
				if ( $suffix_start === FALSE )
		
					$trimmed_field_name = $field_name;
				else
		
					$trimmed_field_name = substr(
						$field_name,
						0,
						strlen( $field_name ) - $suffix_length
					);

 				$link_broken = FALSE;

				// check the field option property				
				if ( isset( $properties[2] ) )

					// set a field option
					$field_option = $properties[2];

				// Select option flag 
				$select_option = $tag === HTML_ELEMENT_OPTION;

                    break;
        }

        // check the properties validity flag
        if ( ! $valid_properties )

            // throw an exception
            throw new Exception( EXCEPTION_INVALID_ARGUMENT ); 

        switch ( $tag )
        {
            case HTML_ELEMENT_FORM:

                // set attributes
                $attributes = array(
                    HTML_ATTRIBUTE_ACCEPT_CHARSET,
                    HTML_ATTRIBUTE_ACTION,
                    HTML_ATTRIBUTE_ENCTYPE,
                    HTML_ATTRIBUTE_METHOD,
                );

            case HTML_ELEMENT_INPUT:
            case HTML_ELEMENT_OPTION:
            case HTML_ELEMENT_SELECT:
            case HTML_ELEMENT_TEXTAREA:

                // set attributes
                $attributes = array_merge(
                    $attributes,
                    array(
						HTML_ATTRIBUTE_ACCESSKEY,
                        HTML_ATTRIBUTE_CLASS,
                        HTML_ATTRIBUTE_COLS,
                        HTML_ATTRIBUTE_DISABLED,
                        HTML_ATTRIBUTE_ID,
                        HTML_ATTRIBUTE_FOR,
                        HTML_ATTRIBUTE_NAME,
                        HTML_ATTRIBUTE_READ_ONLY,
                        HTML_ATTRIBUTE_ROWS,
						HTML_ATTRIBUTE_TABINDEX,
                        HTML_ATTRIBUTE_TITLE
                    )
                );
				
				if (
					in_array(
						$tag,
						array(
							HTML_ELEMENT_INPUT,
							HTML_ELEMENT_OPTION,
							HTML_ELEMENT_SELECT
						)
					)
				)
				{
					/**
					*
					* Select elements have no value nor type attribute
					*
					*/
					if ( $tag !== HTML_ELEMENT_SELECT )

						$attributes = array_merge(
							$attributes,
							array( HTML_ATTRIBUTE_VALUE, HTML_ATTRIBUTE_TYPE )
						);

					// unset some attributes for input, option and select elements
					unset(
						// remove the cols attribute
						$attributes[2],

						// remove the for attribute						  
						$attributes[5],

						// remove the rows attribute						  
						$attributes[8],

						// remove the title attribute
						$attributes[10]
					);

					if ( $select_option )
					{
						// remove the name attribute for option elements
						unset( $attributes[6] );

						// remove the tabindex attribute for option elements
						unset( $attributes[9] ); 
 
						end( $attributes );
						list( $last_attribute_key, ) = each( $attributes );
						reset( $attributes );
 
						// remove the type attribute for option elements
						unset( $attributes[$last_attribute_key] );
					}
				}

				if ( ! $static_mode )

					switch ( $tag )
					{
						case HTML_ELEMENT_INPUT:
						case HTML_ELEMENT_OPTION:
						case HTML_ELEMENT_SELECT:
						case HTML_ELEMENT_TEXTAREA:
	
							if (
								( $field_type != FIELD_TYPE_HIDDEN ) &&
								! $select_option
							)
							
								// set an array of elements
								$elements = array(
									HTML_ELEMENT_DIV => array(
										CLASS_DOM_ELEMENT =>
											$document->createElement(
												HTML_ELEMENT_DIV
											)
									),
									HTML_ELEMENT_LABEL => array(
										CLASS_DOM_ELEMENT =>
											$document->createElement(
												HTML_ELEMENT_LABEL
											)
									),
									HTML_ELEMENT_SPAN => array(
										CLASS_DOM_ELEMENT =>
											$document->createElement(
												HTML_ELEMENT_SPAN
											)
									),

									/**
									*
									* Create element of type
									* input
									* select
									* textarea
									* 
									*/

									$tag  => array(
										CLASS_DOM_ELEMENT =>
											$document->createElement( $tag )
									)
								);

							else if ( $select_option)

								/**
								*
								* Create element of type
								* option
								* 
								*/

								$elements = array(
									HTML_ELEMENT_OPTION => array(
										CLASS_DOM_ELEMENT =>
											$document->createElement(
												HTML_ELEMENT_OPTION
											)
									)
								);
							else 

								$elements = array(
									HTML_ELEMENT_INPUT => array(
										CLASS_DOM_ELEMENT =>
											$document->createElement(
												HTML_ELEMENT_INPUT
											)
									)
								);

							if ( $select_option )
		
								$class_dumper::log(
									__METHOD__,
									array(
										forEachItem(
											$elements,
											function ( $item )
											{
												list( , $dom_element ) = each( $item );
												
												if (
													isset(
														$dom_element
															->{PROPERTY_DOM_ELEMENT_TAG_NAME}
													)
												)
				
													$callback_parameters =
														$dom_element
															->{PROPERTY_DOM_ELEMENT_TAG_NAME};
												else 
			
													$callback_parameters = $dom_element;
													
												return $callback_parameters;
											}
										)
									),
									DEBUGGING_DISPLAY_SELECT_OPTIONS
								);

								break;
	
						case HTML_ELEMENT_FORM:
	
							// set an array of elements
							$elements = array(
								HTML_ELEMENT_FORM => array(
									CLASS_DOM_ELEMENT =>
										$document->createElement(
											HTML_ELEMENT_FORM
										)
								)
							);
	
								break;
					}
				else
				{
					$elements = array(
						$tag => array(
							CLASS_DOM_ELEMENT =>
								$element
						)
					);

					if ( isset( $element_div ) )
					
						$elements[HTML_ELEMENT_DIV] = array(
							CLASS_DOM_ELEMENT =>
								$element_div
						);

					if ( isset( $element_label) )
					
						$elements[HTML_ELEMENT_LABEL] = array(
							CLASS_DOM_ELEMENT =>
								$element_label
						);

					if ( isset( $element_span ) )
					
						$elements[HTML_ELEMENT_SPAN] = array(
							CLASS_DOM_ELEMENT =>
								$element_span
						);
				}

                // loop on elements
                while (
					list( $element_type, $element_properties ) =
						each( $elements )
				)
                {
                    // loop on attributes
                    while (
						list( $attribute_id, $attribute ) =
							each( $attributes )
					)
                    {
                        // switch from the current attribute
                        switch ( $attribute )
                        {
                            case HTML_ATTRIBUTE_ACCEPT_CHARSET:
                            case HTML_ATTRIBUTE_ACTION:

                                // switch from the DOM element property
                                switch ( $element_type )
                                {
                                    case HTML_ELEMENT_FORM:

                                        // set the class attribute of an element
                                        $this->setAttribute(
                                            $element_properties[CLASS_DOM_ELEMENT],
                                            $element_type,
                                            $attribute,
                                            $properties,
											$static_parameters
                                        );

                                            break; 
                                }

                                    break;

                            case HTML_ATTRIBUTE_ACCESSKEY:

                                // switch from the DOM element property
                                switch ( $element_type )
                                {
                                    case HTML_ELEMENT_INPUT:
                                    case HTML_ELEMENT_OPTION:
                                    case HTML_ELEMENT_SELECT:

                                        // set the class attribute of an element
                                        $this->setAttribute(
                                            $element_properties[CLASS_DOM_ELEMENT],
                                            $element_type,
                                            $attribute,
                                            $properties,
											$static_parameters
                                        );

                                            break; 
                                }

                                    break;

                            case HTML_ATTRIBUTE_CLASS:

                                // switch from the DOM element property
                                switch ( $element_type )
                                {
                                    case HTML_ELEMENT_DIV:
                                    case HTML_ELEMENT_FORM:
                                    case HTML_ELEMENT_INPUT:
                                    case HTML_ELEMENT_LABEL:
                                    case HTML_ELEMENT_OPTION:
                                    case HTML_ELEMENT_SELECT:
                                    case HTML_ELEMENT_SPAN:

                                        // set the class attribute of an element
                                        $this->setAttribute(
                                            $element_properties[CLASS_DOM_ELEMENT],
                                            $element_type,
                                            $attribute,
                                            $properties,
											$static_parameters
                                        );

                                            break; 
                                }

                                    break;

                            case HTML_ATTRIBUTE_COLS:

                                // switch from the DOM element property
                                switch ( $element_type )
                                {
                                    case HTML_ELEMENT_TEXTAREA:

                                        // set the read only attribute of an element
                                        $this->setAttribute(
                                            $element_properties[CLASS_DOM_ELEMENT],
                                            $element_type,
                                            $attribute,
                                            $properties,
											$static_parameters
                                        );

                                            break;                                       
                                }

                                    break;

                            case HTML_ATTRIBUTE_DISABLED:

                                // switch from the DOM element property
                                switch ( $element_type )
                                {
                                    case HTML_ELEMENT_INPUT:
                                    case HTML_ELEMENT_OPTION:
                                    case HTML_ELEMENT_SELECT:

                                        // set the disabled attribute of an input element
                                        $this->setAttribute(
                                            $element_properties[CLASS_DOM_ELEMENT],
                                            $element_type,
                                            $attribute,
                                            $properties,
											$static_parameters
                                        );

                                            break;                                       
                                }

                                    break;

                            case HTML_ATTRIBUTE_ENCTYPE:

                                // switch from the DOM element property
                                switch ( $element_type ) 
                                {
                                    case HTML_ELEMENT_FORM:

                                        // set the disabled attribute of an input element
                                        $this->setAttribute(
                                            $element_properties[CLASS_DOM_ELEMENT],
                                            $element_type,
                                            $attribute,                                                
                                            $properties,
											$static_parameters
                                        );

                                            break;                                       
                                }

                                    break;
                                
                            case HTML_ATTRIBUTE_FOR:

                                // switch from the DOM element property
                                switch ( $element_type )
                                {
                                    case HTML_ELEMENT_LABEL:

                                        // set the for attribute of an input element
                                        $this->setAttribute(
                                            $element_properties[CLASS_DOM_ELEMENT],
                                            $element_type,
                                            $attribute,
                                            $properties,
											$static_parameters
                                        );

                                            break;                                       
                                }

                                    break;

                            case HTML_ATTRIBUTE_ID:

                                // switch from the DOM element property
                                switch ( $element_type )
                                {
                                    case HTML_ELEMENT_DIV:
                                    case HTML_ELEMENT_FORM:
                                    case HTML_ELEMENT_INPUT:
                                    case HTML_ELEMENT_LABEL:
                                    case HTML_ELEMENT_OPTION:
                                    case HTML_ELEMENT_SPAN:
                                    case HTML_ELEMENT_SELECT:
                                    case HTML_ELEMENT_TEXTAREA:

                                        // set the id attribute of an element
                                        $this->setAttribute(
                                            $element_properties[CLASS_DOM_ELEMENT],
                                            $element_type,
                                            $attribute,
                                            $properties,
											$static_parameters
                                        );

                                            break;           
                                }

                                    break;

                            case HTML_ATTRIBUTE_METHOD:

                                // switch from the DOM element property
                                switch ( $element_type )
                                {
                                    case HTML_ELEMENT_FORM:

                                        // set the for attribute of an input element
                                        $this->setAttribute(
                                            $element_properties[CLASS_DOM_ELEMENT],
                                            $element_type,
                                            $attribute,
                                            $properties,
											$static_parameters
                                        );

                                            break;                                       
                                }

                            case HTML_ATTRIBUTE_NAME:

                                // switch from the DOM element property
                                switch ( $element_type )
                                {
                                    case HTML_ELEMENT_INPUT:
                                    case HTML_ELEMENT_SELECT:
                                    case HTML_ELEMENT_TEXTAREA:

                                        // set the read only attribute of an element
                                        $this->setAttribute(
                                            $element_properties[CLASS_DOM_ELEMENT],
                                            $element_type,
                                            $attribute,
                                            $properties,
											$static_parameters
                                        );

                                            break;                                       
                                }

                                    break;

                            case HTML_ATTRIBUTE_READ_ONLY:
                            case HTML_ATTRIBUTE_TABINDEX:
                            case HTML_ATTRIBUTE_TYPE:

								/**
								*
								* The type attribute is available
								* for input elements
								*
								*/
								if (
									( $attribute !== HTML_ATTRIBUTE_TYPE ) ||
									( $element_type === HTML_ELEMENT_INPUT )
								)
	
									// switch from the DOM element property
									switch ( $element_type )
									{
										case HTML_ELEMENT_INPUT:
										case HTML_ELEMENT_OPTION:
										case HTML_ELEMENT_SELECT:
										case HTML_ELEMENT_TEXTAREA:
	
											// set the read only attribute of an element
											$this->setAttribute(
												$element_properties[CLASS_DOM_ELEMENT],
												$element_type,
												$attribute,
												$properties,
												$static_parameters
											);

												break;
									}

                                    break;

                            case HTML_ATTRIBUTE_ROWS:

                                // switch from the DOM element property
                                switch ( $element_type )
                                {
                                    case HTML_ELEMENT_TEXTAREA:

                                        // set the read only attribute of an element
                                        $this->setAttribute(
                                            $element_properties[CLASS_DOM_ELEMENT],
                                            $element_type,
                                            $attribute,
                                            $properties,
											$static_parameters
                                        );

                                            break;                                       
                                }

                                    break;

                            case HTML_ATTRIBUTE_TITLE:

                                // switch from the DOM element property
                                switch ( $element_type )
                                {
                                    case HTML_ELEMENT_LABEL:

                                        // set the title attribute of an element
                                        $this->setAttribute(
                                            $element_properties[CLASS_DOM_ELEMENT],
                                            $element_type,
                                            $attribute,
                                            $properties,
											$static_parameters
                                        );

                                            break;                                       
                                }

                                    break;

                            case HTML_ATTRIBUTE_VALUE:

                                // switch from the DOM element property
                                switch ( $element_type )
                                {						
                                    case HTML_ELEMENT_INPUT:
                                    case HTML_ELEMENT_OPTION:

                                        // set the value attribute of an element
                                        $this->setAttribute(
                                            $element_properties[CLASS_DOM_ELEMENT],
                                            $element_type,
                                            $attribute,
                                            $properties,
											$static_parameters
                                        );

                                            break;                                       
                                }

                                    break;
                        }
                    }
                    
                    reset( $attributes );
                }
                
                reset( $elements );

				if ( ! $static_mode )

					// switch from the tag argument
					switch ( $tag )
					{
						case HTML_ELEMENT_INPUT:
						case HTML_ELEMENT_OPTION:
						case HTML_ELEMENT_SELECT:
						case HTML_ELEMENT_TEXTAREA:
	
							// check the i18n member
							if (
								$field_type != FIELD_TYPE_HIDDEN &&
								defined(
									strtoupper(
										$_i18n
											[PROPERTY_NAMESPACE].
												PREFIX_LABEL.
													$prefix_affordance.
														$field_name
									)
								) ||
								isset( $field_option ) &&
								
								/**
								*
								* i18n inhabitants are defined within the
								* body of the View_Builder::settleOptions method 
								*
								*/
								isset(
									$_inhabitants
										[$field_name]
											[AFFORDANCE_PROVIDE_WITH_OPTIONS]
								) &&
								is_array(
									$_inhabitants
										[$field_name]
											[AFFORDANCE_PROVIDE_WITH_OPTIONS]
								) &&
								isset(
									$_inhabitants
										[$field_name]
											[AFFORDANCE_PROVIDE_WITH_OPTIONS]
												[$field_option]
								)
							)
							{
								// check the field type
								if (
									isset(
										$_inhabitants
											[$field_name]
												[HTML_ATTRIBUTE_TYPE]
									)
								)
	
									// get requirements concerning field type
									// from mandatory suffix appendance
									$required = substr(
										$_inhabitants
											[$field_name]
												[HTML_ATTRIBUTE_TYPE],
											-1,
											1
										) == SUFFIX_MANDATORY
									;
								else
	
									// throw a new exception
									throw new Exception(
										EXCEPTION_UNDEDINED_FIELD_TYPES
									);
	
								// check if the current field is required
								if (
									$required &&
									$tag !== HTML_ELEMENT_OPTION
								)
								{
									$span_element = $document->createElement(
										HTML_ELEMENT_SPAN
									);
	
									$span_element->setAttribute(
										HTML_ATTRIBUTE_CLASS,
										STYLE_CLASS_MANDATORY
									);
								
									// construct a new DOMText object
									$span_node = new DOMText();
									
									// append the mandatory suffix to
									// the DOMText object
									$span_node->appendData( SUFFIX_MANDATORY );
									
									// append the DOMText object to
									// the span DOMelement
									$span_element->appendChild( $span_node );
								}
	
								$span_label_element = $document->createElement(
									HTML_ELEMENT_SPAN
								);
	
								$span_label_element->setAttribute(
									HTML_ATTRIBUTE_CLASS,
									STYLE_CLASS_LABEL
								);
							
								// construct a new DOMText object
								$dom_text_node = new DOMText();

								/**
								*
								* Each radio / checkbox option
								* has its own label
								* 
								* Though select elements have options,
								* they are bound to a unique label element
								*
								*/

								if (
									! isset( $field_option ) ||
									$tag === HTML_ELEMENT_SELECT 									
								)
	
									// append the field value
									// to the DOMText object
									$dom_text_node->appendData(
										constant(
											strtoupper(
												$_i18n[PROPERTY_NAMESPACE].
													PREFIX_LABEL.
														$prefix_affordance.
															$field_name
											)
										)
									);
								else
								{
									$language_item_option =
										$_inhabitants
											[$field_name]
												[AFFORDANCE_PROVIDE_WITH_OPTIONS]
													[$field_option]
									;

									if ( defined( $language_item_option ) )

										$option_text =
											constant( $language_item_option )
										;
									else
									
										$option_text = $language_item_option;

									// Append a field value
									// to a DOMText object
									$dom_text_node->appendData(
										$option_text
									);

									if ( $select_option )

										// append the option text node
										// to an option DOMElement
										$elements
											[HTML_ELEMENT_OPTION]
												[CLASS_DOM_ELEMENT]->appendChild(
												$dom_text_node
											)
										;
								}
								
								/**
								*
								* Elements different from select and submit inputs 
								* are bound to a label element
								* embedding a span element with a text node
								*
								*/
								if (
									! $select_option &&
									isset(
										$_inhabitants
											[$field_name]
												[HTML_ATTRIBUTE_TYPE]
									) &&
									$_inhabitants
										[$field_name]
											[HTML_ATTRIBUTE_TYPE] != FIELD_TYPE_SUBMIT									
								)

									// append the DOMText object
									// to the span DOMelement
									$span_label_element->appendChild(
										$dom_text_node
									);
							}

							// check the label node
							if (
								isset( $elements[HTML_ELEMENT_LABEL] ) &&
								isset( $span_label_element ) &&
								is_object( $span_label_element ) &&
								(
									get_class( $span_label_element ) ==
										CLASS_DOM_ELEMENT
								)
							)
	
								// append the label node
								// to the label tag
								$elements
									[HTML_ELEMENT_LABEL]
										[CLASS_DOM_ELEMENT]->appendChild(
											$span_label_element
										)
								;
	
							// check if the current field is required
							if (
								isset( $required ) &&
								$required === TRUE &&
								isset( $span_element ) &&
								is_object( $span_element ) &&
								(
									get_class( $span_element ) ==
										CLASS_DOM_ELEMENT
								)
							)
	
								// append a span element to the label element
								$elements
									[HTML_ELEMENT_LABEL]
										[CLASS_DOM_ELEMENT]
											->appendChild( $span_element )
								;

							if (
								isset( $_store ) &&
								isset( $_dashboard )
							)

								$class_test_case::perform(
									DEBUGGING_FIELD_ERROR_HANDLING,
									$verbose_mode && DEBUGGING_FIELD_ERROR_HANDLING,
									array(
										ENTITY_CHECK => $_check,	
										ENTITY_STORE => $_store,	
										ENTITY_DASHBOARD => $_dashboard,
										PROPERTY_NAME => $field_name
									)
								);

							if ( isset( $_POST ) ) 

								/**
								*
								* context for test case of revision 561
								*
								* Revise field links controller
								*
								*/
						
								$context = array_merge(
									$context,
									array(
										PROPERTY_NAME => $field_name,
										PROPERTY_CONDITION_FIELD_VALUE_ERROR =>
											isset( $_store ) && is_array( $_store ) &&
											! empty( $_store[SESSION_STATUS] ) &&
								
											(
												(
													$_store[SESSION_STATUS] ==
														SESSION_STATUS_DATA_SUBMITTED
												) ||
												count(
													$_check
														[SESSION_STORE_FIELD]
															[SESSION_STORE_HALF_LIVING]
												)
											) &&
											
											isset( $_dashboard ) && is_array( $_dashboard ) &&
											! empty( $_dashboard[$field_name] ) &&
											is_array( $_dashboard[$field_name] ) &&
											count( $_dashboard[$field_name] ) != 0 &&
											(
												! empty(
													$_dashboard
														[$field_name]
															[ERROR_FIELD_MISSING]
												) ||
												! empty(
													$_dashboard
														[$field_name]
															[ERROR_ALREADY_TAKEN]
												) ||
												! empty(
													$_dashboard
														[$field_name]
															[ERROR_WRONG_VALUE]
												)
											)
									)
								);

							// check if an already taken error can be detected
							if (
								isset( $_store ) && is_array( $_store ) &&
								! empty( $_store[SESSION_STATUS] ) &&

								(
									(
										$_store[SESSION_STATUS] ==
											SESSION_STATUS_DATA_SUBMITTED
									) ||
									count(
										$_check
											[SESSION_STORE_FIELD]
												[SESSION_STORE_HALF_LIVING]
									)
								) &&
								
								isset( $_dashboard ) && is_array( $_dashboard ) &&
								! empty( $_dashboard[$field_name] ) &&
								is_array( $_dashboard[$field_name] ) &&
								count( $_dashboard[$field_name] ) != 0 &&
								(
									! empty(
										$_dashboard
											[$field_name]
												[ERROR_FIELD_MISSING]
									) ||
									! empty(
										$_dashboard
											[$field_name]
												[ERROR_ALREADY_TAKEN]
									) ||
									! empty(
										$_dashboard
											[$field_name]
												[ERROR_WRONG_VALUE]
									)
								)
							)
							{
								// create a new p element
								$p_element = $document->createElement(
									HTML_ELEMENT_P
								);
	
								// construct a new DOMText object
								$p_node = new DOMText();

								/**
								*
								* context for test case of revision 561
								*
								* Revise field links controller
								*
								*/

								if ( isset( $_POST ) )
								
									$context = array_merge(
										$context,
										array(
											PROPERTY_NAME => $field_name,
											PROPERTY_CONDITION_FIELD_VALUE_STORED,
											! empty(
												$_dashboard
													[$field_name]
														[ERROR_FIELD_MISSING]
											) &&
											empty(
												$_check
													[SESSION_STORE_FIELD]
														[SESSION_STORE_VALUE]
															[$field_name]
											) &&
											empty(
												$_check
													[SESSION_STORE_FIELD]
														[SESSION_STORE_HALF_LIVING]
															[$field_name]
											),
											PROPERTY_CONDITION_FIELD_VALUE_CONFIRMED =>
												$trimmed_field_name,
												$field_name,
												$_check
													[SESSION_STORE_FIELD]
														[SESSION_STORE_HALF_LIVING]
															[$field_name],
												$_check
													[SESSION_STORE_FIELD]
														[SESSION_STORE_HALF_LIVING]
															[$trimmed_field_name],
											PROPERTY_CONDITION_FIELD_VALUE_MISSING => 
												! empty(
													$_dashboard
														[$field_name]
															[ERROR_FIELD_MISSING]
												)											
										)
									);
	
								// check the already taken error flag							
								if (
									! empty(
										$_dashboard
											[$field_name]
												[ERROR_ALREADY_TAKEN]
									)
								)
	
									// append an error message
									// to the DOMText object
									$p_node->appendData(
										$_dashboard
											[$field_name]
												[ERROR_ALREADY_TAKEN]
									);
	
								// check the wrong value error flag
								else if (
									! empty(
										$_dashboard
											[$field_name]
												[ERROR_WRONG_VALUE]
									)
								)
	
									// append an error message
									// to the DOMText object
									$p_node->appendData(
										$_dashboard
											[$field_name]
												[ERROR_WRONG_VALUE]
									);
	
								// check the required field error flag
								else if (
									! empty(
										$_dashboard
											[$field_name]
												[ERROR_FIELD_MISSING]
									) &&
									empty(
										$_check
											[SESSION_STORE_FIELD]
												[SESSION_STORE_VALUE]
													[$field_name]
									) &&
									empty(
										$_check
											[SESSION_STORE_FIELD]
												[SESSION_STORE_HALF_LIVING]
													[$field_name]
									) ||
									(
										( $link_broken = ( $field_name != $trimmed_field_name ) ) &&
										(
											$_check
													[SESSION_STORE_FIELD]
														[SESSION_STORE_HALF_LIVING]
															[$field_name] !==
											$_check
													[SESSION_STORE_FIELD]
														[SESSION_STORE_HALF_LIVING]
															[$trimmed_field_name]
										)
									)
								)
								{
									if (
										! empty(
											$_dashboard
												[$field_name]
													[ERROR_WRONG_CONFIRMATION]
											)
										)
	
										// append an error message
										// to the DOMText object
										$p_node->appendData(
											$_dashboard
												[$field_name]
													[ERROR_WRONG_CONFIRMATION]
										);	
									else
	
										// append an error message
										// to the DOMText object
										$p_node->appendData(
											$_dashboard
												[$field_name]
													[ERROR_FIELD_MISSING]
										);
								}
	
								// append the DOMText object
								// to the p DOMelement
								$p_element->appendChild( $p_node );


								// Append the broken link property
								// to a test case context

								$context[PROPERTY_LINK] = 
									$link_broken ? 'TRUE' : 'FALSE'
								;	

								if (
									! empty(
										$_check
											[SESSION_STORE_FIELD]
												[SESSION_STORE_HALF_LIVING]
													[$field_name]
									) && ! $link_broken
								)
								{
									// delete the half living value
									// of the current field
									unset(
										$_check
											[SESSION_STORE_FIELD]
												[SESSION_STORE_HALF_LIVING]
													[$field_name]
									);
	
									if (
										empty(
											$_dashboard
												[$field_name]
													[ERROR_WRONG_VALUE]
										) &&
										empty(
											$_dashboard
												[$field_name]
													[ERROR_ALREADY_TAKEN]
										)
									)
	
										// unset the p element
										unset( $p_element );
								}
	
								// dismiss the already taken error
								// for the current field
								unset(
									$_dashboard
										[$field_name]
											[ERROR_ALREADY_TAKEN]
								);
							}
	
							if ( $tag === HTML_ELEMENT_TEXTAREA )
							{
								// construct a new DOMText object
								$default_value = new DOMText();
		
								$field_lookup = function ( &$value, $index )
								{
									if (
										is_array($value) &&
										count($value) &&
										isset($value[PROPERTY_NAME])
									)
							
										$value = $value[PROPERTY_NAME];
								};
	
								if ( ! is_null( $edition ) && $edition )
								{
									$fields = $_configuration[PROPERTY_FIELDS];
	
									array_walk( $fields, $field_lookup );
	
									$current_key = array_search(
										$field_name,
										$fields
									);
		
									if (
										isset(
											$_configuration
												[PROPERTY_FIELDS]
													[$current_key]
										)
									)
									{					
										$field_properties =
											$_configuration
												[PROPERTY_FIELDS]
													[$current_key]
										;
	
										if (
											isset(
												$field_properties[PROPERTY_TEXT]
											)
										)
	
											$default_value->appendData(
												$field_properties[PROPERTY_TEXT]
											);
									}
	
									$edition_mode_type_preview =
										$class_data_fetcher::getEntityTypeValue(
											array(
												PROPERTY_NAME => PROPERTY_PREVIEW,
												PROPERTY_ENTITY => ENTITY_EDITION_MODE
											)
										)
									;
	
									if ( $edition === $edition_mode_type_preview )
	
										$elements
											[$tag]
												[CLASS_DOM_ELEMENT]
													->setAttribute(
														HTML_ATTRIBUTE_READ_ONLY,
														HTML_ATTRIBUTE_READ_ONLY
													)
										;
								}
								else 
	
									// append an error message to
									// the DOMText object
									$default_value->appendData(
										FORM_DEFAULT_VALUE_TEXTAREA
									);
	
								// set the default textarea value
								$elements
									[HTML_ELEMENT_TEXTAREA]
										[CLASS_DOM_ELEMENT]
											->appendChild( $default_value );
							}

							if (
								( $field_type != FIELD_TYPE_HIDDEN ) &&
								! $select_option 
							)
							{
								$element_field =
									$elements[$tag][CLASS_DOM_ELEMENT]
								;

								// append an input or textarea element to
								// a span element
								$elements
									[HTML_ELEMENT_SPAN]
										[CLASS_DOM_ELEMENT]
											->appendChild( $element_field )
								;

								// append a label to the div element
								// used as tags carrier 
								$elements
									[HTML_ELEMENT_DIV]
										[CLASS_DOM_ELEMENT]
											->appendChild(
												$elements
													[HTML_ELEMENT_LABEL]
														[CLASS_DOM_ELEMENT]
											)
								;

								$dom_element_span =
									$elements
										[HTML_ELEMENT_SPAN]
											[CLASS_DOM_ELEMENT]
								;

								if (
									! isset(
										$instances_properties[HTML_ELEMENT_SPAN]
									)
								)
								{
									// Initialize a collection of HTML elements
									// of type span
									$html_elements[HTML_ELEMENT_SPAN] =
								
									$instances_properties[HTML_ELEMENT_SPAN] =
										array()
									;
								}

								if ( isset( $field_option ) )
								{
									if (
										! isset(
											$instances_properties
												[HTML_ELEMENT_SPAN]
													[$field_name]
										)
									)

										$instances_properties
											[HTML_ELEMENT_SPAN]
												[$field_name] = array()
										;

									if (
										! isset(
											$html_elements
												[HTML_ELEMENT_SPAN]
													[$field_name]
										)
									)

										$html_elements
											[HTML_ELEMENT_SPAN]
												[$field_name] = array()
										;

									// Initialize the HTML elements of type span
									// for current field option and name
									$html_elements[HTML_ELEMENT_SPAN]
										[$field_name][$field_option]
										= NULL
									;
									
									$html_element_span =
										&$html_elements[HTML_ELEMENT_SPAN]
											[$field_name]
												[$field_option]
									;

									// Initialize instances properties 
									// for current field option and name
									$instances_properties[HTML_ELEMENT_SPAN]
										[$field_name][$field_option]
										= NULL
									;
									
									$html_element_properties =
										&$instances_properties
											[HTML_ELEMENT_SPAN]
												[$field_name]
													[$field_option]
									;
								}
								else
								{
									$html_elements[HTML_ELEMENT_SPAN]
										[$field_name] = NULL
									;

									$html_element_span =
										&$html_elements[HTML_ELEMENT_SPAN]
											[$field_name]
									;

									$instances_properties[HTML_ELEMENT_SPAN]
										[$field_name] = NULL
									;

									$html_element_properties =
										&$instances_properties[HTML_ELEMENT_SPAN]
										[$field_name]
									;
								}

								// Declare the properties to be passed
								// to the constructor of the Element_Html class
								$html_element_properties = array(
										PROPERTY_DOM_DOCUMENT => $document,
										PROPERTY_DOM_ELEMENT =>
											$dom_element_span
									)
								;

								$html_element_span =
									new $class_element_html(
										$html_element_properties
									)
								;

								// wrap the current Html element
								$html_element_span->wrap();

								$elements
									[HTML_ELEMENT_DIV]
										[CLASS_DOM_ELEMENT]
											->appendChild(
												$html_element_span
													->{PROPERTY_WRAPPERS}
														[PROPERTY_WRAPPER]
											)
								;
		
								if ( isset( $p_element ) )
		
									$elements
										[HTML_ELEMENT_DIV]
											[CLASS_DOM_ELEMENT]
												->appendChild( $p_element )
									;
		
								$document->appendChild(
									$elements
										[HTML_ELEMENT_DIV]
											[CLASS_DOM_ELEMENT]
								);
	
								$element =
									&$elements
										[HTML_ELEMENT_DIV]
											[CLASS_DOM_ELEMENT]
								;
							}
							else

								$element =
									&$elements
										[$tag]
											[CLASS_DOM_ELEMENT]
								;
	
								break;
	
						case HTML_ELEMENT_FORM:
	
							$document->appendChild(
								$elements[HTML_ELEMENT_FORM]
									[CLASS_DOM_ELEMENT]
							);
	
							$element =
								&$elements
									[HTML_ELEMENT_FORM]
										[CLASS_DOM_ELEMENT]
							;
	
								break;                            
					}
        }

		$class_test_case::perform(
			DEBUGGING_FIELD_HANDLING_LINK_FIELDS,
			$verbose_mode,
			$context
		);

        return $element;
    }

	/**
	* Extract attributes from a collection of settings
	*
	* @param	array	$setting		setting
	* @param	array	&$attributes	attributes
	* @param	integer	$index			attributes index
	* @param	string	$context		context
	* @return	mixed	DOM element type 
	*/
	public static function extractAttributes(
		$setting,
		&$attributes,
		$index,
		$context
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$prefix_language_item = self::getLanguageItemPrefix( $context );

		// initializing the attributes for the current element
		if ( ! isset( $attributes[$index] ) )
		
			$attributes[$index] = array();

		$attributes[$index][HTML_ATTRIBUTE_ID] =
		$attributes[$index][HTML_ATTRIBUTE_NAME] =		
		$attributes[$index][HTML_ATTRIBUTE_TYPE] =
		$dom_element_type = NULL;

		$attributes[$index][PROPERTY_MANDATORY] = FALSE;

		if (
			isset( $setting[HTML_ATTRIBUTE_TYPE] ) &&
			$element_type = rtrim(
				$setting[HTML_ATTRIBUTE_TYPE],
				SUFFIX_MANDATORY
			)
		)

 			$attributes[$index][HTML_ATTRIBUTE_TYPE] = $element_type;

		if (
			strlen( $element_type ) + 1 ==
				strlen( $setting[HTML_ATTRIBUTE_TYPE] )
		)

 			$attributes[$index][PROPERTY_MANDATORY] = TRUE;

		// email input fields are text input fields
		if ( $element_type == FIELD_TYPE_EMAIL ) 
 
			$attributes[$index][HTML_ATTRIBUTE_TYPE] = FIELD_TYPE_TEXT;

		$attributes_names =  array(
			HTML_ATTRIBUTE_ACCESSKEY,
			HTML_ATTRIBUTE_CLASS,
			HTML_ATTRIBUTE_COLS,
			HTML_ATTRIBUTE_DISABLED,
			HTML_ATTRIBUTE_ID,
			HTML_ATTRIBUTE_FOR,
			HTML_ATTRIBUTE_NAME,
			HTML_ATTRIBUTE_READ_ONLY,
			HTML_ATTRIBUTE_ROWS,
			HTML_ATTRIBUTE_TABINDEX,
			HTML_ATTRIBUTE_TITLE,
			HTML_ATTRIBUTE_VALUE
		);

		while ( list( , $name ) = each( $attributes_names ) )

			// checking if an id attribute is set 
			if ( isset( $setting[$name] ) )
			{
				if (
					$name == HTML_ATTRIBUTE_VALUE &&
					(
						defined(
							strtoupper(
								$prefix_language_item.
									$setting[$name]
							)
						)
					)
				)
					
					$attributes[$index][$name] =
						constant(
							strtoupper(
								$prefix_language_item.
									$setting[$name]
							)					
						)
					;
				else 

					$attributes[$index][$name] = $setting[$name];
			}
	 

		reset( $attributes );
		
		if (
			! is_null(
				$attributes[$index][HTML_ATTRIBUTE_TYPE]
			)
		)
		{
			switch ( $attributes[$index][HTML_ATTRIBUTE_TYPE] )
			{
				case FIELD_TYPE_SELECT:

					$dom_element_type = HTML_ELEMENT_SELECT;

						break;

				case FIELD_TYPE_TEXTAREA:

					$dom_element_type = HTML_ELEMENT_TEXTAREA;

						break;

				case FIELD_TYPE_TEXT:
				default:

					$dom_element_type = HTML_ELEMENT_INPUT;
			}
		}

		return $dom_element_type;
	}

    /**
    * Get the inhabitants
    *
    * @return  array    representing inhabitants
    */
    public function getInhabitants()
    {
        // return the member DOMDocument
        return $this->_inhabitants;
    }

    /**
    * Construct a View Builder
    *
    * @param    object		&$element     		reference to a DOMDocument object
    * @param    string  	$element_type   	containing an element type
    * @param    string      $attribute      	containing an attribute name
    * @param    array     	$properties     	containing properties
    * @param    boolean		$static_parameters	static mode
    * @return   nothing
    */
    public function setAttribute(
        &$element,
        $element_type,
        $attribute,
        $properties,
		$static_parameters = NULL
    )
    {
		global $class_application, $verbose_mode;

		$class_data_fetcher = self::getDataFetcherClass();

		$class_dumper = self::getDumperClass();

		$class_test_case = self::getTestCaseClass();

        // declare the default attribute value
        $attribute_value = '';

		// set the default form identifier as an empty string
		$form_identifier = '';

		$static_mode = FALSE;

		if ( ! is_null( $static_parameters ) )
		{
			$static_mode = TRUE;

			list(
				$form_identifier,
				$settings
			) = $static_parameters;
		}	

        // switch from the element type
        switch ( $element_type )
        {
            case HTML_ELEMENT_FORM:

                // set the default value of the action attribute 
                $attribute_value = '';

                    break;

            default:

                // get the properties
                list( $field_type, $field_name ) = $properties;

				// check the field option property				
				if ( isset( $properties[2] ) )

					// set a field option
					$field_option = $properties[2];				
        }

		if ( ! $static_mode )
		{
			// get the configuration
			$_configuration = self::getConfiguration();
	
			// check the form identifier
			if ( ! empty( $_configuration[PROPERTY_FORM_IDENTIFIER] ) )
			
				// set the form identifier
				$form_identifier = $class_application::translate_entity(
					$_configuration[PROPERTY_FORM_IDENTIFIER],
					ENTITY_CSS_CLASS
				);
	
			// get the check signs
			$_check = &$this->readSigns( PROPERTY_CHECK );

			// get the dasboard signs
			$_dashboard = &$this->readSigns( PROPERTY_DASHBOARD );

			// get the inhabitants member
			$_inhabitants = $this->getInhabitants();

			// get the i18n member
			$_i18n = &$this->getI18n();

	         // get the stylesheets member 
	        $_stylesheet = $this->getStylesheet();

			// get the insight target
			$_target = self::getProperty(PROPERTY_TARGET);
		}
		else
		{
			if ( ! is_null( $properties ) )
			{
				$_check = array(
					SESSION_STORE_FIELD =>
						array(
							SESSION_STORE_HALF_LIVING =>
								array( $field_name => NULL )
						)
				);
	
				$_inhabitants = array(
					$field_name =>
						$settings
				);
	
				$_dashbaord = array(
					$field_name =>
						array(
							ERROR_ALREADY_TAKEN => NULL,
							ERROR_FIELD_MISSING => NULL,
							ERROR_WRONG_VALUE => NULL
						)
				);
			}

			$_stylesheet = NULL;
			
			$_target = '';
		}

        // declare the default setting flag
        $set_attribute = TRUE;

        // check the attribute name
        switch ( $attribute )
        {
            case HTML_ATTRIBUTE_ACCEPT_CHARSET:

                // set the default value of the accept-charset attribute 
                $attribute_value = I18N_CHARSET_UTF8;

                break;

            case HTML_ATTRIBUTE_ACCESSKEY:
            case HTML_ATTRIBUTE_TABINDEX:

				// check the existence of a field inhabitant 
				if (
					isset( $_inhabitants[$field_name] ) &&
					isset( $_inhabitants[$field_name][$attribute] ) &&
					( NULL !== $_inhabitants[$field_name][$attribute] )
				)
				{
					if ( $element_type !== HTML_ELEMENT_OPTION )

						// set the attribute value
						$attribute_value = $_inhabitants[$field_name][$attribute];

					else if (
						isset( $field_option ) &&
						$attribute === HTML_ATTRIBUTE_ACCESSKEY
					)

						// set the attribute value
						$attribute_value = $field_option;
				}
				else 

					// toggle the setting flag
					$set_attribute = FALSE;

                break;

            case HTML_ATTRIBUTE_ACTION:

				if ( ! $static_mode )

					$action = $properties->get_action();
				else

					$action = 
							isset( $_SERVER['REQUEST_URI'] )
						?
							$_SERVER['REQUEST_URI']
						:
							(
									isset( $_SERVER['PHP_SELF'] )
								?
	
									$_SERVER['PHP_SELF']
								:
									''
							)
					;

                // set the default value of the action attribute 
                $attribute_value =
					$action.
					(
						isset( $form_identifier )
					?
						// append an internal anchor to the action value
						'#'.$form_identifier
					:
						''
					)
				;

                    break;

            case HTML_ATTRIBUTE_CLASS:
        
                // switch from the element type
                switch ( $element_type )
                {
                    case HTML_ELEMENT_FORM:

                        // toggle the setting flag
                        $set_attribute = FALSE;

                            break;
        
                    default:

                        // check the existence of a field inhabitant 
                        if (
                            isset( $_inhabitants[$field_name] ) &&
                            ! empty( $_inhabitants[$field_name][$attribute] )
                        )
					
							// set the attribute value
							$attribute_value =
								$_inhabitants[$field_name][$attribute]
							;
                        else 
                        
                            // toggle the setting flag
                            $set_attribute = FALSE;

                        // check the stylesheet
                        if (
							is_array( $_stylesheet ) &&
							isset( $_stylesheet[$field_name] ) &&
							$element_type == HTML_ELEMENT_LABEL
						)
                        {

                            $class_dumper::log(
                                __METHOD__,
                                array(
                                    'attribute settings',
                                    $set_attribute,
                                    'stylesheet',
                                    $_stylesheet
                                ),
                                FALSE
                            );

                            // set the stylesheet class attribute
                            $attribute_value =
                                    $set_attribute
                                ?
                                    $attribute_value." ".$_stylesheet[$field_name]
                                :
                                    $_stylesheet[$field_name];

                            // toggle the setting flag
                            $set_attribute = true;
                        }
                }

                    break;

            case HTML_ATTRIBUTE_COLS:

                // switch from the element type
                switch ( $element_type )
                {
                    case HTML_ELEMENT_TEXTAREA:

						if (
							! empty(
								$_inhabitants
									[$field_name]
										[HTML_ATTRIBUTE_COLS]
							) &&
						 	is_numeric(
								$_inhabitants
									[$field_name]
										[HTML_ATTRIBUTE_COLS]
							)
						)
	
	                        $attribute_value =
								$_inhabitants
									[$field_name]
										[HTML_ATTRIBUTE_COLS]
							;

							break;
                }

            case HTML_ATTRIBUTE_DISABLED:
            case HTML_ATTRIBUTE_READ_ONLY:
            case HTML_ATTRIBUTE_TITLE:

                // toggle the setting flag
                $set_attribute = FALSE;

                // check the inhabitant attribute value of the current field
                if (
                    isset($_inhabitants[$field_name]) &&
                    isset($_inhabitants[$field_name][$attribute]) &&
                    $_inhabitants[$field_name][$attribute] == TRUE
                )

                    // toggle the setting flag
                    $set_attribute = true;

                    break;

            case HTML_ATTRIBUTE_ENCTYPE:

                // set the default value of the enctype attribute                 
                $attribute_value = FORM_ENCODING_TYPE_MULTIPART;

                    break;

            case HTML_ATTRIBUTE_FOR:
            case HTML_ATTRIBUTE_ID:
            case HTML_ATTRIBUTE_NAME:

                // switch from the element type
                switch ( $element_type )
                {
                    case HTML_ELEMENT_FORM:

                        // toggle the setting flag
                        $set_attribute = FALSE;

                            break;
        
                    default:

                        // check the existence of a field inhabitant 
                        if (
                            isset( $_inhabitants[$field_name] ) &&
                            ! empty( $_inhabitants[$field_name][$attribute] )
                        )
                        {
                            // check the current attribute
                            if (
                                ! empty( $_inhabitants[$field_name][$attribute ] ) &&
								(
									$element_type == HTML_ELEMENT_INPUT ||
									$element_type == HTML_ELEMENT_SELECT ||
									$element_type == HTML_ELEMENT_TEXTAREA
								)
                            )

								// set the attribute value
								$attribute_value =
									$_inhabitants[$field_name][$attribute].
									(
										isset( $field_option ) &&
										$field_type == FIELD_TYPE_CHECKBOX
									?
										SUFFIX_ARRAY
									:
										''
									);
							else

                                // set the attribute value
                                $attribute_value =
									$element_type.
										'_'.
											$_inhabitants
												[$field_name]
													[$attribute]
								;
                        }
        
                        // check if the attribute name is id 
                        else if (
                            $attribute == HTML_ATTRIBUTE_FOR ||
                            $attribute == HTML_ATTRIBUTE_ID
                        )
                        {
                            // check the member attribute name inhabitant 
                            if (
								isset(
									$_inhabitants
										[$field_name]
											[HTML_ATTRIBUTE_NAME]
								) &&
								(
									$name =
										$_inhabitants
											[$field_name]
												[HTML_ATTRIBUTE_NAME]
								)
							)

								$attribute_value =  self::generateUniqueId(
									$attribute,
									(
										(
											isset(
												$_inhabitants[$field_name]
											) &&
											is_array( 
												$_inhabitants[$field_name]
											) &&
 											isset(
												$_inhabitants
													[$field_name]
														[HTML_ATTRIBUTE_ID]
											) &&
											(
												$id =
													$_inhabitants
														[$field_name]
															[HTML_ATTRIBUTE_ID]
											)
										)
										?
											$id
										:
											NULL
									),
									$form_identifier,
									$name,
									isset( $field_option ) ? $field_option : NULL,
									$_target,
									$element_type
								);
                        }
                }
 
                    break;

            case HTML_ATTRIBUTE_METHOD:

	            // set the method attribute value

				if ( ! $static_mode)

	                $attribute_value = $properties->get_method();
				else 

	                $attribute_value = PROTOCOL_HTTP_METHOD_POST;

                    break;

            case HTML_ATTRIBUTE_ROWS:

                // switch from the element type
                switch ( $element_type )
                {
                    case HTML_ELEMENT_TEXTAREA:

						if (
							! empty(
								$_inhabitants[$field_name][HTML_ATTRIBUTE_ROWS]
							) &&
						 	is_numeric(
								$_inhabitants[$field_name][HTML_ATTRIBUTE_ROWS]
							)
						)
	
	                        $attribute_value =
								$_inhabitants[$field_name][HTML_ATTRIBUTE_ROWS];

							break;
                }

            case HTML_ATTRIBUTE_TYPE:

                // switch from the field type
                switch ( $field_type )
                {
                    case FIELD_TYPE_BUTTON:
					case FIELD_TYPE_CHECKBOX:
                    case FIELD_TYPE_FILE:
                    case FIELD_TYPE_HIDDEN:
                    case FIELD_TYPE_PASSWORD:
					case FIELD_TYPE_RADIO:
					case FIELD_TYPE_SELECT:
                    case FIELD_TYPE_SUBMIT:

                        $attribute_value = $field_type;

							break;

                    case FIELD_TYPE_EMAIL:
                    case FIELD_TYPE_TEXT:
    
                        $attribute_value = strtolower(
							substr(
								FORM_FIELD_TYPE_TEXT,
								strlen( ENTITY_FIELD )
							)
						);
    
                            break;
                }

                    break;

            case HTML_ATTRIBUTE_VALUE:

                // set the cascading assignment flag 
                $cascading_assignment = TRUE;

                // switch from the field type
                switch ( $field_type )
                {
                    case FIELD_TYPE_PASSWORD:

                        $cascading_assignment = FALSE;

						if (
							! empty(
								$_check
									[SESSION_STORE_FIELD]
										[SESSION_STORE_HALF_LIVING]
											[$field_name]) &&
							empty(
								$_dashboard
									[$field_name]
										[ERROR_WRONG_VALUE]
							)
						)
						{
							// toggle the field missing error flag
							unset(
								$_dashboard
									[$field_name]
										[ERROR_FIELD_MISSING]
							);

							// set the value attribute of the current field
							$attribute_value =
								$_check
									[SESSION_STORE_FIELD]
										[SESSION_STORE_HALF_LIVING]
											[$field_name]
							;

							$class_test_case::perform(
								DEBUGGING_FIELD_HANDLING_DEFAULT_PASSWORD,
								$verbose_mode,
								array(
									PROPERTY_NAME => $field_name,
									PROPERTY_VALUE => $attribute_value
								)
							);
						}

                            break;

					case FIELD_TYPE_BUTTON:
					case FIELD_TYPE_CHECKBOX:
                    case FIELD_TYPE_EMAIL:
                    case FIELD_TYPE_FILE:
					case FIELD_TYPE_HIDDEN:
					case FIELD_TYPE_RADIO:
					case FIELD_TYPE_SELECT:
                    case FIELD_TYPE_SUBMIT: 
                    case FIELD_TYPE_TEXT:

                        $cascading_assignment = FALSE;

						if (
							! $static_mode &&
							$field_type == FIELD_TYPE_SUBMIT &&
							defined(
								strtoupper(
									$_i18n[PROPERTY_NAMESPACE].
										PREFIX_LABEL.
											$class_application::translate_entity(
												$form_identifier,
												ENTITY_CSS_CLASS
											)."_".
												$field_name
								)								
							)
						)

							$_inhabitants[$field_name][$attribute] = constant(
								strtoupper(
									$_i18n[PROPERTY_NAMESPACE].
										PREFIX_LABEL.
											$class_application::translate_entity(
												$form_identifier,
												ENTITY_CSS_CLASS
											)."_".
												$field_name
								)
							);

                        // check the existence of the field inhabitant 
						if
						(
                            isset(
								$_inhabitants[$field_name]
							) &&
                            (
								! empty(
									$_inhabitants
										[$field_name]
											[$attribute]
								) ||
								isset(
									$_inhabitants
										[$field_name]
											[AFFORDANCE_PROVIDE_WITH_OPTIONS]
								) ||
								! empty(
									$_inhabitants
										[$field_name]
											[AFFORDANCE_DISPLAY_DEFAULT_VALUE]
								)
                            )
                        )
                        {
							if (
								isset( $field_option ) &&
								isset(
									$_inhabitants
										[$field_name]
											[AFFORDANCE_PROVIDE_WITH_OPTIONS]
								) &&
								is_array(
									$_inhabitants
										[$field_name]
											[AFFORDANCE_PROVIDE_WITH_OPTIONS]
								) &&
								isset(
									$_inhabitants
										[$field_name]
											[AFFORDANCE_PROVIDE_WITH_OPTIONS]
												[$field_option]
								)
							)

                                // set the attribute value
								$attribute_value = $field_option;

                            // check the current attribute
                            else if (
								! empty(
									$_inhabitants
										[$field_name]
											[$attribute]
								)
							)

                                // set the attribute value
								$attribute_value =
									$_inhabitants
										[$field_name]
											[$attribute]
								;
							else
							{
								$match = preg_match(
									REGEXP_OPEN.
										SHORTHAND_DATABASE.
											'\.'.'([^\.]*)\.([^.*]*)(\*)?'.
									REGEXP_CLOSE,
									$_inhabitants
										[$field_name]
											[AFFORDANCE_DISPLAY_DEFAULT_VALUE],
									$matches
								);

								// check the matching values
								if ( $match )
								{
									$default_value =
										$class_data_fetcher::fetchFieldValue(
											$matches[2],
											$matches[1]
										)
									;

									// toggle the field missing error flag
									unset(
										$_dashboard
											[$field_name]
												[ERROR_FIELD_MISSING]
									);

									if (
										! empty(
											$_check
												[SESSION_STORE_FIELD]
													[SESSION_STORE_HALF_LIVING]
														[$field_name]
										) &&
										$_check
											[SESSION_STORE_FIELD]
												[SESSION_STORE_HALF_LIVING]
													[$field_name] !=
														$default_value &&
										! empty(
											$_dashboard
												[$field_name]
													[ERROR_ALREADY_TAKEN]
										)
									)

										$default_value =
											$_check
												[SESSION_STORE_FIELD]
													[SESSION_STORE_HALF_LIVING]
														[$field_name]
										;

									// set the attribute value
									$attribute_value = $default_value;
								}
								else

									// set the attribute value								
									$attribute_value =
										$_inhabitants
											[$field_name]
												[AFFORDANCE_DISPLAY_DEFAULT_VALUE]
									;
							}
                        }
						else if (
							! empty(
								$_check
									[SESSION_STORE_FIELD]
										[SESSION_STORE_HALF_LIVING]
											[$field_name]
							)
						)

							// set the attribute value
							$attribute_value =
								$_check
									[SESSION_STORE_FIELD]
										[SESSION_STORE_HALF_LIVING]
											[$field_name]
							;

                            break;
                }

                // check the cascading assignment
                if ( ! $cascading_assignment )

                    break;

            default:

                $attribute_value =
                    CHARACTER_BRACKET_START.
                        CHARACTER_DOLLAR.
                        $attribute.
                        CHARACTER_UNDERSCORE.$element_type.
                        CHARACTER_UNDERSCORE.$field_name.
                    CHARACTER_BRACKET_END
                ;            
        }

        // check if the current field exists among the member inhabitants
        if ( $set_attribute && ! is_array( $attribute_value ) )

            // set an element attribute
            $element->setAttribute(
                $attribute,
                $attribute_value
            );
    }

    /**
    * Set the stylesheet member
    *
    * @param    mixed  $stylesheet     stylesheet
    * @return   nothing
    */
    public function setStylesheet($stylesheet)
    {
        $this->_stylesheet = $stylesheet;
    }

    /**
    * Build a view
    *
    * @param    mixed	$resources	store containing resources
    * @param    string	$from  		input entity
    * @return  	mixed
    */	            
    public static function build( $resources, $from = ENTITY_FIELD_HANDLER )
    {
		global $class_application;

		// set the data fetcher class name 
		$class_data_fetcher = $class_application::getDataFetcherClass();

		// set the dumper class name
		$class_dumper = $class_application::getDumperClass();

		// set the html element class name
		$class_element_html = $class_application::getElementHtmlClass();

        // declare a empty array of DOM nodes
        $dom_nodes =

		// display default position indexes
		$position_indexes =

		// declare an empty array of instances of the Element_HTML class
		$html_elements =

		// declare properties to be passed
		// to the constructor of the Element_Html Class 		
		$instances_properties = array();

		// set the default edition mode flag
		$edition_mode = NULL;

		$edition_mode_type_preview = $class_data_fetcher::getEntityTypeValue(
			array(
				PROPERTY_NAME => PROPERTY_PREVIEW,
				PROPERTY_ENTITY => ENTITY_EDITION_MODE
			)
		);

		/**
		* Check resources
		*
		* @return	object	$field_handler		field handler
		* @return	string	$form_identifier 	form identifier
		*/
		extract( self::checkResources( $resources ) );

		// Write the html elements on the blackboard
		self::writeSigns( PROPERTY_HTML_ELEMENTS, $html_elements );

		// Write the elements properties on the blackboard 
		self::writeSigns( PROPERTY_ELEMENTS_PROPERTIES, $instances_properties );

		// Set the current target as property
		self::setProperty( PROPERTY_TARGET, $target );

        // declare a default view
        $view = CHARACTER_BLANK;

        // construct a new view builder
        $view_builder = new self();        

        // get the member DOM
        $_dom = &$view_builder->getDOM();

        // get the configuration
        $configuration = self::getConfiguration();

		$mode_preview = FALSE;

		if (
			is_array( $configuration ) &&
			count( $configuration ) &&
			isset( $configuration[PROPERTY_EDITION] ) &&
			( $edition_mode = $configuration[PROPERTY_EDITION] ) &&
			$edition_mode === $edition_mode_type_preview
		)

			$mode_preview = TRUE;

		if (
			isset( $field_handler ) &&
			is_object( $field_handler ) &&
			get_class( $field_handler ) == CLASS_FIELD_HANDLER &&
			$field_handler->get_config() == NULL
		)

			// set the field handler configuration
			$field_handler->set_config( $configuration );

        // get the member DOMDocument object
        $document = &$_dom[ENTITY_DOCUMENT];

        // check the resources
        if (
            is_object( $field_handler ) &&
            get_class( $field_handler ) == constant(
				strtoupper(
					$class_application::translate_entity(
						PREFIX_CLASS,
						ENTITY_PHP_VARIABLE
					).
					$from
				)
			)
        )
        {
	        // get fields
            $fields = $field_handler->get_fields();

            // get field view
            $dom_nodes[0] = $view_builder->buildDOMNode(
				HTML_ELEMENT_FORM,
				$resources
			);

			$element_fieldset = $document->createElement(
				HTML_ELEMENT_FIELDSET
			);

			$properties[HTML_ELEMENT_FIELDSET] = array(
				PROPERTY_DOM_DOCUMENT => $document,
				PROPERTY_DOM_ELEMENT => $element_fieldset
			);
			
			$html_elements[HTML_ELEMENT_FIELDSET] =
				new $class_element_html( $properties[HTML_ELEMENT_FIELDSET] )
			;

			$html_elements[HTML_ELEMENT_FIELDSET]->wrap(
				str_replace( '.', '_', $form_identifier )
			);
	
            // get field view
            $dom_nodes[1] = $element_fieldset;
		
			// get the first field index
			list( $first_field_index ) = each( $fields );
			reset( $fields );

			// get the last field index
			end( $fields );
			list( $latest_field_index ) = each( $fields );
			reset( $fields );

			// review the positionning of each field
			while (
				list( $field_index, $field_configuration ) =
					each( $configuration[PROPERTY_FIELDS] )
			)

				if ( isset( $field_configuration[PROPERTY_INDEX] ) )

					$position_indexes[$field_configuration[PROPERTY_NAME]] =
						$field_configuration[PROPERTY_INDEX] + 1;

			reset( $configuration[PROPERTY_FIELDS] );

			while (
				list( $target_name, $position_index ) =
					each( $position_indexes )
			)
			{
				$target_properties = $fields[$target_name];

				unset( $fields[$target_name] );

				$slice_start = array_slice(
					$fields,
					1,
					$position_index - 1,
					TRUE
				);

				$slice_end = array_slice(
					$fields,
					$position_index - 1,
					count( $fields ),
					TRUE
				);

				if ( $position_index != count( $fields ) )

					$fields = array_merge(
						$slice_start,
						array( $target_name => $target_properties ),
						$slice_end
					);
				else

					$fields = array_merge(
						$slice_start,
						$slice_end,
						array( $target_name => $target_properties )
					);
			}

            // check the fields
            if ( is_array( $fields ) && count( $fields ) != 0)
			{
				if ( ! isset( $html_elements[HTML_ELEMENT_INPUT] ))
				{
					$html_elements[HTML_ELEMENT_INPUT] =

					$instances_properties[HTML_ELEMENT_INPUT] = array();
				}

                // loop on fields
                while ( list( $field_index, $field ) = each( $fields ) )

					self::buildFormField(
						array(
							PROPERTY_FIELD_CONTEXT => array(
								'dom_nodes' => &$dom_nodes,
								'field' => $field,
								'field_index' => $field_index,
								'first_field_index' => $first_field_index,
								'mode_preview' => $mode_preview,
								'view_builder' => $view_builder
							),
							PROPERTY_RESOURCES => $resources
						)
					);
			}

			// create a div element
			$element_div = $document->createElement( HTML_ELEMENT_DIV );

			// set the value of the id attribute of the div element 
			$element_div->setAttribute(
				HTML_ATTRIBUTE_ID,
				$class_application::translate_entity(
					$form_identifier.$target,
					ENTITY_SMARTY_VARIABLE
				)
			);

			// set the value of the class attribute of the div element 
			$element_div->setAttribute(
				HTML_ATTRIBUTE_CLASS,
				STYLE_CLASS_FORM_CONTAINER
			);

			// append the fieldset node to the form element
			$dom_nodes[0]->appendChild(
				$html_elements[HTML_ELEMENT_FIELDSET]->{PROPERTY_WRAPPERS}
					[PROPERTY_WRAPPER]
			);

			// append the form node to the div element
			$element_div->appendChild( $dom_nodes[0] );
			
			if ( ! is_null( $edition_mode ) )
			{
				$container_form_container_parent =
					$document->createElement( HTML_ELEMENT_DIV )
				;

				$container_form_container_parent->setAttribute(
					HTML_ATTRIBUTE_CLASS,
					STYLE_CLASS_FORM_CONTAINER_PARENT
				);

				// box containing the form container div element
				// used for editon and preview mode
				$container_form_container_parent->appendChild(
					$element_div
				);
			}

			// check if some links are to be appended 
			$links = self::checkLinks(
				array( PROPERTY_VIEW_BUILDER => $view_builder )
			);
		
			// check the disclaimers configuration
			if ( isset( $configuration[PROPERTY_DISCLAIMERS] ) )
			{
				if (
					isset( $_SESSION[ENTITY_FEEDBACK] ) &&
					isset(
						$_SESSION[ENTITY_FEEDBACK]
							[$form_identifier ]
					) &&
					isset(
						$_SESSION[ENTITY_FEEDBACK]
							[$form_identifier]
								[AFFORDANCE_DISPLAY]
					)
				)
				{
					if (
						is_array(
								$_SESSION[ENTITY_FEEDBACK]
									[$form_identifier]
										[AFFORDANCE_DISPLAY]
											[ENTITY_DISCLAIMER]
						) &&
						(
							$configuration[PROPERTY_DISCLAIMERS][AFFORDANCE_DISPLAY] =
								$_SESSION[ENTITY_FEEDBACK]
									[$form_identifier]
										[AFFORDANCE_DISPLAY]
											[ENTITY_DISCLAIMER]
						) ||
						is_string(
							$configuration[PROPERTY_DISCLAIMERS][AFFORDANCE_DISPLAY] =
								$_SESSION[ENTITY_FEEDBACK]
									[$form_identifier]
										[AFFORDANCE_DISPLAY]							
							
						) && (
							$configuration[PROPERTY_DISCLAIMERS][AFFORDANCE_DISPLAY] =
								$_SESSION[ENTITY_FEEDBACK]
									[$form_identifier]
										[AFFORDANCE_DISPLAY]
						) && isset( $configuration[PROPERTY_DISCLAIMERS][0] )
					)

						// Remove the original disclaimer
						unset( $configuration[PROPERTY_DISCLAIMERS][0] );
				}

				// settle the disclaimers
				self::settleDisclaimers(
					
					// element of type form 
					$dom_nodes[0],
					
					// form identifier
					$configuration[PROPERTY_FORM_IDENTIFIER],

					// context
					$context = array(
						'dom_document' => $document,
						'settings' => $configuration
					)
				);
			}

			// check the layout configuration
			if (
				isset( $configuration[PROPERTY_LAYOUT] ) &&
				isset( $configuration[PROPERTY_LAYOUT] ) &&
				is_array( $configuration[PROPERTY_LAYOUT] ) &&
				$configuration[PROPERTY_LAYOUT][LAYOUT_TITLE_LEVEL_2] ||
				isset( $configuration[PROPERTY_FORM_IDENTIFIER] ) &&
				defined(
					strtoupper(
						substr(PREFIX_FORM, 0, -1)."_".
							PROPERTY_LAYOUT."_".
								LAYOUT_TITLE_LEVEL_1."_".
									$class_application::translate_entity(
										$configuration[PROPERTY_FORM_IDENTIFIER],
										ENTITY_CONSTANT
									)
					)	
					
				)				
			)
			{
				if (
					isset( $configuration[PROPERTY_FORM_IDENTIFIER] ) &&
					defined(
						strtoupper(
							substr( PREFIX_FORM, 0, -1 )."_".
								PROPERTY_LAYOUT."_".
									LAYOUT_TITLE_LEVEL_1."_".
										$class_application::translate_entity(
											$configuration[PROPERTY_FORM_IDENTIFIER],
											ENTITY_CONSTANT
										)
						)
					)
				)
				{
					// create a new title node
					$title_level_1_node = $document->createElement( HTML_ELEMENT_H2 );

					// construct a new DOMText object
					$title_level_1_text = new DOMText();

					// append data to the DOMText object
					$title_level_1_text->appendData(
						constant(
							strtoupper(
								substr(PREFIX_FORM, 0, -1)."_".
									PROPERTY_LAYOUT."_".
										LAYOUT_TITLE_LEVEL_1."_".
											$class_application::translate_entity(
												$configuration[PROPERTY_FORM_IDENTIFIER],
												ENTITY_CONSTANT
											)
							)
						)
					);

					// append a child to the title level 2 node
					$title_level_1_node->appendChild( $title_level_1_text );

					// append a child to the main div element before the fields
					$dom_nodes[0]->insertBefore(
						$title_level_1_node,
						$dom_nodes[0]->childNodes->item( 0 )
					);					
				}
				else
				{
					$prefix_title_level_2 =
						substr(PREFIX_FORM, 0, -1)."_".
						PROPERTY_LAYOUT."_".
						LAYOUT_TITLE_LEVEL_2."_".
						$class_application::translate_entity(
							$configuration[PROPERTY_FORM_IDENTIFIER],
							ENTITY_CONSTANT
						).'_'
					;
	
					if (
						defined(
							strtoupper(
								$prefix_title_level_2.
									$configuration
										[PROPERTY_LAYOUT]
											[LAYOUT_TITLE_LEVEL_2]
							)
						)
					)
					{
						// create a new title node
						$title_level_2_node =
							$document->createElement( HTML_ELEMENT_H2 )
						;
	
						// construct a new DOMText object
						$title_level_2_text = new DOMText();
	
						// append data to the DOMText object
						$title_level_2_text->appendData(
							constant(
								strtoupper(
									$prefix_title_level_2.
									$configuration
									[PROPERTY_LAYOUT]
									[LAYOUT_TITLE_LEVEL_2]
								)
							)
						);
	
						// append a child to the title level 2 node
						$title_level_2_node->appendChild( $title_level_2_text );
	
						// append a child to the main div element before the fields
						$dom_nodes[0]->insertBefore(
							$title_level_2_node,
							$dom_nodes[0]->childNodes->item(0)
						);
					}
				}
			}
			
			if ( isset( $links ) && is_array( $links ) && count( $links ) > 0 )
			{
				$link_container = $document->createElement( HTML_ELEMENT_DIV );

				$link_container->setAttribute(
					HTML_ATTRIBUTE_CLASS,
					STYLE_CLASS_LINK_CONTAINER
				);

				while ( list( $index, $link ) = each( $links ) )

					if (
						is_object( $link->{PROPERTY_NODE} ) && 
						get_class( $link->{PROPERTY_NODE} ) == CLASS_DOM_ELEMENT
					)
					{
						if ( $link->{ PROPERTY_POSITION} == POSITION_AFTER )
						{
							$link_container->appendChild( $link->{PROPERTY_NODE} );

							$element_div->appendChild( $link_container );
						}
						else if (
							$link->{PROPERTY_POSITION} == POSITION_BEFORE
						)
						{
							$link_container->appendChild( $link->{PROPERTY_NODE} );

							$element_div->insertBefore(
								$link_container,
								$element_div->childNodes->item(0)
							);
						}
					}
			}
			
			if (
				! is_null( $edition_mode ) &&
				is_bool( $edition_mode ) ||
				is_integer( $edition_mode )
			)
			
				$element_div = &$container_form_container_parent ;
			
			// append the div element to the document
			$document->appendChild( $element_div );

			// get field view
			$view .= self::removePlaceholders(
				$document->saveXML( $element_div ),
				TRUE
			);

			// fix issues with self-closing div tags 
			$view = preg_replace(
				'/<div([^\/>]+)\/>/',
				'<div\1></div>',
				$view
			);
        }

        // return the view        
        return $view;
    }

    /**
    * Build a block
    *
    * @param    integer		$page			page
    * @param    string		$block			block
    * @param    mixed		$configuration	configuration
    * @return   string		block
    */	            
    public static function buildBlock(
		$page,
		$block = NULL,
		$configuration = NULL
	)
	{
		// set the application class name
		global $class_application, $verbose_mode;

		// declare an empty block
		$block_view = '';

		// set the dumper class name
		$class_dumper = $class_application::getDumperClass();

		// set the content manager class name
		$class_content_manager = $class_application::getContentManagerClass();

		// set the member class name
		$class_member = $class_application::getMemberClass();

		// set the template engine class name
		$class_template_engine = $class_application::getTemplateEngineClass();

		// set the user handler class name
		$class_user_handler = $class_application::getUserHandlerClass();

		// set the constant name
		$constant_name = reverse_constant($block, strtoupper(PREFIX_BLOCK));

		// declare an empty array of properties
		$properties = array();

		// construct a new Smarty object
		$template_engine = new $class_template_engine();

		// set the administration flag
		$administration = FALSE;

		// check the page property of the provided configuration
		if (
			is_array( $configuration ) && 
			isset( $configuration[PROPERTY_PAGE] ) &&
			$configuration[PROPERTY_PAGE] == PAGE_OVERVIEW
		)

			// toggle the administration flag							
			$administration = TRUE;

		$slice = self::getPersistentProperty( ENTITY_SLICE );

		// set an empty view
		$view = '';

		// construct a new view builder
		$view_builder = new self();

		// swich from the current page
		switch ( $page )
		{
			case PAGE_ANY:

				// switch from the page
				switch ( $block )
				{
					case BLOCK_FOOTER:
						
						$view = self::getFooter();
	
							break;

					case BLOCK_FORM_LOGIN:
					case BLOCK_FORM_SIGN_IN:

						// set the current affordance
						$affordance = str_replace( PREFIX_FORM, '', $block );

						// check if a user is logged in
						if ( ! $class_user_handler::loggedIn( $administration ) )

							// get a form view
							$view = $class_application::getFormView(
								$affordance,
								PAGE_UNDEFINED,
								BLOCK_FORM
							);
						else

							// get a form view
							$view = self::buildDialog(
								$affordance,
								$configuration
							);

							break;

					case BLOCK_LEFT:

						$menu_items = array();

						if (
							isset( $configuration[ENTITY_TABS] ) &&
							is_array( $configuration[ENTITY_TABS] ) &&
							count( $configuration[ENTITY_TABS] ) != 0
						)
						{
							foreach (
								$configuration[ENTITY_TABS] as
									$tab_index => $tab
							)

								if (
									! empty( $tab[PROPERTY_AFFORDANCE] ) &&
									! empty( $tab[ENTITY_MENU] ) &&
									$tab[PROPERTY_TYPE] == BLOCK_FORM &&
									defined(
										strtoupper(
											substr(
												PREFIX_FORM,
												0,
												-1
											).'_'.
											PROPERTY_LAYOUT.'_'.
											LAYOUT_TITLE_LEVEL_1.'_'.
											str_replace(
												'.',
												'_',
												$tab[PROPERTY_AFFORDANCE]
											).'_'.
											$tab[ENTITY_MENU]
										)
									)
								)

									$menu_items[$tab_index] = array(
										PROPERTY_AFFORDANCE =>
											$tab[PROPERTY_AFFORDANCE],
										LAYOUT_TITLE_LEVEL_1 => constant(
											strtoupper(
												substr(
													PREFIX_FORM,
													0,
													-1
												).'_'.
												PROPERTY_LAYOUT.'_'.
												LAYOUT_TITLE_LEVEL_1.'_'.
												str_replace(
													'.',
													'_',
													$tab[PROPERTY_AFFORDANCE]
												).'_'.
												$tab[ENTITY_MENU]
											)
										)
									); 
						}

						if ( count( $menu_items ) != 0 )

							// get a menu view
							$view = self::buildMenu( $menu_items );

							break;

					case BLOCK_MENU_HEADER:

						// set the administration flag
						$administration = FALSE;

						if (
							isset( $configuration[PROPERTY_PAGE] ) &&
							$configuration[PROPERTY_PAGE] == PAGE_OVERVIEW
						)
						{
							$administration = TRUE;

							$page = PAGE_OVERVIEW;
						}

						// get the member DOM
						$_dom = &$view_builder->getDom();

						// declare an empty array of menu items
						$menu_items = array();

						// create a div element
						$menu_block = $_dom[ENTITY_DOCUMENT]->createElement(
							HTML_ELEMENT_DIV
						);

						// create a ul element
						$menu_list = $_dom[ENTITY_DOCUMENT]->createElement(
							HTML_ELEMENT_UL
						);

						// set menu text nodes by retrieving HTML a element
						// with href attributes
						$menu_texts = $class_content_manager::getMenu(
							$block,
							$page
						);

						// loop on text nodes
						while ( list( $menu_index ) = each( $menu_texts ) )
						{
							// create a li element
							$menu_items[$menu_index][ENTITY_ELEMENT] =
								$_dom[ENTITY_DOCUMENT]->createElement(
									HTML_ELEMENT_LI
								)
							;

							// create an a element
							$menu_items[$menu_index][HTML_ELEMENT_A] =
								$_dom[ENTITY_DOCUMENT]->createElement(
									HTML_ELEMENT_A
								)
							;

							// create a text node
							$menu_items[$menu_index][ENTITY_TEXT] =
								$_dom[ENTITY_DOCUMENT]->createTextNode(
									$menu_texts[$menu_index]->{ENTITY_TEXT}
								)
							;

							// set an href attribute
							$menu_items[$menu_index][HTML_ELEMENT_A]->setAttribute(
								HTML_ATTRIBUTE_HREF,
								$menu_texts[$menu_index]->{HTML_ATTRIBUTE_HREF}
							);

							// append a text node to the a element
							$menu_items[$menu_index][HTML_ELEMENT_A]->appendChild(
								$menu_items[$menu_index][ENTITY_TEXT]
							);

							// append the a element to the li element
							$menu_items[$menu_index][ENTITY_ELEMENT]->appendChild(
								$menu_items[$menu_index][HTML_ELEMENT_A]
							);

							// append the li element to the ul element 
							$menu_list->appendChild(
								$menu_items[$menu_index][ENTITY_ELEMENT]
							);

							// set the value of the class attribute of the li element
							$menu_items[$menu_index][ENTITY_ELEMENT]->setAttribute(
								HTML_ATTRIBUTE_CLASS,
								'menu_item'
							);
						}

						// append the menu list to the menu block
						$menu_block->appendChild( $menu_list );

						// set the menu id value to the menu block
						$menu_block->setAttribute(
							HTML_ATTRIBUTE_ID,
							STYLE_ID_BLOCK_MENU
						);    

						// set the menu id value to the menu block
						$menu_block->setAttribute(
							HTML_ATTRIBUTE_CLASS,
							STYLE_CLASS_BLOCK_MENU_HEADER
						);    

						// append the menu block to the document entity
						$_dom[ENTITY_DOCUMENT]->appendChild( $menu_block );

						$xml_source =
							$_dom[ENTITY_DOCUMENT]->saveXML( $menu_block )
						;

						// beautify the source
						$properties[
							$class_application::translate_entity(
								$block,
								ENTITY_SMARTY_VARIABLE
							)
						] = $class_application::beautifySource(
							$xml_source,
							NULL,
							TRUE,
							FALSE,
							VALIDATE_TREE_PLANTING,
							array( TIDY_OPTION_BODY_ONLY => TRUE )
						);

							break;

					case BLOCK_RIGHT:

						$menu_items = array();

						if ( empty( $configuration ) )
						{
							if (
								is_array( $slice ) &&
								count( $slice ) != 0
							)
	
								foreach ( $slice as $photograph_id => $photograph )
	
									$menu_items[$photograph_id] = array(
										LAYOUT_TITLE_LEVEL_1 =>
											$photograph->getTitle(),
										ENTITY_AFFORDANCE =>
											PREFIX_DOM_IDENTIFIER_FRAME_PHOTOGRAPH.
												$photograph_id
									);						
						}
						else if (
							empty( $configuration->{ROW_KEYWORDS} ) &&
							(
								is_array( $slice ) &&
								count( $slice ) != 0
							)
						)
		
							foreach ( $slice as $item_id => $item )

								$menu_items[$item_id] = array(
									LAYOUT_TITLE_LEVEL_1 => $item->title,
									ENTITY_AFFORDANCE =>
										PREFIX_DOM_IDENTIFIER_DOCUMENT.$item_id
								);

						// get a menu view
						$view = self::buildMenu(
							$menu_items,
							$block,
							$configuration
						);
				}

					break;

			case PAGE_CONTENT:

				if ( isset( $block ) )
				{
					switch ( $block )
					{
						case BLOCK_RIGHT:

							// set a menu
							$properties[
								$class_application::translate_entity(
									BLOCK_RIGHT,
									ENTITY_SMARTY_VARIABLE
								)
							] = self::buildBlock(
								PAGE_ANY,
								BLOCK_RIGHT,
								$configuration
							);

								break;
					}
				}

			case PAGE_DIALOG:
			case PAGE_HOMEPAGE:
			case PAGE_OVERVIEW:

				// check the block argument				
				if ( isset( $block ) )
				{
					switch ( $block )
					{
						case BLOCK_FOOTER:

								break;

						case BLOCK_CONTENT:

								break;

						case BLOCK_HEADER:

							// set a sign in form
							$properties[
								$class_application::translate_entity(
									BLOCK_FORM_SIGN_IN,
									ENTITY_SMARTY_VARIABLE
								)
							] = self::buildBlock(
								PAGE_ANY,
								BLOCK_FORM_SIGN_IN,
								array(
									PROPERTY_PAGE => $page
								)
							);

							if (

								$class_user_handler::loggedIn() ||
								
								// check if an administrator is logged in
								// and if the overview folder is opened
								$class_user_handler::loggedIn( TRUE ) &&
								FALSE !== strpos(
									$_SERVER['REQUEST_URI'],
									URI_ACTION_OVERVIEW
								) 

							)

								// set a menu
								$properties[
									$class_application::translate_entity(
										BLOCK_MENU_HEADER,
										ENTITY_SMARTY_VARIABLE
									)
								] = self::buildBlock(
									PAGE_ANY,
									BLOCK_MENU_HEADER,
									array(
										PROPERTY_PAGE => $page
									)
								);

								break;

						case BLOCK_RIGHT:

								break;
					}
				}

					break;
		}

		// check the view
		if ( empty( $view ) )
		{
			$cache_id =
				md5(
					serialize(
						array(
							(
									( int ) $class_user_handler::loggedIn(
										$administration
									) ||
									( int ) $class_user_handler::loggedIn()
								?
									( int ) $class_user_handler::loggedIn(
										$administration
									).
									( int ) $class_user_handler::loggedIn().
									$class_member::getIdentifier( FALSE, FALSE ).
									$class_member::getIdentifier( TRUE, FALSE )
								:
									( int ) $class_user_handler::loggedIn(
										$administration
									).
									( int ) $class_user_handler::loggedIn()							
							),
							func_get_args(),
							$slice,
							$properties
						)
					)
				)
			;

			// check if a constant is defined
			if (
				defined(
					strtoupper(
						PREFIX_TEMPLATE.PREFIX_BLOCK
					).$constant_name
				)
			)

				// get the current block template
				$template_name = constant(
					strtoupper(
						PREFIX_TEMPLATE.PREFIX_BLOCK
					).$constant_name
				);

			if (
				! (
				   $cached = $template_engine->is_cached(
						$template_name,
						$cache_id
					)
				)
			)
			{
				// check the properties
				if ( count( $properties ) != 0 )

					// loop on properties
					foreach ( $properties as $property => $value )

						// assign the current property
						$template_engine->assign( $property, $value );
			}

			if ( isset( $template_name ) )

				// set the view
				$view = $template_engine->fetch( $template_name, $cache_id );

			// clear all cache
			$template_engine->clear();
		}

		// return the view
		return $view;
	}

    /**
    * Build a content
    *
    * @param    string    	$identifier		identifier
    * @param	string		$entity_type	entity type
    * @param	mixed		$constraints	constraints
    * @return   string   	content
    */
    public static function buildContent(
		$identifier,
		$entity_type = ENTITY_CONTENT,
		$constraints = NULL
	)
    {
		global $class_application;

		// declare the default content
		$panel_content = '';

		// set the content manager class name
		$class_content_manager = self::getContentManagerClass();

		// set the dumper class name
		$class_dumper = self::getDumperClass();

		// set the entity class name
		$class_entity = self::getEntityClass();

		// set the flag manager name
		$class_flag_manager = self::getFlagManagerClass();

		// set the insight class name
		$class_insight = self::getInsightClass();

		// set the interceptor class name
		$class_interceptor = self::getInterceptorClass();

		// set the media manager class name
		$class_media_manager = self::getMediaManagerClass();

		// set the template engine class name		
		$class_template_engine = self::getTemplateEngineClass();

		// set the user handler class name
		$class_user_handler = self::getUserHandlerClass();

		// set the default content
		$content = '';

		// construct a new instance of the template engine
		$template_engine = new $class_template_engine();

		// construct a new view builder
		$view_builder = new self();

		// get the member DOM
		$_dom = &$view_builder->getDOM();

		// get the member DOMDocument object
		$document = &$_dom[ENTITY_DOCUMENT];

		// switch from the entity type
		switch ( $entity_type )
		{
			case ENTITY_CONTENT:

				// check if a member is logged in
				if ( $class_user_handler::anybodyThere() )
				{
					// load items
					$item_collection =
						$class_content_manager::getList(
							DOCUMENT_TYPE_XHTML,
							0,
							0
						)
					;
					
					// get the default border
					$border = $class_interceptor::getDefaultBorder();

					if (isset($item_collection->count))

						// get the content from the appropriate manager
						$_document = $class_content_manager::getDocument(
							DOCUMENT_TYPE_XHTML,
							$border,
							null != $constraints ? $constraints : null,
							$border - PAGINATION_COUNT_PER_PAGE_DOCUMENT
						);

					$start_position_div =
						strpos( $_document, '<div id="main">' ) +
							strlen('<div id="main">')
					;

					$length_div =
						strpos( $_document, '<div id="tools">' ) -
							$start_position_div
					;

					// skip tools
					$document_restriction = substr(
						$_document,
						$start_position_div,
						$length_div
					);

					$start_position_a =
						strpos( $document_restriction, '/h1>' ) + 4
					;

					$end_position_a =
						strpos(
							$document_restriction,
							'Get Feed</a>'
						) + strlen( 'Get Feed</a>' )
					;

					// skip link to feed 
					$document_restriction = substr(
							$document_restriction,
							0,
							$start_position_a
						).
						substr(
							$document_restriction,
							$end_position_a,
							-1
					);

					$start_position_comments =
						strpos(
							$document_restriction,
							'<h2 id="comments_heading"'
						)
					;

					$end_position_comments =
						strpos( $document_restriction, 'Report This</a>' ) +
						strlen( 'Report This</a>' )
					;

					// skip public comments
					$document_restriction = substr(
							$document_restriction,
							0,
							$start_position_comments
						).
						'<div>'.
						substr(
							$document_restriction,
							$end_position_comments,
							-1
					);

					// remove tiny mce script
					$document_restriction = preg_replace(
						'/<script type="text\/javascript" '.
						'src="\/js\/lib\/tiny-mce\/tiny_mce\.js\?[0-9]*">/', 
						'',
						$document_restriction
					);

					// remove all images
					$document_restriction = preg_replace(
						'/<img [^>]*>/', 
						'',
						$document_restriction
					);

					$element_div = $document->createElement( HTML_ELEMENT_DIV );

					$element_div->setAttribute(
						HTML_ATTRIBUTE_CLASS,
						STYLE_CLASS_CONTENT
					);

					// construct a new DOMText object
					$text_node = new DOMText();
			
					// append data to the DOMText object
					$text_node->appendData('{document}');

					$element_div->appendChild($text_node);
					
					$_content = $document->saveXML($element_div);

					$content = str_replace(
						'{document}',
						$document_restriction,
						$_content
					);

					// get the default border
					$border = $class_interceptor::getDefaultBorder();

					if ( isset( $item_collection->count ) )
					{
						$class_interceptor::updateOuterBorder(
							ceil(
								$item_collection->count
								/ PAGINATION_COUNT_PER_PAGE_DOCUMENT_XHTML
							)
						);

						// check the provided border
						if (
							$border * PAGINATION_COUNT_PER_PAGE_DOCUMENT_XHTML >
								$item_collection->count
						)

							$border = $border % $item_collection->count;
					}

					// load items
					$items = 

					// set the placeholder value
					$placeholder_value = $class_content_manager::getList(
						DOCUMENT_TYPE_XHTML,
						$border * PAGINATION_COUNT_PER_PAGE_DOCUMENT_XHTML,
						PAGINATION_COUNT_PER_PAGE_DOCUMENT_XHTML
					)->items;

					// set the protected placeholder
					self::setPlaceholder( $placeholder_value );

					// set a property
					self::setProperty( ENTITY_SLICE, $items );
				}
				else

					// jump to the root index
					$class_application::jumpTo( PREFIX_ROOT );

					break;

			case ENTITY_ROUTE:

				if ( $constraints == NULL )

					switch ( $identifier )
					{
						case ROUTE_WONDERING:

							// check if a member is logged in
							if (
								$member_qualities =
									$class_user_handler::anybodyThere()
							)
							{
								// get the default border
								$border = $class_interceptor::getDefaultBorder();

								// declare the default photograph contents
								$photograph_contents = '';
	
								$member_identifier =
									$member_qualities->{ROW_MEMBER_IDENTIFIER}
								;
	
								// load photographs
								$item_collection =
									$class_media_manager::loadPhotosByAuthorId(
										//$member_identifier,
										AUTHOR_IDENTIFIER_SHAL,
										FALSE
									)
								;
	
								// load photos by author identifier
								$photos = 
	
								// set the placeholder value
								$placeholder_value = array_slice(
									$item_collection,
									$border *
										PAGINATION_COUNT_PER_PAGE_PHOTOGRAPH -
											PAGINATION_COUNT_PER_PAGE_PHOTOGRAPH,
									PAGINATION_COUNT_PER_PAGE_PHOTOGRAPH,
									TRUE
								);

								$flags = $class_flag_manager::getFlags(
									array( 'usr_id' => $member_identifier )
								);

								$context = array(
									$border,
									$item_collection,
									$flags,
									$member_identifier
								);
		
								while ( list( $id, ) = each( $photos ) )
								
									$context[] = $class_insight::loadThread(
										$id,
										$class_entity::getByName(
											CLASS_PHOTOGRAPH
										)->{PROPERTY_ID}
									);

								reset( $photos );

								$cache_id = md5( serialize( $context ) );

								$template_name = TPL_BLOCK_CONTENT;

								if (
									! (
										$cached =
											$template_engine->is_cached(
												$template_name,
												$cache_id
											)
									)
								)
								{
									$class_interceptor::updateOuterBorder(
										ceil(
											count(
												$item_collection
											) /
												PAGINATION_COUNT_PER_PAGE_PHOTOGRAPH
										)
									);
		
									// check the provided border
									if (
										$border *
											PAGINATION_COUNT_PER_PAGE_PHOTOGRAPH  >
												count( $item_collection )
									)
		
										$border = $border % count( $item_collection );

									// set a property
									self::setProperty( ENTITY_SLICE, $photos );
		
									// set the protected placeholder
									self::setPlaceholder( $placeholder_value );
	
									$count = 0;
		
									// loop on photos
									foreach ( $photos as $photo_id => $photo )
									{
										if (
											$photo->getStatus() ==
												PHOTOGRAPH_STATUS_ENABLED
										)
										{
											// declare a new instance of the standard class
											$resource = new stdClass();
			
											// declare a new instance of the standard class
											$properties = new stdClass();
			
											// get the photograph key
											$properties->{PROPERTY_KEY} =
												sha1(
													COOKIE_MEMBER_IDENTIFER
												).
												sha1(
													$member_qualities->{ROW_MEMBER_IDENTIFIER}
												).
												sha1(
													$member_qualities->{ROW_MEMBER_USER_NAME}
												)
											;
			
											// get the photograph height
											$properties->{PROPERTY_HEIGHT} =
												$photo->getHeight()
											;
			
											// get the photograph width
											$properties->{PROPERTY_WIDTH} =
												$photo->getWidth()
											;
			
											// fetch a photograph
											$photograph_contents =
												$class_application::fetchPhotograph(
													$photo_id,
													$properties
												)
											;
			
											$resource->{PROPERTY_INSTANCE} =
												$photo
											;
			
											$resource->{PROPERTY_CONTENT} =
												$photograph_contents
											;
			
											$panel_content .= self::buildPanel(
												$resource
											);

											$count++;										
										}
									}

									// assign the content to the template
									$template_engine->assign(
										ENTITY_CONTENT,
										$panel_content
									);
								}
								else
								{
									$photos = 
		
									// set the placeholder value
									$placeholder_value = array_slice(
										$item_collection,
										$border *
											PAGINATION_COUNT_PER_PAGE_PHOTOGRAPH -
												PAGINATION_COUNT_PER_PAGE_PHOTOGRAPH,
										PAGINATION_COUNT_PER_PAGE_PHOTOGRAPH,
										TRUE
									);

									// set a slice of photos persistent
									self::setProperty( ENTITY_SLICE, $photos );		
								}

								// fetch the content
								$content = $template_engine->fetch(
									$template_name,
									$cache_id
								);
							}
							else
	
								// jump to the root index
								$class_application::jumpTo( PREFIX_ROOT );
	
								break;
					}
				else if ( $constraints == ACTION_DISPLAY_DOCUMENT )
				{

				}

					break;				
		}

		// return the content
		return $content;
	}

    /**
    * Build a dialog
    *
    * @param    mixed   	$dialog_identifier  dialog identifier
    * @param	array		$configuration		configuration properties
    * @param	mixed		$context			context
    * @return   string   	dialog
    */
    public static function buildDialog(
		$dialog_identifier,
		$configuration = NULL,
		$context = NULL
	)
    {
		global $verbose_mode;

		// set the dumper class name
		$class_dumper = self::getDumperClass();

		// set the interceptor class name
		$class_interceptor = self::getInterceptorClass();

		// set the template engine class name
		$class_template_engine = self::getTemplateEngineClass();

		// set the view builder class name
		$class_view_builder = __CLASS__;

        // declare a default dialog view
        $dialog_view = '';

        // declare an empty array of parameters
        $parameters = array();

        // construct a new Smarty object
        $template_engine = new $class_template_engine();

		$build_div = function() use ( $class_view_builder )
		{
			// construct a new view builder
			$view_builder = new $class_view_builder();
	
			// get the member DOM
			$_dom = &$view_builder->getDOM();
	
			// get the member DOMDocument object
			$document = &$_dom[ENTITY_DOCUMENT];
	
			// create an div element
			$element_div = $document->createElement( HTML_ELEMENT_DIV );
	
			// set the value of the class attribute
			$element_div->setAttribute(
				HTML_ATTRIBUTE_CLASS,
				STYLE_CLASS_FEEDBACK
			) ;
	
			return array( $document, $element_div );
		};

		if ( is_string( $dialog_identifier ) )

			// switch from a form identifier
			switch ( $dialog_identifier )
			{
				case AFFORDANCE_SIGN_IN:
	
					// set the administration flag
					$administration = FALSE;
	
					// set the member class name
					$class_member = CLASS_MEMBER;
	
					// 	declare empty arrays of
					//	actions
					//	access keys
					//	affordances
					// 	dialogs
					//	tooltips
					$parameters[PLACEHOLDER_ACTIONS] =
					$parameters[PLACEHOLDER_ACCESSKEYS] = 
					$parameters[PLACEHOLDER_AFFORDANCES] =
					$parameters[PLACEHOLDER_DIALOGS] =
					$parameters[PLACEHOLDER_LINKS] =
					$parameters[PLACEHOLDER_TOOLTIPS] = array();
	
					// check the page property of the provided configuration
					if (
						! empty( $configuration[PROPERTY_PAGE] ) &&
						$configuration[PROPERTY_PAGE] == PAGE_OVERVIEW
					)
	
						$administration = TRUE;
	
					// get the qualities of a logged in member
					$qualities = $class_member::getQualities( $administration );
	
					// set the first dialog parameter
					$parameters[PLACEHOLDER_DIALOGS][0] = USER_DIALOG_WELCOME;
	
					// set the second dialog parameter
					$parameters[PLACEHOLDER_DIALOGS][1] =
							isset($qualities->{ROW_MEMBER_USER_NAME})
						?
							(
								! $administration
							?
								$qualities->{ROW_MEMBER_USER_NAME}
							:
								' '.USER_ADMINISTRATOR
							)
						:
							''
					;
	
					// set the second dialog parameter
					$parameters[PLACEHOLDER_TOOLTIPS][DIALOG_MEMBER_PROFILE] =
						CONTENT_AFFORDANCE_GET_ACCESS_TO." ".
						CONTENT_ADJECTIVE_POSSESSIVE." ".
						CONTENT_DIALOG_ACCOUNT
					;
	
					// set the third dialog parameter
					$parameters[PLACEHOLDER_DIALOGS][2] = "!";
	
					// declare an action URI
					$parameters[PLACEHOLDER_ACCESSKEYS][AFFORDANCE_LOGOUT] =
						ACCESS_KEY_AFFORDANCE_LOGOUT
					;
	
					// declare an action URI
					$parameters[PLACEHOLDER_ACTIONS][] = URI_DIALOG_LOGOUT;
	
					// declare an action
					$parameters[PLACEHOLDER_AFFORDANCES][] = AFFORDANCE_LOGOUT;                
	
					if ( ! $administration )
	
						// declare a link to the member profile
						$parameters
							[PLACEHOLDER_LINKS]
								[DIALOG_MEMBER_ACCOUNT] =
							URI_AFFORDANCE_EDIT_MEMBER_ACCOUNT
						;
					else
	
						// declare a link to the member profile
						$parameters
							[PLACEHOLDER_FLAGS]
								[FLAG_TYPE_ADMINISTRATION] =
							TRUE
						;				

					$template_name = TPL_DIALOG_LOGOUT;

					$cache_id = md5(
						serialize(
							array( $parameters, $qualities )
						)
					);

					if (
						! ( $cached = $template_engine->is_cached(
								$template_name,
								$cache_id
							)
						)
					)

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

					// fetch the template
					$dialog_view = $template_engine->fetch(
						$template_name,
						$cache_id
					);

						break;

				case ENTITY_FEEDBACK:

					$backlink = DIALOG_LINK_RETURN_TO_LOGIN_FORM;

					$href_attribute = PREFIX_ROOT;

					list( $document, $element_div ) = $build_div();

					// create an p element
					$element_p = $document->createElement( HTML_ELEMENT_P );

					if (
						is_array( $configuration ) &&
						isset( $configuration[ENTITY_MESSAGE] )
					)
					{	
						// construct a new DOMText object
						$text_node = new DOMText();
	
		
						// append data to the DOMText object
						$text_node->appendData( $configuration[ENTITY_MESSAGE] );
	
						// append a text node to the a node
						$element_p->appendChild( $text_node );
	
						// append the current paragraph to the div element
						$element_div->appendChild( $element_p );
					}

					if ( ! is_null( $context ) && is_string( $context ) )
					{
						$message_failure =

						$message_success = NULL;

						/**
						*
						* Reverse engineer the current form configuration
						* to retrieve links bound to the feedback
						* messages
						* 
						*/

						$stacks = array(
							array(
								PROPERTY_DESTINATION => &$message_failure,
								PROPERTY_STACK => array(
									ENTITY_FEEDBACK,
									PROPERTY_FAILURE,
									PROPERTY_MESSAGE
								)
							),
							array(
								PROPERTY_DESTINATION => &$message_success,
								PROPERTY_STACK => array(
									ENTITY_FEEDBACK,
									PROPERTY_SUCCESS,
									PROPERTY_MESSAGE
								)
							)
						);

						self::getFeedbackMessages(
							$stacks,
							$context
						);

						$success = FALSE;

						if ( isset( $configuration[PROPERTY_MESSAGE] ) )
						{
							if (
								$configuration[PROPERTY_MESSAGE] !==
									$message_failure
							)
								
								$success = PROPERTY_UNDEFINED;

							else if (
								$configuration[PROPERTY_MESSAGE] ==
									$message_success
							)
							
								$success = TRUE;
						}

						if (
							in_array(
								$success,
								array( FALSE, TRUE ),
								TRUE
							)
						)
						{
							$backlinks = array();

							$links = self::getFeedbackLinks( $context );
							
							while ( list( $index, $properties ) = each( $links ) )
							{
								if ( isset( $properties[PROPERTY_NAME] ) )
								{
									$constant_name =
										strtoupper( 
											SUBSTR( PREFIX_FORM, 0, -1 ).'_'.
												PREFIX_LINK.'_'.
													str_replace(
														'.',
														'_',
														$context
													).'_'.
														$properties
															[PROPERTY_NAME]
										)
									;								
								
									$backlinks[$index] = array(
										PROPERTY_TEXT =>
											(
												defined( $constant_name )
											?
												constant( $constant_name )
											:
												$index
											),
										HTML_ATTRIBUTE_HREF => 
											$class_interceptor::dereference(
												substr( PREFIX_FORM, 0, - 1 ).'.'.
												str_replace(
													'.',
													'_',
													$properties[PROPERTY_TARGET]
												)
											)
									);
								}
								else

									throw new Exception(
										sprintf(
											EXCEPTION_UNDEFINED_ENTITY,
											ENTITY_PROPERTY
										).' ('.PROPERTY_NAME.') '
									);

							}
				
							if ( $success === TRUE )
							
								$property_selected = PROPERTY_SUCCESS;
							else 

								$property_selected = PROPERTY_FAILURE;

							$backlink =
								$backlinks
									[$property_selected]
										[PROPERTY_TEXT]
							;

							$href_attribute =
								$backlinks
									[$property_selected]
										[HTML_ATTRIBUTE_HREF]
							;
						}
					}

					// create an a element
					$element_a = $document->createElement( HTML_ELEMENT_A );

					// append a text node to the a node
					$element_a->setAttribute( HTML_ATTRIBUTE_HREF, $href_attribute );

					// construct a new DOMText object
					$text_node = new DOMText();
			
					// append data to the DOMText object
					$text_node->appendData( $backlink );

					// append a text node to the a node
					$element_a->appendChild( $text_node );

					$element_div->appendChild( $element_a );					

					// save the xml document
					$dialog_view .= $document->saveXML( $element_div );

					if (
						is_array( $configuration ) &&
						isset( $configuration[ENTITY_LAYOUT] )
					)
					{
						$template_engine = parent::get_templates();

						if (
							is_array( $template_engine ) &&
							isset( $template_engine[BLOCK_HTML] ) &&
							isset( $template_engine[BLOCK_HTML][TEMPLATE_SOURCE] )
						)

							$dialog_view = str_replace(
								'{$body}',
								$dialog_view,
								$template_engine[BLOCK_HTML][TEMPLATE_SOURCE]
							);
					}

						break;
	
				default:

					throw new \Exception(
						EXCEPTION_UNDEFINED_FORM_IDENTIFIER.
							" ($dialog_identifier)."
					);
			}

		else if ( is_array( $dialog_identifier ) )
		{
			list( $document, $element_div ) = $build_div();

			// set the value of the class attribute
			$element_div->setAttribute(
				HTML_ATTRIBUTE_CLASS,
				STYLE_CLASS_FEEDBACK
			);

			if (
				isset( $dialog_identifier[STORE_MESSAGE] ) &&
				is_array( $dialog_identifier[STORE_MESSAGE] ) &&
				count( $dialog_identifier[STORE_MESSAGE]) 
			)

				while (
					list( $message_index, $message ) =
						each( $dialog_identifier[STORE_MESSAGE] )
				)
				{
					// create an p element
					$element_p = $document->createElement( HTML_ELEMENT_P );

					// construct a new DOMText object
					$text_node = new DOMText();
			
					// append data to the DOMText object
					$text_node->appendData( $message );

					// append a text node to the a node
					$element_p->appendChild( $text_node );

					// append the current paragraph to the div element
					$element_div->appendChild( $element_p );
				}

			if (
				isset( $dialog_identifier[STORE_LINKS] ) &&
				is_array( $dialog_identifier[STORE_LINKS] ) &&
				count( $dialog_identifier[STORE_LINKS] )
			)
			{
				// create an div element
				$element_div_links = $document->createElement( HTML_ELEMENT_DIV );

				// create a ul element
				$element_ul_links = $document->createElement( HTML_ELEMENT_UL );

				while (
					list( $link_index, $link ) =
						each( $dialog_identifier[STORE_LINKS] )
				)
				{
					if (
						defined( strtoupper( 'URI_ACTION_'.$link ) ) ||
						defined( strtoupper( 'URI_AFFORDANCE_'.$link ) )
					)
					{
						// create an li element
						$element_li = $document->createElement(HTML_ELEMENT_LI);
	
						// create an a element
						$element_a = $document->createElement(HTML_ELEMENT_A);
	
						// construct a new DOMText object
						$text_node = new DOMText();
				
						// append data to the DOMText object
						$text_node->appendData( ucfirst( $link ) );
	
						// append a text node to the a node
						$element_a->appendChild( $text_node );
	
						$href_attribute =
								defined( strtoupper( 'URI_ACTION_'.$link ) )
							?
								constant( strtoupper( 'URI_ACTION_'.$link ) )
							:
								constant( strtoupper( 'URI_AFFORDANCE_'.$link ) )
						;

						// append a text node to the a node
						$element_a->setAttribute(
							HTML_ATTRIBUTE_HREF,
							$href_attribute
						);

						// append the current paragraph to the li element
						$element_li->appendChild( $element_a );
						
						// append the li element to the list of links
						$element_ul_links->appendChild( $element_li );
					}
				}
	
				// append the list of links to the links div element
				$element_div_links->appendChild( $element_ul_links );

				// append the links div element of links to the div element
				$element_div->appendChild( $element_div_links );
			}
			
			// save the xml document
			$dialog_view .= $document->saveXML( $element_div );
		}

        // return a dialog view
        return $dialog_view;
    }

    /**
    * Build an editor
    *
    * @return   string   editor
    */	
	public static function buildEditor()
	{
		global $class_application;

		$class_template_engine = $class_application::getTemplateEngineClass();

		// contruct a new template engine 
		$template_engine = new $class_template_engine();
		
		// fetch a view
		$view = $template_engine->fetch(TPL_SANDBOX_EXTJS);

		// clear all cache
		$template_engine->clear();

		// return a view
		return $view;
	}


	/**
	* Build a field 
	*
	* @param	mixed	$context			context
	* @param	integer	$affordance_type	affordance type
	* @return	mixed
	*/
	public static function buildField(
		$context,
		$affordance_type = NULL
	)
	{
		/**
		* Extract affordances types
		*
		* @return	$default_affordance_type			default affordance type
		* @return	$affordance_type_select_options		select options affordance type
		*/
		extract( self::getAffordancesTypes() );
	
		// check the provided affordance type
		if ( is_null( $affordance_type ) )

			// set the default affordance type if required
			$affordance_type = $default_affordance_type;

		switch ( $affordance_type )
		{
			case $default_affordance_type:

				$callback_parameters = self::buildTextInputField( $context );
		
					break;

			case $affordance_type_select_options:

				$callback_parameters = self::buildOptionsField( $context );

					break;
		}
		
		return $callback_parameters;
	}

	/**
	* Build a field of type hidden
	*
	* @param	array	$context	field context
	* @return	mixed
	*/
	public static function buildFieldHidden( $context )
	{
		if ( ! is_array( $context ) || ! count( $context ))
		
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );
		else
		{
			/**
			* Extract the following variables
			* 
			* @return	array	$dom_nodes			array of DOMElement instances 
			* @return	object	$view_builder		instance of the View_Builder class
			*/
			extract( $context, EXTR_REFS );			

			// get the member DOM
			$_dom = &$view_builder->getDOM();
	
			// get the configuration
			$configuration = self::getConfiguration();
	
			// get the member DOMDocument object
			$document = &$_dom[ENTITY_DOCUMENT];
	
			// create a new input element
			$input_element =
				$document->createElement( HTML_ELEMENT_INPUT );
	
			// set the value of the input element type attribute
			$input_element->setAttribute(
				HTML_ATTRIBUTE_TYPE,
				strtolower(
					substr(
						FORM_FIELD_TYPE_HIDDEN,
						strlen(ENTITY_FIELD)
					)
				)
			);
	
			// check the form identifier property
			// of the current configuration
			if ( ! empty( $configuration[PROPERTY_FORM_IDENTIFIER] ) )
			{
				// set the value of the input element type attribute
				$input_element->setAttribute(
					HTML_ATTRIBUTE_NAME,
					FIELD_NAME_AFFORDANCE
				);							
	
				// set the value of the input element value attribute
				$input_element->setAttribute(
					HTML_ATTRIBUTE_VALUE,
					$configuration[PROPERTY_FORM_IDENTIFIER]
				);
			}
	
			$dom_nodes[1]->appendChild( $input_element );
		}
	}

    /**
    * Build a flowing data view
    *
    * @param	object	$route			route
    * @param	string	$block			block name
    * @param	boolean	$administrator	administrator flag
    * @param	mixed	$informant		informant
    */	
	public static function buildFlowingDataView(
		$route,
		$block = BLOCK_HTML,
		$administrator = FALSE,
		$informant = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_db = $class_application::getDbClass();

		$class_dumper = $class_application::getDumperClass();

		$class_form = $class_application::getFormClass(NAMESPACE_SEMANTIC_FIDELITY);

		$class_template_engine = $class_application::getTemplateEngineClass();

		$exception = NULL;

		$rows = array();
		
		$tags = array( 0 => array() );

		$template_name = TPL_FLOWING_DATA;

		// construct a new template engine
		$template_engine = new $class_template_engine();

		$view = '';

		if (
			! is_object( $route ) ||
			get_class( $route ) != CLASS_STANDARD_CLASS ||
			! isset( $route->{PROPERTY_FORM} ) ||
			! ( $form_id = $route->{PROPERTY_FORM} )
		)
		{
			if (
				!is_object( $route ) ||
				get_class( $route ) != CLASS_STANDARD_CLASS
			)
		
				$invalid_entity =  ENTITY_ROUTE;
			else 

				$invalid_entity =  ENTITY_FORM;

			$exception =  sprintf(
				EXCEPTION_INVALID_ENTITY,
				$invalid_entity
			);
		}
		else 

			$form = $class_form::getById( $form_id );

		$foreign_objects = $form->getForeignObjects();

		if (
			isset(
				$foreign_objects->{
					'('.PROPERTY_OBJECT.') '.
						CLASS_QUERY
				}
			)
		)
		{
			$queries = $foreign_objects->{
				'('.PROPERTY_OBJECT.') '.
					CLASS_QUERY
			};

			if ( is_array($queries) )
			{
				list( , $query ) = each( $queries );

				$query_fetch_flowing_data = $query->{PROPERTY_VALUE};

				if (
					is_string( $query_fetch_flowing_data ) &&
					$query_fetch_flowing_data
				)
				{
					$results_flowing_data = $class_db::query(
						$query_fetch_flowing_data
					);

					if (
						is_object( $results_flowing_data ) &&
						$results_flowing_data->num_rows
					)

						while (  $row = $results_flowing_data->fetch_object() )
						{
							if ( ! count ( $rows ) )
							{
								// extract the column headers
								// from the select clause aliases 
								while ( list( $name, $value ) = each( $row ) )
								{
									if ( ! isset( $rows[0] ) )

										$rows[0] = array();

									// remove tags from names
									if ( strpos( $name, '__' ) !== FALSE )
									{
										// extract tags from column aliases
										list( $options, $_name ) = explode(
											'__',
											$name
										);

										$collection_options = explode(
											'_', $options
										);

										$tag = $collection_options[
												count( $collection_options ) - 1
											];

										$name = $_name;

										if (
											count( $tags ) == 1 &&
											isset( $tags[0] )
										)

											unset($tags[0]);

										if ( ! isset( $tags[$tag] ) )

											$tags[$tag] = array();

										$tags[$tag][] = $name;
									}

									$rows[0][] =
										str_replace(
											'_',
											' ',
											$name
										)
									;
								}
	
								reset( $row );
							}

							$properties = array();

							// extract the column headers
							// from the select clause aliases 
							while (
								( list( $name, $value ) = each( $row ) ) &&
								! in_array( $name , $properties )
							)
							{
								$properties[] = $name;

								if ( strpos( $name, '__' ) !== FALSE )
								{
									// extract tags from column aliases
									list( $options, $_name ) = explode(
										'__',
										$name
									);

									$collection_options = explode(
										'_', $options
									);

									unset(
										$collection_options[
											count( $collection_options )  - 1
										]
									);

									while (
										list( , $accessor )
										   = each( $collection_options )
									)
									{
										if (
											is_scalar( $value ) &&
											! is_null( $value )
										)
										{
											$value = call_user_func_array(
												$accessor,
												array( $value )
											);
										}
										// cast the value 
										else if ( $accessor == PROPERTY_OBJECT )
											
											$value = ( object ) $value ;

										// check if the value
										// has some predefined methods
										else if (
											is_object( $value ) &&
											( $methods =
												get_class_methods(
													get_class( $value )
												)
											) &&	  
											in_array( $accessor, $methods )
										)

											$value = $value->$function;

										// check if the value has properties
										else if (
											is_object( $value ) &&
											isset( $value->$accessor )
										)
										
											$value = $value->$accessor;
									}
									
									reset( $collection_options );

									$row->$_name = $value;

									unset( $row->$name );
								}
								// preventing column indexing form being changed
								else
								{
									$row->{$name.'_'} = $value;

									unset( $row->$name );
									
									$row->$name = $value;

									unset( $row->{$name.'_'} );	
								}
							}
									

							reset( $row );

							if ( isset( $row->{PROPERTY_ID} ) )

								$rows[$row->{PROPERTY_ID}] = ( array ) $row ;
							else

								$rows[] = ( array ) $row ;
						}

					$cache_id = md5( serialize( $rows ) );

					$class_dumper::log(
						__METHOD__,
						array($tags, $rows)
					);

					if (
						!(
							$cached_contents = $template_engine->is_cached(
								$template_name,
								$cache_id
							)
						)
					)
					{
						// assign the rows
						$template_engine->assign( PLACEHOLDER_ROWS, $rows );

						// assign the tags
						$template_engine->assign( PLACEHOLDER_TAGS, $tags );						
					}

					// fetch a panel
					$view = $template_engine->fetch(
						$template_name,
						$cache_id
					);

					// clear the template cache
					$template_engine->clear();
				}
				else

					$exception = EXCEPTION_INVALID_ARGUMENT;
			}
		}

		if ( ! is_null( $exception ) )
		
			throw new Exception( $exception );

		if ( ! empty( $view ) )

			return $view;
	}

	/**
	* Build a form field
	* 
	* @param	array	$context	form field context 
	* @return	mixed
	*/
	public static function buildFormField( $context )
	{
		if (
			! is_array( $context ) ||
			! count( $context ) ||
			! isset( $context[PROPERTY_FIELD_CONTEXT] ) ||
			! isset( $context[PROPERTY_RESOURCES] )
		)

			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		$callback_parameters = array();

		/**
		* Extract affordances types
		*
		* @return	$default_affordance_type			default affordance type
		* @return	$affordance_type_select_options		select options affordance type
		*/
		extract( self::getAffordancesTypes() );

		/**
		* Extract the following variables
		* 
		* @return	array	$context_field
		* @return	array	$resources
		*/
		extract( $context );

		/**
		* Extract the following variables
		* 
		* @return	array	$dom_nodes			array of DOMElement instances 
		* @return	object	$field				field
		* @return	integer	$field_index		field index
		* @return	integer	$first_field_index	first field index
		* @return	mixed	$mode_preview 		preview mode flag
		* @return	object	$view_builder		instance of the View_Builder class
		*/
		extract( $context_field, EXTR_REFS );

		/**
		* Check resources
		*
		* @return	object	$field_handler		field handler
		* @return	string	$form_identifier 	form identifier
		*/
		extract( self::checkResources( $resources ) );

		// append the field handler to the field context
		$context_field[PROPERTY_FIELD_HANDLER] = $field_handler;

		// get fields
		$fields = $field_handler->get_fields();

		$field_type = strtolower(
			str_replace(
				PREFIX_FIELD.'_',
				CHARACTER_EMPTY_STRING,
				strtolower( get_class( $fields[$field_index] ) )
			)
		);

		if (
			$field_type == FIELD_TYPE_CHECKBOX ||
			$field_type == FIELD_TYPE_RADIO ||
			$field_type == FIELD_TYPE_SELECT
		)

			/**
			*
			* Set affordance to build a field with options and
			* append it to the fieldset
			*
			*/						
			$affordance_type = $affordance_type_select_options;

		else if (
			$field->{PROPERTY_NAME} != FIELD_NAME_AFFORDANCE &&
			! $mode_preview ||
			(
				$mode_preview &&
				$field_type != FIELD_TYPE_SUBMIT &&
				$field_type != FIELD_TYPE_BUTTON
			)
		)

			/**
			*
			* Set affordance to build a text input field and
			* append the fields to the fieldset
			*
			*/	
			$affordance_type = $default_affordance_type;
		
		if ( isset( $affordance_type ) )

			// build a field 
			$callback_parameters = self::buildField(
				$context_field,
				$affordance_type
			);

		// check if the current field has the first index
		else if ( $first_field_index === $field_index )
		{
			if ( isset( $context_field[PROPERTY_FIELD_FIRST_INDEX] ) )
			
				unset( $context_field[PROPERTY_FIELD_FIRST_INDEX] );

			$callback_parameters = self::buildFieldHidden( $context_field );
		}

		return $callback_parameters;
	}

    /**
    * Build a list
    *
    * @param	mixed	$list	list
    * @return   string 	list
    */	
	public static function buildList($list)
	{
		// construct a new view builder
        $view_builder = new self();

        // get the member DOM
        $_dom = &$view_builder->getDOM();

        // get the member DOMDocument object
        $document = &$_dom[ENTITY_DOCUMENT];

		// create a ul element
		$element_ul = $document->createElement(HTML_ELEMENT_UL);

		foreach ($list->items as $index => $item)
		{
			// create a li element
			$element_li = $document->createElement(HTML_ELEMENT_LI);

			// create an a element
			$element_a = $document->createElement(HTML_ELEMENT_A);

			// construct a new DOMText object
			$text_node = new DOMText();

			// append data to the DOMText object
			$text_node->appendData($item->title);

			// append a text node to the a node
			$element_a->appendChild($text_node);

			// append a href attribute to the a node
			$element_a->setAttribute(
				HTML_ATTRIBUTE_HREF,
				URI_ACTION_DISPLAY_DOCUMENT.'-'.$item->id
			);

			$element_li->appendChild($element_a);

			$element_ul->appendChild($element_li);
		}

		return $document->saveXML($element_ul);
	}

    /**
    * Build a menu
    *
    * @param   	array	$menu_items			menu items
    * @param	string	$block				block
    * @param	array	$configuration		configuration properties
    * @return  	string	view
    */	            
    public static function buildMenu(
		$menu_items,
		$block = BLOCK_LEFT,
		$configuration = NULL
	)
    {
		global $verbose_mode;

		$class_dumper = self::getDumperClass();

		// set the interceptor class name
		$class_interceptor = self::getInterceptorClass();

		// set the member class name
		$class_member = self::getMemberClass();

		// get member qualities
		$qualities = $class_member::getQualities();

		// construct a new view builder
        $view_builder = new self();

        // get the member DOM
        $_dom = &$view_builder->getDOM();

        // get the member DOMDocument object
        $document = &$_dom[ENTITY_DOCUMENT];

		$view = '';

		if ( $block != BLOCK_LEFT )

			// create a div element
			$element_div = $document->createElement( HTML_ELEMENT_DIV );

        // create a ul element
        $element_ul = $document->createElement( HTML_ELEMENT_UL );

		if (
			is_array( $menu_items ) &&
			count( $menu_items ) != 0
			||
			!empty( $configuration->{ROW_KEYWORDS} )
		)
		{
			if ( empty( $configuration ) )

				foreach ( $menu_items as $menu_index => $menu_item )
				{
					if ( ! empty( $menu_item[LAYOUT_TITLE_LEVEL_1] ) )
					{
						// create a li element
						$element_li = $document->createElement( HTML_ELEMENT_LI );
				
						// create an a element
						$element_a = $document->createElement( HTML_ELEMENT_A );
	
						// construct a new DOMText object
						$text_node = new DOMText();
	
						// append data to the DOMText object
						$text_node->appendData( $menu_item[LAYOUT_TITLE_LEVEL_1] );
	
						// append a text node to the a node
						$element_a->appendChild( $text_node );
	
						// set the anchor href attribute
						$element_a->setAttribute(
							HTML_ATTRIBUTE_HREF,
							CHARACTER_SHARP.
							str_replace(
								array('-', '.'),
								'_',
								$menu_item[PROPERTY_AFFORDANCE]
							)
						);
	
						// set the anchor element stylesheet class
						$element_a->setAttribute(
							HTML_ATTRIBUTE_CLASS,
							STYLE_CLASS_MENU_ITEM
						);
	
						if ( $block == BLOCK_LEFT )
	
							// set the list node stylesheet class
							$element_ul->setAttribute(
								HTML_ATTRIBUTE_CLASS,
								STYLE_CLASS_MENU_LEFT
							);
						else 
	
							// set the list node stylesheet class
							$element_ul->setAttribute(
								HTML_ATTRIBUTE_CLASS,
								STYLE_CLASS_MENU_RIGHT
							);
	
						// append a li element to the list node
						$element_li->appendChild( $element_a );
	
						// set the current li element stylesheet class
						$element_li->setAttribute(
							HTML_ATTRIBUTE_CLASS,
							STYLE_CLASS_MENU_ITEM
						);
	
						// append a li element to the list node
						$element_ul->appendChild( $element_li );
					}
				}
			else
			{
				// get the default border
				$border = $class_interceptor::getDefaultBorder();

				if (
					isset($configuration->{ROW_KEYWORDS})
					&& !isset($configuration->{ACTION_SEARCH})
					||
					isset($configuration->{ACTION_SEARCH}) &&
					!isset($configuration->{BORDER_DEFAULT})
				)

					// get the default border
					list( $start_border, $item ) = each( $configuration->items );

				// check the configuration search action 
				else if (
					isset( $configuration->{ACTION_SEARCH} ) &&
					isset( $configuration->{BORDER_DEFAULT} )
				)
				{
					// set the current border
					$border = $configuration->{BORDER_DEFAULT};

					$start_border = 1;
				}

				if ( $block == BLOCK_LEFT )

					// set the list node stylesheet class
					$element_ul->setAttribute(
						HTML_ATTRIBUTE_CLASS,
						STYLE_CLASS_MENU_LEFT
					);
				else 

					// set the list node stylesheet class
					$element_ul->setAttribute(
						HTML_ATTRIBUTE_CLASS,
						STYLE_CLASS_MENU_RIGHT
					);

				if (
					isset( $start_border ) && $start_border != 1 &&
					isset( $qualities->{ROW_GROUP_IDENTIFIER} ) &&
					$qualities->{ROW_GROUP_IDENTIFIER} == GROUP_ADMINISTRATOR
				)
				{
					// create a li element
					$element_li = $document->createElement( HTML_ELEMENT_LI );
			
					// create an a element
					$element_a = $document->createElement( HTML_ELEMENT_A );
	
					// construct a new DOMText object
					$text_node = new DOMText();
	
					// append data to the DOMText object
					$text_node->appendData(DIALOG_REMOVE_ITEM);
	
					// append a text node to the a node
					$element_a->appendChild($text_node);
	
					// set the anchor href attribute
					$element_a->setAttribute(
						HTML_ATTRIBUTE_HREF,
						URI_AFFORDANCE_REMOVE."-".
						$border
					);
	
					// set the anchor element stylesheet class
					$element_a->setAttribute(
						HTML_ATTRIBUTE_CLASS,
						STYLE_CLASS_MENU_ITEM
					);
	
					// append a li element to the list node
					$element_li->appendChild( $element_a);
	
					// set the current li element stylesheet class
					$element_li->setAttribute(
						HTML_ATTRIBUTE_CLASS,
						STYLE_CLASS_MENU_ITEM
					);
	
					// append a li element to the list node
					$element_ul->appendChild( $element_li );
				}
			}
			
			if ( $block != BLOCK_LEFT )
			{
				$first_page = FALSE;

				$last_page = FALSE;				

				if ( empty( $border ) )

					// get the default border
					$border = $class_interceptor::getDefaultBorder();

				$position = $class_interceptor::getPosition( POSITION_PREVIOUS );

				if ( ! isset( $configuration->{ROW_KEYWORDS} ) )

					// get the outer border
					$outer_border = $class_interceptor::getOuterBorder();

				// check if the search action is enabled				
				else if (
					isset( $configuration->{ACTION_SEARCH} ) &&
					isset( $configuration->{BORDER_DEFAULT} )
				)

					$outer_border = ceil(
						count($configuration->items ) /
						PAGINATION_COUNT_PER_PAGE_SEARCH_RESULTS
					);
				else
				{
					end( $configuration->items );
					list( $outer_border) = each( $configuration->items );
					reset( $configuration->items );

					$keys = array_keys($configuration->items);
				}

				if (
					! isset( $configuration->{ROW_KEYWORDS} ) &&
					$border == 1 ||
					isset( $start_border) && $border == $start_border
				)
				
					$first_page = true;

				if ($border == $outer_border)
				
					$last_page = true;

				// set the div element class attribute
				$element_div->setAttribute(
					HTML_ATTRIBUTE_ID,
					STYLE_CLASS_BLOCK_MENU_RIGHT
				);

				// append a ul element to the div node
				$element_div->appendChild( $element_ul );

				$element_navigation =
					$document->createElement( HTML_ELEMENT_UL )
				;

				$element_previous_page =
					$document->createElement( HTML_ELEMENT_A )
				;

				$element_last_page =
					$document->createElement( HTML_ELEMENT_A )
				;

				$element_next_page =
					$document->createElement( HTML_ELEMENT_A )
				;

				$element_first_page =
					$document->createElement( HTML_ELEMENT_A )
				;

				$element_search_results_page =
					$document->createElement( HTML_ELEMENT_A )
				;

				if (!isset( $configuration->{ROW_KEYWORDS} ) )

					$href_attribute_first_page = $position;

				// check if the search action is enabled
				else if (
					isset( $configuration->{ACTION_SEARCH} ) &&
					isset( $configuration->{BORDER_DEFAULT} )
				)

					$href_attribute_first_page = URI_CONTENT_SEARCH_RESULTS;

				else if (isset($start_border))

					$href_attribute_first_page =
						PREFIX_ROOT.
							self::rewrite(
								$configuration->items[$start_border]->title
							).
								'-'.GET_DOCUMENT_IDENTIFIER_REWRITTEN.
									$start_border
					;

				$element_first_page->setAttribute(
					HTML_ATTRIBUTE_HREF,
					$href_attribute_first_page
				);

				if ( ! isset( $configuration->{ROW_KEYWORDS} ) )

					$href_attribute_previous_page = $position."-".( $border - 1 );

				else if (
					isset( $configuration->{ACTION_SEARCH} ) &&
					isset( $configuration->{BORDER_DEFAULT} ) &&
					$border - 1 != 1
				)
					
					$href_attribute_previous_page =
						URI_CONTENT_SEARCH_RESULTS."-".
						( $border - 1 )
					;

				else if (
					isset( $keys ) &&
					false !== array_search($border, $keys ) &&
					isset( $keys[ array_search( $border, $keys ) - 1 ] ) &&
					isset( $configuration->items[
						$keys[ array_search( $border, $keys ) - 1 ]
					])
				)

					if (isset($keys[array_search($border, $keys) - 1]))
					{
						$previous_id = $configuration->items[
							$keys[array_search($border, $keys) - 1]
						]->{PROPERTY_ID};

						$rewritten_previous_title = self::rewrite($configuration->items[
							$keys[array_search($border, $keys) - 1]
						]->title);
		
						$url_words = explode('-', $rewritten_previous_title);

						$shortened_url = array_slice($url_words, 0, 5);

						while (strlen($shortened_url[count($shortened_url) - 1]) == 1)
		
							array_pop($shortened_url);

						$rewritten_previous_url = implode('-', $shortened_url);

						$href_attribute_previous_page =
							PREFIX_ROOT.
							$rewritten_previous_url.
							'-'.GET_DOCUMENT_IDENTIFIER_REWRITTEN.
							$previous_id
						;
					}

				if ( ! empty( $href_attribute_previous_page ) )

					$element_previous_page->setAttribute(
						HTML_ATTRIBUTE_HREF,
						$href_attribute_previous_page
					);

				if ( ! isset($configuration->{ROW_KEYWORDS} ) )

					$href_attribute_value = $position."-".$outer_border;

				// check if the search action is enabled
				else if (
					isset($configuration->{ACTION_SEARCH}) &&
					isset($configuration->{BORDER_DEFAULT})
				)

					$href_attribute_value = 
						URI_CONTENT_SEARCH_RESULTS.
							'-'.
								$outer_border
					;
				else 
						
					$href_attribute_value =
						PREFIX_ROOT.
							self::rewrite($configuration->items[$outer_border]->title)
								.'-'.GET_DOCUMENT_IDENTIFIER_REWRITTEN.
									$outer_border
					;

				$element_last_page->setAttribute(
					HTML_ATTRIBUTE_HREF,
					$href_attribute_value
				);

				if (!isset($configuration->{ROW_KEYWORDS}))

					$href_attribute_next_page = $position."-".($border + 1);
				else if (
					isset($configuration->{ACTION_SEARCH}) &&
					isset($configuration->{BORDER_DEFAULT}) &&
					$border + 1 < $outer_border
				)
					
					$href_attribute_next_page = URI_CONTENT_SEARCH_RESULTS."-".($border + 1);

				else if (
					isset( $keys ) &&
					FALSE !== array_search( $border, $keys ) &&
					isset( $keys[array_search( $border, $keys ) + 1] ) &&
					isset( $configuration->items[
						$keys[array_search($border, $keys) + 1]
					] )
				)

					if ( isset( $keys[array_search( $border, $keys ) + 1] ) )
					{
						$next_id = $configuration->items[
							$keys[array_search( $border, $keys ) + 1]
						]->{PROPERTY_ID};

						$rewritten_next_title = self::rewrite(
							$configuration->items[
								$keys[array_search($border, $keys) + 1]
							]->title
						);

						$url_words = explode( '-', $rewritten_next_title );

						$shortened_url = array_slice( $url_words, 0, 5 );

					while (
						1 == strlen(
							$shortened_url[count($shortened_url ) - 1]
						)
					)
	
						array_pop( $shortened_url );
						
						$rewritten_next_url = implode( '-', $shortened_url );

						$href_attribute_next_page =
							PREFIX_ROOT.
								$rewritten_next_url.
									'-'.GET_DOCUMENT_IDENTIFIER_REWRITTEN.$next_id
						;
					}

				if ( ! empty( $href_attribute_next_page ) )

					$element_next_page->setAttribute(
						HTML_ATTRIBUTE_HREF,
						$href_attribute_next_page
					);

				if ( isset( $configuration->{ROW_KEYWORDS} ) )

					$element_search_results_page->setAttribute(
						HTML_ATTRIBUTE_HREF,
						URI_CONTENT_SEARCH_RESULTS
					);

				$element_navigation->setAttribute(
					HTML_ATTRIBUTE_CLASS,
					STYLE_CLASS_NAVIGATION
				);

				if ( ! empty( $configuration ) )
				{
					$element_first_page->setAttribute(
						HTML_ATTRIBUTE_ACCESSKEY,
						ACCESS_KEY_PAGE_FIRST
					);

					$element_last_page->setAttribute(
						HTML_ATTRIBUTE_ACCESSKEY,
						ACCESS_KEY_PAGE_LAST
					);

					$element_previous_page->setAttribute(
						HTML_ATTRIBUTE_ACCESSKEY,
						ACCESS_KEY_PAGE_PREVIOUS
					);

					$element_next_page->setAttribute(
						HTML_ATTRIBUTE_ACCESSKEY,
						ACCESS_KEY_PAGE_NEXT
					);

					if ( isset( $configuration->{ROW_KEYWORDS} ) )
	
						$element_search_results_page->setAttribute(
							HTML_ATTRIBUTE_ACCESSKEY,
							ACCESS_KEY_PAGE_SEARCH_RESULTS
						);
				}

				$node_text_first_page = new DOMText();

				$node_text_next_page = new DOMText();

				$node_text_last_page = new DOMText();

				$node_text_previous_page = new DOMText();

				// append data to the DOMText object
				$node_text_first_page->appendData( CONTENT_LINK_START );

				// append data to the DOMText object
				$node_text_last_page->appendData( CONTENT_LINK_END );

				// append data to the DOMText object
				$node_text_next_page->appendData(
					CONTENT_LINK_NEXT." ".CONTENT_NAVIGATION_PAGE
				);

				// append data to the DOMText object
				$node_text_previous_page->appendData(
					CONTENT_LINK_PREVIOUS." ".CONTENT_NAVIGATION_PAGE
				);

				if ( isset( $configuration->{ROW_KEYWORDS} ) )
				{
					$node_text_search_results = new DOMText();					

					// append data to the DOMText object
					$node_text_search_results->appendData(
						CONTENT_LINK_SEARCH_RESULTS
					);
				}
				
				if (
					$border != 1 && ! isset( $configuration->{ROW_KEYWORDS} ) ||
					isset( $start_border ) && $border != $start_border
				)
				{
					// append the first page text node to the first page element 
					$element_first_page->appendChild( $node_text_first_page );

					$element_item = $document->createElement( HTML_ELEMENT_LI );

					$element_item->appendChild( $element_first_page );

					$element_navigation->appendChild( $element_item );
				}

				if ( ! $first_page )
				{
					// append the previous page text node to the previous page element 
					$element_previous_page->appendChild( $node_text_previous_page );

					$element_item = $document->createElement( HTML_ELEMENT_LI );

					$element_navigation->appendChild( $element_previous_page );

					$element_navigation->appendChild( $element_item );
				}

				if ( ! $last_page )
				{
					// append the next page text node to the next page element 
					$element_next_page->appendChild( $node_text_next_page );

					$element_item = $document->createElement( HTML_ELEMENT_LI );

					$element_item->appendChild( $element_next_page );

					$element_navigation->appendChild( $element_item );
				}

				if ( $border != $outer_border )
				{
					// append the last page text node to the last page element 
					$element_last_page->appendChild( $node_text_last_page );

					$element_item = $document->createElement( HTML_ELEMENT_LI );

					$element_item->appendChild( $element_last_page );

					$element_navigation->appendChild( $element_item );
				}

				if ( isset( $configuration->{ROW_KEYWORDS} ) )
				{
					$element_search_results_page->appendChild( $node_text_search_results );

					$element_item = $document->createElement( HTML_ELEMENT_LI );

					$element_navigation->appendChild( $element_search_results_page );
				}

				$element_div->appendChild( $element_navigation );
			
				// save the xml document
				$view .= $document->saveXML( $element_div );
			}
			else 

				// save the xml document
				$view .= $document->saveXML( $element_ul );
		}

		return $view;
	}

	/**
	* Build input field with options
	*
	* @param	array	&$context options context
	* @return	nothing
	*/
	public static function buildOptionsField( &$context )
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$combo_box = FALSE;

		/**
		* Extract the following parameters
		*
		* @tparam	array	&$dom_nodes				array of DOMElements
		* @tparam	object	$field 					field
		* @tparam	object	$field_handler			field handler
		* @tparam	integer	$field_index			field index
		* @tparam	array	$html_elements			instances of the Element_Html class
		* @tparam	array	$instances_properties	properties of Element_Html instances
		* @tparam	array	$options				field options
		* @tparam	object	$view_builder			instance of the View_Builder class
		*/
		extract( self::checkFieldContext( $context, TRUE ), EXTR_REFS );

		/**
		*
		* checkbox and radio options are input HTML elements
		* select options are option HTML elements
		* 
		*/
		if ( $field->{PROPERTY_TYPE} === CLASS_FIELD_SELECT )
		{
			$combo_box = TRUE;

			$_option_element_type = HTML_ELEMENT_OPTION;
		}
		else if (
			in_array(
				$field->{PROPERTY_TYPE},
				array(
					CLASS_FIELD_CHECKBOX,
					CLASS_FIELD_RADIO
				)
			)
		)

			$_option_element_type = HTML_ELEMENT_INPUT;

		if (
			is_array( $options[$field->{PROPERTY_NAME}] ) &&
			$combo_box
		)
		{
			/**
			*
			* Retrieve first and last options keys
			*
			*/

			list( $first_option_key ) = each( $options[$field->{PROPERTY_NAME}] );
			reset( $options[$field->{PROPERTY_NAME}] );

			end( $options[$field->{PROPERTY_NAME}] );
			list( $last_option_key ) = each( $options[$field->{PROPERTY_NAME}] );
			reset( $options[$field->{PROPERTY_NAME}] );

			$container_field_index = $field_index + 2;
		}

		// loop on available options to build a DOM node
		// for each of them
		foreach (
			$options[$field->{PROPERTY_NAME}] as
				$key => $value
		)
		{
			$field_properties[2] = $key;

			if (
				! isset(
					$instances_properties[HTML_ELEMENT_INPUT]
					[$field->{PROPERTY_NAME}]
				)
			)
			{
				// Declare an empty elements properties container
				// for the current field option 
				$instances_properties[HTML_ELEMENT_INPUT]
					[$field->{PROPERTY_NAME}] = array()
				;

				// Declare an empty array of instances
				// of the class Element_Html
				// for the current field
				$html_elements[HTML_ELEMENT_INPUT]
					[$field->{PROPERTY_NAME}] = array();
			}

			if ( ! isset( $first_option_key ) || ( $key === $first_option_key ) )

				$container_element = $view_builder->buildDOMNode(
	
					$combo_box ? HTML_ELEMENT_SELECT : $_option_element_type,

					$field_properties
				);

			if ( $combo_box )

				$contained_element = $view_builder->buildDOMNode(
	
					$_option_element_type,
	
					$field_properties
				);

			if ( isset( $container_element->{PROPERTY_DOM_ELEMENT_TAG_NAME} ) )
			{
				
				if ( ! $combo_box )
				{
					// get field view
					$dom_nodes[( int )( $field_index + 2 )] =
						$container_element
					;
		
					// append nodes to a container (fieldset)
					$dom_nodes[1]->appendChild(
						$dom_nodes[( int )( $field_index + 2 )]
					);
				}
				else 
				{
					if ( $key === $first_option_key )
					{
						// get field view
						$dom_nodes[( int ) $container_field_index ] =
							$container_element
						;

						/**
						*
						* Retrieve a wrapped select HTML element
						*
						*/
						$element_select = $container_element
							->childNodes->item(1)
								->childNodes->item(0)
									->childNodes->item(1)
										->childNodes->item(1)
											->childNodes->item(0)
						;
					}

					// get field view
					$element_select
						->appendChild( $contained_element )
					;

					if ( $key === $last_option_key )

						// append nodes to a container (fieldset)
						$dom_nodes[1]->appendChild( $container_element );
				}
			}
		}

		$class_dumper::log(
			__METHOD__,
			array(
				forEachItem(
					$dom_nodes,
					function( $item ) {
						return $item->{PROPERTY_DOM_ELEMENT_TAG_NAME};
					}
				)
			)
		);
	}

    /**
    * Build a panel 
    *
    * @param	string		$resource		resource
    * @param	string		$resource_type	resource type	
    * @param	string		$panel_type		panel_type
    * @return   string 		panel
    */	
	public static function buildPanel(
		$resource,
		$resource_type = RESOURCE_PHOTOGRAPH,
		$panel_type = PANEL_TYPE_AFFORDANCE
	)
	{
		global $class_application;

		$class_dumper = self::getDumperClass();

		$class_entity = self::getEntityClass();

		$class_flag_manager = self::getFlagManagerClass();

		$class_insight = self::getInsightClass();

		$class_member = self::getMemberClass();

		$class_template_engine = self::getTemplateEngineClass();

		$class_user_handler = self::getUserHandlerClass();

		// set the default panel
		$panel = '';

		// construct a new template engine
		$template_engine = new $class_template_engine();

		// switch from the panel type
		switch ( $panel_type )
		{
			case PANEL_TYPE_AFFORDANCE:
			
				// switch from the resource type
				switch ( $resource_type )
				{
					case RESOURCE_PHOTOGRAPH:

						// check the provided resource
						if ( is_object( $resource ) )
						{
							if (
								$member_qualities =
									$class_user_handler::anybodyThere()
							)
							{
								// get the resource instance
								$instance = $resource->{PROPERTY_INSTANCE};

								// get the identifier
								$identifier = $instance->getId();

								$thread = $class_insight::getThread($identifier);

								$template_name = TPL_BLOCK_IMAGE_PANEL;

								$administrator_identifier =
									$class_member::getIdentifier( TRUE, FALSE );

								$member_identifier =
									$class_member::getIdentifier( FALSE, FALSE )
								;

								$flags = $class_flag_manager::getFlags(
									array('usr_id' => $member_identifier)
								);

								$cache_id = md5(
									serialize(
										array(
											$flags,
											$identifier,
											$administrator_identifier,
											$member_identifier,
											$thread
										)
									)
								);

								if (
									! (
										$cached_contents = $template_engine->is_cached(
											$template_name,
											$cache_id
										)
									)
								)
								{
									// declare empty arrays of information and affordances
									$affordances =
									$information = array();
	
									// declare an empty set of keywords
									$keywords_set = array();
	
									// set the first item flag
									$first_item = false;
	
									// set the last item flag
									$last_item = false;
	
									// set the default next item index
									$next_item_index = null;
	
									// set the default previous item index
									$previous_item_index = null;
	
									// get the resource content
									$content = $resource->{PROPERTY_CONTENT};
	
									// replace comma with semi columns in the keywords list
									$_keywords_set = str_replace(",", ";", $instance->getKeywords());
	
									// get the list of keywords
									$keywords_set = explode(";", $_keywords_set);
	
									// apply a callack to the keywords set 
									$keywords_set = array_map(
										function ($keyword) {
					
											// return a trimmed keyword
											return trim($keyword);
										},
										$keywords_set
									);
	
									// set a new list of keywords
									$_keywords_set = implode(", ", $keywords_set);
	
	/*
									// apply a callack to the keywords set 
									$set = array_map(
										function ($keyword) {
					
											// check if the current keyword contains a blank
											$match = preg_match("/\s/", trim($keyword));
		
											$_keyword = !$match ? "#".$keyword : "#[".$keyword."]";
	
											return $_keyword;
										},
										$keywords_set
									);
	
									// implode the list of keywords
									$_keywords_set = implode(", ", $set);
	*/
									// get the placeholder value
									$placeholder_value = self::getPlaceholder();
	
									// get the keys
									$keys = array_keys($placeholder_value);
	
									// get the latest key
									$latest_key = count($keys) - 1;
		
									// get the index
									$index = array_search($instance->getId(), $keys);
	
									// get the index
									$accesskeys = array(
										PLACEHOLDER_POSITION_BOTTOM => ACCESS_KEY_POSITION_BOTTOM,
										PLACEHOLDER_POSITION_NEXT => ACCESS_KEY_POSITION_NEXT,
										PLACEHOLDER_POSITION_PREVIOUS => ACCESS_KEY_POSITION_PREVIOUS,
										PLACEHOLDER_POSITION_TOP => ACCESS_KEY_POSITION_TOP
									);
	
									// set the internal anchors
									$internal_anchors = array(
										PLACEHOLDER_POSITION_BOTTOM =>
											"#".PREFIX_DOM_IDENTIFIER_IMAGE_PHOTOGRAPH.
											$placeholder_value[$keys[$latest_key]]->getId(),
										PLACEHOLDER_POSITION_CURRENT => $instance->getId(),
										'prefix' => PREFIX_DOM_IDENTIFIER_FRAME_PHOTOGRAPH,
										PLACEHOLDER_POSITION_TOP => INTERNAL_ANCHOR_BODY,
										'class_bottom' => STYLE_CLASS_POSITION_BOTTOM,
										'class_next' => STYLE_CLASS_POSITION_NEXT,
										'class_previous' => STYLE_CLASS_POSITION_PREVIOUS,
										'class_top' => STYLE_CLASS_POSITION_TOP,
									);
	
									// check the index
									if ( ! $index )
	
										// set the first item flag
										$first_item = TRUE;
									else
									{
										// get the next item
										$previous_item_index =
											$placeholder_value
												[$keys[$index - 1]]
													->getId()
										;
	
										// set the internal anchors
										$internal_anchors = array_merge(
											$internal_anchors,
											array(
												'previous' =>
													"#".
														PREFIX_DOM_IDENTIFIER_IMAGE_PHOTOGRAPH.
															$previous_item_index
											)
										);
									}
	
									// check the next index
									if ( ! isset( $keys[$index + 1] ) )
	
										// set the last item flag
										$last_item = TRUE;
									else
									{
										// get the next item
										$next_item_index =
											$placeholder_value
												[$keys[$index + 1]]
													->getId()
										;
	
										// set the internal anchors
										$internal_anchors = array_merge(
											$internal_anchors,
											array(
												'next' =>
													"#".PREFIX_DOM_IDENTIFIER_IMAGE_PHOTOGRAPH.
														$next_item_index
											)
										);
									}
	
									$links = array(
										'bottom' => CONTENT_LINK_BOTTOM,
										'top' => CONTENT_LINK_TOP
									);
	
									// check if the current item is the last one
									if ( ! $last_item )
	
										$links = array_merge(
											$links,
											array(
												'next' =>
													CONTENT_LINK_NEXT
											)
										);
	
									// check if the current item is the first one
									if ( ! $first_item )
	
										$links = array_merge(
											$links,
											array(
												'previous' =>
													CONTENT_LINK_PREVIOUS
											)
										);
	
									// assign the internal anchors
									$template_engine->assign(
										PLACEHOLDER_INTERNAL_ANCHORS,
										$internal_anchors
									);
	
									// assign the links
									$template_engine->assign(
										PLACEHOLDER_LINKS,
										$links
									);

									// set the public affordances
									$affordances = 	array(
										array(
											ENTITY_LABEL => CONTENT_AFFORDANCE_COMMENT,
											ENTITY_LINK => PREFIX_LINK_INTERNAL.ACTION_POST.'_'.$identifier,
											HTML_ATTRIBUTE_CLASS => JAVASCRIPT_IDENTIFIER_AFFORDANCE_COMMENT,
											HTML_ATTRIBUTE_ID => JAVASCRIPT_IDENTIFIER_AFFORDANCE_COMMENT."-".$identifier,
											PROPERTY_ACCESS_KEY => ACCESS_KEY_AFFORDANCE_COMMENT,
											PROPERTY_ACCESS_TYPE => array(
												ACCESS_CONTROL_LIST_GROUP => GROUP_VISITOR
											)
										),									
										array(										
											ENTITY_LABEL => 
												(
													isset($flags[FLAG_TYPE_LIKE][$identifier])
												?
													CONTENT_FACT_LIKE
												:
													CONTENT_AFFORDANCE_LIKE
												),
											ENTITY_LINK => URI_AFFORDANCE_LIKE."-".$identifier,
											HTML_ATTRIBUTE_CLASS =>
												JAVASCRIPT_IDENTIFIER_AFFORDANCE_LIKE.' '.
												(
													isset($flags[FLAG_TYPE_LIKE][$identifier])
												?
													STYLE_CLASS_ENABLED
												:
													STYLE_CLASS_DISABLED
												),
											HTML_ATTRIBUTE_ID => JAVASCRIPT_IDENTIFIER_AFFORDANCE_LIKE."-".$identifier,
											PROPERTY_ACCESS_KEY => ACCESS_KEY_AFFORDANCE_LIKE,
											PROPERTY_ACCESS_TYPE => array(
												ACCESS_CONTROL_LIST_GROUP => GROUP_VISITOR
											)
										),
										array(
											ENTITY_LABEL =>
													isset($flags[FLAG_TYPE_DO_NOT_LIKE][$identifier])
												?
													CONTENT_FACT_PREFIX_DID_NOT." ".strtolower(CONTENT_AFFORDANCE_LIKE)
												:
													CONTENT_AFFORDANCE_PREFIX_DO_NOT." ".strtolower(CONTENT_AFFORDANCE_LIKE)
											,
											ENTITY_LINK => URI_AFFORDANCE_DO_NOT_LIKE."-".$identifier,
											HTML_ATTRIBUTE_CLASS =>
												JAVASCRIPT_IDENTIFIER_AFFORDANCE_DO_NOT_LIKE.' '.
												(
													isset($flags[FLAG_TYPE_DO_NOT_LIKE][$identifier])
												?
													STYLE_CLASS_ENABLED
												:
													STYLE_CLASS_DISABLED
												),
											HTML_ATTRIBUTE_ID => JAVASCRIPT_IDENTIFIER_AFFORDANCE_DO_NOT_LIKE."-".$identifier,
											PROPERTY_ACCESS_KEY => ACCESS_KEY_AFFORDANCE_DO_NOT_LIKE,
											PROPERTY_ACCESS_TYPE => array(
												ACCESS_CONTROL_LIST_GROUP => GROUP_VISITOR
											)
										),
										array(
											ENTITY_LABEL =>
													isset($flags[FLAG_TYPE_SUGGEST_REMOVAL][$identifier])
												?									
													CONTENT_FACT_SUGGEST." ".strtolower(CONTENT_ACTION_REMOVAL)
												:
													CONTENT_AFFORDANCE_SUGGEST." ".strtolower(CONTENT_ACTION_REMOVAL)
											,
											ENTITY_LINK => URI_AFFORDANCE_SUGGEST_REMOVAL."-".$identifier,
											HTML_ATTRIBUTE_CLASS =>
												JAVASCRIPT_IDENTIFIER_AFFORDANCE_SUGGEST_REMOVAL.' '.
												(
													isset($flags[FLAG_TYPE_SUGGEST_REMOVAL][$identifier])
												?
													STYLE_CLASS_ENABLED
												:
													STYLE_CLASS_DISABLED
												),
											HTML_ATTRIBUTE_ID => JAVASCRIPT_IDENTIFIER_AFFORDANCE_SUGGEST_REMOVAL."-".$identifier,
											PROPERTY_ACCESS_KEY => ACCESS_KEY_AFFORDANCE_SUGGEST_REMOVAL,
											PROPERTY_ACCESS_TYPE => array(
												ACCESS_CONTROL_LIST_GROUP => GROUP_VISITOR
											)
										),
										array(
											ENTITY_LABEL =>
													isset($flags[FLAG_TYPE_REPORT][$identifier])
												?									
													CONTENT_FACT_REPORT
												:
													CONTENT_AFFORDANCE_REPORT
											,
											ENTITY_LINK => URI_AFFORDANCE_REPORT."-".$identifier,
											HTML_ATTRIBUTE_CLASS =>
												JAVASCRIPT_IDENTIFIER_AFFORDANCE_REPORT.' '.
												(
													isset($flags[FLAG_TYPE_REPORT][$identifier])
												?
													STYLE_CLASS_ENABLED
												:
													STYLE_CLASS_DISABLED
												),
											HTML_ATTRIBUTE_ID => JAVASCRIPT_IDENTIFIER_AFFORDANCE_REPORT."-".$identifier,
											PROPERTY_ACCESS_KEY => ACCESS_KEY_AFFORDANCE_REPORT,
											PROPERTY_ACCESS_TYPE => array(
												ACCESS_CONTROL_LIST_GROUP => GROUP_VISITOR
											)
										)									
									);
	
									// declare an array of information
									$information = array(
										array(
											ENTITY_CLASS =>
												ENTITY_ELEMENT."_".STYLE_CLASS_TITLE." ".
													ENTITY_ELEMENT."_".ENTITY_PANEL,
	
											ENTITY_CLASS."_".ENTITY_PROPERTY =>
												ENTITY_PROPERTY."_".STYLE_CLASS_TITLE,
											
											ENTITY_CLASS."_".ENTITY_SEPARATOR =>
												ENTITY_SEPARATOR."_".STYLE_CLASS_TITLE." ".
													ENTITY_SEPARATOR."_".ENTITY_PANEL,
											
											ENTITY_CLASS."_".ENTITY_VALUE =>
												ENTITY_VALUE."_".STYLE_CLASS_TITLE,
											
											ENTITY_PROPERTY =>
												CONTENT_PROPERTY_TITLE,
											
											ENTITY_VALUE =>
												$instance->getTitle()
										),
										array(
											ENTITY_CLASS =>
												ENTITY_ELEMENT."_".STYLE_CLASS_KEYWORDS." ".
													ENTITY_ELEMENT."_".ENTITY_PANEL,
	
											ENTITY_CLASS."_".ENTITY_PROPERTY =>
												ENTITY_PROPERTY."_".STYLE_CLASS_KEYWORDS,
	
											ENTITY_CLASS."_".ENTITY_SEPARATOR =>
												ENTITY_SEPARATOR."_".STYLE_CLASS_KEYWORDS." ".
													ENTITY_SEPARATOR."_".ENTITY_PANEL,
	
											ENTITY_CLASS."_".ENTITY_VALUE =>
												ENTITY_VALUE."_".STYLE_CLASS_KEYWORDS,
	
											ENTITY_PROPERTY =>
												CONTENT_PROPERTY_KEYWORDS,
	
											ENTITY_VALUE =>
												$_keywords_set
										)										
									);
	
									if (
										isset(
											$member_qualities->{ROW_GROUP_IDENTIFIER}
										) &&
										$member_qualities->{ROW_GROUP_IDENTIFIER}
											== GROUP_ADMINISTRATOR
									)
									{
										// declare an array of affordances
										$affordances = array_merge(
											$affordances,
											array(
												array(
													ENTITY_LABEL => CONTENT_AFFORDANCE_REMOVE." ".CONTENT_PROPERTY_SELF_REFERENCE,
													ENTITY_LINK => URI_AFFORDANCE_REMOVE."-".$identifier,
													HTML_ATTRIBUTE_CLASS =>
														JAVASCRIPT_IDENTIFIER_AFFORDANCE_REMOVE.' '.STYLE_CLASS_LINK,
													HTML_ATTRIBUTE_ID => JAVASCRIPT_IDENTIFIER_AFFORDANCE_REMOVE."-".$identifier,
													PROPERTY_ACCESS_KEY => ACCESS_KEY_AFFORDANCE_REMOVE,
													PROPERTY_ACCESS_TYPE => array(
														ACCESS_CONTROL_LIST_GROUP => GROUP_ADMINISTRATOR
													)
												)
											)
										);
	
										if (
											'0000-00-00'
												!= substr(
													$instance->getLastModificationDate(), 0, 10
												)
										)
	
											$information = array_merge(
												$information,
												array(
													array(
														ENTITY_CLASS => STYLE_CLASS_DATE, 
														ENTITY_PROPERTY => CONTENT_PROPERTY_DATE_LAST_MODIFICATION,
														ENTITY_VALUE => substr(
															$instance->getLastModificationDate(), 0, 10
														)
													),
												)
											);
	
										if (
											'0000-00-00'
												!= substr(
													$instance->getCreationDate(),
													0,
													10
												)
										)
	
											$information = array_merge(
												$information,
												array(
													array(
														ENTITY_CLASS =>
															STYLE_CLASS_DATE,											
														ENTITY_PROPERTY =>
															CONTENT_PROPERTY_DATE_CREATION,
														ENTITY_VALUE =>
															substr(
																$instance->getCreationDate(),
																0,
																10
															)
													)
												)
											);
	
										// assign the user type flag
										$template_engine->assign(
											USER_TYPE_ADMINISTRATOR,
											TRUE
										);
									}
	
									// assign the access keys
									$template_engine->assign(
										PLACEHOLDER_ACCESSKEYS,
										$accesskeys
									);
	
									// assign the user type flag
									$template_engine->assign(
										PLACEHOLDER_AFFORDANCES,
										$affordances
									);
	
									// assign the user type flag
									$template_engine->assign(
										PLACEHOLDER_INFORMATION,
										$information
									);

									// assign a thread
									$template_engine->assign(
										PLACEHOLDER_FORM_INSIGHT,
										$class_insight::getForm(
											$class_entity::getByName( CLASS_PHOTOGRAPH )
												->{PROPERTY_ID},
											$identifier,
											INSIGHT_TYPE_PARENT_ROOT
										)
									);
	
									$safe_title = str_replace(
										array(
											"'",
											'"'
										),
										array(
											"&quot;"
										),
										$instance->getTitle()
									);

									// assign a resource
									$template_engine->assign(
										ENTITY_RESOURCE,
										str_replace(
											array(
												'{alt}',
												'{title}',
											),
											array(
												$safe_title
											),
											$content
										)
									);
								}
						
								// fetch a panel
								$panel = $template_engine->fetch(
									$template_name,
									$cache_id
								);

								$pattern_variable_local = '/(\{[^\}]+\})/';
								
								if ( FALSE == strpos( $panel, '{literal}' ) )

									$panel = preg_replace(
										$pattern_variable_local,
										'{literal}\1{/literal}',
										$panel
									);

								$panel = str_replace(
									'{literal}{'.PLACEHOLDER_THREAD.'}{/literal}',
									$thread,
									$panel
								);

								// clear the template cache
								$template_engine->clear();
							}
							else

								// jump to the root index
								$class_application::jumpTo( PREFIX_ROOT );
						}
						
						break;
				}

					break;
		}

		// return a panel
		return $panel;
	}

    /**
    * Build a result page
    *
    * @param	mixed	$results	results
    * @param	integer	$start		start
    * @return   nothing
    */
	public static function buildResultPage($results, $start = 0)
	{
		global $class_application, $verbose_mode;

		// construct a new view builder
		$view_builder = new self();

		// get the member DOM
		$_dom = &$view_builder->getDOM();

		// get the member DOMDocument object
		$document = &$_dom[ENTITY_DOCUMENT];

		$element_div = $document->createElement(HTML_ELEMENT_DIV);

		$element_div->setAttribute(HTML_ATTRIBUTE_ID, STYLE_CLASS_SEARCH_RESULTS);

		// create a ul element
		$element_ul = $document->createElement(HTML_ELEMENT_UL);

		if (isset($results->items) && count($results->items) > 0)
		{
			$slice = array_slice(
				$results->items,
				$start * PAGINATION_COUNT_PER_PAGE_SEARCH_RESULTS,
				PAGINATION_COUNT_PER_PAGE_SEARCH_RESULTS,
				TRUE
			);

			while (list($index, $item) = each($slice))
			{
				$element_li = $document->createElement(HTML_ELEMENT_LI);

				// construct a new DOMText object
				$text_node = new DOMText();

				$cp1252_map = array(

					// EURO SIGN
					"\xc2\x80" => "\xe2\x82\xac",

					// SINGLE LOW-9 QUOTATION MARK
					"\xc2\x82" => "\xe2\x80\x9a",

					// LATIN SMALL LETTER F WITH HOOK
					"\xc2\x83" => "\xc6\x92", 

					// DOUBLE LOW-9 QUOTATION MARK
					"\xc2\x84" => "\xe2\x80\x9e", 

					// HORIZONTAL ELLIPSIS 
					"\xc2\x85" => "\xe2\x80\xa6", 

					// DAGGER 
					"\xc2\x86" => "\xe2\x80\xa0", 

					// DOUBLE DAGGER 
					"\xc2\x87" => "\xe2\x80\xa1", 

					// MODIFIER LETTER CIRCUMFLEX ACCENT 
					"\xc2\x88" => "\xcb\x86", 

					// PER MILLE SIGN 
					"\xc2\x89" => "\xe2\x80\xb0", 

					// LATIN CAPITAL LETTER S WITH CARON 
					"\xc2\x8a" => "\xc5\xa0", 

					// SINGLE LEFT-POINTING ANGLE QUOTATION 
					"\xc2\x8b" => "\xe2\x80\xb9", 

					// LATIN CAPITAL LIGATURE OE 
					"\xc2\x8c" => "\xc5\x92", 

					// LATIN CAPITAL LETTER Z WITH CARON 
					"\xc2\x8e" => "\xc5\xbd", 

					// LEFT SINGLE QUOTATION MARK 
					"\xc2\x91" => "\xe2\x80\x98", 

					// RIGHT SINGLE QUOTATION MARK 
					"\xc2\x92" => "\xe2\x80\x99", 

					// LEFT DOUBLE QUOTATION MARK 
					"\xc2\x93" => "\xe2\x80\x9c", 

					// RIGHT DOUBLE QUOTATION MARK 
					"\xc2\x94" => "\xe2\x80\x9d", 

					// BULLET 
					"\xc2\x95" => "\xe2\x80\xa2", 

					// EN DASH 
					"\xc2\x96" => "\xe2\x80\x93", 

					// EM DASH 
					"\xc2\x97" => "\xe2\x80\x94", 

					// SMALL TILDE 
					"\xc2\x98" => "\xcb\x9c", 

					// TRADE MARK SIGN 
					"\xc2\x99" => "\xe2\x84\xa2", 

					// LATIN SMALL LETTER S WITH CARON 
					"\xc2\x9a" => "\xc5\xa1", 

					// SINGLE RIGHT-POINTING ANGLE QUOTATION
					"\xc2\x9b" => "\xe2\x80\xba", 

					// LATIN SMALL LIGATURE OE 
					"\xc2\x9c" => "\xc5\x93", 

					// LATIN SMALL LETTER Z WITH CARON 
					"\xc2\x9e" => "\xc5\xbe", 

					// LATIN CAPITAL LETTER Y WITH DIAERESIS
					"\xc2\x9f" => "\xc5\xb8" 
				);

				$title = strtr(utf8_encode($item->title), $cp1252_map);

				$shortened_title = explode(' ', $title);

				if (count($shortened_title) > MAX_TITLE_LENGTH)
				{
					$words = array_slice($shortened_title, 0, MAX_TITLE_LENGTH);

					while (strlen($words[count($words) - 1]) == 1)
					
						array_pop($words);

					$title = implode(' ', $words).SYMBOL_ELIPSIS;
				}

				// append data to the DOMText object
				$text_node->appendData($title);

				$element_a = $document->createElement(HTML_ELEMENT_A);

				$rewritten_title = $class_application::rewrite($item->title);

				$url_words = explode('-', $rewritten_title);

				$shortened_url = array_slice($url_words, 0, 5);

				while (strlen($shortened_url[count($shortened_url) - 1]) == 1)

					array_pop($shortened_url);

				$rewritten_url = implode('-', $shortened_url);

				$element_a->setAttribute(
					HTML_ATTRIBUTE_HREF,
						$rewritten_url.
						'-'.GET_IDENTIFIER.
							$item->id
				);

				$element_a->appendChild($text_node);

				$element_li->appendChild($element_a);

				$element_ul->appendChild($element_li);
			}

			$element_div->appendChild($element_ul);
		}
		else
		{
			$element_p = $document->createElement(HTML_ELEMENT_P);

			// construct a new DOMText object
			$text_node = new DOMText();
	
			// append data to the DOMText object
			$text_node->appendData(DIALOG_NO_ITEMS);
	
			// append a text node to the a node
			$element_p->appendChild($text_node);

			$element_div->appendChild($element_p);
		}

		return $document->saveXML($element_div);
	}

	/**
	* Build input fields
	*
	* @param	array	&$context input fields context
	* @return	nothing
	*/
	public static function buildTextInputField( $context )
	{
		/**
		* Extract the following parameters form a verified context
		*
		* @tparam	array	&$dom_nodes			array of DOMElements
		* @tparam	integer	$field_index		field index
		* @tparam	array	$field_properties	field properties
		* @tparam	object	$view_builder		instance of View_Builder class
		*/
		extract( self::checkFieldContext( $context ), EXTR_REFS );	

		// get field view
		$dom_nodes[( int )( $field_index + 2 )] =
			$view_builder->buildDOMNode(
			(
					$field_type == FIELD_TYPE_TEXTAREA
				?
					HTML_ELEMENT_TEXTAREA
				:
					HTML_ELEMENT_INPUT
			),
			$field_properties
		);

		// append input text elements to a fieldset
		$dom_nodes[1]->appendChild(
			$dom_nodes[( int )( $field_index + 2 )]
		);	
	}

	/**
	* Check field context
	*
	* @param	array	&$context 	input fields context
	* @param	boolean	$options	options flag
	* @return	array	reference to context after checking
	*/
	public static function &checkFieldContext( $context, $options = FALSE )
	{
		if ( ! is_array( $context ) || ! count( $context ) )
		
			throw new Exception(
				sprintf( 
					EXCEPTION_INVALID_ENTITY,
					ENTITY_CONTEXT
				)
			);

		// Initialize the options context
		$_context_options = array();

		/**
		* Extract the following parameters
		*
		* @tparam	object	$field 					field
		* @tparam	object	$field_handler			field handler
		* @tparam	integer	$field_index			field index
		* @tparam	object	$view_builder			instance of View_Builder class
		* @tparam	array	&$dom_nodes				array of DOMElements
		*/
		extract( $context, EXTR_REFS );

		// get fields
		$fields = $field_handler->get_fields();

		$field_type = strtolower(
			str_replace(
				PREFIX_FIELD.'_',
				CHARACTER_EMPTY_STRING,
				strtolower( get_class( $fields[$field_index] ) )
			)
		);

		$field_properties = array(
			$field_type,
			$field->{PROPERTY_NAME}
		);

		$_context = array(
			'dom_nodes' => &$dom_nodes,
			'field' => $field,
			'field_index' => $field_index,
			'field_properties' => $field_properties,
			'field_type' => $field_type,
			'view_builder' => $view_builder
		);
		
		if ( $options === TRUE )
		{
			// Read the HTML elements from the blackboard
			$html_elements = &self::readSigns( PROPERTY_HTML_ELEMENTS );
	
			// Read the instances properties from the blackboard
			$instances_properties = &self::readSigns( PROPERTY_ELEMENTS_PROPERTIES );
	
			// get fields options
			$options = $field_handler->getProperty( PROPERTY_OPTIONS );

			// set the options context
			$_context_options = array(
				'html_elements' => &$html_elements,
				'instances_properties' => &$instances_properties,
				'options' => $options
			);
		}

		$_context = array_merge( $_context, $_context_options );

		return $_context;
	}

	/**
	* Check links to be possibly appended to a container
	*
	* @param	array	$context	context
	* @return	array	links
	*/
	public static function checkLinks( $context )
	{
		// set the interceptor class name
		$class_interceptor = self::getInterceptorClass();

		if (
			! is_array( $context ) ||
			! count( $context ) ||
			! isset( $context[PROPERTY_VIEW_BUILDER] )
		)
		
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );
		else
		{
			/**
			* Extract the following variables
			*
			* @return	object	$view_builder 	instance of View_Builder class
			*/
			extract( $context );

			// get the member DOM
			$_dom = &$view_builder->getDOM();

			// get the member DOMDocument object
			$dom_document = &$_dom[ENTITY_DOCUMENT];

			// get the configuration
			$configuration = self::getConfiguration();
	
			$links = array();
	
			// check the links configuration
			if (
				isset( $configuration[PROPERTY_LINKS] ) &&
				is_array( $configuration[PROPERTY_LINKS] ) &&
				count( $configuration[PROPERTY_LINKS] )
			)
			{
				while (
					list( $link_index, $link ) =
						each( $configuration[PROPERTY_LINKS] )
				)
				{
					$links[$link_index] = new stdClass();
	
					// create a new a element
					$links[$link_index]->{PROPERTY_NODE} =
						$dom_document->createElement( HTML_ELEMENT_A )
					;
	
					// set the value of the a element href attribute
					$links[$link_index]->{PROPERTY_NODE}->setAttribute(
						HTML_ATTRIBUTE_HREF,
						$class_interceptor::dereference($link[PROPERTY_TARGET])
					);
	
					if (
						defined(
							strtoupper(
								substr(PREFIX_FORM, 0, -1).'_'.
									PREFIX_LINK.'_'.
										self::translate_entity(
											$configuration[PROPERTY_FORM_IDENTIFIER],
											ENTITY_CONSTANT
										).'_'.
											$link[PROPERTY_NAME]
							)
						)
					)
					{
						// create the link text node
						$links[$link_index]->{PROPERTY_NAME} = 
							constant(
								strtoupper(
									substr(PREFIX_FORM, 0, -1).'_'.
										PREFIX_LINK.'_'.
											self::translate_entity(
												$configuration[PROPERTY_FORM_IDENTIFIER],
												ENTITY_CONSTANT
											).'_'.
												$link[PROPERTY_NAME]
								)
							)
						;
	
						$link_text = $dom_document->createTextNode(
							$links[$link_index]->{PROPERTY_NAME}
						);
	
						$links[$link_index]
							->{PROPERTY_NODE}->appendChild( $link_text );
					}
				
					$links[$link_index]->{PROPERTY_POSITION} =
						$link[PROPERTY_POSITION];
				}
			}
		}

		return $links;
	}

	/**
	* Check resources
	*
	* @param	array	$resources	resources
	* @return 	array	checked resources
	*/
	public static function checkResources( $resources )
	{
		$class_field_handler = self::getFieldHandlerClass();

		$field_handler = NULL;

        // check if the field handler resource
        if ( isset( $resources[CONTEXT_INDEX_FIELD_HANDLER] ) )

			// get a field handler
            $field_handler = $resources[CONTEXT_INDEX_FIELD_HANDLER];
		
		// check the insight entity target
		$target =
				'' != trim( $field_handler->getAProperty( PROPERTY_TARGET ) )
			?
				'_'.$field_handler->getAProperty( PROPERTY_TARGET )
			:
				''
		;

		return array(
			'field_handler' => $field_handler,

			'form_identifier' =>
					is_object( $field_handler ) &&
					get_class( $field_handler ) === $class_field_handler
				?
					$field_handler->getProperty( PROPERTY_FORM_IDENTIFIER )
				:
					''					
			,

			'target' => $target,
		);
	}

    /**
    * Get affordances types
    *
    * @return   array	affordances types
    */
	public static function getAffordancesTypes()
	{
		// set the affordance class name
		$class_affordance = self::getAffordanceClass();
	
		return $class_affordance::getTypes();
	}

    /**
    * Get a footer
    *
    * @return  string	footer
    */
	public static function getFooter()
	{
		global $class_application;

		$class_template_engine = $class_application::getTemplateEngineClass();

		$template_engine = new $class_template_engine;

		$template_name = TPL_BLOCK_FOOTER;

		$footer = $class_application::beautifySource(
			$template_engine->fetch( $template_name )
		);

		if ( FALSE !== strpos( $_SERVER['REQUEST_URI'],  AFFORDANCE_SEND_FEEDBACK ) )

			$footer = $class_application::getFormView( AFFORDANCE_SEND_FEEDBACK );

		return $footer;
	}

    /**
    * Get a form
    *
    * @param	mixed	$context		context
    * @param	mixed	$entity_type	entity type
    * @param	boolean	$edition		edition flag
    * @param	boolean	$standalone		standalone indicator
    * @return   mixed
    */
	public static function getForm(
		$context,
		$entity_type,
		$edition = FALSE,
		$standalone = TRUE
	)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$class_entity = $class_application::getEntityClass();

		$callback_parameters = NULL;

		switch ( $entity_type )
		{
			case CLASS_INSIGHT_NODE:

				$class_data_fetcher = $class_application::getDataFetcherClass();

				$class_insight = $class_application::getInsightClass();

				$class_insight_node = $class_application::getInsightNodeClass();

				$class_member = $class_application::getMemberClass();

				$class_template_engine = $class_application::getTemplateEngineClass();

				$block_header = self::buildBlock( PAGE_DIALOG, BLOCK_HEADER );

				$template_engine = new $class_template_engine;

				if (
					is_object($context) &&
					isset( $context->{PROPERTY_IDENTIFIER} )
				)
		
					$identifier = $context->{PROPERTY_IDENTIFIER};
				else

					throw new Exception(
						EXCEPTION_CONSISTENCY_DATA_ACCESS_ENTITY_IDENTIFIER_MISSING
					);

				// set the i18n package
				$package = $class_data_fetcher::get_package(PACKAGE_I18N);

				// set the identifier prefix
				$identifier_prefix = $package[I18N_IDENTIFIER_PREFIX];

				if (
					!empty($context->{PROPERTY_AFFORDANCE}) &&
					( $affordance = $context->{PROPERTY_AFFORDANCE} )
				)

					if (
						defined(
							strtoupper(
								$identifier_prefix.
									PREFIX_TITLE.
										$affordance
							)
						)
					)

						$template_engine->assign(
							PLACEHOLDER_TITLE_PAGE,
							strtoupper(
								$identifier_prefix.
									PREFIX_TITLE.
										$affordance
							)
						);

				if ( isset( $context->{PROPERTY_BODY} ) )
				{
					$identifier = $class_entity::getByName(CLASS_PHOTOGRAPH)->{PROPERTY_ID};

					$text = $class_insight_node::fetchInsightNode(
							$context->{PROPERTY_IDENTIFIER}
						)->{PROPERTY_BODY}
					;

					$cache_id = md5(
						serialize(
							array(
								$identifier,
								$text,
								$class_member::getIdentifier(FALSE, FALSE),
								$class_member::getIdentifier(TRUE, FALSE),
								$standalone
							)
						)
					);

					$template_name = TPL_DEFAULT_XHTML_STRICT_LAYOUT;

					if (
						! (
							$cached = $template_engine->is_cached(
								$template_name,
								$cache_id
							)
						) || $standalone
					)
					{
						$search = array(
							'{$affordance}',
							'{$parent}',
							'{$target}',
							'{$target_type}',
							'{$text}'
						);
	
						$replace = array(
							ACTION_EDIT_INSIGHT,
							$context->{PROPERTY_PARENT},
							$context->{PROPERTY_TARGET},
							$context->{PROPERTY_TARGET_TYPE},
							$text
						);
	
						$form = $class_application::spawnFormView(
							ACTION_POST.'.'.$identifier,
							$search,
							$replace,
							NULL,
							$edition
						);

						$template_engine->assign(
							PLACEHOLDER_BODY,
							( $standalone !== TRUE ? $block_header : '' ).$form
						);

						// Assign a footer to the template engine
						$template_engine->assign(
							PLACEHOLDER_FOOTER,
							self::getFooter()
						);
					}
				}

				if ( $standalone !== TRUE )

					$callback_parameters = array(
						$affordance =>
							$class_application::beautifySource(
								$template_engine->fetch(
									$template_name,
									$cache_id
								)
							)
					);
				else

					$callback_parameters = array( $affordance => $form );

					break;

			default:

				throw new Exception(
					EXCEPTION_DEVELOPMENT_BEHAVIORAL_DEFINITION_MISSING
				);
		}	

		return $callback_parameters;
	}

	/**
    * Get a preview
    *
    * @param	mixed	$context		context
    * @param	mixed	$entity_type	type of entity
    * @param	boolean	$edition		edition flag
    * @return   mixed
    */			
	public static function getPreview(
		$context,
		$entity_type,
		$edition = NULL			
	)
	{
		$class_data_fetcher = self::getDataFetcherClass();

		$class_dumper = self::getDumperClass();

		if ( is_null( $edition ) )
		{
			$edition_mode_type_preview = $class_data_fetcher::getEntityTypeValue(
				array(
					PROPERTY_NAME => PROPERTY_PREVIEW,
					PROPERTY_ENTITY => ENTITY_EDITION_MODE
				)
			);

			$edition = $edition_mode_type_preview;
		}

		return self::getView( $context, $entity_type, $edition );
	}

	/**
    * Get a view
    *
    * @param	mixed	$context		context
    * @param	mixed	$entity_type	type of entity
    * @param	boolean	$edition		edition flag
    * @param	boolean	$standalone		standlone flag
    * @return   mixed
    */
	public static function getView(
		$context,
		$entity_type = NULL,
		$edition = FALSE,
		$standalone = TRUE
	)
	{
		global $class_application;

		$class_dumper = self::getDumperClass();

		$class_template_engine = self::getTemplateEngineClass();

		$default_type = VIEW_TYPE_FORM;

		$callback_parameters = array();

		if ( is_null( $entity_type ) )
		
			$entity_type = $default_type;

		switch ( $entity_type )
		{
			case VIEW_TYPE_INJECTION:

				$block_header = self::buildBlock(
					PAGE_DIALOG,
					BLOCK_HEADER
				);

				$template_engine = new $class_template_engine;

				$template_name = TPL_DEFAULT_XHTML_STRICT_LAYOUT;
	
				$condition_valid = 			  
					(
						FALSE !== (
							$key_exists_body =
								self::key_exists( $context, PROPERTY_BODY )
							)
					) &&
					(
						FALSE !== (
							$key_exists_cache_id =
								self::key_exists( $context, PROPERTY_CACHE_ID )
							)
					)				  
				;

				if ( $condition_valid )
				{
					list( , $body ) = each( $key_exists_body );

					list( , $cache_id ) = each( $key_exists_cache_id );

					if (
						FALSE !== (
							$key_exists_container =
								self::key_exists( $context, PROPERTY_CONTAINER )
							)	
					)
					{
						list( , $container ) = each( $key_exists_container );

						$class_dom_document =
							$class_application::getDomDocumentClass()
						;

						$dom_document = new $class_dom_document;

						while (
							list( $element_type, $attributes ) =
								each( $container )
						)
						{
							$element = $dom_document->createElement(
								$element_type
							);

							while ( list( $name, $value ) = each( $attributes ) )

								$element->setAttribute( $name, $value );

							// construct a new DOMText object
							$text_node = new DOMText();

							// append the body to the DOMText object
							$text_node->appendData( '{'.PLACEHOLDER_BODY.'}' );

							$element->appendChild( $text_node );
							
							$_body = $dom_document->saveXML( $element );
						}

						$body = str_replace(
							'{'.PLACEHOLDER_BODY.'}',
							$body,
							$_body
						);
					}

					$_cached_id = md5( $block_header.$body );

					if (
						! (
							$cached = $template_engine->is_cached(
								$template_name,
								$_cached_id
							)
						)
					)
					{
						$template_engine->assign(
							PLACEHOLDER_BODY,
							$block_header.$body
						);

						// Assign a footer
						$template_engine->assign(
							PLACEHOLDER_FOOTER,
							self::getFooter()
						);						
					}

					$callback_parameters = array(
						$cache_id =>
							$class_application::beautifySource(
								$template_engine->fetch(
									$template_name,
									$_cached_id
								)
							)
					);
				}

					break;

			case $default_type:
			default:

				$callback_parameters = self::getForm(
					$context,
					$entity_type,
					$edition,
					$standalone
				);
		}

		return $callback_parameters;
	}

	/**
	* Load a settlement map
    *
    * @param	mixed	$element
    * @param	mixed	$context
    * @return   nothing
    */
	public static function loadSettlementMap( &$element, $context )	
	{
		global $class_application, $verbose_mode;

		$class_dom_document = $class_application::getDomDocumentClass();

		$class_dumper = $class_application::getDumperClass();

		$class_template_engine = $class_application::getTemplateEngineClass();

		$element_div =

		$element_fieldset =

		$element_form =
		
		$element_input =

		$element_textarea =

		$exception =

		$options = FALSE;

		$dom_element_type = $element->{PROPERTY_DOM_ELEMENT_TAG_NAME};

		$exception = FALSE;
		
		$accept_charset = I18N_CHARSET_UTF8;

		$access_key = 

		$class =

		$cols =

		$disabled =

		$for =

		$id =

		$name =

		$read_only =

		$rows =

		$tab_index =

		$title =

		$value = NULL;

		if ( isset( $_SERVER['REQUEST_URI'] ) )

			$action = $_SERVER['REQUEST_URI'];
		else
		
			$action = PREFIX_ROOT;

		$enctype = FORM_ENCODING_TYPE_MULTIPART;
		
		$method = PROTOCOL_HTTP_METHOD_POST;

		switch ( $dom_element_type )
		{
			case HTML_ELEMENT_DIV:
			case HTML_ELEMENT_FORM:

				if ( $dom_element_type === HTML_ELEMENT_DIV )
	
					$element_div = TRUE;
				else 

					$element_form = TRUE;
	
				$class_file_manager = $class_application::getFileManagerClass();

					break;

			case HTML_ELEMENT_FIELDSET:
			
				$element_fieldset = TRUE;
				
					break;

			case HTML_ELEMENT_INPUT:
			case HTML_ELEMENT_TEXTAREA:

				if ( $dom_element_type === HTML_ELEMENT_INPUT )

					$element_input = TRUE;
				else 

					$element_textarea = TRUE;

					break;
		}
	
		$blackboard = self::getBlackboard();

		if ( is_object( $blackboard ) )
		{
			if ( get_class( $blackboard ) == $class_dom_document ) 

				$dom_document = &$blackboard;

			else if ( isset( $blackboard->{PROPERTY_BACKUP} ) )

				$dom_document = &$blackboard->{PROPERTY_BACKUP};
			else

				$exception = TRUE;
		}
		else
		
			$exception = TRUE;

		if ( $exception )

			throw new Exception(
				sprintf(
					EXCEPTION_INVALID_ENTITY,
					ENTITY_BLACKBOARD
				)
			);

		if (
			(
				( $element_form || $element_div ) &&
				( ! is_string( $context ) || empty( $context ) )
			) ||
			(
				! $element_fieldset &&
				! $element_form &&
				! $element_div &&
				( ! is_array( $context ) || ! count( $context ) )
			)
		)

			throw new Exception( EXCEPTION_INVALID_ARGUMENT );
		
		$tools = array(
			$dom_document,
			$class_dom_document,
			$class_dumper
		);

		if (
			$element_form ||
			$element_div
		)
		{
			$file = PREFIX_FORM.$context.EXTENSION_YAML;
	
			$settings = $class_file_manager::loadSettings( $file );

			array_splice(
				$tools,
				1,
				2,
				array(
					$class_application,
					$class_dom_document,
					$class_dumper,
					$class_template_engine,
					$settings
				)
			);

			if ( isset( $context[HTML_ATTRIBUTE_ACCEPT_CHARSET] ) )
			
				$accept_charset = $context[HTML_ATTRIBUTE_ACCEPT_CHARSET];

			$tools[] = $accept_charset;

			if ( isset( $context[HTML_ATTRIBUTE_ACTION] ) )

				$action = $context[HTML_ATTRIBUTE_ACTION];

			$tools[] = $action;

			if ( isset( $context[HTML_ATTRIBUTE_ENCTYPE] ) )

				$enctype = $context[HTML_ATTRIBUTE_ENCTYPE];

			$tools[] = $enctype;

			if ( isset( $context[HTML_ATTRIBUTE_METHOD] ) )
			
				$method = $context[HTML_ATTRIBUTE_METHOD];

			$tools[] = $method;
		}
		else if ( $element_input || $element_textarea )
		{
			if (
				isset( $context[PROPERTY_FORM_IDENTIFIER] ) &&
				$form_identifier = $context[PROPERTY_FORM_IDENTIFIER]
			)

				$tools[] = $form_identifier;
			else
			
				$exception = str_replace( PROPERTY_FORM_IDENTIFIER , '_', ' ' );

			if ( isset( $context[PROPERTY_MANDATORY] ) )

				$required = $context[PROPERTY_MANDATORY];

			$tools[] = $required;

			if ( isset( $context[HTML_ATTRIBUTE_ACCESSKEY] ) )
			
				$access_key = $context[HTML_ATTRIBUTE_ACCESSKEY];

			$tools[] = $access_key;

			if ( isset( $context[HTML_ATTRIBUTE_CLASS] ) )
				
				$class = $context[HTML_ATTRIBUTE_CLASS];

			$tools[] = $class;

			if ( isset( $context[HTML_ATTRIBUTE_DISABLED] ) )
				
				$disabled = $context[HTML_ATTRIBUTE_DISABLED];

			$tools[] = $disabled;

			if ( isset( $context[HTML_ATTRIBUTE_ID] ) )
				
				$id = $context[HTML_ATTRIBUTE_ID];

			$tools[] = $id;

			if (
				isset( $context[HTML_ATTRIBUTE_NAME] ) &&
				$name = $context[HTML_ATTRIBUTE_NAME]
			)

				$tools[] = $name;
			else
			
				$exception = HTML_ATTRIBUTE_NAME;

			if ( isset( $context[HTML_ATTRIBUTE_READ_ONLY] ) )
				
				$read_only = $context[HTML_ATTRIBUTE_READ_ONLY];

			$tools[] = $read_only;

			if ( isset( $context[HTML_ATTRIBUTE_TABINDEX] ) )
				
				$tab_index = $context[HTML_ATTRIBUTE_TABINDEX];

			$tools[] = $tab_index;

			if ( isset( $context[HTML_ATTRIBUTE_TITLE] ) )
				
				$title = $context[HTML_ATTRIBUTE_TITLE];

			$tools[] = $title;

			if (
				isset( $context[HTML_ATTRIBUTE_TYPE] ) &&
				$type = $context[HTML_ATTRIBUTE_TYPE]
			)

				$tools[] = $type;
			else
			
				$exception = HTML_ATTRIBUTE_TYPE;

			$element_with_options =
				$type == FIELD_TYPE_RADIO ||
				$type == FIELD_TYPE_CHECKBOX
			;
			
			if ( isset( $context[HTML_ATTRIBUTE_VALUE] ) )
			
				$value = $context[HTML_ATTRIBUTE_VALUE];

			$tools[] = $value;

			if (
				isset( $context[PROPERTY_OPTIONS] ) &&
				$options = $context[PROPERTY_OPTIONS]
			)
			{
				if ( isset( $options[$name] ) ) 

					$tools[] = $options[$name];
				else if ( $element_with_options )

					$exception = PROPERTY_OPTIONS;
			}
			else if ( $element_with_options )

				$exception = $type;		
			else
			
				$tools[] = $options;

			if ( $exception )

				throw new Exception(
					sprintf( 
						EXCEPTION_INVALID_ENTITY,
						$exception
					)
				);
		}

		return $tools;
	}

    /**
    * Get a sign
    *
    * @param	string	$name	name of a sign
    * @return   mixed
    */
	public static function &readSigns( $name )
	{
		$class_dumper = self::getDumperClass();
		
		// get a blackboard
		$blackboard = &self::getBlackboard();

		// set the default signs to be returned
		$signs = NULL;

		if ( is_object( $blackboard ) )
		{
			if ( ! isset( $blackboard->$name ) )

				$blackboard->$name = new stdClass();

			$signs = &$blackboard->$name;
		}
		else if ( is_array( $blackboard ) )
		{
			if ( ! isset( $blackboard[$name] ) )
			
				$blackboard[$name] = array();

			$signs = &$blackboard[$name];
		}

		return $signs;
	}

    /**
    * Remove placeholders
    *
    * @param	string	$text		text
    * @param	boolean	$linefeeds	only line feeds
    * @return   mixed
    */
	public static function removePlaceholders( $text, $linefeeds = FALSE )
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$_text = NULL;

		if ( is_string( $text ) )
		{
			if ( ! $linefeeds )
			{
				$pattern = REGEXP_OPEN.'\{\$[^\}]+\}'.REGEXP_CLOSE;
		
				if ( $match = preg_match_all( $pattern, $text, $matches ) )
		
					$_text = preg_replace( $pattern, '', $text );
				else

					$_text = $text;
			}
			else 

				$_text = str_replace(
					array(
						'{'.PLACEHOLDER_LINE_FEED.'}',
					),
					array(
						'<br />'
					),
					$text
				);
		}

		return $_text;
	}

    /**
    * Render an element
    *
    * @param	mixed	$context	context
    * @param	string	$type		element type
    * @return   mixed
    */
	public static function renderElement( $context, $type = NULL)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$class_dom_document = $class_application::getDomDocumentClass();

		$class_tag_form = $class_application::getTagFormClass();

		$blackboard = &self::getBlackboard();

		if ( is_null( $blackboard ) )
		{
			$dom_document = new $class_dom_document();

			self::setBlackboard( $dom_document );			
		}

		$rendering = NULL;

		if (
			strpos(
				$type_lowered = strtolower( $type ),
				ENTITY_TAG
			) !== FALSE
		)
		{
			$pattern =
				REGEXP_OPEN.
					ENTITY_TAG.'_'.
					REGEXP_CATCH_START.
					REGEXP_WILDCARD.REGEXP_ANY.
					REGEXP_CATCH_END.
				REGEXP_CLOSE
			;

			if (
				$match = preg_match( $pattern,  $type_lowered, $matches ) &&
				! empty( $matches[1] ) 
			)
			{
				// create an dom element from a recognized element name
				$element = $dom_document->createElement( $matches[1] );

				self::settleContext( $element, $context );
			
				$rendering = self::seedElement( $element, $context );
			}
		}

		return $rendering;
	}

    /**
    * Render a thread
    *
    * @param	mixed	$thread	thread
    * @return   string	thread
    */
	public static function renderThread($thread)
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$class_member = $class_application::getMemberClass();

		$class_template_engine = $class_application::getTemplateEngineClass();

		$class_user_handler = $class_application::getUserHandlerClass();

		// set the default user id for anonymous visitors
		$logged_in_user_id = 0;

		// contruct a new template engine 
		$template_engine = new $class_template_engine();

		$threads = array();

		$view = '';

		if ( $class_user_handler::loggedIn() )
		{
			$member_qualities = $class_member::getQualities();
			
			$logged_in_user_id = $member_qualities->{ROW_MEMBER_IDENTIFIER};
		}

		if ( is_array( $thread ) && count( $thread ) )
		{
			list( $root_index, ) = each( $thread );
			reset( $thread );
			
			// prevent an interruption from occuring as soon as
			// the threads are displayed in verbose mode with
			// Data_Fetcher::fetchProperties method query observer enabled
			$root_index = (int) $root_index;

			if (
				is_array( $thread[$root_index] ) &&
				count( $thread[$root_index] )
			)
			{		
				$cache_id = md5(
					serialize(
						array(
							$thread,
							$class_member::getIdentifier(TRUE, FALSE),
							$class_member::getIdentifier(FALSE, FALSE)
						)
					)
				);

				$template_name = TPL_BLOCK_INSIGHT;

				if (
					! (
					   $cached =
					   $template_engine->is_cached(
							$template_name,
							$cache_id
						)
					)
				);
				{
					while ( list( $parent, $children) =  each( $thread ) )
					{
						list( $first_index ) = each( $children );
						reset( $children );
						
						if (
							is_array( $children ) &&
							isset( $children[$first_index] )
						)
						{
							try {
								$unique_thread_id =
									$class_application::generateUniqueEntityId(
										array(
											PROPERTY_OWNER =>
												$children[$first_index]
													->{PROPERTY_OWNER},
											PROPERTY_THREAD =>
												$children[$first_index]
													->{PROPERTY_THREAD}
										),
										ENTITY_INSIGHT
									)
								;
							}
							catch (Exception $exception)
							{
								$class_dumper::log(
									__METHOD__,
									array($exception),
									DEBUGGING_DISPLAY_EXCEPTION,
									AFFORDANCE_CATCH_EXCEPTION
								);						
							}
				
							$_thread = array();
				
							$nodes = array();
			
							while ( list( $index, $child ) = each( $children ) )
							{
								$node = array();
			
								$qualities = $class_member::fetchQualities(
									array(
										ROW_MEMBER_IDENTIFIER
											=> $child->{PROPERTY_OWNER}
									)
								);
			
								try {
									$unique_insight_node_id =
										$class_application::generateUniqueEntityId(
											$child,
											ENTITY_INSIGHT_NODE
									);
								}
								catch ( Exception $exception )
								{
									$class_dumper::log(
										__METHOD__,
										array($exception),
										DEBUGGING_DISPLAY_EXCEPTION,
										AFFORDANCE_CATCH_EXCEPTION
									);						
								}	
			
								$node[PLACEHOLDER_INSIGHT_ACTIONS] = array(
									array(
										PLACEHOLDER_CLASS => STYLE_CLASS_INSIGHT_REPLY,
										PLACEHOLDER_LINK =>
											URI_AFFORDANCE_REPLY_TO.'-'.
												ENTITY_INSIGHT.'-'.
													ENTITY_NODE.'-'.
														$child->id
										,
										PLACEHOLDER_LABEL => DIALOG_LINK_REPLY_TO_INSIGHT
									)
								);
				
								if (
									$child->{PROPERTY_OWNER} ==
										$logged_in_user_id
								)
								{
									$node[PLACEHOLDER_INSIGHT_ACTIONS][] = array(
										PLACEHOLDER_CLASS => STYLE_CLASS_INSIGHT_EDIT,
										PLACEHOLDER_LINK =>
											URI_AFFORDANCE_EDIT.'-'.
												ENTITY_INSIGHT.'-'.
													ENTITY_NODE.'-'.
														$child->id
										,
										PLACEHOLDER_LABEL => DIALOG_LINK_EDIT_INSIGHT
									);
									
									$node[PLACEHOLDER_INSIGHT_ACTIONS][] = array(
										PLACEHOLDER_CLASS => STYLE_CLASS_INSIGHT_REMOVE,
										PLACEHOLDER_LINK =>
											URI_AFFORDANCE_REMOVE.'-'.
												ENTITY_INSIGHT.'-'.
													ENTITY_NODE.'-'.
														$child->id
										,
										PLACEHOLDER_LABEL => DIALOG_LINK_REMOVE_INSIGHT
									);
								}
				
								$node[PROPERTY_ID] =
									$unique_insight_node_id
								;
				
								$node[PROPERTY_AVATAR] =
									$qualities->{PROPERTY_AVATAR}
								;
				
								$node[PROPERTY_BODY] =
									$child->body
								;
				
								$node[PROPERTY_DATE_CREATION] =
									$child->{PROPERTY_DATE_CREATION}
								;

								$node[PROPERTY_DATE_MODIFICATION] =
									$child->{PROPERTY_DATE_MODIFICATION}
								;
				
								$node[ROW_MEMBER_USER_NAME] =
									htmlentities(
										$qualities->{ROW_MEMBER_USER_NAME}
									)
								;
			
								if ( isset( $child->{PROPERTY_CHILDREN} ) )

									$node[PLACEHOLDER_CHILDREN] =
										self::renderThread(
											array(
												$index =>
													$child->{PROPERTY_CHILDREN}
											)
										);

								$nodes[] = $node;
			
								if ( $first_index == $index && ! $parent )
			
									$_thread[PROPERTY_ID] = $unique_thread_id;
							}
				
							$_thread[PLACEHOLDER_NODES] = $nodes;
			
							$threads[] = $_thread;
						}
					}
			
					// assign the threads to their placeholder
					$template_engine->assign( PLACEHOLDER_THREADS, $threads );
				}

				// fetch a view
				$view = $template_engine->fetch( $template_name, $cache_id );

				// clear all cache
				$template_engine->clear();
			}
		}

		return $view;
	}

    /**
    * Seed an element into a context
    *
    * @param	object		$element	element
    * @param	mixed		$context	context
    * @return   nothing
    */
	public static function seedElement( $element, $context )
	{
		list(
			$dom_document,
			$class_application,
			$class_dom_document,
			$class_dumper,
			$class_template_engine,
			$settings
		) = self::loadSettlementMap( $element, $context );

		$target = '';
	
		$rendering = NULL;
		
		$template_name = TPL_DEFAULT_XHTML_STRICT_LAYOUT;

		// construct a new instance of the template engine object
		$template_engine = new $class_template_engine();

		// create a div element
		$element_div = $dom_document->createElement( HTML_ELEMENT_DIV );

		// set the value of the id attribute of the div element 
		$element_div->setAttribute(
			HTML_ATTRIBUTE_ID,
			$class_application::translate_entity(
				$context.$target,
				ENTITY_SMARTY_VARIABLE
			)
		);

		// set the value of the class attribute of the div element 
		$element_div->setAttribute(
			HTML_ATTRIBUTE_CLASS,
			STYLE_CLASS_FORM_CONTAINER
		);

		self::settleDisclaimers( $element_div, $context );

		$element_div->appendChild( $element );			

		$rendering = $dom_document->saveXML( $element_div );

		$rendering = self::removePlaceholders(
			self::removePlaceholders( $rendering ),
			TRUE
		);
	
		if (
			$element->{PROPERTY_DOM_ELEMENT_TAG_NAME} ==
				HTML_ELEMENT_FORM
		)
		{
			$cache_id = md5(
				serialize(
					array(
						serialize( $element ),
						serialize( $context )
					)
				)
			);

			if (
				! (
					$cached = $template_engine->is_cached(
						$template_name,
						$cache_id
					)
				)
			)
			{
				$template_engine->assign(
					PLACEHOLDER_BODY,
					$rendering
				);

				// Assign a footer to the template engine
				$template_engine->assign(
					PLACEHOLDER_FOOTER,
					self::getFooter()
				);		
			}

			$rendering = $class_application::beautifySource(
				$template_engine->fetch(
					$template_name,
					$cache_id
				)
			);
		}
		
		return $rendering;
	}
	
    /**
    * Set the static blackboard
    *
    * @param	mixed	$blackboard	blackboard
    * @return   nothing
    */
	public static function setBlackboard( &$blackboard )
	{
		$class_dom_document = self::getDomDocumentClass();

		$class_dumper = self::getDumperClass();

		// get the static blackboard
		$_blackboard = &self::getBlackboard();

		if (
			! is_null( $_blackboard ) &&
			is_object( $_blackboard )
		)
		{
			// check if the blackboard is an instance of the DOMDocument class 
			if ( get_class( $_blackboard ) === $class_dom_document )
		
				$blackboard_backup = $_blackboard;

			// retrieve blackboard backup when possible
			else if ( isset( $_blackboard->{PROPERTY_BACKUP} ) )

				$blackboard_backup = $_blackboard->{PROPERTY_BACKUP};

			else if ( count( get_object_vars( $_blackboard) ) )

				$blackboard_old = $_blackboard;
		}

		// overwrite existing signs on the blackboard
		$_blackboard = $blackboard;

		if ( isset( $blackboard_backup ) )
		
			$_blackboard = ( object ) array_merge(
				( array ) $_blackboard,
				array( PROPERTY_BACKUP => $blackboard_backup )
			);

		// prevent existing signs from being lost
		else if ( isset( $blackboard_old  ) )

			$_blackboard = ( object ) array_merge(
				( array ) $_blackboard,
				( array ) $blackboard_old
			);
	}

    /**
    * Set the static configuration
    *
    * @param	mixed	$configuration	configuration
    * @return   nothing
    */
	public static function setConfiguration(&$configuration)
	{
		// get the static placeholder
		$_configuration = &self::getConfiguration();

		// set the placeholder value
		$_configuration = $configuration;
	}

    /**
    * Set a persistent property
    *
    * @param	mixed	$name	name
    * @param	mixed	$value	value
    * @return   nothing
    */
	public static function setPersistentProperty($name, $value)
	{
		// get the static placeholder
		$property = &self::getPersistentProperty($name);

		// set the placeholder value
		$property = $value;
	}

    /**
    * Set the static placeholder
    *
    * @param	mixed	$value	value
    * @return   nothing
    */
	public static function setPlaceholder(&$value)
	{
		// get the static placeholder
		$placeholder = &self::getPlaceholder();

		// set the placeholder value
		$placeholder = $value;
	}

    /**
    * Set a property
    *
    * @param	mixed	$name	name
    * @param	mixed	$value	value
    * @return   nothing
    */
	public static function setProperty($name, $value)
	{
		// get a property
		$_property = &self::getProperty($name);

		// set a property		
		$_property = $value;

		// set a persistent property
		self::setPersistentProperty($name, $value);
	}

    /**
    * Settle a context
    *
    * @param	mixed	$element
    * @param	mixed	$context
    * @return 	nothing
    */
	public static function settleAttributes( &$element, $context )
	{
		$map = self::loadSettlementMap( $element, $context );

		$resources = NULL;

		switch ( $element->{PROPERTY_DOM_ELEMENT_TAG_NAME})
		{
			case HTML_ELEMENT_INPUT:
			case HTML_ELEMENT_TEXTAREA:
		
				list(
					$dom_document,
					$class_dom_document,
					$class_dumper
				) = $map;

				$elements = array( HTML_ELEMENT_INPUT => $element );

				if ( isset( $context[PROPERTY_LABEL] ) )

					$elements[HTML_ELEMENT_LABEL] = $context[PROPERTY_LABEL];

				if ( isset( $context[PROPERTY_DIV] ) )

					$elements[HTML_ELEMENT_DIV] = $context[PROPERTY_DIV];

				if ( isset( $context[PROPERTY_SPAN] ) )

					$elements[HTML_ELEMENT_SPAN] = $context[PROPERTY_SPAN];

				$form_identifier = $context[PROPERTY_FORM_IDENTIFIER];

				$resources = array(
					$context[HTML_ATTRIBUTE_TYPE],
					$context[HTML_ATTRIBUTE_NAME]
				);

				// setting the value of a checkbox or radio field 
				if ( isset( $map[14] ) )
				
					$resources[2] = $map[14];
			
					break;
		
			case HTML_ELEMENT_FORM:
		
				list(
					$dom_document,
					$class_application,
					$class_dom_document,
					$class_dumper,
				) = $map;

				$elements = array( HTML_ELEMENT_FORM => $element );

				$form_identifier = $context;				

					break;
		}

		$view_builder = new self();

		$static_parameters = array(
			$form_identifier,
			$context,
			array( PROPERTY_NAMESPACE => LANGUAGE_PREFIX_FORM ),
			$dom_document,
			$elements
		);
	
		$view_builder->buildDOMNode(
			$element->{PROPERTY_DOM_ELEMENT_TAG_NAME},
			$resources,
			$static_parameters
		);
	}

    /**
    * Settle a context
    *
    * @param	mixed	$element
    * @param	mixed	$context
    * @return   nothing
    */
	public static function settleContext( &$element, $context )
	{
		global $class_application, $verbose_mode;

		$class_dom_element = $class_application::getDomElementClass();

		$class_dumper = $class_application::getDumperClass();

		if (
			is_object( $element ) &&
			get_class( $element) === $class_dom_element
		)
		{
			switch ( $element->{PROPERTY_DOM_ELEMENT_TAG_NAME} )
			{
				case HTML_ELEMENT_FORM:
					
					self::settleForm( $element, $context );
					
						break;

				case HTML_ELEMENT_INPUT:
				case HTML_ELEMENT_TEXTAREA:

					self::settleTextInput( $element, $context );

						break;

				case HTML_ELEMENT_SELECT:

					self::settleSelect( $element, $context );

						break;
			}
		}
	}

    /**
    * Settle disclaimers
    *
    * @param	object	$element			element
    * @param	string	$form_identifier	form identifier
    * @param	mixed	$context			context
    * @return   nothing
    */		
	public static function settleDisclaimers(
		$element,
		$form_identifier,
		$context = NULL
	)
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		if ( is_null( $context ) )
		{
			list(
				$dom_document,
				,
				,
				,
				,
				$settings
			) = self::loadSettlementMap( $element, $form_identifier );
		}
		else

			extract( $context );

		// set the current i18n prefix
		$prefix_i18n =
			substr(PREFIX_FORM, 0, -1)."_".
				PREFIX_DISCLAIMER.'_'.
					$class_application::translate_entity(
						$form_identifier,
						ENTITY_CONSTANT
					).
						'_'
		;

		if (
			isset( $settings[PROPERTY_DISCLAIMERS] ) &&
			is_array( $settings[PROPERTY_DISCLAIMERS] ) 
		)
		{
			// create a div element for disclaimers
			$element_div_disclaimers =
				$dom_document->createElement( HTML_ELEMENT_DIV );

			$element_div_disclaimers->setAttribute(
				HTML_ATTRIBUTE_CLASS,
				STYLE_CLASS_DISCLAIMERS
			);

			while (
				list( $index, $disclaimer_index ) =
					each( $settings[PROPERTY_DISCLAIMERS] )
			)
	
				// check if a constant is defined
				if (
					defined( strtoupper( $prefix_i18n.$disclaimer_index ) )
					|| $index == AFFORDANCE_DISPLAY
				)
				{
					// create a new paragraph node
					$paragraph_node =
						$dom_document->createElement( HTML_ELEMENT_P )
					;
	
					// construct a new DOMText object
					$disclaimer_node = new DOMText();
	
					if ( is_string( $index ) && ( $index == AFFORDANCE_DISPLAY ) )
					{
						$paragraph_node->setAttribute(
							HTML_ATTRIBUTE_CLASS,
							$index
						);
						
						if ( ! is_string( $disclaimer_index[ENTITY_DISCLAIMER] ) )

							$disclaimer_value =
								$disclaimer_index[ENTITY_DISCLAIMER]
							;
						else 
							
							$disclaimer_value = $disclaimer_index;

                        // append data to the DOMText object
						$disclaimer_node->appendData( $disclaimer_value );
	
						$_SESSION[ENTITY_FEEDBACK]
							[$form_identifier]
								[$index] =
									NULL
						;
					}
					else if (
						defined( strtoupper( $prefix_i18n.$disclaimer_index ) )
					)
	
						// append data to the DOMText object
						$disclaimer_node->appendData(
							str_replace(
								"\n",
								'<br />',
								constant(
									strtoupper(
										$prefix_i18n.$disclaimer_index
									)
								)
							)
						);
	
					// append a child to the paragraph node
					$paragraph_node->appendChild( $disclaimer_node );
	
					// append a child to the main div element before the fields
					$element_div_disclaimers->appendChild( $paragraph_node );
				}

			// append a child to the main div element before the fields
			$element->insertBefore(
				$element_div_disclaimers,
				$element->childNodes->item( 0 )
			);				
		}
	}

    /**
    * Settle a form
    *
    * @param	mixed	$element
    * @param	mixed	$context
    * @return   nothing
    */
	public static function settleForm( &$element, $context)
	{
		list(
			$dom_document,
			$class_application,
			$class_dom_document,
			$class_dumper,
			$class_template_engine,
			$settings
		) = self::loadSettlementMap( $element, $context );

		if (
			is_array( $settings ) &&
			isset( $settings[CONTEXT_FIELDS] ) &&
			is_array( $settings[CONTEXT_FIELDS] ) &&
			count( $settings[CONTEXT_FIELDS] )
		)
		{
			// initializing a collection of attributes
			$attributes = array();

			// create a fieldset element
			$element_fieldset =
				$dom_document->createElement( HTML_ELEMENT_FIELDSET )
			;

			self::settleAttributes( $element, $context );

			// add a hidden input containing the form identifier
			self::settleInputHidden( $element_fieldset, $context );

			$settle_input_context = function(
				$dom_document,
				$element_type,
				$settings,
				$context,
				$attributes,
				$value = NULL
			) use ( $class_application, $element_fieldset )
			{
				$class_dumper = $class_application::getDumperClass();

				$class_view_builder = $class_application::getViewBuilderClass();
				
				$element = $dom_document->createElement(
					$element_type
				);

				$_context = array_merge(
					$attributes,
					array(
						PROPERTY_FORM_IDENTIFIER =>
							$context
					)
				);

				if (
					is_array( $settings ) &&
					isset( $settings[CONTEXT_OPTIONS] )
				)

					$_context[PROPERTY_OPTIONS] = $settings[CONTEXT_OPTIONS];

				if ( isset( $value ) )

					$_context[HTML_ATTRIBUTE_VALUE] = $value;

				$class_view_builder::settleContext(
					$element,
					$_context
				);
				
				$element_fieldset->appendChild( $element );								
			};

			// looping on the context elements corresponding to
			// as many tags or html elements 
			foreach(
				$settings[CONTEXT_FIELDS] as
					$index => $setting
			)
			{
				if ( is_array( $setting ) && count( $setting ) )
				{
					$dom_element_type = self::extractAttributes(
						$setting,
						$attributes,
						$index,
						$context
					);

					if ( ! is_null( $dom_element_type ) )
					{
						if (
							isset( $setting[HTML_ATTRIBUTE_TYPE] ) &&
							isset( $setting[HTML_ATTRIBUTE_NAME] ) &&
							in_array(
								rtrim(
									$setting[HTML_ATTRIBUTE_TYPE],
									SUFFIX_MANDATORY
								),
								array(
									FIELD_TYPE_RADIO,
									FIELD_TYPE_CHECKBOX
								)
							) &&
							isset( $settings[CONTEXT_OPTIONS] ) &&
							(
								$_context[PROPERTY_OPTIONS] =
									$settings[CONTEXT_OPTIONS]
							) &&
							isset(
								$_context[PROPERTY_OPTIONS]
								  [$setting[HTML_ATTRIBUTE_NAME]]
							)
						)
						{
							$options =
								&$_context[PROPERTY_OPTIONS]
									[$setting[HTML_ATTRIBUTE_NAME]]
							;

							// loop on options of radio or checkbox input elements
							foreach ( $options as $value => $label )
	
								$_element = $settle_input_context(
									$dom_document,
									$dom_element_type,
									$settings,
									$context,
									$attributes[$index],
									$value
								);
						}
						else

							$_element = $settle_input_context(
								$dom_document,
								$dom_element_type,
								$settings,
								$context,
								$attributes[$index]
							);
					}
				}
			}

			$element->appendChild( $element_fieldset );
		}
	}

	/**
	* Settle a hidden input
    *
    * @param	mixed	$element	element
    * @param	mixed	$context	context
    * @return   nothing
    */
	public static function settleInputHidden( &$element, $context )	
	{	
		list(
			$dom_document,
			$class_application,
			$class_dom_document
		) = self::loadSettlementMap( $element, $context );

		// create a new input element
		$input_element =
			$dom_document->createElement( HTML_ELEMENT_INPUT );

		// set the value of the input element type attribute
		$input_element->setAttribute(
			HTML_ATTRIBUTE_TYPE,
			strtolower(
				substr(
					FORM_FIELD_TYPE_HIDDEN,
					strlen( ENTITY_FIELD )
				)
			)
		);

		// check the form identifier property
		// of the current configuration
		if ( ! empty( $context ) )
		{
			// set the value of the input element type attribute
			$input_element->setAttribute(
				HTML_ATTRIBUTE_NAME,
				FIELD_NAME_AFFORDANCE
			);							

			// set the value of the input element value attribute
			$input_element->setAttribute(
				HTML_ATTRIBUTE_VALUE,
				$context
			);
		}

		$element->appendChild( $input_element );
	}

	/**
	* Settle an input
    *
    * @param	mixed	$element	element 
    * @param	mixed	$context	context	
    * @return   nothing
    */
	public static function settleTextInput( &$element, $context )	
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();		

		self::settleInputLabel( $element, $context );
	}

	/**
	* Settle an input label
    *
    * @param	mixed	$element	element
    * @param	mixed	$context	context
    * @return   nothing
    */
	public static function settleInputLabel( &$element, $context )	
	{
		global $class_application, $verbose_mode;

        // set the field handler class name
        $class_dumper = $class_application::getDumperClass();

		$map = self::loadSettlementMap( $element, $context );

		$value = NULL;

		list(
			$dom_document,
			$class_dom_document,
			$class_dumper,
			$form_identifier,
			$required,
			$access_key,
			$class,
			$disabled,
			$id,
			$name,
			$read_only,
			$tab_index,
			$title,
			$type
		) = $map;

		if ( isset( $map[14] ) )

			$value = $map[14];

		if ( isset( $map[15] ) && $map[15] )
		{
			$options = $map[15];
			
			if ( is_array( $options ) && count( $options ) )
			
			$index = NULL;

			while (
				( $index != $value ) &&
				list( $index ) = each( $options )
			);

			$option_element = array(
				AFFORDANCE_PROVIDE_WITH_OPTIONS => '',
				HTML_ATTRIBUTE_NAME => $name
			);

			$option_context = array(
				array(
					PROPERTY_OPTIONS =>
						array( $name => $options )
				),
				$form_identifier
			);

			self::settleOptions( $option_element, $option_context );
		}

		$field_hidden = $type === FIELD_TYPE_HIDDEN;

		$prefix_language_item = self::getLanguageItemPrefix( $form_identifier );

		$label_required = $type != FIELD_TYPE_HIDDEN;

		$element_div = $dom_document->createElement( HTML_ELEMENT_DIV );

		$element_span = $dom_document->createElement( HTML_ELEMENT_SPAN );

		if ( $label_required )
		{
			$element_label = $dom_document->createElement( HTML_ELEMENT_LABEL );

			$context[PROPERTY_LABEL] = &$element_label;

			$context[PROPERTY_DIV] = &$element_div;

			$context[PROPERTY_SPAN] = &$element_span;
		}

		// check the i18n member
		if (
			$label_required &&
			defined( strtoupper( $prefix_language_item.$name ) ) ||
			isset( $options ) ||
			in_array(
				$type,
				array(
					FIELD_TYPE_SUBMIT,
					FIELD_TYPE_HIDDEN
				)
			)
		)
		{
			// check if the current field is required
			if ( $required )
			{
				$span_element = $dom_document->createElement(
					HTML_ELEMENT_SPAN
				);
		
				$span_element->setAttribute(
					HTML_ATTRIBUTE_CLASS,
					STYLE_CLASS_MANDATORY
				);
			
				// construct a new DOMText object
				$span_node = new DOMText();
				
				// append the mandatory suffix to
				// the DOMText object
				$span_node->appendData( SUFFIX_MANDATORY );
				
				// append the DOMText object to
				// the span DOMelement
				$span_element->appendChild( $span_node );
			}
		
			if ( ! $field_hidden )
			{
				$span_label_element = $dom_document->createElement(
					HTML_ELEMENT_SPAN
				);
			
				$span_label_element->setAttribute(
					HTML_ATTRIBUTE_CLASS,
					STYLE_CLASS_LABEL
				);
			}

			if (
				! in_array(
					$type ,
					array(
						FIELD_TYPE_SUBMIT,
						FIELD_TYPE_HIDDEN
					)
				)
			)
			{
				// construct a new DOMText object
				$dom_text_node = new DOMText();

				// check the field option
				if ( ! isset( $index ) )
	
					// append the field value
					// to the DOMText object
					$dom_text_node->appendData(
						constant(
							strtoupper(
								strtoupper( $prefix_language_item.$name )
							)
						)
					);
				else
	
					// append the field value
					// to the DOMText object
					$dom_text_node->appendData( 
						$option_element
							[AFFORDANCE_PROVIDE_WITH_OPTIONS]
								[$index]	
					);
			
				// append the DOMText object
				// to the span DOMelement
				$span_label_element->appendChild( $dom_text_node );
			}
		}
		
		// check the label node
		if (
			isset( $span_label_element ) &&
			is_object( $span_label_element ) &&
			get_class( $span_label_element ) == CLASS_DOM_ELEMENT ||
			in_array(
				$type,
				array(
					FIELD_TYPE_SUBMIT,
					FIELD_TYPE_HIDDEN
				)
			)
		)
		{
			if ( ! $field_hidden )
			{
				// append the label node
				// to the label tag
				$element_label->appendChild( $span_label_element );
			
				// check if the current field is required
				if (
					isset( $required ) &&
					$required === TRUE &&
					is_object( $span_element ) &&
					get_class( $span_element ) == CLASS_DOM_ELEMENT
				)
	
					// append a span element to the label element
					$element_label->appendChild( $span_element );
	
				$element_div->appendChild( $element_label );
			}

			self::settleAttributes( $element, $context );

			if ( ! $field_hidden )
			{
				$element_span->appendChild( $element );
	
				$element_div->appendChild( $element_span );
	
				$element = $element_div;
			}
		}
		else if ( $label_required )

			throw new Exception( WARNING_MESSAGE_CONTACT_ADMINISTRATOR );
	}

	/**
	* Settle options
    *
    * @param	array	&$element 	element
    * @param	mixed	$context	context
    * @return   nothing
    */
	public static function settleOptions( &$element, $context = NULL )
	{
		global $class_application, $verbose_mode;

		$class_data_fetcher = $class_application::getDataFetcherClass();

		$class_dumper = $class_application::getDumperClass();

		$class_form_manager = $class_application::getFormManagerClass();

		if (
			is_array( $context ) &&
			count( $context ) == 2 &&
			isset( $context[1] ) &&
			is_int( $context[1] )
		)

			list(
				$configuration,
				$handler_id
			) = $context;

		else if (
			is_array( $context ) &&
			isset( $context[0] ) &&
			isset( $context[1] )
		)
		{
			$configuration = &$context[0];

			$form_identifier = &$context[1];

			$handler_id = FORM_ORDINARY;
		}
		else
		
			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		// check if the field has some options
		if (
			is_array( $element ) &&
			isset( $element[AFFORDANCE_PROVIDE_WITH_OPTIONS] )
		)
		{
			if ( ! isset( $form_identifier ) )

				// get the current form identifier
				$form_identifier =
					$class_form_manager::get_persistent_property(
						PROPERTY_FORM_IDENTIFIER,
						$handler_id
					)
				;

			// set the i18n package
			$package = $class_data_fetcher::get_package(
				PACKAGE_I18N,
				NULL,
				$handler_id
			);

			// set the identifier prefix
			$identifier_prefix = $package[I18N_IDENTIFIER_PREFIX];

			// check the identifier prefix
			if ( empty( $identifier_prefix ) )

				// throw an exception
				throw new Exception( EXCEPTION_INVALID_I18N_SCOPE );

			// declare field name and identifier substrate
			$substrate_field_name = new stdClass();

			$substrate_form_identifier = new stdClass();

			$substrate_i18n_identifier = new stdClass();

			// set the length propert of the substrates
			$substrate_field_name->{PROPERTY_LENGTH} = 

			$substrate_form_identifier->{PROPERTY_LENGTH} =
			
			$substrate_i18n_identifier->{PROPERTY_LENGTH} =
				count(
					$configuration
						[PROPERTY_OPTIONS]
							[$element[HTML_ATTRIBUTE_NAME]]
				)
			;

			// set the pattern properties of the substrate
			$substrate_field_name->{PROPERTY_PATTERN} =
				$element[HTML_ATTRIBUTE_NAME];
			
			$substrate_form_identifier->{PROPERTY_PATTERN} =
				$form_identifier;						
			
			$substrate_i18n_identifier->{PROPERTY_PATTERN} =
				$identifier_prefix;


			// build spaces from the substrates 
			$prefix_field_name =
				$class_application::buildSpace(
					$substrate_field_name
				)
			;

			$prefix_form_identifier =
				$class_application::buildSpace(
					$substrate_form_identifier
				)
			;

			$prefix_i18n_identifier =
				$class_application::buildSpace(
					$substrate_i18n_identifier
				)
			;

			// Apply a callback to the optional elements
			$option_elements = array_map(
				FUNCTION_EXTRACT_LANGUAGE_ITEM,
				$configuration[PROPERTY_OPTIONS][$element[HTML_ATTRIBUTE_NAME]],
				$prefix_form_identifier,
				$prefix_i18n_identifier,
				$prefix_field_name
			);

			// unshift items in the option elements
			$_count = array_unshift( $option_elements, '' );

			// take a slice of the array of option element		
			$option_elements = array_slice(
				$option_elements,
				1,
				$_count, TRUE
			);

			$element[AFFORDANCE_PROVIDE_WITH_OPTIONS] =
				$option_elements
			;
		}					
	}

	/**
	* Settle a select tag
    *
    * @param	mixed	$element
    * @param	mixed	$context
    * @return   nothing
    */
	public static function settleSelect( &$element, $context )	
	{
		list(
			$class_dom_document,
			$class_dumper,
			$dom_document	
		) = self::loadSettlementMap( $element, $context );
	}

    /**
    * Add a sign to the blackboard
    *
    * @param	string	$name	name 
    * @param	mixed	&$value	value
    * @return   mixed
    */
	public static function writeSigns( $name, &$value )
	{
		global $class_application, $verbose_mode;

		
		$class_dumper = $class_application::getDumperClass();
		
		// retrieve the static blackboard 
		$blackboard = self::getBlackboard();

		if ( ! is_string( $name ) || empty( $name ) )

			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		if ( is_null( $blackboard ) )

			// set a blackboard as a new instance of the standard class
	        $blackboard = new stdClass();

		// set the blackboard HTML elments
		$blackboard->$name = &$value;

		// set the blackboard
		self::setBlackboard( $blackboard );
	}
}
