<?php

if ( ! function_exists( 'assignConstant' ) ) throw new Exception( 'A function is missing' );

$exceptions = array( 
	'EXCEPTION_EVENT_BACKUP_FAILURE' => 'Impossible to make a backup of the last event.',
	'EXCEPTION_CONNECTION_FAILURE' => 'Connecting to the database is currently impossible.',
	'EXCEPTION_CONSISTENCY_DATA_ACCESS_ENTITY_DESCRIPTION_INCOMPLETE' => 'Sorry, the description of an entity is incomplete.',
	'EXCEPTION_CONSISTENCY_DATA_ACCESS_ENTITY_IDENTIFIER_MISSING' => 'Accessing data requires an entity identifier.',
	'EXCEPTION_CONSISTENCY_DATA_ACCESS_ENTITY_MISSING' => 'No record can be found for the entity "%s".',
	'EXCEPTION_CONSISTENCY_DATA_ACCESS_ENTITY_TYPE_DEFAULT_VALUE_MISSING' => 'The default value of the entity type "%s" does not seem to be defined.',
	'EXCEPTION_CONSISTENCY_DATA_ACCESS_QUERY_INVALID' => 'The query used for accessing some data is invalid.',
	'EXCEPTION_CONSISTENCY_ISSUE' => 'Some data consistency issue has been detected.',
	'EXCEPTION_DATABASE_CONNECTION_WRONG_DATABASE_NAME' => 'The name of the database is invalid for connecting to the server.',
	'EXCEPTION_DATABASE_CONNECTION_WRONG_HOST_NAME' => 'The host name is invalid for connecting to the database server.',
	'EXCEPTION_DATABASE_CONNECTION_WRONG_USER_NAME' => 'The user name is invalid for connecting to the database server.',
	'EXCEPTION_DEVELOPMENT_BEHAVIORAL_DEFINITION_MISSING' => 'Some systemic behaviour has to be defined here.',
	'EXCEPTION_DEVELOPMENT_CLASS_REQUIRED' => 'The "%s" class is to be implemented.',
	'EXCEPTION_DEVELOPMENT_CLASS_METHOD_REQUIRED' => 'The "%s" method of the "%s" class is to be implemented.',
	'EXCEPTION_DEVELOPMENT_MISSING_CLASS' => 'There might be some needs here for implementing a new class (%s).',
	'EXCEPTION_DEVELOPMENT_MISSING_ENTITY' => 'There might be some needs here for implementing a new kind of entity.',
	'EXCEPTION_DEVELOPMENT_PDO_DISCONTINUED' => 'PDO implementation is discontinued for this class.',
	'EXCEPTION_EXISTING_ENTITY' => 'Such a%s exists already.',
	'EXCEPTION_EXPECTATION_ARRAY' => 'an argument of type array is expected.',
	'EXCEPTION_EXPECTATION_OBJECT' => 'a argument of type object is expected.',
	'EXCEPTION_FURTHER_IMPLEMENTATION_REQUIRED' => 'There might be a need for few more lines of code here...',
	'EXCEPTION_KEYWORD_RESERVED' => 'Sorry, reserved keywords can not be used (%s)',
	'EXCEPTION_IMPOSSIBLE_CONNECTION ' => 'Sorry, it is now impossible to connect to %s.',
	'EXCEPTION_INCOMPLETE_SERVICE_CONFIGURATION' => 'Sorry, the configuration of services is incomplete.',
	'EXCEPTION_INCONSISTENT_RECORDS' => 'The provided records are inconsistent.',
	'EXCEPTION_INVALID_ARGUMENT' => 'The provided arguments are not valid',
	'EXCEPTION_INVALID_CALLBACK' => 'The callback is not valid.',
	'EXCEPTION_INVALID_CLASS_NAME' => 'The class name is not valid.',
	'EXCEPTION_INVALID_CONFIGURATION' => 'The provided configuration is invalid.',
	'EXCEPTION_INVALID_CONTENTS' => 'The contents are not valid.',
	'EXCEPTION_INVALID_CREDENTIALS' => 'The provided credentials are not valid.',
	'EXCEPTION_INVALID_CREDENTIALS_DATABASE_CONNECTION' => 'The provided credentials for conntected to the database are not valid.',
	'EXCEPTION_INVALID_DATABASE' => 'The provided database name is invalid.',
	'EXCEPTION_INVALID_DESTINATION_PATH' => 'The destination path is invalid.',
	'EXCEPTION_INVALID_DIRECTORY' => 'The provided directory is not valid.',
	'EXCEPTION_INVALID_ENTITY' => 'The provided %s is not valid.',
	'EXCEPTION_INVALID_ERROR_HANDLER' => 'The error handler needs some revision.',
	'EXCEPTION_INVALID_EVENT_SOURCE' => 'The event source is not valid.',
	'EXCEPTION_INVALID_FIELD_TYPE' => 'There is no class definition for such field type.',
	'EXCEPTION_INVALID_FILE_ACCESS_MODE' => 'The file access mode is not valid.',
	'EXCEPTION_INVALID_FILE_NAME' => 'The file name is not valid.',
	'EXCEPTION_INVALID_FILE_PATH' => 'The file path is not valid.',
	'EXCEPTION_INVALID_FILE_TYPE' => 'The file type is not valid.',
	'EXCEPTION_INVALID_HTML_TAG' => 'There is no class definition for such HTML tag',
	'EXCEPTION_INVALID_I18N_SCOPE' => 'The i18n scope is not valid.',
	'EXCEPTION_INVALID_IDENTIFIER' => 'The provided identifier is not valid.',
	'EXCEPTION_INVALID_INITIALZATION' => 'Sorry some initialization went wrong.',
	'EXCEPTION_INVALID_MEMCACHED_SERVER' => 'The memcached server settings are not valid.',
	'EXCEPTION_INVALID_OPERATION' => 'The %s operation is not valid.',
	'EXCEPTION_INVALID_PROPERTY_NAME' => 'the name of the property is not valid.',
	'EXCEPTION_INVALID_PROPERTY' => 'A required property is not valid (%s).',
	'EXCEPTION_INVALID_QUERY' => 'The provided query is not valid.',
	'EXCEPTION_INVALID_PROPORTIONS' => 'The proportions of a given photograph are not valid',
	'EXCEPTION_INVALID_ROUTE' => 'The provided route is not valid.',
	'EXCEPTION_INVALID_SERVER_CONFIGURATION_INVALID_TIDY_EXTENSION' => 'The tidy extension for PHP is most likely invalid.',
	'EXCEPTION_INVALID_SERVER_CONFIGURATION_TEMPORARY_DIRECTORY' => 'The temporary directory is not set properly.',
	'EXCEPTION_INVALID_SERVICE_CONFIGURATION' => 'The provided service configuration is invalid.',
	'EXCEPTION_LOST_IN_TRANSLATION' => 'A piece of information was lost in translation',
	'EXCEPTION_MISSING_ENTITY' => 'A %s is missing.',
	'EXCEPTION_MISSING_LIBRARY' => 'The %s library is missing.',
	'EXCEPTION_MISSING_PROPERTY' => 'A property is missing (%s).',
	'EXCEPTION_MISSING_RESOURCE' => 'A file is missing',
	'EXCEPTION_NOBODY_THERE' => 'Nobody is currently logged in.',
	'EXCEPTION_OPERATION_FAILURE_PROCESSING' => 'The following operation failed: entity processing',
	'EXCEPTION_OPERATION_FAILURE_REMOVAL' => 'The following operation failed: entity removal',
	'EXCEPTION_RIGHTS_MANAGEMENT_CREDENTIALS_INSUFFICIENT' => 'Sorry, your credentials are insufficient for running this operation',
	'EXCEPTION_SOURCE_BUILDER_SYNTAX_ERROR' => 'Sorry, source builder has generated some code with incorrect syntax.',
	'EXCEPTION_SQL_INVALID_CLAUSE_ORDER_BY' => 'The Order By clause is not valid.',
	'EXCEPTION_UNDEFINED_CONFIGURATION_PROPERTY' => 'Some configuration properties are missing.',
	'EXCEPTION_UNDEFINED_DATA_TYPES' => 'Please fix the data type definition.',
	'EXCEPTION_UNDEFINED_ENTITY' => 'Undefined %s',
	'EXCEPTION_UNDEFINED_FIELD_VALUES' => 'Some field values are undefined.',
	'EXCEPTION_UNDEDINED_FIELD_TYPES' => 'Some field types are undefined.',
	'EXCEPTION_UNDEDINED_FIELDS' => 'The provided fields are not valid.',
	'EXCEPTION_UNDEFINED_FORM_IDENTIFIER' => 'The current form identifier has not been defined yet',
	'EXCEPTION_UNDEFINED_KEY_IDENTIFER' => 'A key identifier is missing.',
	'EXCEPTION_UNDEFINED_OPTIONS' => 'Some options are missing',
	'EXCEPTION_UNIT_TESTING_ASSERTION_INVALID' => 'An assertion is not valid (%s)'
);

declareConstantsBatch( $exceptions, FALSE );

/**
*************
* Changes log
*
*************
* 2011 10 03
*************
*
* project :: wtw ::
*
* development :: introspection ::
* 
* Declare invalid property exception
* 
* (branch 0.1 :: revision :: 674)
* (branch 0.2 :: revision :: 379)
*
*************
* 2011 10 20
*************
*
* project :: wtw ::
*
* development :: introspection ::
* 
* Declare exceptions array
* 
* (branch 0.1 :: revision :: 724)
* (branch 0.2 :: revision :: 393)
*
*************
* 2012 05 01
*************
*
* project :: wtw ::
*
* development :: object-relational mapping ::
*
* Declare exception for invalid Order By clause in SQL queries
* 
* (branch 0.1 :: revision :: 874)
*
*/
