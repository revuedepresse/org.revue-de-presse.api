<?php

/**
* Affordance class
*
* Class for handling an affordance
* @package  sefi
*/
class Affordance extends cid\Token
{
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
	* Get types
	*
	* @return	array	types
	*/
	public static function getTypes()
	{
		// prepare the select options action token
		$action_select_options = str_replace( '.', '_', ACTION_SELECT_OPTIONS );

		// retrieve the default affordance type
		$default_affordance_type = self::fetchDefaultType()->{PROPERTY_VALUE};

		// retrieve the select options affordance type
		$affordance_type_select_options = self::getTypeValue(
			array(
				PROPERTY_NAME => $action_select_options,
				PROPERTY_ENTITY => ENTITY_AFFORDANCE
			)
		);

		return array(
			'default_affordance_type' => $default_affordance_type,
			'affordance_type_select_options' => $affordance_type_select_options
		);
	}
}
