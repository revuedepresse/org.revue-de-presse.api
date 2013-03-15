<?php

$exception = NULL;

if ( ! function_exists( 'assignConstant' ) && defined( 'ENTITY_FUNCTION' ) )
	$exception = sprintf( EXCEPTION_MISSING_ENTITY, ENTITY_FUNCTION );

if ( ! defined( 'CLASS_APPLICATION' ) &&  defined( 'ENTITY_CLASS' ) )
	$exception = sprintf( EXCEPTION_MISSING_ENTITY, ENTITY_CLASS );

if ( ! is_null( $exception ) ) throw new Exception( $exception );
if ( ! isset( $class_application ) ) $class_application = CLASS_APPLICATION;

declareConstantsBatch( array(

	// Services

	'SERVICE_AMAZON' => 'amazon',
	'SERVICE_CACHING' => 'caching',
	'SERVICE_DEBUGGING' => 'debug',
	'SERVICE_DEPLOYMENT' => 'deployment',
	'SERVICE_FACEBOOK' => 'facebook',
    'SERVICE_GITHUB' => 'github',
	'SERVICE_GMAIL' => 'gmail',
	'SERVICE_IMAP' => 'imap',
	'SERVICE_INTROSPECTION' => 'introspection',
	'SERVICE_MEMCACHED' => 'memcached',
	'SERVICE_READITLATER' => 'readitlater',
	'SERVICE_MYSQL' => 'mysql',
	'SERVICE_SEFI' => 'sefi',
	'SERVICE_SESSION' => 'session',
	'SERVICE_SMTP' => 'smtp',
	'SERVICE_SYMFONY' => 'symfony',
	'SERVICE_SVN' => 'svn',
	'SERVICE_TWITTER' =>'twitter',

	// Services configuration

	'SETTING_ACTIVE' => 'active',		
	'SETTING_ALBUM_FLAGS' => 'album_flags',
	'SETTING_ALBUM_COMMENTS' => 'album_comments',
	'SETTING_ALBUM_METADATA' => 'album_metadata',
	'SETTING_ALBUM_NAVIGATION' => 'album_navigation',
	'SETTING_API_KEY' => 'api_key',
	'SETTING_ARTICLES_BASE' => 'articles_base',
	'SETTING_ASSERTION' => 'assertion',
	'SETTING_BASE_URL' => 'base_url',
	'SETTING_CACHING' => 'caching',
	'SETTING_CALLBACK' => 'callback',
	'SETTING_COMMENTS' => 'comments',
	'SETTING_DATABASE' => 'database',
	'SETTING_DIR_BASE' => 'base_dir',
	'SETTING_DIR_LIB' => 'dir_lib',
	'SETTING_EXPIRATION_TIME' => 'expiration_time',
	'SETTING_FLAGS' => 'flags',
	'SETTING_FLUSH_CACHE_FORM' => 'flush_cache_form',
	'SETTING_FLUSH_CACHE_MENU' => 'flush_cache_menu',
	'SETTING_FLUSH_CACHE_PROPERTIES' => 'flush_cache_properties',
	'SETTING_FORM_FEEDBACK' => 'form_send_feedback',
	'SETTING_HOST' => 'host',
	'SETTING_LOG' => 'log',
	'SETTING_MASK' => 'mask',
	'SETTING_MAXIMUM_LONG_LENGTH_AVATAR' => 'maximum_long_length_photograph',
	'SETTING_MAXIMUM_LONG_LENGTH_PHOTOGRAPH' => 'maximum_long_length_avatar',
	'SETTING_MODE' => 'mode',
	'SETTING_PASSWORD' => 'password',
	'SETTING_PORT' => 'port',
	'SETTING_PROTOCOL' => 'protocol',
	'SETTING_REPOSITORY' => 'repository',
	'SETTING_ROUTING' => 'routing',
	'SETTING_SECRET' => 'secret',
    'SETTING_TOKEN' => 'token',
	'SETTING_UNIT_TESTING' => 'unit_testing',
	'SETTING_USER_ID' => 'user_id',
	'SETTING_USER_NAME' => 'username',
	'SETTING_VERBOSE' => 'verbose',

	// DSN for PDO

	'DB_DSN_PREFIX' => 'data_source_prefix',
	'DB_DSN_PREFIX_MYSQL' => 'mysql',

	'DB_DEFAULT_HOST' => 'localhost',
) );

