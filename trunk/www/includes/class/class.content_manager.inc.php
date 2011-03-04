<?php

/**
* Content Manager
*
* Class for content management
* @package  sefi
*/
class Content_Manager extends Content
{
	static protected $tools;

    /**
    * Get an editor
    *
    * @return 	string	editor
    */	    	
	public static function &getEditor()
	{
		// get the member tools
		$tools = &self::getTools();

		// check if the editor tool is an object
		if (!isset($tools->{ENTITY_EDITOR}) || !is_object($tools->{ENTITY_EDITOR}))

			// construct a new instance of the standard class
			$tools->{ENTITY_EDITOR} = new stdClass();

		// return the editor tool
		return $tools->{ENTITY_EDITOR};
	}

    /**
    * Get tools
    *
    * @return 	object	tools
    */	    	
	public static function &getTools()
	{
		// check if the member tools is an object
		if (!is_object(self::$tools))

			// construct a new standard class
			self::$tools = new stdClass();
		
		// return the member tools
		return self::$tools;
	}

    /**
    * Display a document
    *
    * @param	integer	$document_type	document type
    * @param	integer	$id				identifier
    * @param	string	$parent			parent
    * @return 	string
    */
	public static function displayDocument($document_type = DOCUMENT_TYPE_RDF, $id = null, $parent = null)
	{
		switch ($document_type)
		{
			case DOCUMENT_TYPE_RDF:
				
				$mime_type = MIME_TYPE_APPLICATION_RDF_XML;
				
					break;

			case DOCUMENT_TYPE_XHTML:

				$mime_type = MIME_TYPE_TEXT_HTML;
				
					break;
		}

		// get a document
		$document = self::getDocument($document_type, $id, $parent);

		// check the result
		if (!empty($document))
		{
			// send headers
			header('Content-Type: '.$mime_type.'; charset='.I18N_CHARSET_UTF8);
			
			// display the contents
			echo $document;
		}
	}

    /**
    * Display an editor
    *
    * @return nothing
    */
	public static function displayEditor()
	{
		// display the editor
		echo self::getEditorView();
	}

	/**
	* Display a list of entities
	* 
	* @param	string	$document_type 	document type
	* @param	integer	$start			start
	* @param	integer	$limit			limit	
	* @param	object	$view_builder	view builder
	* return	nothing
	*/
	public static function displayList(
		$document_type = DOCUMENT_TYPE_XHTML,
		$start = 0,
		$limit = PAGINATION_COUNT_PER_PAGE_DOCUMENT_XHTML,
		$view_builder = CLASS_VIEW_BUILDER
	)
	{
		$list = self::getList($document_type, $start, $limit);

		// call a user function
		echo call_user_func($view_builder."::".METHOD_BUILD_LIST, $list);
	}

    /**
    * Get the editor view
    *
    * @return 	string	editor
    */	    	
	public static function getEditorView()
	{
		global $class_application;

		// set the view builder class name
		$class_view_builder = $class_application::getViewBuilderClass();

		// get the editor tool 
		$_editor = &self::getEditor();

		// check if the editor tool is an object
		if (!isset($_editor->{ENTITY_VIEW}) || !is_string($_editor->{ENTITY_VIEW}))

			// get the editor view
			$_editor->{ENTITY_VIEW} = $class_view_builder::buildEditor();

		// return the editor tool
		return $_editor->{ENTITY_VIEW};
	}

