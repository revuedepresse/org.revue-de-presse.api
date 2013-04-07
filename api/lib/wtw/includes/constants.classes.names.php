<?php

if ( ! function_exists( 'assignConstant' ) )
{
	$exception = '';

	if ( defined( 'ENTITY_FUNCTION' ) )
		$exception = sprintf( EXCEPTION_MISSING_ENTITY, ENTITY_FUNCTION );

	throw new Exception( $exception );
}

$classes = array(

	// Agents
	'AGENT_ALPHA' => 'Alpha',
	'AGENT_CONTEXT' => 'Context',
	'AGENT_ENTITY' => 'Entity',
	'AGENT_ENVIRONMENT' => 'Environment',
	'AGENT_SYMFONY' => 'Symfony',

	// Class names
	'CLASS_ADMINISTRATOR' => 'Administrator',
	'CLASS_AFFORDANCE' => 'Affordance',
	'CLASS_APPOINTMENT' => 'Appointment',
	'CLASS_API' => 'Api',
	'CLASS_APPLICATION' => 'Application',
	'CLASS_ARC' => 'Arc',
	'CLASS_AUTHOR' => 'Author',
	'CLASS_BOOK' => 'Book',
	'CLASS_CONTENT' => 'Content',
	'CLASS_CONTENT_MANAGER' => 'Content_Manager',
	'CLASS_CONTROLLER' => 'Controller',
	'CLASS_CRAFTSMAN' => 'Craftsman',
	'CLASS_DATABASE' => 'Database',
	'CLASS_DATABASE_CONNECTION' => 'Database_Connection',
	'CLASS_DATA_FETCHER' => 'Data_Fetcher',
	'CLASS_DATA_MINER' => 'Data_Miner',
	'CLASS_DB' => 'DB',
	'CLASS_DEPLOYER' => 'Deployer',
	'CLASS_DIAPORAMA' => 'Diaporama',
	'CLASS_DOM' => 'Dom',
	'CLASS_DOM_ATTRIBUTE' => 'DOMAttr',			
	'CLASS_DOM_DOCUMENT' => 'DOMDocument',
	'CLASS_DOM_ELEMENT' => 'DOMElement',
	'CLASS_DOM_NODE' => 'DOMNode',
	'CLASS_DOM_NODE_LIST' => 'DOMNodeList',
	'CLASS_DOM_TEXT' => 'DOMText',
	'CLASS_DUMPER' => 'Dumper',
	'CLASS_EDGE' => 'Edge',
	'CLASS_ELEMENT' => 'Element',
	'CLASS_ELEMENT_HTML' => 'Element_Html',
	'CLASS_ENTITY' => 'Entity',
	'CLASS_ENTITY_TYPE' => 'Entity_Type',
	'CLASS_EVENT' => 'Event',
	'CLASS_EVENT_MANAGER' => 'Event_Manager',
	'CLASS_EXCEPTION' => 'Exception',
	'CLASS_EXCEPTION_HANDLER' => 'Exception_Handler',
	'CLASS_EXCEPTION_INVALID_ARGUMENT' => 'InvalidArgumentException',
	'CLASS_EXECUTOR' => 'Executor',
	'CLASS_EXTJS_GENERATOR' => 'Extjs_Generator',
	'CLASS_FACEBOOK' => 'Facebook',
	'CLASS_FEED' => 'Feed',
	'CLASS_FEEDBACK' => 'Feedback',
	'CLASS_FEED_READER' => 'Feed_Reader',
	'CLASS_FIELD_BUTTON' => 'Field_Button',
	'CLASS_FIELD_CHECKBOX' => 'Field_Checkbox',
	'CLASS_FIELD_EMAIL' => 'Field_Email',
	'CLASS_FIELD_HANDLER' => 'Field_Handler',
	'CLASS_FIELD_HIDDEN' => 'Field_Hidden',
	'CLASS_FIELD_RADIO' => 'Field_Radio',
	'CLASS_FIELD_SELECT' => 'Field_Select',
	'CLASS_FIELD_SUBMIT' => 'Field_Submit',
	'CLASS_FIELD_TEXT' => 'Field_Text',
	'CLASS_FIELD_TEXTAREA' => 'Field_Textarea',
	'CLASS_FILE' => 'File',
	'CLASS_FILE_MANAGER' => 'File_Manager',
	'CLASS_FIREPHP' => 'FirePHP',
	'CLASS_FLAG' => 'Flag',
	'CLASS_FLAG_MANAGER' => 'Flag_Manager',
	'CLASS_FOLDER' => 'Folder',
	'CLASS_FORM' => 'Form',
	'CLASS_FORM_DRAFT' => 'Draft_Form',
	'CLASS_FORM_MANAGER' => 'Form_Manager',
	'CLASS_HEADER' => 'Header',
	'CLASS_HTML_INPUT' => 'Html_Input',
	'CLASS_HTML_SELECT' => 'Html_Select',
	'CLASS_HTML_TAG' => 'Html_Tag',
	'CLASS_HTML_TEXTAREA' => 'Html_Textarea',
	'CLASS_I18N' => 'I18n',
	'CLASS_INF_MODELF' => 'InfModelF',
	'CLASS_INSIGHT' => 'Insight',
	'CLASS_INSIGHT_NODE' => 'Insight_Node',
	'CLASS_INTERCEPTOR' => 'Interceptor',
	'CLASS_INTROSPECTOR' => 'Introspector',
	'CLASS_JQUERY4PHP' => 'YsJQuery',
	'CLASS_JSON' => 'Json',
	'CLASS_LAYOUT_MANAGER' => 'Layout_Manager',
	'CLASS_LOCATION' => 'Location',
	'CLASS_LOCK' => 'Lock',
	'CLASS_LOCKSMITH' => 'Locksmith',
	'CLASS_LSQL' => 'Lsql',
	'CLASS_MEDIA_MANAGER' => 'Media_Manager',
	'CLASS_MEMBER' => 'Member',
	'CLASS_MEMENTO' => 'Memento',
	'CLASS_MEMORY_CACHE' => 'Memcache',
	'CLASS_MESSAGE' => 'Message',
	'CLASS_MESSENGER' => 'Messenger',
	'CLASS_MYSQLI' => 'mysqli',
	'CLASS_MYSQLI_RESULT' => 'mysqli_result',
	'CLASS_MYSQLI_STATEMENT' => 'mysqli_stmt',
	'CLASS_NOTIFIER' => 'notifier',
	'CLASS_OBJECT_BUILDER' => 'Object_Builder',
	'CLASS_OPTIMIZER' => 'Optimizer',
	'CLASS_PAPER_MAKER' => 'Paper_Maker',
	'CLASS_PARSER' => 'Parser',
	'CLASS_PARTICIPANT' => 'Participant',
	'CLASS_PDO' => 'PDO',
	'CLASS_PHOTO' => 'Photo',
	'CLASS_PHOTOGRAPH' => 'Photograph',
	'CLASS_PLACEHOLDER' => 'Placeholder',
	'CLASS_PROCESSOR' => 'Processor',
	'CLASS_PROVER' => 'Prover',
	'CLASS_PUBLICATION' => 'Publication',
	'CLASS_QUERY' => 'Query',
	'CLASS_ROUTE' => 'Route',
	'CLASS_ROUTER' => 'Router',
	'CLASS_SCHEDULE' => 'Schedule',
	'CLASS_SERIALIZER' => 'Serializer',
	'CLASS_SERVICE_MANAGER' => 'Service_Manager',
	'CLASS_SMARTY' => 'Smarty',
	'CLASS_SMARTY_SEFI' => 'Smarty_Sefi',
	'CLASS_SNAPSHOT' => 'Snapshot',
	'CLASS_SOURCE' => 'Source',
	'CLASS_SOURCE_BUILDER' => 'Source_Builder',
	'CLASS_STANDARD' => 'stdClass',
	'CLASS_STANDARD_CLASS' => array(
		PROPERTY_NAME => 'CLASS_STANDARD',
		PROPERTY_CONSTANT => TRUE
	),
	'CLASS_STORE' => 'Store',
	'CLASS_STORE_ITEM' => 'Store_Item',
	'CLASS_STRING_MODIFIER' => 'Toolbox',
	'CLASS_STYLESHEET' => 'stylesheet',
	'CLASS_SUBSCRIBER' => 'subscriber',
	'CLASS_SYMFONY_YAML' => 'sfYaml',
	'CLASS_SYNDICATION' => 'Syndication',
	'CLASS_TAG_DIV' => 'Tag_Div',
	'CLASS_TAG_FIELDSET' => 'Tag_Fieldset',
	'CLASS_TAG_FORM' => 'Tag_Form',
	'CLASS_TAG_HTML' => 'Tag_Html',
	'CLASS_TAG_INPUT' => 'Tag_Input',
	'CLASS_TAG_P' => 'Tag_P',
	'CLASS_TAG_SELECT' => 'Tag_Select',
	'CLASS_TAG_SPAN' => 'Tag_Span',
	'CLASS_TAG_TEXTAREA' => 'Tag_Textarea',
	'CLASS_TEMPLATE_ENGINE' => 'Template_Engine',
	'CLASS_TEST_CASE' => 'Test_Case',
	'CLASS_TOKEN' => 'Token',
	'CLASS_TOKENS_STREAM' => 'Tokens_Stream',
	'CLASS_TOOLBOX' => 'Toolbox',
	'CLASS_TRANSFER' => 'Transfer',
	'CLASS_TWITTEROAUTH' => 'TwitterOAuth',
	'CLASS_USER' => 'User',
	'CLASS_USER_HANDLER' => 'User_Handler',
	'CLASS_USER_INTERFACE' => 'User_Interface',
	'CLASS_VALIDATOR' => 'Validator',
	'CLASS_VIEW_BUILDER' => 'View_Builder',
	'CLASS_WEAVER' => 'Weaver',
	'CLASS_YAML' => 'Yaml',
	'CLASS_ZEND_MAIL' => 'Zend_Mail',
	'CLASS_YSJQUERY' => 'jquery4PHP',
	'CLASS_ZIP_ARCHIVE' => 'ZipArchive'
);

