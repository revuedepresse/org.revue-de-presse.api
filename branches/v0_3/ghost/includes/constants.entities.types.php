<?php

if ( ! function_exists( 'assignConstant' ) )
{
	$exception = '';

	if ( defined( 'ENTITY_FUNCTION' ) )

		$exception = sprintf( EXCEPTION_MISSING_ENTITY, ENTITY_FUNCTION );

	throw new Exception( $exception );
}

// Action type

assignConstant('ACTION_TYPE_FETCH_DATA*', 1);

// Field affordance types

assignConstant('AFFORDANCE_TYPE_SELECT_OPTIONS', 'select_options');
assignConstant('AFFORDANCE_TYPE_INPUT_TEXT*', 'input_text');

// API types

assignConstant('API_TYPE_AMAZON', 'amazon');
assignConstant('API_TYPE_TWITTER*', 'twitter');

// Arc type

assignConstant( 'ARC_TYPE_ENCAPSULATION', 5 );
assignConstant( 'ARC_TYPE_MODEL', 0 );
assignConstant( 'ARC_TYPE_OWNERSHIP*', 1 );
assignConstant( 'ARC_TYPE_EXECUTE_QUERY', 2 );
assignConstant( 'ARC_TYPE_INSTANTIATE_ENTITY', 3 );
assignConstant( 'ARC_TYPE_TAKE_SNAPSHOT', 4 );
assignConstant( 'ARC_TYPE_VISIBILITY', 6 );


// Callback types

assignConstant('AUTHORIZATION_SCOPE_TYPE_MEMBERSHIP*', 0);

// Callback types

assignConstant('CALLBACK_TYPE_OPERATION_RUNNING*', 0);

// Code types

assignConstant('CODE_TYPE_HTML*', 'html');

// Column types

assignConstant('COLUMN_TYPE_INDEX', 'index');

// Constraints

assignConstant('CONSTRAINT_TYPE_PRIMARY_KEY*', 'primary key');
assignConstant('CONSTRAINT_TYPE_UNIQUE', 'unique');

// Content levels

assignConstant('CONTENT_LEVEL_TYPE_ROOT*', 0);
assignConstant('CONTENT_LEVEL_TYPE_OVERVIEW', 1);

// Contact type

assignConstant('CONTACT_TYPE_EMAIL', 1);
assignConstant('CONTACT_TYPE_USER_NAME', 0);

// Content types

assignConstant('CONTENT_TYPE_ANY*', 0);
assignConstant('CONTENT_TYPE_ART', 1);
assignConstant('CONTENT_TYPE_BOOKMARK', 3);
assignConstant('CONTENT_TYPE_DOCUMENT', 7);
assignConstant('CONTENT_TYPE_FILE', 4);
assignConstant('CONTENT_TYPE_FORM', 6);
assignConstant('CONTENT_TYPE_PHOTOGRAPH', 2);
assignConstant('CONTENT_TYPE_SEARCH_RESULTS', 8);
assignConstant('CONTENT_TYPE_STREAM', 10);
assignConstant('CONTENT_TYPE_STYLESHEET', 5);
assignConstant('CONTENT_TYPE_TREE', 9);

// Context types

assignConstant('CONTEXT_TYPE_QUERY', 'query');

// Data types

assignConstant("DATA_TYPE_CREDENTIALS", 0);
assignConstant("DATA_TYPE_LITERAL", 'literal');
assignConstant("DATA_TYPE_NUMBER", 'number');
assignConstant("DATA_TYPE_OBJECT", 'object');
assignConstant("DATA_TYPE_UNKNOWN", 'unknown');

// Deployment types
assignConstant( 'DEPLOYMENT_TYPE_DEVELOPMENT', 0 );
assignConstant( 'DEPLOYMENT_TYPE_PREPRODUCTION', 1 );
assignConstant( 'DEPLOYMENT_TYPE_UNIT_TESTING', 2 );
assignConstant( 'DEPLOYMENT_TYPE_PRODUCTION*', 3 );

// Document types

assignConstant("DOCUMENT_TYPE_RDF", 0);
assignConstant("DOCUMENT_TYPE_XHTML", 1);

// Edge types

assignConstant('EDGE_TYPE_FORM', 0);
assignConstant('EDGE_TYPE_TEMPLATE_EMAIL', 4);
assignConstant('EDGE_TYPE_TEMPLATE_DISCLAIMER', 3);

// Edition mode

assignConstant('EDITION_MODE_TYPE_POST*', 0);
assignConstant('EDITION_MODE_TYPE_EDITION', 1);
assignConstant('EDITION_MODE_TYPE_PREVIEW', 2);

