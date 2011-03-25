<?php

if ( ! function_exists( 'assignConstant' ) )
{
	$exception = '';

	if ( defined( 'ENTITY_FUNCTION' ) )

		$exception = sprintf( EXCEPTION_MISSING_ENTITY, ENTITY_FUNCTION );

	throw new Exception( $exception );
}

// Properties

assignConstant('PROPERTY_ACCESS_KEY', 'accesskey');
assignConstant('PROPERTY_ACCESS_TYPE', 'access_type');
assignConstant('PROPERTY_ACTION', 'action');
assignConstant('PROPERTY_AFFORDANCE', 'affordance');
assignConstant('PROPERTY_ALIAS', 'alias');
assignConstant('PROPERTY_ANONYMOUS', 'anonymous');
assignConstant('PROPERTY_ANY', 'any');
assignConstant('PROPERTY_API_CONSUMER_CALLBACK', 'api_consumer_callback');
assignConstant('PROPERTY_API_CONSUMER_KEY', 'api_consumer_key');
assignConstant('PROPERTY_API_CONSUMER_SECRET', 'api_consumer_secret');
assignConstant('PROPERTY_ARGUMENTS', 'arguments');
assignConstant('PROPERTY_ATTRIBUTES', 'attributes');
assignConstant('PROPERTY_AUTHOR', 'author');
assignConstant('PROPERTY_AVATAR', 'avatar');
assignConstant('PROPERTY_BACKUP', 'backup');
assignConstant('PROPERTY_BLANKS', 'blanks');
assignConstant('PROPERTY_BODY', 'body');
assignConstant('PROPERTY_BODY_HTML', 'body_html');
assignConstant('PROPERTY_BODY_TEXT', 'body_text');
assignConstant('PROPERTY_CACHE_ID', 'cache_id');
assignConstant('PROPERTY_CALLBACK', 'callback');
assignConstant('PROPERTY_CALLBACK_METHOD', 'callback.method');
assignConstant('PROPERTY_CALLEE', 'callee');
assignConstant('PROPERTY_CELL', 'cell');
assignConstant('PROPERTY_CHECK', 'check');
assignConstant('PROPERTY_CHILDREN', 'children');
assignConstant('PROPERTY_CLASS', 'class');
assignConstant('PROPERTY_CLEAN_UP', 'clean_up');
assignConstant('PROPERTY_COLUMN', 'column');
assignConstant('PROPERTY_COLUMN_PREFIX', 'column_prefix');
assignConstant('PROPERTY_COMPASS', 'compass');
assignConstant('PROPERTY_COMPONENT', 'component');
assignConstant('PROPERTY_COMPUTATION', 'computation');
assignConstant('PROPERTY_CONFIGURATION', 'configuration');
assignConstant('PROPERTY_CONFIGURATION_FILE', 'configuration_file');
assignConstant('PROPERTY_CONNECTOR', 'connector');
assignConstant('PROPERTY_CONDITION_FIELD_VALUE_CONFIRMED', 'condition_field_value_confirmed');
assignConstant('PROPERTY_CONDITION_FIELD_VALUE_ERROR', 'condition_field_value_error');
assignConstant('PROPERTY_CONDITION_FIELD_VALUE_MISSING', 'condition_field_value_missing');
assignConstant('PROPERTY_CONDITION_FIELD_VALUE_STORED', 'condition_field_value_stored');
assignConstant('PROPERTY_CONDITION_FIELD_VALUE_UNCONFIRMED', 'condition_field_value_unconfirmed');
assignConstant('PROPERTY_CONDITION_VALIDATION_FAILURE', 'condition_validation_failure');
assignConstant('PROPERTY_CONTAINER', 'container');
assignConstant('PROPERTY_CONTENT', 'content');
assignConstant('PROPERTY_CONTENT_TYPE', 'content_type');
assignConstant('PROPERTY_CONTEXT', 'context');
assignConstant('PROPERTY_COORDINATES', 'coordinates');
assignConstant('PROPERTY_DASHBOARD', 'dashboard');
assignConstant('PROPERTY_DATA', 'data');
assignConstant('PROPERTY_DATA_CONFIRMED', 'confirmed_data');
assignConstant('PROPERTY_DATA_POSTED', 'posted_data');
assignConstant('PROPERTY_DATA_SUBMISSION', 'data_submission');
assignConstant('PROPERTY_DATA_SUBMITTED', 'submitted_data');
assignConstant('PROPERTY_DATA_VALIDATED', 'validated_data');
assignConstant('PROPERTY_DATA_VALIDATION', 'data_validation');
assignConstant('PROPERTY_DATA_VALIDATION_FAILURE', 'data_validation_failure');
assignConstant('PROPERTY_DATABASE', 'database');
assignConstant('PROPERTY_DATE_CREATION', 'date_creation');
assignConstant('PROPERTY_DATE_LAST_OCCURRENCE', 'date_last_occurrence');
assignConstant('PROPERTY_DATE_MODIFICATION', 'date_modification');
assignConstant('PROPERTY_DESTINATION', 'destination');
assignConstant('PROPERTY_DISCLAIMERS', 'disclaimers');
assignConstant('PROPERTY_DEFAULT', 'default');
assignConstant('PROPERTY_DESCRIPTION', 'description');
assignConstant('PROPERTY_DIV', 'div');
assignConstant('PROPERTY_DOM_ATTRIBUTES', 'attributes');
assignConstant('PROPERTY_DOM_CHILD_NODES', 'childNodes');
assignConstant('PROPERTY_DOM_DOCUMENT', 'dom_document');
assignConstant('PROPERTY_DOM_ELEMENT', 'dom_element');
assignConstant('PROPERTY_DOM_ELEMENT_TAG_NAME', 'tagName');
assignConstant('PROPERTY_DOM_NODE_NAME', 'nodeName');
assignConstant('PROPERTY_DOM_NODE_VALUE', 'nodeValue');
assignConstant('PROPERTY_DOMAIN', 'domain');
assignConstant('PROPERTY_EDITION', 'edition');
assignConstant('PROPERTY_EDITION_MODE', 'edition_mode');
assignConstant('PROPERTY_ELEMENTS_PROPERTIES', 'elements_properties');
assignConstant('PROPERTY_ENCAPSULATION', 'encapsulation');
assignConstant('PROPERTY_ENTITY', 'entity');
assignConstant('PROPERTY_ENTITY_NAME', 'entity_name');
assignConstant('PROPERTY_ENTITY_TYPE', 'entity_type');
assignConstant('PROPERTY_ERROR', 'error');
assignConstant('PROPERTY_ERRORS', 'errors');
assignConstant('PROPERTY_EXCEPTION', 'exception');
assignConstant('PROPERTY_EXPRESSION', 'expression');
assignConstant('PROPERTY_EVALUATION', 'evaluation');
assignConstant('PROPERTY_FAILURE', 'failure');
assignConstant('PROPERTY_FEEDBACK', 'feedback');
assignConstant('PROPERTY_FIELD', 'field');
assignConstant('PROPERTY_FIELD_CONTEXT', 'context_field');
assignConstant('PROPERTY_FIELD_FIRST_INDEX', 'first_field_index');
assignConstant('PROPERTY_FIELD_HANDLER', 'field_handler');
assignConstant('PROPERTY_FIELDS', 'fields');
assignConstant('PROPERTY_FIELD_VALUES', 'field_values');
assignConstant('PROPERTY_FILE', 'file');
assignConstant('PROPERTY_FILTER', 'filter');
assignConstant('PROPERTY_FOLDER', 'folder');
assignConstant('PROPERTY_FORM', 'form');
assignConstant('PROPERTY_FORM_DESCRIPTION', 'form_description');
assignConstant('PROPERTY_FORM_CONFIGURATION', 'form_configuration');
assignConstant('PROPERTY_FORM_IDENTIFIER', 'form_identifier');
assignConstant('PROPERTY_FOREIGN_KEY', 'foreign_key');
assignConstant('PROPERTY_FUNCTION', 'function');
assignConstant('PROPERTY_HANDLER', 'handler');
assignConstant('PROPERTY_HANDLER_STATUS', 'handler_status');
assignConstant('PROPERTY_HANDLERS', 'handlers');
assignConstant('PROPERTY_HASH', 'hash');
assignConstant('PROPERTY_HEIGHT', 'height');
assignConstant('PROPERTY_HOST', 'host');
assignConstant('PROPERTY_HTML_ELEMENTS', 'html_elements');
assignConstant('PROPERTY_ID', 'id');
assignConstant('PROPERTY_IDENTIFIER', 'identifier');
assignConstant('PROPERTY_IDENTITY', 'identity');
assignConstant('PROPERTY_IMAGE', 'image');
assignConstant('PROPERTY_IMAP_MESSAGE_NUMBER', 'imap_message_number');
assignConstant('PROPERTY_IMAP_UID', 'imap_uid');
assignConstant('PROPERTY_INDEX', 'index');
assignConstant('PROPERTY_INSTANCE', 'instance');
assignConstant('PROPERTY_ISA', 'isa');
assignConstant('PROPERTY_IS_NULL', 'is_null');
assignConstant('PROPERTY_KEY', 'key');
assignConstant('PROPERTY_KEYS', 'keys');
assignConstant('PROPERTY_KEYWORDS', 'keywords');
assignConstant('PROPERTY_HEADER', 'header');
assignConstant('PROPERTY_LABEL', 'label');
assignConstant('PROPERTY_LANGUAGE', 'language');
assignConstant('PROPERTY_LAYOUT', 'layout');
assignConstant('PROPERTY_LAST_INSERT_ID', 'last_insert_id');
assignConstant('PROPERTY_LAST_UID_RECORDED', 'last_recorded_uid');
assignConstant('PROPERTY_LEFT_MEMBER', 'left.member');
assignConstant('PROPERTY_LEFT_OPERAND', 'left.operand');
assignConstant('PROPERTY_LENGTH', 'length');
assignConstant('PROPERTY_LEVEL', 'level');
assignConstant('PROPERTY_LEVELS', 'levels');
assignConstant('PROPERTY_LINE', 'line');
assignConstant('PROPERTY_LINK', 'link');
assignConstant('PROPERTY_LINK_MYSQLI', 'link_mysqli');
assignConstant('PROPERTY_LINKS', 'links');
assignConstant('PROPERTY_LOCATION', 'location');
assignConstant('PROPERTY_LOCAL', 'local');
assignConstant('PROPERTY_LOCKED', 'locked');
assignConstant('PROPERTY_LOGIN', 'login');
assignConstant('PROPERTY_MATCH', 'matching_values');
assignConstant('PROPERTY_MANDATORY', 'mandatory');
assignConstant('PROPERTY_MEMBER', 'member');
assignConstant('PROPERTY_MEMBERSHIP', 'membership');
assignConstant('PROPERTY_MESSAGE', 'message');
assignConstant('PROPERTY_METHOD', 'method');
assignConstant('PROPERTY_NAME', 'name');
assignConstant('PROPERTY_NAME_TRIMMED', 'trimmed_name');
assignConstant('PROPERTY_NAMESPACE', 'namespace');
assignConstant('PROPERTY_NECESSARY', 'necessary');
assignConstant('PROPERTY_NODE', 'node');
assignConstant('PROPERTY_NULL', 'null');
assignConstant('PROPERTY_OBJECT', 'object');
assignConstant('PROPERTY_OCCURRENCES', 'occurences');
assignConstant('PROPERTY_OCCURENCE', 'occurence');
assignConstant('PROPERTY_OPERAND', 'operand');
assignConstant('PROPERTY_OPERANDS', 'operands');
assignConstant('PROPERTY_OPTIONS', 'options');
assignConstant('PROPERTY_OVERVIEW', 'overview');
assignConstant('PROPERTY_OBJECT', 'object');
assignConstant('PROPERTY_OWNER', 'owner');
assignConstant('PROPERTY_OWNERSHIP', 'ownership');
assignConstant('PROPERTY_PAGE', 'page');
assignConstant('PROPERTY_PARAMETER', 'parameter');
assignConstant('PROPERTY_PARENT', 'parent');
assignConstant('PROPERTY_PARENT_HUB', 'parent_hub');
assignConstant('PROPERTY_PARENT_ROOT', 'parent_root');
assignConstant('PROPERTY_PASSWORD', 'password');
assignConstant('PROPERTY_PATH', 'path');
assignConstant('PROPERTY_PATTERN', 'pattern');
assignConstant('PROPERTY_PDO', 'pdo');
assignConstant('PROPERTY_POSITION', 'position');
assignConstant('PROPERTY_PREVIEW', 'preview');
assignConstant('PROPERTY_PRIMARY_KEY', 'primary_key');
assignConstant('PROPERTY_PRIVILEGE', 'privilege');
assignConstant('PROPERTY_PROPERTIES', 'properties');
assignConstant('PROPERTY_PROPERTY', 'property');
assignConstant('PROPERTY_PROPERTY_NAME', 'property_name');
assignConstant('PROPERTY_PROTOCOL', 'protocol');
assignConstant('PROPERTY_PUBLIC', 'public');
assignConstant('PROPERTY_REPOSITORY', 'repository');
assignConstant('PROPERTY_REFERRAL', 'referral');
assignConstant('PROPERTY_RESOURCES', 'resources');
assignConstant('PROPERTY_REFERENCE', 'reference');
assignConstant('PROPERTY_QUALITY', 'quality');
assignConstant('PROPERTY_RELEASED', 'released');
assignConstant('PROPERTY_REQUEST', 'request');
assignConstant('PROPERTY_RETURN', 'return');
assignConstant('PROPERTY_RESOLUTION', 'resolution');
assignConstant('PROPERTY_RIGHT_MEMBER', 'right.member');
assignConstant('PROPERTY_RIGHT_OPERAND', 'right.operand');
assignConstant('PROPERTY_ROADMAP', 'roadmap');
assignConstant('PROPERTY_ROOT', 'root');
assignConstant('PROPERTY_ROUTE', 'route');
assignConstant('PROPERTY_ROW', 'row');
assignConstant('PROPERTY_ROWS_CALCULATED', 'calculated_rows');
assignConstant('PROPERTY_SECRET', 'secret');
assignConstant('PROPERTY_SECRET_OAUTH', 'oauth_secret');
assignConstant('PROPERTY_SETTINGS', 'settings');
assignConstant('PROPERTY_SHORTHAND_ARGUMENTS', 'args');
assignConstant('PROPERTY_SHORTHAND_CONFIGURATION', 'config');
assignConstant('PROPERTY_SIGNATURE', 'signature');
assignConstant('PROPERTY_SIZE', 'size');
assignConstant('PROPERTY_SORT', 'sort');
assignConstant('PROPERTY_SENDER', 'sender');
assignConstant('PROPERTY_SOURCE', 'source');
assignConstant('PROPERTY_SPAN', 'span');
assignConstant('PROPERTY_STACK', 'stack');
assignConstant('PROPERTY_START', 'start');
assignConstant('PROPERTY_STATE', 'state');
assignConstant('PROPERTY_STATUS', 'status');
assignConstant('PROPERTY_STORE', 'store');
assignConstant('PROPERTY_STORE_ITEM', 'store_item');
assignConstant('PROPERTY_STRUCTURE', 'structure');
assignConstant('PROPERTY_SUB_OPERATIONS', 'sub_operations');
assignConstant('PROPERTY_SUBJECT', 'subject');
assignConstant('PROPERTY_SUBTITLE', 'subtitle');
assignConstant('PROPERTY_SUCCESS', 'success');
assignConstant('PROPERTY_SYNCHRONIZATION', 'synchronization');
assignConstant('PROPERTY_SYNCING', 'syncing');
assignConstant('PROPERTY_TABLE', 'table');
assignConstant('PROPERTY_TABLE_ALIAS', 'table_alias');
assignConstant('PROPERTY_TAG', 'tag');
assignConstant('PROPERTY_TARGET', 'target');
assignConstant('PROPERTY_TARGET_TYPE', 'target_type');
assignConstant('PROPERTY_TEXT', 'text');
assignConstant('PROPERTY_THREAD', 'thread');
assignConstant('PROPERTY_TITLE', 'title');
assignConstant('PROPERTY_TOKEN_ACCESS', 'access_token');
assignConstant('PROPERTY_TOKEN_OAUTH', 'oauth_token');
assignConstant('PROPERTY_TOKEN_OAUTH_SECRET', 'oauth_token_secret');
assignConstant('PROPERTY_TYPE', 'type');
assignConstant('PROPERTY_URI', 'uri');
assignConstant('PROPERTY_UNDECLARED', 'undeclared');
assignConstant('PROPERTY_UNDEFINED', 'undefined');
assignConstant('PROPERTY_UNIQUE', 'unique');
assignConstant('PROPERTY_UNLOCKED', PROPERTY_RELEASED);
assignConstant('PROPERTY_USER_NAME', 'user_name');
assignConstant('PROPERTY_VALUE', 'value');
assignConstant('PROPERTY_VALUES', 'values');
assignConstant('PROPERTY_WIDTH', 'width');
assignConstant('PROPERTY_VIEW_BUILDER', 'view_builder');
assignConstant('PROPERTY_WRAPPER', 'wrapper');
assignConstant('PROPERTY_WRAPPERS', 'wrappers');
assignConstant('PROPERTY_YAML_FOLDING_DEPTH', 10);