    /**
    * Get a menu
    *
    * @param	string	$block	block
    * @param	integer	$page	page
    * @return 	array
    */
	public static function getMenu($block, $page)
	{
		global $class_application, $verbose_mode;

		// set the db class name
		$class_db = $class_application::getDbClass();

		// set the dumper class name
		$class_dumper = $class_application::getDumperClass();

		// set the entity class name
		$class_entity = $class_application::getEntityClass();

		// declare an empty array of menu items
		$menu = array();

		// switch from the page
		switch ($page)
		{
			case PAGE_ANY:
			case PAGE_OVERVIEW:

				// switch from the block
				switch ($block)
				{
					case BLOCK_MENU_HEADER:

						$content_level_type_root = $class_entity::getTypeValue(
							array(
								PROPERTY_NAME => PROPERTY_ROOT,
								PROPERTY_ENTITY => ENTITY_CONTENT_LEVEL
							)
						);

						$content_level_type_overview = $class_entity::getTypeValue(
							array(
								PROPERTY_NAME => PROPERTY_OVERVIEW,
								PROPERTY_ENTITY => ENTITY_CONTENT_LEVEL
							)
						);

						$route_type_content = self::getTypeValue(
							array(
								PROPERTY_NAME => ENTITY_CONTENT,
								PROPERTY_ENTITY => ENTITY_ROUTE
							)
						);

						// select contents
						$select_contents = '
							SELECT
								ctt_title,
								'.TABLE_ALIAS_ROUTE.'.rte_index,
								CONCAT(
									IF (
										'.TABLE_ALIAS_ROUTE.'_.rte_uri != "/",
										'.TABLE_ALIAS_ROUTE.'_.rte_uri,
										""
									),
									'.TABLE_ALIAS_ROUTE.'.rte_uri
								) rte_uri
							FROM
								'.TABLE_CONTENT.'
							LEFT JOIN
								'.TABLE_ROUTE.' '.TABLE_ALIAS_ROUTE.'
							USING
								(rte_id)
							LEFT JOIN
								'.TABLE_ROUTE.' '.TABLE_ALIAS_ROUTE.'_
							ON
							(
								'.TABLE_ALIAS_ROUTE.'_.rte_id =
									'.TABLE_ALIAS_ROUTE.'.rte_parent_hub 
							)
							WHERE
								ctt_status = '.CONTENT_STATUS_ACTIVE.' AND
								'.TABLE_ALIAS_ROUTE.'.rte_level = '.
									(
										$page == PAGE_ANY
									?
										$content_level_type_root
									:
										$content_level_type_overview
									).' AND
								'.TABLE_ALIAS_ROUTE.'.rte_status = '.ROUTE_OPENED.' AND
								'.TABLE_ALIAS_ROUTE.'.rte_type = '.
								(
									$page == PAGE_ANY
								?
									$route_type_content.' '.SQL_OR.'
									'.TABLE_ALIAS_ROUTE.'.rte_type = '.ROUTE_TYPE_ROOT
								:
									ROUTE_TYPE_ADMINISTRATION
								).'
							ORDER BY
								'.TABLE_ALIAS_ROUTE.'.rte_index
						';

						$class_dumper::log(
							__METHOD__,
							array($select_contents)
						);

						// get a link
						$link = $class_db::getLink();

						// prepare the queyr
						$statement = $link->prepare($select_contents);

						// bind the result
						$statement->bind_result($text, $index, $href);

						// execute a statement
						$execution_result = $statement->execute();
				
						// fetch result of a statement
						while ( $fetch_result = $statement->fetch() )
	
							// check the result
							if ($fetch_result)
							{
								$menu[$index] = new stdClass();

								$menu[$index]->{ENTITY_TEXT} = $text;

								$menu[$index]->{HTML_ATTRIBUTE_HREF} = $href;								
							}

						break;
				}				

					break;
		}

		// return a menu
		return $menu;
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
    * Get a document
    *
    * @param	integer	$document_type	document type
    * @param	integer	$id				identifier
    * @param	string	$parent			parent
    * @param	integer	$start			starting value
    * @param	integer	$limit			limit
    * @param	boolean	$next			next flag
    * @param	boolean	$last_attempt	last attempt flag
    * @return 	nothing
    */	
	public static function getDocument(
		$document_type = DOCUMENT_TYPE_RDF,
		$id = null,
		$parent = null,
		$start = 0,
		$limit = PAGINATION_COUNT_PER_PAGE_DOCUMENT,
		$next = false,
		$last_attempt = false
	)
	{
		global $class_application;
		
		$content = '';

		// set the data fetcher class name
		$class_data_fetcher = $class_application::getDataFetcherClass();

		$contents = $class_data_fetcher::fetchDocument(
			$document_type,
			$id,
			$parent,
			$start,
			$limit,
			$next,
			$last_attempt
		);

		return $contents;
	}
	
	/**
	* Get a list of entities
	* 
	* @param	string	$document_type 	document type
	* @param	integer	$start			start
	* @param	integer	$limit			limit	
	* @return	object	list
	*/
	public static function getList(
		$document_type = DOCUMENT_TYPE_XHTML,
		$start = 0,
		$limit = PAGINATION_COUNT_PER_PAGE_DOCUMENT_XHTML
	)
	{
		global $class_application;

		$class_data_fetcher = $class_application::getDataFetcherClass();

		return $class_data_fetcher::fetchList($document_type, $start, $limit);
	}

	/**
	* Get search results
	* 
	* @param	array	&$context 	contextual parameters
	* @param	integer	$page 		page
	* @return	object	list
	*/
	public static function getSearchResults(&$context, $page)
	{
		// set the application class name
		global $class_application;

		// set the data fetcher class name
		$class_data_fetcher = $class_application::getDataFetcherClass();

		// set the field handler class name
		$class_field_handler = $class_application::getFieldHandlerClass();

		// set the form manager class name
		$class_form_manager = $class_application::getFormManagerClass();

		if (
			isset($context[CONTEXT_INDEX_FIELD_HANDLER]) &&
			is_object($context[CONTEXT_INDEX_FIELD_HANDLER])	
		)
		{		
			$field_handler = $context[CONTEXT_INDEX_FIELD_HANDLER];
			
			$handler_id = $field_handler->getHandlerId();

			$field_values = $field_handler->getFieldValues($handler_id);

			$entity = new stdClass();

			$entity->{PROPERTY_TYPE} = CONTENT_TYPE_SEARCH_RESULTS;

			if (!empty($_SESSION[STORE_DIALOG]))
			
				unset($_SESSION[STORE_DIALOG]);

			// destroy the current field handler
			$class_form_manager::destroyHandler($handler_id);

			if (!empty($field_values[ROW_KEYWORDS]))
			{
				$entity->{PROPERTY_VALUE} = $field_values[ROW_KEYWORDS];

				$_SESSION[STORE_SEARCH_RESULTS] = $class_data_fetcher::fetchList($entity);

				$_SESSION[STORE_SEARCH_RESULTS]->{ROW_KEYWORDS} = $field_values[ROW_KEYWORDS];

				$class_application::jumpTo(URI_CONTENT_SEARCH_RESULTS);
			}
			else

				$class_application::jumpTo(PREFIX_ROOT);
		}
		else

			$class_application::jumpTo(PREFIX_ROOT);			
	}
}
?>