declareConstantsBatch( $classes, FALSE );

/**
*************
* Changes log
*
*************
* 2011 09 25
*************
* 
* Declare following class names
*
*	Book
* 
* (branch 0.1 :: revision :: 658)
*
*************
* 2011 10 01
*************
* 
* Declare following class names
*
*	Feed
* 	Publication
* 	Syndication
* 
* (branch 0.1 :: revision :: 662)
*
*************
* 2011 10 02
*************
* 
* Declare following class names
*
*	Token_Stream
* 
* (branch 0.1 :: revision :: 665)
*
*************
* 2011 10 11
*************
* 
* Declare following class names
*
*	Data_Miner
*	Introspector
* 
* (branch 0.1 :: revision :: 705)
*
*************
* 2011 10 16
*************
* 
* Reorder declarations
*
*	Token_Stream
* 
* (branch 0.1 :: revision :: 711)
* (branch 0.2 :: revision :: 391)
*
*************
* 2011 10 20
*************
* 
* Add the classes names to an array
*
* (branch 0.1 :: revision :: 724)
* (branch 0.2 :: revision :: 393)
*
*************
* 2012 04 03
*************
* 
* development :: api :: facebook ::
*
* Declare following classes
*
* 	Json
* 	Store
* 
* (branch 0.1 :: revision 839)
* (branch 0.2 :: revision 422)
*
*/