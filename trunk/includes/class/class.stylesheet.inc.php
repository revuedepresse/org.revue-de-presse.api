<?php

/**
* Stylesheet class
*
* Class for stylesheet management
* @package  sefi
*/
class Stylesheet extends Placeholder
{
	/**
	* Get a Placeholder by providing its id
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
				PROPERTY_STATUS,
				PROPERTY_TYPE,
				PROPERTY_NAME
			),
			CLASS_STYLESHEET,
			TRUE
		);	
	}

    /**
    * Get placeholders
    *
	* @param	boolean	$name	stylesheet name
	* @return	mixed	placeholders
	*/
	public static function getPlaceholders( $name = NULL )
	{
		global $class_application, $verbose_mode;

		$class_arc = $class_application::getArcClass();

		$class_dumper = $class_application::getDumperClass();

		$class_edge = $class_application::getEdgeClass();

		$class_placeholder = $class_application::getPlaceholderClass();

		$exception = EXCEPTION_INVALID_ARGUMENT;

		$placeholders = array();

		if ( is_null( $name ) )

			$name = STYLESHEET_NAME_MAIN;

		if ( ! is_string( $name ) )
		
			throw new Exception( $exception );

		/**
		*
		* Get an instance of the Stylesheet class
		*
		*/
		$stylesheet = self::getByConditions(
			array( PROPERTY_NAME => $name ),
			ENTITY_STYLESHEET
		);
		
		/**
		*
		* Check if the instance of the stylesheet class
		* has a valid id property
		*
		*/
		if (
			! is_object( $stylesheet ) ||
			! isset( $stylesheet->{PROPERTY_ID} ) ||
			! is_numeric( $stylesheet->{PROPERTY_ID} ) ||
			! ( $stylesheet_id = $stylesheet->{PROPERTY_ID} )
		)

			throw new Exception( $exception );

		$results = $class_arc::getByDestinationKey(
			$stylesheet_id,
			ENTITY_STYLESHEET,
			PROPERTY_ENCAPSULATION
		);

		if ( is_object( $results ) )
		
			$arcs = array( $results );
		else

			$arcs = $results;

		if ( is_array( $arcs ) && count( $arcs ) )

			while ( list( , $arc ) = each( $arcs ) )

				/**
				*
				* Check if the current instance of the Arc class is valid
				*
				*/
				if (
					is_object( $arc) &&
					( get_class( $arc ) === $class_arc ) &&
					isset( $arc->{PROPERTY_SOURCE} )
				)
				{
					$edge_id = $arc->{PROPERTY_SOURCE};
				
					$edge = $class_edge::getById( $edge_id );

					/**
					*
					* Check if the current instance of the Edge class
					* has a valid key property
					*
					*/		
					if (
						is_object( $edge ) &&
						( get_class( $edge ) === $class_edge ) &&
						isset( $edge->{PROPERTY_KEY} ) &&
						is_numeric( $edge->{PROPERTY_KEY} ) &&
						( $edge_key = $edge->{PROPERTY_KEY} ) 
					)

						$placeholders[] =
							$class_placeholder::getById( $edge_key )
						;
				}
		
		reset( $placeholders );

		return $placeholders;
	}

    /**
    * Get a stylesheet
    *
	* @param	boolean	$namespace	namespace flag
	* @return	string	signature
	*/
	public static function getSignature( $namespace = TRUE )
	{
		$_class = __CLASS__;

		if ( ! $namespace )

			list(
				$_namespace,
				$_class
			) = explode( '\\', __CLASS__ );

		return $_class;
	}

	/**
	* Make an instance of the Stylesheet class
	*
	* @return	object	Stylesheet instance
	*/
	public static function make()
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();

		$default_type = self::getDefaultType();

		$arguments = func_get_args();

		if ( isset( $arguments[0] ) )

			$name = $arguments[0];
		else

			throw new Exception( EXCEPTION_INVALID_ARGUMENT );

		if ( isset( $arguments[1] ) )

			$type = $arguments[1];
		else

			$type = $default_type;

		if ( isset( $arguments[2] ) )

			$status = $arguments[2];
		else

			$status  = ENTITY_STATUS_INACTIVE;

		$properties = array(
			PROPERTY_NAME => $name,
			PROPERTY_STATUS => $status,
			PROPERTY_TYPE => $type
		);

		return self::add( $properties );
	}
}