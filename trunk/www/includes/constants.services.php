<?php

$exception = NULL;

if ( ! function_exists( 'assignConstant' ) && defined( 'ENTITY_FUNCTION' ) )

		$exception = sprintf( EXCEPTION_MISSING_ENTITY, ENTITY_FUNCTION );

if ( ! defined( 'CLASS_APPLICATION' ) &&  defined( 'ENTITY_CLASS' ) )

		$exception = sprintf( EXCEPTION_MISSING_ENTITY, ENTITY_CLASS );

if ( ! is_null( $exception ) )

	throw new Exception( $exception );

if ( ! isset( $class_application ) )

	$class_application = CLASS_APPLICATION;

// Services

declareConstantsBatch(
	array(

		// services

		'SERVICE_DEBUGGING' => 'debug',
		'SERVICE_CACHING' => 'caching',
		'SERVICE_DEPLOYMENT' => 'deployment',
		'SERVICE_GMAIL' => 'gmail',
		'SERVICE_IMAP' => 'imap',
		'SERVICE_MEMCACHED' => 'memcached',
		'SERVICE_READITLATER' => 'readitlater',
		'SERVICE_MYSQL' => 'mysql',
		'SERVICE_SEFI' => 'sefi',
		'SERVICE_SMTP' => 'smtp',
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
		'SETTING_EXPIRATION_TIME' => 'expiration_time',
		'SETTING_FLAGS' => 'flags',
		'SETTING_FLUSH_CACHE_FORM' => 'flush_cache_form',
		'SETTING_FLUSH_CACHE_MENU' => 'flush_cache_menu',
		'SETTING_FORM_FEEDBACK' => 'form_feedback',
		'SETTING_HOST' => 'host',
		'SETTING_MAXIMUM_LONG_LENGTH_AVATAR' => 'maximum_long_length_photograph',
		'SETTING_MAXIMUM_LONG_LENGTH_PHOTOGRAPH' => 'maximum_long_length_avatar',
		'SETTING_MODE' => 'mode',
		'SETTING_PASSWORD' => 'password',
		'SETTING_PORT' => 'port',
		'SETTING_ROUTING' => 'routing',
		'SETTING_UNIT_TESTING' => 'unit_testing',
		'SETTING_USER_NAME' => 'username',

		// DSN for PDO

		'DB_DSN_PREFIX' => 'data_source_prefix',
		'DB_DSN_PREFIX_MYSQL' => 'mysql',

		'DB_DEFAULT_HOST' => 'localhost',
	)
);

declareConstantsBatch(
	array(

		// Api for Twitter 

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
			),

		// Database

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
			),

		// SMTP

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
			),

		// Deployment

		'DEBUGGING_ROUTING' => (
				$class_application::getServiceProperty(
					SETTING_ROUTING, SERVICE_DEBUGGING
				)
				?
					TRUE
				:
					FALSE
			),

		// Deployment

		'DEPLOYMENT_CACHING' => 
			$class_application::getServiceProperty(
				SETTING_CACHING, SERVICE_DEPLOYMENT
			),
		'DEPLOYMENT_MODE' => 
			(int) $class_application::getServiceProperty(
				SETTING_MODE, SERVICE_DEPLOYMENT
			),

		// Gmail

		'GMAIL_USER_NAME' => 
			$class_application::getServiceProperty(
				SETTING_USER_NAME, SERVICE_GMAIL
			),
		'GMAIL_PASSWORD' => 
			$class_application::getServiceProperty(
				SETTING_PASSWORD, SERVICE_GMAIL
			),

		// IMAP

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
			),

		// Memcached

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
				?
					TRUE
				:
					FALSE
			),

		'MEMCACHED_FLUSH_CACHE_MENU' => (
					$class_application::getServiceProperty(
						SETTING_FLUSH_CACHE_MENU, SERVICE_MEMCACHED
					)
				?
					TRUE
				:
					FALSE
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
			),
		
		// ReadItLater

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
			),

		// Generic

		'SEFI_ALBUM_COMMENTS' => 
				$class_application::getServiceProperty(
					SETTING_ALBUM_COMMENTS, SERVICE_SEFI
				)
			?
				TRUE
			:
				FALSE
		,
		'SEFI_ALBUM_FLAGS' => 
				$class_application::getServiceProperty(
					SETTING_ALBUM_FLAGS, SERVICE_SEFI
				)
			?
				TRUE
			:
				FALSE
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
			?
				TRUE
			:
				FALSE
		,
		'SEFI_ALBUM_NAVIGATION' => 
				$class_application::getServiceProperty(
					SETTING_ALBUM_NAVIGATION, SERVICE_SEFI
				)
			?
				TRUE
			:
				FALSE
		,
		'SEFI_ARTICLES_BASE' => 
				$class_application::getServiceProperty(
					SETTING_ARTICLES_BASE, SERVICE_SEFI
				)
			?
				TRUE
			:
				FALSE
		,		
		'SEFI_BASE_URL' => 
			$class_application::getServiceProperty(
				SETTING_BASE_URL, SERVICE_SEFI
			)
		,
		'SEFI_FORM_FEEDBACK' => 
				$class_application::getServiceProperty(
					SETTING_FORM_FEEDBACK, SERVICE_SEFI
				)
			?
				TRUE
			:
				FALSE
		,
		'SEFI_USER_PROFILE_MAXIMUM_LONG_LENGTH_AVATAR' => 
			$class_application::getServiceProperty(
				SETTING_MAXIMUM_LONG_LENGTH_AVATAR, SERVICE_SEFI
			)
		,

		// Unit testing

		'UNIT_TESTING_MODE_STATUS' => 
				$class_application::getServiceProperty(
					SETTING_UNIT_TESTING, SERVICE_DEPLOYMENT
				)
			?
				UNIT_TESTING_MODE_ENABLED
			:
				UNIT_TESTING_MODE_DISABLED
		,
		'UNIT_TESTING_ASSERTIVE_MODE_STATUS' => 
				$class_application::getServiceProperty(
					SETTING_ASSERTION, SERVICE_DEPLOYMENT
				)
			?
				UNIT_TESTING_ASSERTIVE_MODE_ENABLED
			:
				UNIT_TESTING_ASSERTIVE_MODE_DISABLED
		)
);

declareConstantsBatch(
	array(
		'IMAP_USERNAME' => GMAIL_USER_NAME,
		'IMAP_PASSWORD' => GMAIL_PASSWORD
	)
);