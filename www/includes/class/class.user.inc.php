<?php

/**
* User class
*
* @package  sefi
*/
class User extends Controller
{
	/**
	* Get a user name
	*
	* @param	mixed	$service	service
	* @return	mixed	user name
	*/
	public static function getUserName( $service = NULL )
	{
		$user_name = NULL;

		if ( is_null( $service ) )

			$user_name = API_TWITTER_USER_NAME;

		return $user_name;
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
}