<?php

/**
* User handler class
*
* Class for user management
* @package  sefi
*/
class User_Handler extends Administrator
{
    /**
    * Log a member in
    *
    * @param	integer	$member_id		member identifier
    * @param	boolean	$administrator	administrator flag
    * @return 	nothing
    */	
	public static function &logMemberIn($member_id, $administrator = FALSE)
	{
		// log a member in
		return Member::login($member_id, $administrator);		
	}

    /**
    * Alias to the anybody_there method
    *
    * @param	boolean		$administration		administration flag
    * @return	mixed 		qualities
    */
    public static function anybodyThere( $administration = FALSE )
	{
		if ( ! $administration )

			$store = STORE_MEMBER;
		else

			$store = STORE_ADMINISTRATOR;

		// check if a subscriber is logged in
		if ( self::loggedIn( $administration ) && $_SESSION[$store] )

			// return the member store
			return $_SESSION[$store];
		else

			return FALSE;
	}

    /**
    * Check if a user has access to an asset
    *
    * @param	string	$asset			asset
    * @param	integer	$asset_type 	asset type
    * @return	mixed 	
    */
    public static function authorizedUser(
		$asset = NULL,
		$asset_type = ENTITY_FORM
	)
	{
		global $class_application, $verbose_mode;

		$class_data_fetcher = $class_application::getDataFetcherClass();

		$class_dumper = $class_application::getDumperClass();

		$authorization_granted = FALSE;

		if ( is_null( $asset ) )
		{
			// replace the action prefix with an empty string in the current URI
			$affordance = substr(
				str_replace(
					array(
						PREFIX_ROOT.PREFIX_ACTION,
						EXTENSION_PHP,
						EXTENSION_HTML,
						'-'
					),
					array(
						CHARACTER_EMPTY_STRING,
						CHARACTER_EMPTY_STRING,
						CHARACTER_EMPTY_STRING,
						'.'
					),
					$_SERVER['REQUEST_URI']
				),
				1
			);

			$asset = $affordance;
		}

		switch ( $asset_type )
		{
			case ENTITY_FORM:

				$form_configuration =
					$class_data_fetcher::fetchFormConfiguration( $asset )
				;

				if (
					is_object( $form_configuration ) &&
					is_null( $form_configuration->privilege_level )
				)
				
					$authorization_granted = TRUE;

					break;
		}

		return $authorization_granted;
	}

    /**
    * Get an array of qualities
    *
    * @param	integer	$page	representing a page
    * @return	mixed
    */
    public static function get_qualities($page = PAGE_SIGN_UP_START)
	{
		// check an array of qualities
		if (
			isset($_SESSION[STORE_SUBSCRIBER]) &&
			is_object($_SESSION[STORE_SUBSCRIBER]) &&
			get_class($_SESSION[STORE_SUBSCRIBER]) == CLASS_STANDARD_CLASS &&
			isset($_SESSION[STORE_SUBSCRIBER]->{STORE_QUALITIES}) &&
			is_array($_SESSION[STORE_SUBSCRIBER]->{STORE_QUALITIES}) &&
			isset($_SESSION[STORE_SUBSCRIBER]->{STORE_QUALITIES}[$page]) &&
			is_array($_SESSION[STORE_SUBSCRIBER]->{STORE_QUALITIES}[$page]) &&
			count($_SESSION[STORE_SUBSCRIBER]->{STORE_QUALITIES}[$page]) != 0
		)
			return $_SESSION[STORE_SUBSCRIBER]->{STORE_QUALITIES}[$page];
		else
			return false;
	}

    /**
    * Check if a member is logged in
    *
    * @param	boolean		$administrator		administrator flag
    * @return 	boolean		logged in status
    */	
	public static function loggedIn($administrator = FALSE)
	{
		// check the member store 
		if (
			!$administrator &&
			isset($_SESSION[STORE_MEMBER]) &&
			is_object($_SESSION[STORE_MEMBER]) &&
			isset($_SESSION[STORE_MEMBER]->{ROW_MEMBER_IDENTIFIER}) &&
			is_numeric($_SESSION[STORE_MEMBER]->{ROW_MEMBER_IDENTIFIER})
			||
			$administrator &&
			isset($_SESSION[STORE_ADMINISTRATOR]) &&
			is_object($_SESSION[STORE_ADMINISTRATOR]) &&
			isset($_SESSION[STORE_ADMINISTRATOR]->{ROW_MEMBER_IDENTIFIER}) &&
			is_numeric($_SESSION[STORE_ADMINISTRATOR]->{ROW_MEMBER_IDENTIFIER})
		)

			return TRUE;
		else

			return FALSE;
	}

    /**
    * Logout a member
    *
    * @param	boolean	$administrator	administrator flag
    * @return 	nothing
    */
    public static function logout($administrator = false)
	{
		// set the member class name
		$class_member = CLASS_MEMBER;

		// logout the logged in member
		$class_member::logout($administrator = false);
	}

    /**
    * Generate a random password
    *
    * @return	string	password
    */
    public static function randomPassword()
	{
		$class_processor = CLASS_PROCESSOR;

		return $class_processor::randomString();
	}
}
?>