<?php

/**
* Content class
*
* @package  sefi
*/
class Content extends Controller
{
	/**
	* Get a content by providing its id
	*
	* @param	integer	$id	identifier
	* @return	object	Store
	*/
	public static function getById($id)
	{
		if ( ! is_numeric( $id  ) )
			
			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		return self::getByProperty(
			$id,
			PROPERTY_ID,
			array(
				PROPERTY_BODY,
				PROPERTY_ROUTE =>
					array(
						PROPERTY_FOREIGN_KEY =>
							PREFIX_TABLE_COLUMN_ROUTE.PROPERTY_ID
					),
				PROPERTY_STATUS,
				PROPERTY_SUBTITLE,
				PROPERTY_TITLE,
				PROPERTY_TYPE
			),
			CLASS_CONTENT,
			TRUE
		);	
	}

	/**
	* Get the class signature
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
	* Make an instance of the Route class
	*
	* @return	object	Route
	*/
	public static function make()
	{
		global $class_application;
		
		$class_dumper = $class_application::getDumperClass();

		$arguments = func_get_args();

		if (isset($arguments[0]))

			$title = $arguments[0];
		else

			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		if (isset($arguments[1]))

			$route = $arguments[1];
		else

			throw new Exception(EXCEPTION_INVALID_ARGUMENT);

		if (!isset($arguments[2]))

			$type = NULL;
		else
		
			$type = $arguments[2];

		if (is_string($title))

			$title = $title;
		else

			throw new \Exception(EXCEPTION_INVALID_ARGUMENT);

		if (is_numeric($route))

			$route_id = $route;
		else
			
			throw new \Exception(EXCEPTION_INVALID_ARGUMENT);

		// fetch the default content type: any
		$default_content_type = self::getDefaultType();

		if (is_null($type))

			// fetch the default content type: any
			$content_type = $default_content_type;

		else 
		{
			$properties = array(
				PROPERTY_NAME => $type,
				PROPERTY_ENTITY => ENTITY_CONTENT
			);

			// fetch the content type
			$content_type = self::getTypeValue($properties);
		}

		$properties = array(
			PREFIX_TABLE_COLUMN_ROUTE.PROPERTY_ID => array(
				PROPERTY_FOREIGN_KEY => $route_id
			),
			PROPERTY_STATUS => ENTITY_STATUS_ACTIVE,
			PROPERTY_TITLE => $title,
			PROPERTY_TYPE => $content_type
		);

		return self::add($properties);
	}
}
?>