// Event types

assignConstant('EVENT_TYPE_ARCHIVE_OPENING', 2);
assignConstant('EVENT_TYPE_EXCEPTION_CAUGHT', 3);
assignConstant('EVENT_TYPE_LANGUAGE_ITEM_IMPORT', 1);
assignConstant('EVENT_TYPE_TAKE_SNAPSHOT', 4);
assignConstant('EVENT_TYPE_INSTANTIATE_ENTITY', 5);
assignConstant('EVENT_TYPE_SYNCHRONIZE_ENTITY', 6);

// Extension types

assignConstant('EXTENSION_TYPE_HTML*', 0);

// Feedback types

assignConstant( 'FEEDBACK_TYPE_FAILURE', 'failure' );
assignConstant( 'FEEDBACK_TYPE_SUCCESS', 'success' );
assignConstant( 'FEEDBACK_TYPE_SUGGEST_FEATURE_REQUEST', 5 );
assignConstant( 'FEEDBACK_TYPE_SUGGEST_IMPROVEMENT', 4 );
assignConstant( 'FEEDBACK_TYPE_REPORT_EPIC_FAIL', 3 );
assignConstant( 'FEEDBACK_TYPE_REPORT_MALFUNCTION*', 1 );
assignConstant( 'FEEDBACK_TYPE_REPORT_OTHER', 6 );
assignConstant( 'FEEDBACK_TYPE_REPORT_PERFORMANCE_ISSUES', 2 );

// Flags types

assignConstant('FLAG_TYPE_ADMINISTRATION', 4);
assignConstant('FLAG_TYPE_LIKE', 0);
assignConstant('FLAG_TYPE_DO_NOT_LIKE', 1);
assignConstant('FLAG_TYPE_SHARE', 5);
assignConstant('FLAG_TYPE_SUGGEST_REMOVAL', 2);
assignConstant('FLAG_TYPE_REPORT', 3);

// Feed types

assignConstant("FEED_TYPE_TWINE", 0);

// Field types

assignConstant('FIELD_TYPE_BUTTON', 'button');
assignConstant('FIELD_TYPE_CHECKBOX', 'checkbox');
assignConstant('FIELD_TYPE_EMAIL', 'email');
assignConstant('FIELD_TYPE_FILE', 'file');
assignConstant('FIELD_TYPE_HIDDEN', 'hidden');
assignConstant('FIELD_TYPE_IMAGE', 'image');
assignConstant('FIELD_TYPE_MULTIPLE', 'multiple');
assignConstant('FIELD_TYPE_PASSWORD', 'password');
assignConstant('FIELD_TYPE_RADIO', 'radio');
assignConstant('FIELD_TYPE_SELECT', 'select');
assignConstant('FIELD_TYPE_SUBMIT', 'submit');
assignConstant('FIELD_TYPE_TEXT', 'text');
assignConstant('FIELD_TYPE_TEXTAREA', 'textarea');

// File types

assignConstant( 'FILE_TYPE_ORDINARY*', 0 );
assignConstant( 'FILE_TYPE_DIRECTORY', 1 );

// Folder types

assignConstant('FOLDER_TYPE_CACHE', DIR_CACHE);
assignConstant('FOLDER_TYPE_COMPILATION', DIR_TEMPLATES_C);
assignConstant('FOLDER_TYPE_CONFIGURATION', DIR_CONFIG);
assignConstant('FOLDER_TYPE_TEMPLATE*', '../../..'.DIR_TEMPLATES);

// Format types

assignConstant( 'FORMAT_TYPE_XML', 'format_type_xml' );

// Form encoding

assignConstant('FORM_ENCODING_TYPE_MULTIPART',  'multipart/form-data');

// Form field types