declareConstantsBatch(
	array(
		'API_AMAZON_CONSUMER_KEY' => 
			$class_application::getServiceProperty(
				SETTING_API_KEY, SERVICE_AMAZON
			),
		'API_AMAZON_CONSUMER_SECRET' => 
			$class_application::getServiceProperty(
				SETTING_PASSWORD, SERVICE_AMAZON
			),
		'API_AMAZON_USER_NAME' => 
			$class_application::getServiceProperty(
				SETTING_USER_NAME, SERVICE_AMAZON
			), // API for Amazon

		'API_FACEBOOK_API_KEY' => 
			$class_application::getServiceProperty(
				SETTING_API_KEY, SERVICE_FACEBOOK
			),
		'API_FACEBOOK_SECRET' => 
			$class_application::getServiceProperty(
				SETTING_PASSWORD, SERVICE_FACEBOOK
			),
		'API_FACEBOOK_USER_ID' => 
			$class_application::getServiceProperty(
				SETTING_USER_ID, SERVICE_FACEBOOK
			), // API for Facebook

		'API_GITHUB_TOKEN' =>
     		$class_application::getServiceProperty(
  				SETTING_TOKEN, SERVICE_GITHUB
 		), // API for Github

		'API_TWITTER_USER_NAME' => 
			$class_application::getServiceProperty(
				SETTING_USER_NAME, SERVICE_TWITTER
			),
		'API_TWITTER_CALLBACK' => 
			$class_application::getServiceProperty(
				SETTING_CALLBACK, SERVICE_TWITTER
			),
		'API_TWITTER_CONSUMER_KEY' => 
			$class_application::getServiceProperty(
				SETTING_API_KEY, SERVICE_TWITTER
			),
		'API_TWITTER_CONSUMER_SECRET' => 
			$class_application::getServiceProperty(
				SETTING_PASSWORD, SERVICE_TWITTER
			), // Api for Twitter 

		'DB_SEFI' => 
			$class_application::getServiceProperty(
				SETTING_DATABASE
			),
		'DB_HOST' => 
			$class_application::getServiceProperty(
				SETTING_HOST
			),
		'DB_USER_NAME' => 
			$class_application::getServiceProperty(
				SETTING_USER_NAME
			),
		'DB_PASSWORD' => 
			$class_application::getServiceProperty(
				SETTING_PASSWORD
			), // Database

		'DEPLOYMENT_CACHING' => 
			$class_application::getServiceProperty(
				SETTING_CACHING, SERVICE_DEPLOYMENT
			),
		'DEPLOYMENT_MODE' => 
			(int) $class_application::getServiceProperty(
				SETTING_MODE, SERVICE_DEPLOYMENT
			),
		'DEPLOYMENT_LOG' => 
			(int) $class_application::getServiceProperty(
				SETTING_LOG, SERVICE_DEPLOYMENT
			), // Deployment

		'DEBUGGING_ROUTING' => (
				$class_application::getServiceProperty(
					SETTING_ROUTING, SERVICE_DEBUGGING
				)
				? TRUE : FALSE
			),

		'DIR_BASE' => 
			$class_application::getServiceProperty(
				SETTING_DIR_BASE, SERVICE_SEFI
			)
		,
		'DIR_LIB_ABSOLUTE' => 
			$class_application::getServiceProperty(
				SETTING_DIR_LIB, SERVICE_SEFI
			)
		,

		'GMAIL_USER_NAME' => 
			$class_application::getServiceProperty(
				SETTING_USER_NAME, SERVICE_GMAIL
			),
		'GMAIL_PASSWORD' => 
			$class_application::getServiceProperty(
				SETTING_PASSWORD, SERVICE_GMAIL
			), // Gmail

		'IMAP_HOST' => 
			$class_application::getServiceProperty(
				SETTING_HOST, SERVICE_IMAP
			),
		'IMAP_PORT' => 
			$class_application::getServiceProperty(
				SETTING_PORT, SERVICE_IMAP
			),
		'IMAP_FLAGS' => 
			$class_application::getServiceProperty(
				SETTING_FLAGS, SERVICE_IMAP
			), // IMAP

		'INTROSPECTION_VERBOSE' => 
			$class_application::getServiceProperty(
				SETTING_VERBOSE, SERVICE_INTROSPECTION
			), // Introspection

		'MEMCACHED_ACTIVE' => (
				$class_application::getServiceProperty(
					SETTING_ACTIVE, SERVICE_MEMCACHED
				)
			),

		'MEMCACHED_EXPIRATION_TIME' => (
				$class_application::getServiceProperty(
					SETTING_EXPIRATION_TIME, SERVICE_MEMCACHED
				)
			),

		'MEMCACHED_FLUSH_CACHE_FORM' => (
					$class_application::getServiceProperty(
						SETTING_FLUSH_CACHE_FORM, SERVICE_MEMCACHED
					)
				? TRUE : FALSE
			),

		'MEMCACHED_FLUSH_CACHE_MENU' => (
					$class_application::getServiceProperty(
						SETTING_FLUSH_CACHE_MENU, SERVICE_MEMCACHED
					)
				? TRUE : FALSE
			),

		'MEMCACHED_FLUSH_CACHE_PROPERTIES' => (
					$class_application::getServiceProperty(
						SETTING_FLUSH_CACHE_PROPERTIES, SERVICE_MEMCACHED
					)
				? TRUE : FALSE
			),

		'MEMCACHED_HOST' => (
				$class_application::getServiceProperty(
					SETTING_HOST, SERVICE_MEMCACHED
				)
			),
		'MEMCACHED_PORT' => (
				$class_application::getServiceProperty(
					SETTING_PORT, SERVICE_MEMCACHED
				)
			), // Memcached
		
		'READITLATER_API_KEY' => 
			$class_application::getServiceProperty(
				SETTING_API_KEY, SERVICE_READITLATER
			),
		'READITLATER_USER_NAME' => 
			$class_application::getServiceProperty(
				SETTING_USER_NAME, SERVICE_READITLATER
			),
		'READITLATER_PASSWORD' => 
			$class_application::getServiceProperty(
				SETTING_PASSWORD, SERVICE_READITLATER
			), // ReadItLater

		'SEFI_ALBUM_COMMENTS' => 
				$class_application::getServiceProperty(
					SETTING_ALBUM_COMMENTS, SERVICE_SEFI
				)
			? TRUE : FALSE
		,
		'SEFI_ALBUM_FLAGS' => 
				$class_application::getServiceProperty(
					SETTING_ALBUM_FLAGS, SERVICE_SEFI
				)
			? TRUE : FALSE
		,
		'SEFI_ALBUM_MAXIMUM_LONG_LENGTH_PHOTOGRAPH' => 
			$class_application::getServiceProperty(
				SETTING_MAXIMUM_LONG_LENGTH_PHOTOGRAPH, SERVICE_SEFI
			)
		,		
		'SEFI_ALBUM_METADATA' => 
				$class_application::getServiceProperty(
					SETTING_ALBUM_METADATA, SERVICE_SEFI
				)
			? TRUE : FALSE
		,
		'SEFI_ALBUM_NAVIGATION' => 
				$class_application::getServiceProperty(
					SETTING_ALBUM_NAVIGATION, SERVICE_SEFI
				)
			? TRUE : FALSE
		,
		'SEFI_ARTICLES_BASE' => 
				$class_application::getServiceProperty(
					SETTING_ARTICLES_BASE, SERVICE_SEFI
				)
			? TRUE : FALSE
		,
		'SEFI_BASE_URL' => 
			$class_application::getServiceProperty(
				SETTING_BASE_URL, SERVICE_SEFI
			)
		,
		'SEFI_FORM_SEND_FEEDBACK' => 
				$class_application::getServiceProperty(
					SETTING_FORM_FEEDBACK, SERVICE_SEFI
				)
			? TRUE : FALSE
		,
		'SEFI_USER_PROFILE_MAXIMUM_LONG_LENGTH_AVATAR' => 
			$class_application::getServiceProperty(
				SETTING_MAXIMUM_LONG_LENGTH_AVATAR, SERVICE_SEFI
			), // Generic

		'SESSION_MASK_DOMAIN' => 
			$class_application::getServiceProperty(
				SETTING_MASK, SERVICE_SESSION
			), // Session

		'SMTP_HOST' => 
			$class_application::getServiceProperty(
				SETTING_HOST, SERVICE_SMTP
			),
		'SMTP_USER_NAME' => 
			$class_application::getServiceProperty(
				SETTING_USER_NAME, SERVICE_SMTP
			),
		'SMTP_PASSWORD' => 
			$class_application::getServiceProperty(
				SETTING_PASSWORD, SERVICE_SMTP
			),
		'SMTP_PORT' => 
			$class_application::getServiceProperty(
				SETTING_PORT, SERVICE_SMTP
			), // SMTP

		'SYMFONY_PROPEL_DATABASE' => 
			$class_application::getServiceProperty(
				SETTING_DATABASE, SERVICE_SYMFONY
			),
		'SYMFONY_PROPEL_HOST' => 
			$class_application::getServiceProperty(
				SETTING_HOST, SERVICE_SYMFONY
			),
		'SYMFONY_PROPEL_PASSWORD' => 
			$class_application::getServiceProperty(
				SETTING_PASSWORD, SERVICE_SYMFONY
			),
		'SYMFONY_PROPEL_PORT' => 
			$class_application::getServiceProperty(
				SETTING_PORT, SERVICE_SYMFONY
			),
		'SYMFONY_PROPEL_SECRET' => 
			$class_application::getServiceProperty(
				SETTING_SECRET, SERVICE_SYMFONY
			),
		'SYMFONY_PROPEL_USER_NAME' => 
			$class_application::getServiceProperty(
				SETTING_USER_NAME, SERVICE_SYMFONY
			), // Symfony

		'SVN_PROTOCOL' => 
			$class_application::getServiceProperty(
				SETTING_PROTOCOL, SERVICE_SVN
			),
		'SVN_HOST' => 
			$class_application::getServiceProperty(
				SETTING_HOST, SERVICE_SVN
			),
		'SVN_USER_NAME' => 
			$class_application::getServiceProperty(
				SETTING_USER_NAME, SERVICE_SVN
			),
		'SVN_PASSWORD' => 
			$class_application::getServiceProperty(
				SETTING_PASSWORD, SERVICE_SVN
			),
		'SVN_REPOSITORY' => 
			$class_application::getServiceProperty(
				SETTING_REPOSITORY, SERVICE_SVN
			), // SVN		

		'UNIT_TESTING_MODE_STATUS' => 
				$class_application::getServiceProperty(
					SETTING_UNIT_TESTING, SERVICE_DEPLOYMENT
				)
			? UNIT_TESTING_MODE_ENABLED
			: UNIT_TESTING_MODE_DISABLED
		,
		'UNIT_TESTING_ASSERTIVE_MODE_STATUS' => 
				$class_application::getServiceProperty(
					SETTING_ASSERTION, SERVICE_DEPLOYMENT
				)
			? UNIT_TESTING_ASSERTIVE_MODE_ENABLED
			: UNIT_TESTING_ASSERTIVE_MODE_DISABLED
		) // Unit testing
);

declareConstantsBatch(
	array(
		'IMAP_USERNAME' => GMAIL_USER_NAME,
		'IMAP_PASSWORD' => GMAIL_PASSWORD
	)
);

/**
*************
* Changes log
*
*************
* 2011 09 29
*************
*
* project :: wtw ::
*
* development :: version control system ::
*
* Setup SVN as a service
* 
* (trunk :: revision :: 350)
*
*************
* 2012 04 29
*************
*
* deployment :: api :: facebook ::
*
* Setup Facebook as a service
* 
* (branch 0.1 :: revision :: 857)
* (trunk :: revision :: 350)
*
*************
* 2012 05 01
*************
*
* deployment :: api :: facebook ::
*
* Add new setting for Facebook Api Service
*
*	user id
*
* Setup Session as a service
* Setup Symfony as a service
* 
* (branch 0.1 :: revision :: 861)
*
*************
* 2012 05 05
*************
*
* deployment :: introspection ::
*
* Setup Introspection as a service
* 
* (branch 0.1 :: revision :: 890)
*
*************
* 2012 05 09 
*************
*
* deployment :: introspection ::
*
* Declare base directory
* 
* (branch 0.1 :: revision :: 926)
*
*/