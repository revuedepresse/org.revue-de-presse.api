<?php

/**
* Html Element class
*
* Class for representing an Html Element
* @package  sefi
*/
class Element_Html extends Element
{
	/**
	* Construct a new instance of the Element_Html class
	*
	* @tparam	boolean	$attributes
	* @return	object	instance of the Element_Html class
	*/
	public function __construct()
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();
		
		// Get the provided arguments as an array
		$arguments = func_get_args();

		// set the default instance attributes
		$attributes =
		
		// set the default instance properties
		$properties = array();

		// Set the default informant value
		$informant = NULL;

		if ( isset( $arguments[0] ) && is_array( $arguments[0]) )
		{
			$dom_container = &$arguments[0];

			// Check if the first argument is an object
			if ( isset( $dom_container[PROPERTY_DOM_ELEMENT] ) )
			{
				// Check if the object is an instance of the DOM element class
				if (
					! is_object( $dom_container[PROPERTY_DOM_DOCUMENT] ) ||
					get_class( $dom_container[PROPERTY_DOM_DOCUMENT] )
						!== CLASS_DOM_DOCUMENT
				)
					throw new Exception(
						EXCEPTION_INVALID_ARGUMENT.' ('.CLASS_DOM_DOCUMENT.')'
					);
				else
				{
					$dom_document = &$dom_container[PROPERTY_DOM_DOCUMENT];

					// set the DOM document property
					$properties[PROPERTY_DOM_DOCUMENT] = &$dom_document;
				}

				// Check if the object is an instance of the DOM element class
				if (
					! is_object( $dom_container[PROPERTY_DOM_ELEMENT] ) ||
					get_class( $dom_container[PROPERTY_DOM_ELEMENT] )
						!== CLASS_DOM_ELEMENT
				)

					throw new Exception(
						EXCEPTION_INVALID_ARGUMENT.'( '.CLASS_DOM_ELEMENT.')'
					);
				else
				{
					$dom_element = &$dom_container[PROPERTY_DOM_ELEMENT];

					if ( $dom_element->hasAttributes() === TRUE )
					{
						if (
							! is_null(
								$dom_element->{PROPERTY_DOM_ATTRIBUTES}
							)
						)
						{
							// loop on children of the DOM element
							foreach(
								$dom_element->{PROPERTY_DOM_ATTRIBUTES} as
									$index => $attribute
							)
							{
								if (
									is_object( $attribute ) &&
									get_class( $attribute ) === CLASS_DOM_ATTRIBUTE
								)

									// append the current attribute
									// to the collection of attributes
									$attributes[$attribute
										->{PROPERTY_DOM_NODE_NAME}] =
										$attribute->{PROPERTY_DOM_NODE_VALUE}
									;
							}

							// otherwise convert the object into an array 
							$attributes = ( array ) $arguments[0];
						}
					}

					// Set the DOM element property
					$properties[PROPERTY_DOM_ELEMENT] = &$dom_element;
				}
			}
			else

				$attributes = $arguments[0];
		}

		// check if an informant has been passed at construction
		if ( isset( $arguments[1] ) )

			$informant = $arguments[1];
			
		// build a collection of properties
		$properties = array_merge(
			$properties,
			array( PROPERTY_ATTRIBUTES => $attributes )
		);