assignConstant('FORM_FIELD_TYPE_BUTTON', 'fieldButton');
assignConstant('FORM_FIELD_TYPE_CHECKBOX', 'fieldCheckbox');
assignConstant('FORM_FIELD_TYPE_DATE', 'fieldDate');
assignConstant('FORM_FIELD_TYPE_EMAIL', 'fieldEmail');
assignConstant('FORM_FIELD_TYPE_FILE', 'fieldFile');
assignConstant('FORM_FIELD_TYPE_HIDDEN', 'fieldHidden');
assignConstant('FORM_FIELD_TYPE_MULTIPLE', 'fieldMultiple');
assignConstant('FORM_FIELD_TYPE_PASSWORD', 'fieldPassword');
assignConstant('FORM_FIELD_TYPE_PHONE', 'fieldPhone');
assignConstant('FORM_FIELD_TYPE_POSTAL_CODE', 'fieldPostalcode');
assignConstant('FORM_FIELD_TYPE_RADIO', 'fieldRadio');
assignConstant('FORM_FIELD_TYPE_RESET', 'fieldReset');
assignConstant('FORM_FIELD_TYPE_SELECT', 'fieldSelect');
assignConstant('FORM_FIELD_TYPE_SUBMIT', 'fieldSubmit');
assignConstant('FORM_FIELD_TYPE_TEXT', 'fieldText');
assignConstant('FORM_FIELD_TYPE_TEXTAREA', 'fieldTextarea');
assignConstant('FORM_FIELD_TYPE_TITLE', 'fieldTitle');
assignConstant('FORM_FIELD_TYPE_TIME', 'fieldTime');
assignConstant('FORM_FIELD_TYPE_IMAGE', 'fieldImage');
assignConstant('FORM_FIELD_TYPE_URL', 'fieldUrl');
assignConstant('FORM_FIELD_TYPE_WORLD', 'fieldWorld');

// Form types

assignConstant('FORM_TYPE_MANAGEMENT_PRIVACY', 1);
assignConstant('FORM_TYPE_MANAGEMENT_SYSTEM*', 0);

// Input types

assignConstant('INPUT_TYPE_CHECKBOX', 'checkbox');
assignConstant('INPUT_TYPE_RADIO', 'radio');
assignConstant('INPUT_TYPE_TEXT', 'text');

// Insight node type 

assignConstant('INSIGHT_NODE_TYPE_LOCAL', 0);

// Insight parent types

assignConstant('INSIGHT_TYPE_PARENT_ROOT', 0);

// Language query types

assignConstant('QUERY_LANGUAGE_TYPE_SPARQL', 1);
assignConstant('QUERY_LANGUAGE_TYPE_SQL*', 0);

// Layout types

assignConstant('LAYOUT_TYPE_TABS', 'tabs');

// Level types

assignConstant('LEVEL_TYPE_LEAF*', -1);
assignConstant('LEVEL_TYPE_ROOT', 0);

// Link types

assignConstant('LINK_TYPE_CONFIRMATION', 1);


// Media types

assignConstant('MASHUP_TYPE_OPEN_DATA*', 'open_data');

// Media types

assignConstant('MEDIA_TYPE_IMAGE', 0);

// Merchant platform types

assignConstant('MERCHANT_PLATFORM_TYPE_E_COMMERCE*', 'e_commerce');

// Message types

assignConstant('MESSAGE_TYPE_EMAIL*', 0);
assignConstant('MESSAGE_TYPE_TWEET', 1);

// Metadata types

assignConstant('METADATA_TYPE_KEYWORDS', 'keywords');
assignConstant('METADATA_TYPE_RDF', 'rdf');
assignConstant('METADATA_TYPE_TITLE', 'title');
assignConstant('METADATA_TYPE_XPACKET', 'xpacket');

// Method Name Sections types

assignConstant('METHOD_NAME_SECTION_TYPE_BY*', 'by');

// Mime types

assignConstant('MIME_TYPE_APPLICATION_JSON', 'application/json');
assignConstant('MIME_TYPE_APPLICATION_RDF_XML', 'application/rdf+xml');
assignConstant('MIME_TYPE_PLAIN_TEXT', 'text/plain');
assignConstant('MIME_TYPE_TEXT_CSS', 'text/css');
assignConstant('MIME_TYPE_TEXT_HTML', 'text/html');

// Mysqli types

assignConstant('MYSQLI_STATEMENT_TYPE_INTEGER', 'i');
assignConstant('MYSQLI_STATEMENT_TYPE_STRING', 's');

// Operation types

assignConstant('OPERATION_TYPE_EXECUTE_QUERY', 'execute_query');
assignConstant('OPERATION_TYPE_GET_BY', 'get_by');
assignConstant('OPERATION_TYPE_GET_ENTITY_TYPE', 'get_entity_type');
assignConstant('OPERATION_TYPE_GET_TYPE', 'get_type');
assignConstant('OPERATION_TYPE_INSTANTIATE_ENTITY','instantiate_entity');
assignConstant('OPERATION_TYPE_SYNCHRONIZE_ENTITY','synchronize_entity');

// Parsing types

