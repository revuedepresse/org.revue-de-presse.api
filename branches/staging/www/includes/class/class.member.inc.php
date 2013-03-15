<?php

/**
* Member class
*
* @package  sefi
*/
class Member extends Controller
{
    /**
    * Get the qualities of a member
    *
    * @param	boolean		$administration		administration flag
    * @return 	mixed		qualities
    */	
	public static function &getQualities( $administration = FALSE )
	{
		$qualities = array();

		$store = STORE_MEMBER;

		if ( $administration )

			$store = STORE_ADMINISTRATOR;

		// check if the member store is already defined in session
		if (
			isset( $_SESSION[$store] ) &&
			is_object( $_SESSION[$store] )
		)

			// return the qualities of a member
			$qualities = $_SESSION[$store];

		// return the qualities
		return $qualities;
	}

    /**
    * Login a member
    *
    * @param	integer		$member_id		member identifier
    * @param	integer		$administrator	administrator flag
    * @return 	nothing
    */	
	public static function &login(
		$member_id = NULL,
		$administrator = FALSE
	)
	{
		$cookie = COOKIE_MEMBER_IDENTIFER;

		$store = STORE_MEMBER;

		if ($administrator)
		{
			$cookie = COOKIE_ADMINISTRATOR_IDENTIFER;

			$store = STORE_ADMINISTRATOR;
		}

		// check if the member store is already defined in session
		if (
			!isset($_SESSION[$store]) ||
			!is_object($_SESSION[$store])
		)

			// declare a new standard class
			$_SESSION[$store] = new stdClass();
	
		// check the member identifier
		if (isset($member_id) && is_numeric($member_id))
		{ 
			$qualities = self::fetchUserName($member_id, $administrator);

			if (is_array($qualities) && count($qualities))
			{
				list($member_id, $group_id, $user_name) = $qualities;

				// set the member identifier			
				$_SESSION[$store]->{ROW_MEMBER_IDENTIFIER} = $member_id;

				// set the member user name
				$_SESSION[$store]->{ROW_MEMBER_USER_NAME} = $user_name;

				// set the member user name
				$_SESSION[$store]->{ROW_GROUP_IDENTIFIER} = $group_id;				

				// set a cookie
				setcookie(
					sha1(
						$cookie.
						$member_id.
						$user_name
					),
					$member_id,
					time() + 3600 * 24 * 15
				);
			}
		}

		return $_SESSION[$store];
	}

    /**
    * Clean a member store
    *
    * @return 	nothing
    */	
	public static function cleanMemberStore($storage_type = STORE_SESSION)
	{
		// check if the member store is already defined in session
		if (isset($_SESSION[STORE_MEMBER]))
		
			unset($_SESSION[STORE_MEMBER]);
	}

    /**
    * Get the email of a member
    *
    * @param	boolean	$administration	administration flag
    * @return 	string	email
    */	
	public static function getEmail( $administration = FALSE )
	{
		// set the data fetcher class name
		$class_data_fetcher = CLASS_DATA_FETCHER;

		// set the user handler class name
		$class_user_handler = CLASS_USER_HANDLER;

		// check if a user is logged in
		if (!$class_user_handler::loggedIn())
	
			$class_application::jumpTo(PREFIX_ROOT);

		// get the qualities of the current logged in member
		$qualities = self::getQualities($administration);

		// get the email of the current logged in member
		return $class_data_fetcher::getMemberEmail($qualities->{ROW_MEMBER_IDENTIFIER});
	}

    /**
    * Fetch qualities from a key
    *
    * @param	mixed	$where_clause	where clause
    * @return 	array	qualities
    */	
	public static function fetchQualities( $where_clause )
	{
		$qualities = array();

		if (is_array($where_clause) && count($where_clause) == 1)
		{
			$class_data_fetcher = CLASS_DATA_FETCHER;			
		
			$member_qualities = $class_data_fetcher::fetchMemberQualities($where_clause);
			
			list($user_id, $qualities) = each($member_qualities);

			if (!empty($qualities->{PROPERTY_AVATAR}))
			{
				$class_photograph = CLASS_PHOTO;

				$qualities->{PROPERTY_AVATAR} =
				str_replace(
					array(
						'{'.HTML_ATTRIBUTE_ALT.'}',
						'{'.HTML_ATTRIBUTE_TITLE.'}',

					),
					$qualities->{ROW_MEMBER_USER_NAME},
					$class_photograph::loadPhotography(
						$qualities->{PROPERTY_AVATAR},
						TRUE
					)
				);
			}
		}
	
		return $qualities;
	}

    /**
    * Get the identifier of a member
    *
    * @param	boolean	$administration	administration flag
    * @param	boolean	$redirect		redirect flag
    * @return 	string	email
    */
	public static function getIdentifier(
		$administration = FALSE,
		$redirect = TRUE
	)
	{
		global $class_application;

		// set the dumper class name
		$class_dumper = $class_application::getDumperClass();

		// set the member class name
		$class_member = $class_application::getMemberClass();

		// set the user handler class name
		$class_user_handler = $class_application::getUserHandlerClass();

		// check if a user is logged in
		if (
			! $class_user_handler::loggedIn( $administration ) &&
			$redirect
		)

			$class_application::jumpTo( PREFIX_ROOT );

		// get the qualities of the logged in member 
		$qualities = $class_member::getQualities( $administration );

		if ( isset( $qualities->{ROW_MEMBER_IDENTIFIER} ) )

			$identifier = $qualities->{ROW_MEMBER_IDENTIFIER};
		else

			$identifier = NULL;

		return $identifier;
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
    * Get the user name of a member
    *
    * @param	boolean	$administration	administration flag
    * @return 	string	user name
    */	
	public static function getUserName($administration = false)
	{
		// set the member class name
		$class_member = CLASS_MEMBER;

		// set the user handler class name
		$class_user_handler = CLASS_USER_HANDLER;

		// check if a user is logged in
		if (!$class_user_handler::loggedIn())
	
			$class_application::jumpTo(PREFIX_ROOT);

		// get the qualities of the logged in member 
		$qualities = $class_member::getQualities($administration);

		return $qualities->{ROW_MEMBER_USER_NAME};
	}

    /**
    * Logout a member
    *
    * @param	boolean	$administrator	administrator flag
    * @return 	nothing
    */	
	public static function logout($administrator = FALSE)
	{
		// set the application class name
		$class_application = CLASS_APPLICATION;

		// destroy the current session
		$class_application::destroySession($administrator);
	}
}
?>