		// call the constructor of the Entity class
		return parent::__construct( $properties, $informant );
	}

	/**
	* Cast an instance of the current object as a string
	*
	* @return	string	
	*/
	public function __toString()
	{
		$output = $this->export();

		return $output;
	}

	/**
	* Export an Element_Html to specifc output format
	*
	* 
	* @return	string	
	*/
	public function export(
		$format = FORMAT_TYPE_XML,
		$standalone = TRUE
	)
	{
		global $class_application;

		$class_view_builder = $class_application::getViewBuilderClass();

		$output = '';
		
		switch ( $format )
		{
			case FORMAT_TYPE_XML:

				if (
					
					isset( $this->{PROPERTY_WRAPPERS} ) &&
					is_array( $this->{PROPERTY_WRAPPERS} ) &&
					count( $this->{PROPERTY_WRAPPERS} ) &&
					( $wrappers = $this->{PROPERTY_WRAPPERS} ) &&
					isset( $this->{PROPERTY_DOM_DOCUMENT} ) &&
					is_object( $this->{PROPERTY_DOM_DOCUMENT} ) &&
					get_class( $this->{PROPERTY_DOM_DOCUMENT} ) ===
						CLASS_DOM_DOCUMENT &&
					( $dom_document = $this->{PROPERTY_DOM_DOCUMENT} )
				)
				{
					$output = $dom_document->saveXML(
						$this->{PROPERTY_WRAPPERS}[PROPERTY_WRAPPER]
					);
		
					$class_application::beautifySource(
						$output,
						NULL,
						TRUE,
						FALSE,
						FALSE
					);
				}
				
					break;
		}

		if ( FALSE === $standalone )
		{
			$context = array(
				PROPERTY_BODY => $output,
				PROPERTY_CACHE_ID => md5( $output )
			);

			$callback_parameters = $class_view_builder::getView(
				$context,
				VIEW_TYPE_INJECTION,
				FALSE,
				$standalone
			);
			
			list( , $output ) = each( $callback_parameters );
		}

		return $output;
	}

	/**
	* Wrap an element in specially crafted multicellular boxes
	* enabling fast renewal of key decisions made on design overhaul
	*
	* @param	string	$id	id
	* @return	nothing
	*/
	public function wrap( $id = NULL )
	{
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		if ( ! isset( $this->{PROPERTY_DOM_DOCUMENT} ) )
			
			throw new Exception(
				sprintf(
					EXCEPTION_INVALID_ENTITY,
					ENTITY_OBJECT
				)
			);
		else
		
			$dom_document = &$this->{PROPERTY_DOM_DOCUMENT};

		if ( isset( $this->{PROPERTY_DOM_ELEMENT} ) )
		{
			$dom_element = &$this->{PROPERTY_DOM_ELEMENT};
			
			if ( $dom_element->hasAttributes() )
			{
				if ( $dom_element->getAttribute( HTML_ATTRIBUTE_ID ) )

					// Retrieve the native id attribute value
					// of the current object instance
					$_id = $dom_element->getAttribute( HTML_ATTRIBUTE_ID );

				if ( $dom_element->getAttribute( HTML_ATTRIBUTE_CLASS ) )

					// Retrieve the native id attribute value
					// of the current object instance
					$_class = $dom_element->getAttribute( HTML_ATTRIBUTE_CLASS );
			}
		}

		// the provided id argument overrides native id attribute value
		if ( ! is_null( $id ) )

			// set the id used to build CSS class names
			$_id = $id;

		// the native class attribute value overrides previously set id
		if ( isset( $_class ) )

			// set the id used to build CSS class names
			$_id = $_class;
			
		else if ( ! isset( $_id ) && isset( $this->{PROPERTY_ATTRIBUTES} ) )

			$_id = '_'.substr(
				md5( serialize( $this->{PROPERTY_ATTRIBUTES} ) ),
				0,
				6
			);
		else
		
			$_id = '_'.uniqid();

		$wrappers = array();

		$mapping_table = array(
			0 => array(

				0 => $_id.'_'.
						STYLE_CLASS_FRAGMENT_CORNER.'_'.			
								STYLE_CLASS_FRAGMENT_POSITION_TOP.'_'.
									STYLE_CLASS_FRAGMENT_POSITION_LEFT,

				1 => $_id.'_'.
						STYLE_CLASS_FRAGMENT_STROKE.'_'.			
								STYLE_CLASS_FRAGMENT_POSITION_TOP,

				2 => $_id.'_'.
						STYLE_CLASS_FRAGMENT_CORNER.'_'.			
								STYLE_CLASS_FRAGMENT_POSITION_TOP.'_'.
									STYLE_CLASS_FRAGMENT_POSITION_RIGHT

			),
			1 => array(

				0 => $_id.'_'.
						STYLE_CLASS_FRAGMENT_STROKE.'_'.			
								STYLE_CLASS_FRAGMENT_POSITION_LEFT,

				// Prepare a placeholder for the current element
				1 => NULL,

				2 => $_id.'_'.
						STYLE_CLASS_FRAGMENT_STROKE.'_'.			
								STYLE_CLASS_FRAGMENT_POSITION_RIGHT

			),
			2 => array(

				0 => $_id.'_'.
						STYLE_CLASS_FRAGMENT_CORNER.'_'.			
								STYLE_CLASS_FRAGMENT_POSITION_BOTTOM.'_'.
									STYLE_CLASS_FRAGMENT_POSITION_LEFT,

				1 =>  $_id.'_'.
						STYLE_CLASS_FRAGMENT_STROKE.'_'.			
								STYLE_CLASS_FRAGMENT_POSITION_BOTTOM,

				2 => $_id.'_'.
						STYLE_CLASS_FRAGMENT_CORNER.'_'.			
								STYLE_CLASS_FRAGMENT_POSITION_BOTTOM.'_'.
									STYLE_CLASS_FRAGMENT_POSITION_RIGHT

			)
		);

		$prefix_element_type = $dom_element->{PROPERTY_DOM_NODE_NAME};

		$wrappers[PROPERTY_CELL] =
		
		$wrappers[PROPERTY_WRAPPER] =
		
		$wrappers[PROPERTY_ROW] = array();

		$element_wrapper = $dom_document->createElement( HTML_ELEMENT_DIV );

		if ( $prefix_element_type === HTML_ELEMENT_SPAN )
		{
			$element_div = $dom_document->createElement( HTML_ELEMENT_DIV );
			
			$element_div->appendChild( $element_wrapper ) ;

			$wrapper = &$element_div;

			if ( isset( $_class ) )

				$class_wrapper = 
					PROPERTY_WRAPPER.'_'.$_class.' '.
						PROPERTY_WRAPPER.'_'.HTML_ELEMENT_SPAN
				;
			else 

				$class_wrapper = PROPERTY_WRAPPER.'_'.HTML_ELEMENT_SPAN;

			$wrapper->setAttribute( HTML_ATTRIBUTE_CLASS, $class_wrapper );
		}	
		else

			$wrapper = &$element_wrapper;	

		// create a box wrapping all wrappers to be created
		// around the instance of Element_Html
		$wrappers[PROPERTY_WRAPPER] = &$wrapper;

		$element_wrapper->setAttribute(

			HTML_ATTRIBUTE_CLASS,

				STYLE_CLASS_WRAPPER.' '.

					$prefix_element_type.'_'.STYLE_CLASS_WRAPPER.' '.

						$prefix_element_type.'_'.$_id.'_'.STYLE_CLASS_WRAPPER

		);

		// browse the mapping table
		while ( list( $x, $coordinates_y ) = each( $mapping_table ) )
		{
			// create a wrapper for each row
			$wrappers[PROPERTY_ROW][$x] =
				$dom_document->createElement( HTML_ELEMENT_DIV );

			$wrappers[PROPERTY_ROW][$x]->setAttribute(

				HTML_ATTRIBUTE_CLASS,

				STYLE_CLASS_WRAPPER_DIMENSION_NULL.' '.

					STYLE_CLASS_WRAPPER_ROW.' '.

						$prefix_element_type.'_'.STYLE_CLASS_WRAPPER_ROW.' '.
	
									$prefix_element_type.'_'.
										$_id.'_'.
											STYLE_CLASS_WRAPPER_ROW.' '.
	
												$prefix_element_type.'_'.
													STYLE_CLASS_WRAPPER_ROW.
														'_'.$x.' '.
											
															STYLE_CLASS_WRAPPER_ROW.
																'_'.$x
			);

			if ( ! isset( $wrappers[PROPERTY_CELL][$x] ) )
			
				$wrappers[PROPERTY_CELL][$x] = array();

			while ( list( $y, $value ) = each( $coordinates_y ) )
			{
				// the current instance is wrapped among all other wrappers
				if ( $y === 1 && $x === 1 )

					$wrappers[PROPERTY_CELL][$x][$y] =
						$this->{PROPERTY_DOM_ELEMENT};
				else
				{
					$suffix_coordinates = '_'.$x.'_'.$y;
				
					// create a wrap at the current coordinates
					$wrappers[PROPERTY_CELL][$x][$y] =
						$dom_document->createElement( HTML_ELEMENT_DIV );
		
					// set the class attribute value for the current cell
					$wrappers[PROPERTY_CELL][$x][$y]->setAttribute(
	
						HTML_ATTRIBUTE_CLASS,

						$prefix_element_type.'_'.
							STYLE_CLASS_WRAPPER_DIMENSION_NULL.' '.

								$prefix_element_type.'_'.
									STYLE_CLASS_WRAPPER_CELL.' '.

									$prefix_element_type.'_'.
										STYLE_CLASS_WRAPPER_CELL.
											$suffix_coordinates.' '.

										$_id.'_'.
											$prefix_element_type.'_'.
												STYLE_CLASS_WRAPPER_CELL.
													$suffix_coordinates.' '.

											// helper used for mapping more naturally
											// wrappers and coordinates
											$value
					);
				}

				// append the current cell to its corresponding row wrapper
				$wrappers[PROPERTY_ROW][$x]->appendChild(
					$wrappers[PROPERTY_CELL][$x][$y]
				);
			}

			// append the current row to the global wrapper
			$element_wrapper->appendChild(
				$wrappers[PROPERTY_ROW][$x]
			);
		}

		$this->{PROPERTY_WRAPPERS} = $wrappers;
	}

	/**
	* Get a signature
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
	* Render an element 
	*
	* @return	nothing
	*/
	public static function render()
	{
		global $class_application, $verbose_mode;

		$class_view_builder = $class_application::getViewBuilderClass();

		$arguments = func_get_args();

		$rendering = NULL;

		if ( isset( $arguments[0] ) )

			$target = $arguments[0];
		else

			$target = NULL;		

		if ( ! is_null( $target ) )

			$rendering = $class_view_builder::renderElement(
				$target,
				static::getSignature()
			);

		return $rendering;
	}
}