assignConstant('PARSING_TYPE_ASSIGNMENT', 5);
assignConstant('PARSING_TYPE_CONNECTOR', 3);
assignConstant('PARSING_TYPE_DOMAIN', 1);
assignConstant('PARSING_TYPE_EXISTENCE_EVALUATION', 7);
assignConstant('PARSING_TYPE_EXPRESSION', 4);
assignConstant('PARSING_TYPE_FUNCTION_CALL', 6);
assignConstant('PARSING_TYPE_MEMBER', 2);

// Placeholder types

assignConstant( 'PLACEHOLDER_TYPE_STYLESHEET*', 0 );

// Query types 

assignConstant('QUERY_TYPE_ALTER', 4);
assignConstant('QUERY_TYPE_DELETE', 3);
assignConstant('QUERY_TYPE_INSERT', 0);
assignConstant('QUERY_TYPE_SELECT*', 1);
assignConstant('QUERY_TYPE_UPDATE', 2);

// Panel types

assignConstant('PANEL_TYPE_AFFORDANCE', 'affordance');

// Route types

assignConstant('ROUTE_TYPE_ACTION', 5);
assignConstant('ROUTE_TYPE_ADMINISTRATION', 8);
assignConstant('ROUTE_TYPE_AFFORDANCE', 1);
assignConstant('ROUTE_TYPE_CONTENT*', 3);
assignConstant('ROUTE_TYPE_DIALOG', 2);
assignConstant('ROUTE_TYPE_FOLDER', 6);
assignConstant('ROUTE_TYPE_MEDIA', 4);
assignConstant('ROUTE_TYPE_ROOT', 7);

// Schema types

assignConstant('SCHEMA_TYPE_ARRAY', 1);
assignConstant('SCHEMA_TYPE_TREE', 2);
assignConstant('SCHEMA_TYPE_CHILDREN', 3);
assignConstant('SCHEMA_TYPE_PARENTS', 4);

// Service types

assignConstant('SERVICE_TYPE_TWITTER*', 0);
assignConstant('SERVICE_TYPE_AMAZON', 1);

// Social network types

assignConstant('SOCIAL_NETWORK_TYPE_MICROBLOGGING_PLATFORM*', 'microblogging_platform');

// Source types

assignConstant('SOURCE_TYPE_HTML*', 0);

// Storage types

assignConstant('STORAGE_TYPE_FILE_SYSTEM*', 'file_system');

// Store item types

assignConstant('STORE_ITEM_TYPE_STORE*', 1);
assignConstant('STORE_ITEM_TYPE_QUERY', 2);

// Store types

assignConstant('STORE_TYPE_ACTION*', 1);
assignConstant('STORE_TYPE_QUERY', 2);

// Stylesheet media types

assignConstant('STYLESHEET_TYPE_PRINT', 1);
assignConstant('STYLESHEET_TYPE_SCREEN*', 0);

// Target types

assignConstant('SYNCHRONIZATION_TYPE_LAYER_TO_PHYSICAL', 1);
assignConstant('SYNCHRONIZATION_TYPE_PHYSICAL_TO_LAYER*', 0);

// Target types

assignConstant('TARGET_TYPE_VISITOR', 0);

// Template types

assignConstant('TEMPLATE_TYPE_CONTENT', 2);
assignConstant('TEMPLATE_TYPE_DISCLAIMER', 3);
assignConstant('TEMPLATE_TYPE_EMAIL', 4);
assignConstant('TEMPLATE_TYPE_MESSAGE', 1);

// Token types

assignConstant('TOKEN_TYPE_OAUTH*', 0);
assignConstant('TOKEN_TYPE_OAUTH_SECRET', 1);

// Transfer types

assignConstant('TRANSFER_TYPE_LAYER_TO_PHYSICAL', 1);
assignConstant('TRANSFER_TYPE_PHYSICAL_TO_LAYER*', 0);

// User types

assignConstant('TRAVERSING_TYPE_RECURSIVELY', 1);

// User types

assignConstant('USER_TYPE_ADMINISTRATOR', 'admin');
assignConstant('USER_TYPE_VISITOR', 1);
assignConstant('USER_TYPE_SUPER_ADMINISTRATOR', 2);

// Visibility

assignConstant('VISIBILITY_TYPE_PARANOID*', 0);
assignConstant('VISIBILITY_TYPE_SHARED', 1);
assignConstant('VISIBILITY_TYPE_PUBLIC', 2);

// View types

assignConstant( 'VIEW_TYPE_FORM', 1 );
assignConstant( 'VIEW_TYPE_INJECTION', 2 );

/**
*************
* Changes log
*
*************
* 2011 09 27
*************
*
* project :: wtw ::
*
* deployment :: template engine ::
* 
* Declare deployment stages
* Revise path leading to templates
* 
* (revision 327)
*
*/