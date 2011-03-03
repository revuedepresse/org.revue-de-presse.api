<?php

if ( ! function_exists( 'assignConstant' ) )
{
	$exception = '';

	if ( defined( 'ENTITY_FUNCTION' ) )

		$exception = sprintf( EXCEPTION_MISSING_ENTITY, ENTITY_FUNCTION );

	throw new Exception( $exception );
}

// Agents

assignConstant('AGENT_ALPHA', 'Alpha');
assignConstant('AGENT_ENTITY', 'Entity');
assignConstant('AGENT_CONTEXT', 'Context');
assignConstant('AGENT_ENVIRONMENT', 'Environment');

// Class names

assignConstant('CLASS_ADMINISTRATOR', 'Administrator');
assignConstant('CLASS_AFFORDANCE', 'Affordance');
assignConstant('CLASS_API', 'Api');
assignConstant('CLASS_APPLICATION', 'Application');
assignConstant('CLASS_ARC','Arc');
assignConstant('CLASS_AUTHOR','Author');
assignConstant('CLASS_CONTENT','Content');
assignConstant('CLASS_CONTENT_MANAGER','Content_Manager');
assignConstant('CLASS_CONTROLLER','Controller');
assignConstant('CLASS_CRAFTSMAN','Craftsman');
assignConstant('CLASS_DATABASE','Database');
assignConstant('CLASS_DATABASE_CONNECTION','Database_Connection');
assignConstant('CLASS_DB','DB');
assignConstant('CLASS_DEPLOYER','Deployer');
assignConstant('CLASS_DIAPORAMA','Diaporama');
assignConstant('CLASS_DATA_FETCHER', 'Data_Fetcher');
assignConstant('CLASS_DOM','Dom');
assignConstant('CLASS_DOM_ATTRIBUTE','DOMAttr');			
assignConstant('CLASS_DOM_DOCUMENT','DOMDocument');
assignConstant('CLASS_DOM_ELEMENT','DOMElement');
assignConstant('CLASS_DOM_NODE','DOMNode');
assignConstant('CLASS_DOM_NODE_LIST','DOMNodeList');
assignConstant('CLASS_DOM_TEXT','DOMText');
assignConstant('CLASS_DUMPER','Dumper');
assignConstant('CLASS_EDGE','Edge');
assignConstant('CLASS_ELEMENT','Element');
assignConstant('CLASS_ELEMENT_HTML','Element_Html');
assignConstant('CLASS_ENTITY', 'Entity');
assignConstant('CLASS_ENTITY_TYPE', 'Entity_Type');
assignConstant('CLASS_EVENT', 'Event');
assignConstant('CLASS_EVENT_MANAGER', 'Event_Manager');
assignConstant('CLASS_EXCEPTION', 'Exception');
assignConstant('CLASS_FEED_READER', 'Feed_Reader');
assignConstant('CLASS_FEEDBACK', 'Feedback');
assignConstant('CLASS_FIELD_HANDLER', 'Field_Handler');
assignConstant('CLASS_FIELD_HANDLER', 'Field_Hidden');
assignConstant('CLASS_FIELD_BUTTON', 'Field_Button');
assignConstant('CLASS_FIELD_CHECKBOX', 'Field_Checkbox');
assignConstant('CLASS_FIELD_EMAIL', 'Field_Email');
assignConstant('CLASS_FIELD_RADIO', 'Field_Radio');
assignConstant('CLASS_FIELD_SELECT', 'Field_Select');
assignConstant('CLASS_FIELD_SUBMIT', 'Field_Submit');
assignConstant('CLASS_FIELD_TEXT', 'Field_Text');
assignConstant('CLASS_FIELD_TEXTAREA', 'Field_Textarea');
assignConstant('CLASS_FIREPHP', 'FirePHP');
assignConstant('CLASS_FLAG', 'Flag');
assignConstant('CLASS_FLAG_MANAGER', 'Flag_Manager');
assignConstant('CLASS_FOLDER', 'Folder');
assignConstant('CLASS_FORM', 'Form');
assignConstant('CLASS_FORM_DRAFT','Draft_Form');
assignConstant('CLASS_HEADER','Header');
assignConstant('CLASS_EXCEPTION', 'Exception');
assignConstant('CLASS_EXCEPTION_HANDLER', 'Exception_Handler');
assignConstant('CLASS_EXCEPTION_INVALID_ARGUMENT', 'InvalidArgumentException');
assignConstant('CLASS_EXECUTOR', 'Executor');
assignConstant('CLASS_EXTJS_GENERATOR', 'Extjs_Generator');
assignConstant('CLASS_FILE_MANAGER', 'File_Manager');
assignConstant('CLASS_FORM_MANAGER', 'Form_Manager');
assignConstant('CLASS_HEADER', 'Header');
assignConstant('CLASS_HTML_INPUT', 'Html_Input');
assignConstant('CLASS_HTML_SELECT', 'Html_Select');
assignConstant('CLASS_HTML_TAG', 'Html_Tag');
assignConstant('CLASS_HTML_TEXTAREA', 'Html_Textarea');
assignConstant('CLASS_I18N', 'I18n');
assignConstant('CLASS_JQUERY4PHP', 'YsJQuery');
assignConstant('CLASS_INF_MODELF', 'InfModelF');
assignConstant('CLASS_INSIGHT', 'Insight');
assignConstant('CLASS_INSIGHT_NODE', 'Insight_Node');
assignConstant('CLASS_INTERCEPTOR', 'Interceptor');
assignConstant('CLASS_LAYOUT_MANAGER','Layout_Manager');
assignConstant('CLASS_LOCATION','Location');
assignConstant('CLASS_LOCK','Lock');
assignConstant('CLASS_LOCKSMITH','Locksmith');
assignConstant('CLASS_LSQL','Lsql');
assignConstant('CLASS_MEDIA_MANAGER', 'Media_Manager');
assignConstant('CLASS_MEMENTO', 'Memento');
assignConstant('CLASS_MESSAGE', 'Message');
assignConstant('CLASS_MESSENGER', 'Messenger');
assignConstant('CLASS_MEMBER', 'Member');
assignConstant('CLASS_MYSQLI', 'mysqli');
assignConstant('CLASS_MYSQLI_RESULT', 'mysqli_result');
assignConstant('CLASS_MYSQLI_STATEMENT', 'mysqli_stmt');
assignConstant('CLASS_NOTIFIER', 'notifier');
assignConstant('CLASS_OBJECT_BUILDER', 'Object_Builder');
assignConstant('CLASS_OPTIMIZER', 'Optimizer');
assignConstant('CLASS_PAPER_MAKER', 'Paper_Maker');
assignConstant('CLASS_PARSER', 'Parser');
assignConstant('CLASS_PDO', 'PDO');
assignConstant('CLASS_PHOTO', 'Photo');
assignConstant('CLASS_PHOTOGRAPH', 'Photograph');
assignConstant('CLASS_PLACEHOLDER','Placeholder');
assignConstant('CLASS_PROCESSOR','Processor');
assignConstant('CLASS_PROVER','Prover');
assignConstant('CLASS_QUERY','Query');
assignConstant('CLASS_ROUTE','Route');
assignConstant('CLASS_ROUTER','Router');
assignConstant('CLASS_SERIALIZER','Serializer');
assignConstant('CLASS_SERVICE_MANAGER', 'Service_Manager');
assignConstant('CLASS_SMARTY','Smarty');
assignConstant('CLASS_SMARTY_SEFI','Smarty_Sefi');
assignConstant('CLASS_SNAPSHOT', 'Snapshot');
assignConstant('CLASS_SOURCE','Source');
assignConstant('CLASS_SUBSCRIBER', 'subscriber');
assignConstant('CLASS_STANDARD', 'stdClass');
assignConstant('CLASS_STANDARD_CLASS', CLASS_STANDARD);
assignConstant('CLASS_STYLESHEET', 'stylesheet');
assignConstant('CLASS_STORE', 'Store');
assignConstant('CLASS_STORE_ITEM', 'Store_Item');
assignConstant('CLASS_STRING_MODIFIER', 'Toolbox');
assignConstant('CLASS_SYMFONY_YAML','sfYaml');
assignConstant('CLASS_TAG_DIV', 'Tag_Div');
assignConstant('CLASS_TAG_FIELDSET', 'Tag_Fieldset');
assignConstant('CLASS_TAG_FORM', 'Tag_Form');
assignConstant('CLASS_TAG_HTML', 'Tag_Html');
assignConstant('CLASS_TAG_INPUT', 'Tag_Input');
assignConstant('CLASS_TAG_P', 'Tag_P');
assignConstant('CLASS_TAG_SELECT', 'Tag_Select');
assignConstant('CLASS_TAG_SPAN', 'Tag_Span');
assignConstant('CLASS_TAG_TEXTAREA', 'Tag_Textarea');
assignConstant('CLASS_TEST_CASE', 'Test_Case');
assignConstant('CLASS_TOKEN', 'Token');
assignConstant('CLASS_TOOLBOX', 'Toolbox');
assignConstant('CLASS_TRANSFER', 'Transfer');
assignConstant('CLASS_TWITTEROAUTH', 'TwitterOAuth');
assignConstant('CLASS_USER', 'User');
assignConstant('CLASS_USER_HANDLER', 'User_Handler');
assignConstant('CLASS_USER_INTERFACE', 'User_Interface');
assignConstant('CLASS_TEMPLATE_ENGINE', 'Template_Engine');
assignConstant('CLASS_VALIDATOR', 'Validator');
assignConstant('CLASS_VIEW_BUILDER', 'View_Builder');
assignConstant('CLASS_WEAVER', 'Weaver');
assignConstant('CLASS_YAML', 'Yaml');
assignConstant('CLASS_ZEND_MAIL', 'Zend_Mail');
assignConstant('CLASS_ZIP_ARCHIVE', 'ZipArchive');

?>