<?php

if ( ! function_exists( 'assignConstant' ) )
{
	$exception = '';

	if ( defined( 'ENTITY_FUNCTION' ) )
		$exception = sprintf( EXCEPTION_MISSING_ENTITY, ENTITY_FUNCTION );

	throw new Exception( $exception );
}

$entities = array(
	'ENTITY_ACTION' => 'action',
	'ENTITY_ADMINISTRATION' => 'administration',
	'ENTITY_ADMINISTRATOR' => 'administrator',
	'ENTITY_AFFORDANCE' => 'affordance',
	'ENTITY_AGENT' => 'agent',
	'ENTITY_AGGREGATION' => 'aggregation',
	'ENTITY_ALIAS' => 'alias',
	'ENTITY_ALPHA' => 'alpha',
	'ENTITY_ANY' => 'any',
	'ENTITY_API' => 'api',
	'ENTITY_APPLICATION' => 'application',
	'ENTITY_APPOINTMENT' => 'appointment',
	'ENTITY_ARC' => 'arc',
	'ENTITY_ARCHIVE' => 'archive',
	'ENTITY_ASSERTION' => 'assertion',
	'ENTITY_AUTHOR' => 'author',
	'ENTITY_AUTHORIZATION_SCOPE' => 'authorization_scope',
	'ENTITY_BLACKBOARD' => 'blackboard',
	'ENTITY_BLOCK' => 'block',
	'ENTITY_BODY' => 'body',
	'ENTITY_BOOK' => 'book',
	'ENTITY_CACHE' => 'cache',
	'ENTITY_CALLBACK' => 'callback',
	'ENTITY_CHECK' => 'check',
	'ENTITY_CLASS' => 'class',
	'ENTITY_CLASS_NAME' => 'class_name',
	'ENTITY_CODE' => 'code',
	'ENTITY_COLUMN' => 'column',
	'ENTITY_COLUMN_PREFIX' => 'column_prefix',
	'ENTITY_COMPILATION' => 'compilation',
	'ENTITY_CONFIGURATION' => 'configuration',
	'ENTITY_CONNECTOR' => 'connector',
	'ENTITY_CONSTANT' => 'constant',
	'ENTITY_CONSTRAINT' => 'constraint',
	'ENTITY_CONSUMER' => 'consumer',
	'ENTITY_CONTAINER' => 'container',
	'ENTITY_CONTENT' => 'content',
	'ENTITY_CONTENT_LEVEL' => 'content_level',
	'ENTITY_CONTENT_MANAGER' => 'content_manager',
	'ENTITY_CONTEXT' => 'context',
	'ENTITY_CONTROLLER' => 'controller',
	'ENTITY_CONTROL_PANEL' => 'control_panel',
	'ENTITY_CRAFTSMAN' => 'craftsman',
	'ENTITY_CSS_CLASS' => 'css_class',
	'ENTITY_DASHBOARD' => 'dashboard',
	'ENTITY_DATABASE' => 'database',
	'ENTITY_DATA_FETCHER' => 'data_fetcher',
	'ENTITY_DATA_MINER' => 'data_miner',
	'ENTITY_DB' => 'db',
	'ENTITY_DECLATION_CLASSES' => 'classes_declaration',
	'ENTITY_DEFINITION' => 'definition',
	'ENTITY_DELIMITER' => 'delimiter',
	'ENTITY_DEPLOYER' => 'deployer',
	'ENTITY_DESTINATION' => 'destination',
	'ENTITY_DIALOG' => 'dialog',
	'ENTITY_DIAPORAMA' => 'diaporama',
	'ENTITY_DIRECTORY' => 'directory',
	'ENTITY_DIRECTORY_ROOT' => 'root_directory',
	'ENTITY_DISCLAIMER' => 'disclaimer',
	'ENTITY_DISPLAY' => 'display',
	'ENTITY_DOCUMENT' => 'document',
	'ENTITY_DOM' => 'dom',
	'ENTITY_DOM_ATTRIBUTE' => 'dom_attribute',
	'ENTITY_DOM_DOCUMENT' => 'dom_document',
	'ENTITY_DOM_ELEMENT' => 'dom_element',
	'ENTITY_DOM_ELEMENT' => 'dom_element',
	'ENTITY_DOM_NODE' => 'dom_node',
	'ENTITY_DOM_NODE_LIST' => 'dom_node_list',
	'ENTITY_DOM_TEXT' => 'dom_text',
	'ENTITY_DUMPER' => 'dumper',
	'ENTITY_EDGE' => 'edge',
	'ENTITY_EDITION_MODE' => 'edition_mode',
	'ENTITY_EDITOR' => 'editor',
	'ENTITY_ELEMENT' => 'element',
	'ENTITY_ELEMENT_HTML' => 'element_html',
	'ENTITY_EMAIL' => 'email',
	'ENTITY_ENTITY' => 'entity',
	'ENTITY_ENTITY_TYPE' => 'entity_type',
	'ENTITY_ENVIRONMENT' => 'environment',
	'ENTITY_ERROR' => 'error',
	'ENTITY_EVENT' => 'event',
	'ENTITY_EVENT_MANAGER' => 'event_manager',
	'ENTITY_EXCEPTION' => 'exception',
	'ENTITY_EXCEPTION_HANDLER' => 'exception_handler',
	'ENTITY_EXECUTOR' => 'executor',
	'ENTITY_FACEBOOK' => 'facebook',
	'ENTITY_FACEBOOK_FEED' => 'facebook_feed',
	'ENTITY_FACEBOOK_HOME' => 'facebook_home',
	'ENTITY_FACTORY' => 'factory',
	'ENTITY_FAILURE' => 'failure',
	'ENTITY_FAVORITE' => 'favorite',
	'ENTITY_FEED' => 'feed',
	'ENTITY_FEEDBACK' => 'feedback',
	'ENTITY_FEED_READER' => 'feed_reader',
	'ENTITY_FIELD' => 'field',
	'ENTITY_FIELD_HANDLER' => 'field_handler',
	'ENTITY_FILE' => 'file',
	'ENTITY_FILE_EXTENSION' => 'file_extension',
	'ENTITY_FILE_MANAGER' => 'file_manager',
	'ENTITY_FLAG' => 'flag',
	'ENTITY_FLAG_MANAGER' => 'flag_manager',
	'ENTITY_FOLDER' => 'folder',
	'ENTITY_FOOTER' => 'footer',
	'ENTITY_FORM' => 'form',
	'ENTITY_FORMAT' => 'format',
	'ENTITY_FORM_MANAGER' => 'form_manager',
	'ENTITY_FUNCTION' => 'function',
	'ENTITY_GITHUB' => 'github',
	'ENTITY_GITHUB_STARRED_REPOSITORIES' => 'github_starred_repositories',
	'ENTITY_HASH' => 'hash',
	'ENTITY_HEADER' => 'header',
	'ENTITY_HELPER' => 'helper',
	'ENTITY_HOST' => 'host',
	'ENTITY_HTML_INPUT' => 'html_input',
	'ENTITY_HTML_SELECT' => 'html_select',
	'ENTITY_HTML_TAG' => 'html_tag',
	'ENTITY_HTML_TEXTAREA' => 'html_textarea',
	'ENTITY_I18N' => 'i18n',
	'ENTITY_IMAGE' => 'image',
	'ENTITY_INITIALIZATION' => 'initialization',
	'ENTITY_INSIGHT' => 'insight',
	'ENTITY_INSIGHT_NODE' => 'insight_node',
	'ENTITY_INSTANCE' => 'instance',
	'ENTITY_INTERCEPTOR' => 'interceptor',
	'ENTITY_INTROSPECTOR' => 'introspector',
	'ENTITY_JQUERY4PHP' => 'jquery4php',
	'ENTITY_JSON' => 'json',
	'ENTITY_KEY' => 'key',
	'ENTITY_KEYWORD' => 'keyword',
	'ENTITY_LABEL' => 'label',
	'ENTITY_LANGUAGE' => 'language',
	'ENTITY_LAYOUT' => 'layout',
	'ENTITY_LAYOUT_MANAGER' => 'layout_manager',
	'ENTITY_LEAF' => 'leaf',
	'ENTITY_LEVEL' => 'level',
	'ENTITY_LINK' => 'link',
	'ENTITY_LINK' => 'link',
	'ENTITY_LIST' => 'list',
	'ENTITY_LIST_ITEM' => 'list_item',
	'ENTITY_LOCATION' => 'location',
	'ENTITY_LOCK' => 'lock',
	'ENTITY_LOCKSMITH' => 'locksmith',
	'ENTITY_LSQL' => 'lsql',
	'ENTITY_MANAGEMENT' => 'management',
	'ENTITY_MAPPING' => 'mapping',
	'ENTITY_MASHUP' => 'mashup',
	'ENTITY_MEDIA_MANAGER' => 'media_manager',
	'ENTITY_MEMBER' => 'member',
	'ENTITY_MEMENTO' => 'memento',
	'ENTITY_MEMORY_CACHE' => 'memory_cache',
	'ENTITY_MENU' => 'menu',
	'ENTITY_MERCHANT_PLATFORM' => 'merchant_platform',
	'ENTITY_MESSAGE' => 'message',
	'ENTITY_MESSAGE' => 'message',
	'ENTITY_MESSENGER' => 'messenger',
	'ENTITY_METHOD' => 'method',
	'ENTITY_METHOD_NAME' => 'method_name',
	'ENTITY_METHOD_NAME_SECTION' => 'method_name_section',
	'ENTITY_MICROBLOGGING' => 'microblogging',
	'ENTITY_MODE_ACCESS' => 'access_mode',
	'ENTITY_MODEL_JSON' => 'json_model',
	'ENTITY_MYSQLI' => 'mysqli',
	'ENTITY_NAME' => 'name',
	'ENTITY_NAME_CLASS' => 'class_name',
	'ENTITY_NAME_METHOD' => 'method_name',
	'ENTITY_NODE' => 'node',
	'ENTITY_OAUTH' => 'oauth',
	'ENTITY_OAUTH_SECRET' => 'oauth_secret',
	'ENTITY_OBJECT' => 'object',
	'ENTITY_OBJECT_BUILDER' => 'object_builder',
	'ENTITY_OBSERVATION' => 'observation',
	'ENTITY_OPERATION' => 'operation',
	'ENTITY_OPTIMIZER' => 'optimizer',
	'ENTITY_OPTION' => 'option',
	'ENTITY_ORDER' => 'order',
	'ENTITY_OVERVIEW' => 'overview',
	'ENTITY_PAGE' => 'page',
	'ENTITY_PANEL' => 'panel',
	'ENTITY_PAPER_MAKER' => 'paper_maker',
	'ENTITY_PARSER' => 'parser',
	'ENTITY_PARTICIPANT' => 'participant',
	'ENTITY_PATH' => 'path',
	'ENTITY_PATTERN' => 'pattern',
	'ENTITY_PDO' => 'pdo',
	'ENTITY_PERSISTENCY' => 'persistency',
	'ENTITY_PHOTO' => 'photo',
	'ENTITY_PHOTOGRAPH' => 'photograph',
	'ENTITY_PHP_VARIABLE' => 'PHP_variable',
	'ENTITY_PLACEHOLDER' => 'placeholder',
	'ENTITY_PLAN' => 'plan',
	'ENTITY_PREFIX' => 'prefix',
	'ENTITY_PREPOSITION' => 'preposition',
	'ENTITY_PROCESSOR' => 'processor',
	'ENTITY_PROPERTY' => 'property',
	'ENTITY_PROTOCOL' => 'protocol',
	'ENTITY_PROVER' => 'prover',
	'ENTITY_PUBLICATION' => 'publication',
	'ENTITY_QUERY' => 'query',
	'ENTITY_QUERY_LANGUAGE' => 'query_language',
	'ENTITY_RAW_CONTENTS' => 'raw_contents',
	'ENTITY_RECORD' => 'record',
	'ENTITY_RENDER' => 'render',
	'ENTITY_RESOURCE' => 'resource',
	'ENTITY_RIVER' => 'river',
	'ENTITY_ROAD' => 'road',
	'ENTITY_ROOT' => 'root',
	'ENTITY_ROUTE' => 'route',
	'ENTITY_ROUTER' => 'router',
	'ENTITY_SCHEDULE' => 'schedule',
	'ENTITY_SECRET' => 'secret',
	'ENTITY_SECTION' => 'section',
	'ENTITY_SEPARATOR' => 'separator',
	'ENTITY_SEQUENCE' => 'sequence',
	'ENTITY_SERIALIZER' => 'serializer',
	'ENTITY_SERVICE' => 'service',
	'ENTITY_SERVICE_MANAGER' => 'service_manager',
	'ENTITY_SIGNAL' => 'signal',
	'ENTITY_SIZE' => 'size',
	'ENTITY_SLICE' => 'slice',
	'ENTITY_SMARTY_SEFI' => 'smarty_sefi',
	'ENTITY_SMARTY_VARIABLE' => 'Smarty_variable',
	'ENTITY_SNAPSHOT' => 'snapshot',
	'ENTITY_SOCIAL_NETWORK' => 'social_network',
	'ENTITY_SOURCE_BUILDER' => 'source_builder',
	'ENTITY_SOURCE_TYPE' => 'source_type',
	'ENTITY_SOURCE' => 'source',
	'ENTITY_SPARQL' => 'sparql',
	'ENTITY_SQL' => 'sql',
	'ENTITY_STANDARD' => 'standard',
	'ENTITY_STANDARD_CLASS' => array(
		PROPERTY_NAME => 'ENTITY_STANDARD', PROPERTY_CONSTANT => TRUE
	),
   'ENTITY_STARRED_REPOSITORIES' => 'starred_repositories',
	'ENTITY_STEP' => 'step',
	'ENTITY_STORAGE' => 'storage',
	'ENTITY_STORE' => 'store',
	'ENTITY_STORE_ITEM' => 'store_item',
	'ENTITY_STREAM' => 'stream',
	'ENTITY_STYLESHEET' => 'stylesheet',
	'ENTITY_SUBSET' => 'subset',
	'ENTITY_SUCCESS' => 'success',
	'ENTITY_SYNCHRONIZATION' => 'synchronization',
	'ENTITY_SYNCING' => 'syncing',
	'ENTITY_SYNDICATION' => 'syndication',
	'ENTITY_SYMFONY' => 'symfony',
	'ENTITY_SYNTAX' => 'syntax',
	'ENTITY_TAB' => 'tab',
	'ENTITY_TABLE' => 'table',
	'ENTITY_TABLE' => 'table',
	'ENTITY_TABLE_ALIAS' => 'table_alias',
	'ENTITY_TABS' => 'tabs',
	'ENTITY_TAG' => 'tag',
	'ENTITY_TAG_DIV' => 'tag_div',
	'ENTITY_TAG_FIELDSET' => 'tag_fieldset',
	'ENTITY_TAG_FORM' => 'tag_form',
	'ENTITY_TAG_FORM' => 'tag_form',
	'ENTITY_TAG_HTML' => 'tag_html',
	'ENTITY_TAG_INPUT' => 'tag_input',
	'ENTITY_TAG_P' => 'tag_p',
	'ENTITY_TAG_SELECT' => 'tag_select',
	'ENTITY_TAG_SPAN' => 'tag_span',
	'ENTITY_TAG_TEXTAREA' => 'tag_textarea',
	'ENTITY_TARGET' => 'target',
	'ENTITY_TEMPLATE' => 'template',
	'ENTITY_TEMPLATE_ENGINE' => 'template_engine',
	'ENTITY_TEST' => 'test',
	'ENTITY_TEST_CASE' => 'test_case',
	'ENTITY_TEXT' => 'text',
	'ENTITY_THREAD' => 'thread',
	'ENTITY_TIMELINE' => 'timeline',
	'ENTITY_TITLE' => 'title',
	'ENTITY_TOKEN' => 'token',
	'ENTITY_TOKENS_STREAM' => 'tokens_stream',
	'ENTITY_TOOLBOX' => 'toolbox',
	'ENTITY_TRACKER' => 'tracker',
	'ENTITY_TRANSFER' => 'transfer',
	'ENTITY_TWITTEROAUTH' => 'twitteroauth',
	'ENTITY_TYPE' => 'type',
	'ENTITY_TWITTER' => 'twitter',
	'ENTITY_TWITTER_USER_STREAM' => 'twitter_user_stream',
	'ENTITY_UNIT_TESTING' => 'unit_testing',
	'ENTITY_URI' => 'URI',
	'ENTITY_USER' => 'user',
	'ENTITY_USER_HANDLER' => 'user_handler',
	'ENTITY_USER_INTERFACE' => 'user_interface',
	'ENTITY_USER_NAME' => 'user_name',
	'ENTITY_USER_STREAM' => 'user_stream',
	'ENTITY_VALIDATOR' => 'validator',
	'ENTITY_VALUE' => 'value',
	'ENTITY_VIEW' => 'view',
	'ENTITY_VIEW_BUILDER' => 'view_builder',
	'ENTITY_VISIBILITY' => 'visibility',
	'ENTITY_VISITOR' => 'visitor',
	'ENTITY_WEAVER' => 'weaver',
	'ENTITY_YAML' => 'yaml',
	'ENTITY_YSJQUERY' => 'ysjquery',
	'ENTITY_ZIP_ARCHIVE' => 'zip_archive'
);

declareConstantsBatch( $entities, FALSE );

/**
*************
* Changes log
*
*************
* 2012 04 03
*************
* 
* development :: api :: facebook ::
*
* Declare JSON entity
* 
*************
* 2012 04 03
*************
* 
* development :: api :: facebook ::
*
* Declare following entities
*
* 	JSON
* 	Store
* 
* (branch 0.1 :: revision :: 839)
* (branch 0.2 :: revision :: 422)
*
*************
* 2012 05 08
*************
*
* development :: code generation ::
*
* Declare following entities
*
* 	access mode
* 	definition
* 	JSON model
* 	signal
* 	size
* 	source type
* 
* (branch 0.1 :: revision :: 924)
*
*************
* 2012 05 09
*************
*
* development :: code generation ::
*
* Declare following entities
*
*	destination
*	persistency
* 	subset
* 
* (branch 0.1 :: revision :: 925)
*
*/
