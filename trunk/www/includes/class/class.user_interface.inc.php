<?php

/**
* User Interface class
*
* @package  sefi
*/
class User_Interface extends Toolbox
{
	private $_dom;
	private $_templates;

    /**
    * Construct a new user interface builder
    *
    * @param	string	$url	containing a URL
    * @param	integer	$page	representing a page
    * @param	string	$block	containing a block name
    * @return  	object  representing a user interface builder
    */	    
    public function __construct($url = null, $page = null, $block = BLOCK_HTML)
    {
		// check the page argument
		if (!isset($page))

			// return from here if no argument is provided
			return;	

		// declare the member DOM
        $this->_dom = array();

		// declare the member templates		
		$this->_templates = array(
			array(
				TEMPLATE_BLOCK => $block,
				TEMPLATE_CONTENTS => CHARACTER_EMPTY_STRING,
				TEMPLATE_PAGE => $page
			)
		);

		// set stream context options
		$opts = array(
			'http' => array(
				'method'  => 'GET',
				'header' =>
					"User-Agent: Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; fr; rv:1.9.1.5) Gecko/20091102 Firefox/3.5.5 GTB6 FirePHP/0.3".
					"Accept-Language: en-us,en;q=0.8,fr;q=0.5,fr-fr;q=0.3"
			)
		);

		// check the URL argument
		if (empty($url))

			// set the default URL
			$url = PROTOCOL_HTTP.$_SERVER['HTTP_HOST'].PREFIX_USER_INTERFACE.ENTITY_FACTORY.EXTENSION_PHP;

		// set the stream context
		$context = stream_context_create($opts);

		// set the contents
		$contents = file_get_contents($url, false, $context);

		// add the contents to the member templates
		$this->_templates[$page][$block] = $contents;

		// set the DOM member
        $this->_dom[ENTITY_DOCUMENT] = new DOMDocument();

		// check the current block
		if (!empty($this->_templates[$page][$block]))

			// Load and valid the current XML document
			$this->_dom[ENTITY_DOCUMENT]->loadXML($this->_templates[$page][$block], LIBXML_DTDVALID);

		// check the block argument
		if (isset($block))

			// display the block
			echo $this->get_block();
    }

    /**
    * Get a string representing a source code
    *
    * @return  string	containing contents
	*/
    public function __toString()
    {
        $dom = &$this->get_dom();
        
		$source = $dom[ENTITY_DOCUMENT]->saveXML();

        $config = array(
            'indent' => TRUE,
            'output-xhtml' => TRUE,
            'wrap' => 200
        );

        $tidy = tidy_parse_string($source, $config, 'UTF8');

        return $tidy->value;        
    }

    /**
    * Check the differences between blocks
    *
    * @return  mixed
    */	 
	public function diff_blocks()
	{
		$class_db = CLASS_DB; 

		$class_lsql = CLASS_LSQL;

		$class_lsql::closeConnecton();

		try
		{
			// get the current MySQL connection
			$link = $class_lsql::getLink();
		}
		catch (\Exception $exception)
		{
			$dumper = new \dumper(						
				__CLASS__,
				__METHOD__,
				array(
					'An exception has been caught while calling l S Q L   : :  g e t L i n k =>',
					$exception
				),
				DEBUGGING_DISPLAY_EXCEPTION,
				AFFORDANCE_CATCH_EXCEPTION
			);
		}

		// get the current contents
		$contents = &$this->get_contents();
		$block = &$this->get_block(true);
		$page = &$this->get_page(true);

		$file_system_contents = mysqli_real_escape_string($link, $contents);
		$file_system_hash = md5($contents);

		$insert_template_model = "
			INSERT INTO ".TABLE_TEMPLATE." (
				tpl_block,
				tpl_contents,
				tpl_hash,
				tpl_modification_date,
				tpl_page
			) VALUES (
				'{block}',
				'{contents}',
				'{hash}',
				NOW(),
				{page}
			)
		";

		$select_template_model = "
			SELECT
				tpl_id,
				tpl_contents
			FROM
				".TABLE_TEMPLATE."
			WHERE
		";

		$update_hash_model = "
			UPDATE ".TABLE_TEMPLATE." SET 
				tpl_hash = md5(tpl_contents),
				tpl_modification_date = NOW()
			WHERE
				tpl_id = {identifier}
		";
		
		$update_status_model = "
			UPDATE ".TABLE_TEMPLATE." SET 
				tpl_status = ".TEMPLATE_STATUS_ACTIVE."
			WHERE
				tpl_id = {identifier}
		";

		$update_template_model = "
			UPDATE ".TABLE_TEMPLATE." SET 
				tpl_status = ".TEMPLATE_STATUS_INACTIVE."
			WHERE
				tpl_block = '{block}' AND
				tpl_id != {identifier} AND
				tpl_page = {page}
		";

		$clause_where_active_template = "
				tpl_block = '{block}' AND
				tpl_page = {page} AND
				tpl_status = ".TEMPLATE_STATUS_ACTIVE
		;

		$clause_where_hash = "
				tpl_hash = '{hash}'
			LIMIT 1
		";

		$select_active_template = preg_replace(
			array(
				"/\{block\}/",
				"/\{page\}/"
			),
			array(
				$block,
				$page
			),
			$select_template_model.$clause_where_active_template
		);

		$select_hash = preg_replace(
			"/\{hash\}/",
			$file_system_hash,
			$select_template_model.$clause_where_hash
		);

		$hash_result = $class_lsql::query($select_hash);
		$template_result = $class_lsql::query($select_active_template);

		if ($hash_result->num_rows || $template_result->num_rows)
		{
			if ($hash_result->num_rows)
			{
				$hash = $hash_result->fetch_object();
				$template_identifier = $hash->tpl_id;

				$update_status = preg_replace(
					"/{identifier}/",
					$template_identifier,
					$update_status_model
				);

				$class_db::query($update_status);

				$update_template = preg_replace(
					array(
						"/\{block\}/",						
						"/\{identifier\}/",
						"/\{page\}/"
					),
					array(
						$block,
						$template_identifier,
						$page
					),
					$update_template_model
				);

				$update_hash = preg_replace(
					array(
						"/\{identifier\}/"
					),
					array(
						$template_identifier
					),
					$update_hash_model
				);

				self::save_template($page, $block, $contents);
				$class_db::query($update_template);
				$class_db::query($update_hash);

				return $template_identifier;
			}
			else if ($template_result->num_rows)
			{
				$template = $template_result->fetch_object();
	
				$database_contents = mysqli_real_escape_string($link, $template->tpl_contents);
	
				if (md5($file_system_contents) != md5($database_contents))
				{
					$insert_template = preg_replace(
						array(
							"/\{block\}/",						
							"/\{contents\}/",
							"/\{hash\}/",
							"/\{page\}/"
						),
						array(
							$block,
							$file_system_contents,
							$file_system_hash,
							$page							
						),
						$insert_template_model
					);
	
					$latest_identifier = $class_db::query($insert_template);
	
					$update_template = preg_replace(
						array(
							"/\{block\}/",						
							"/\{identifier\}/",
							"/\{page\}/"
						),
						array(
							$block,
							$latest_identifier,
							$page
						),
						$update_template_model
					);

					self::save_template($page, $block, $contents);	
					$class_db::query($update_template);
	
					return $latest_identifier;
				}
			}
			else
				return false;
		}
		else
			return $this->save_block();
	}

    /**
    * Get a block access type
    *
    * @param	array	$properties	containing properties overriding the default ones
    * @param	string	$block		containing the name of a block
    * @param	integer	$page		representing a page
	* @param	integer	$handler_id	representing a field handler
    * @return 	array	containing the properties of a block
	*/
	public function get_block_properties(
		$properties = null,
		$block = null,
		$page = PAGE_UNDEFINED,
		$handler_id = FORM_ORDINARY
	)
	{
		// declare the default properties
		$default_properties = array();

		// declare the default access type property		
		$default_properties[PROPERTY_ACCESS_TYPE] = AFFORDANCE_GET_ELEMENT_BY_ID;
	
		// declare the default clean up property
		$default_properties[PROPERTY_CLEAN_UP] = false;

		// check the block argument
		if (!isset($block))

			// set a block		
			$block = $this->get_block(true);

		// switch from the page
		switch ($page)
		{
			default:

				// set the default blanks properties
				$default_properties[PROPERTY_BLANKS] = array(
					HTML_ELEMENT_BODY => CHARACTER_EMPTY_STRING
				);

				// set the default clean up properties
				$default_properties[PROPERTY_CLEAN_UP] = true;
		}

		// check the property filter
		if (isset($properties) && is_array($properties) && count($properties) > 0)

			while (list($property_index, $property) = each($properties))

				if (!empty($property))

					$default_properties[$property_index] = $property;

		// return the default properties
		return $default_properties;
	}

    /**
    * Get a block
    *
    * @param	boolean	$current	indicating if the current block should be retrieved
    * @return 	mixed
	*/
    public function get_block($current = false)
    {
		if ($current)

			return	$this->_templates[0][TEMPLATE_BLOCK];

		$block = &$this->get_block(true);
        $dom = &$this->get_dom();

		$properties = $this->get_block_properties(array(), $block);
		$access_type = $properties[PROPERTY_ACCESS_TYPE];
		
		switch ($access_type)
		{
			case AFFORDANCE_GET_ELEMENT_BY_ID:
				$block_element = $this->_dom[ENTITY_DOCUMENT]->getElementById($block);
				$this->_dom[$access_type][$block] = $block_element;
		
				break;

			case AFFORDANCE_GET_ELEMENTS_BY_TAG:
				$tags = $this->_dom[ENTITY_DOCUMENT]->getElementsByTagName($block);
		        $this->_dom[$access_type][$block] = &$tags->item(0);

				break;
		}

		$different_blocks = $this->diff_blocks();
		$contents = $this->get_contents(true);

		return $contents;
	}

	/**
    * Get contents
    *
    * @param	boolean	$current	indicating if the current contents should be retrieved
    * @return 	mixed
	*/
    public function get_contents($current = false)
    {
		$appending_element_list = array();  
		$nodes = array();

		$block = &$this->get_block(true);
		$properties = $this->get_block_properties();
		$access_type = $properties[PROPERTY_ACCESS_TYPE];
		$clean_up = $properties[PROPERTY_CLEAN_UP];

		$dom = &$this->get_dom();

		// declare text nodes
		$body_node = new DOMText();
		$block_node = new DOMText();
		$head_node = new DOMText();
		$h1_node = new DOMText();
		$span_node = new DOMText();

		// create br, h1, span elements
		$br_element = $dom[ENTITY_DOCUMENT]->createElement(HTML_ELEMENT_BR);
		$h1_element = $dom[ENTITY_DOCUMENT]->createElement(HTML_ELEMENT_H1);
		$span_element = $dom[ENTITY_DOCUMENT]->createElement(HTML_ELEMENT_SPAN);

		// set the class attribute of a br element to clear style
		$br_element->setAttribute(HTML_ATTRIBUTE_CLASS, STYLE_CLASS_CLEAR);    

		// prepare template blanks
		$block_text = CHARACTER_BRACKET_START.CHARACTER_DOLLAR.$block.CHARACTER_BRACKET_END;
		$body_text = CHARACTER_BRACKET_START.CHARACTER_DOLLAR.BLOCK_FORM.CHARACTER_BRACKET_END;
		$h1_text = CHARACTER_BRACKET_START.CHARACTER_DOLLAR.HTML_ELEMENT_H1.CHARACTER_BRACKET_END;	
		$head_text = CHARACTER_BRACKET_START.CHARACTER_DOLLAR.HTML_ELEMENT_HEAD.CHARACTER_BRACKET_END;
		$span_text = CHARACTER_BRACKET_START.CHARACTER_DOLLAR.HTML_ELEMENT_SPAN.CHARACTER_BRACKET_END;

		// append data to nodes
		$block_node->appendData($block_text);
		$body_node->appendData($body_text);
		$h1_node->appendData($h1_text);
		$head_node->appendData($head_text);
		$span_node->appendData($span_text);

		// set the id attribute of a h1 element
		$h1_element->setAttribute(
			HTML_ATTRIBUTE_ID,
			CHARACTER_BRACKET_START.
				CHARACTER_DOLLAR.
				HTML_ELEMENT_H1.
				CHARACTER_UNDERSCORE.
				HTML_ATTRIBUTE_ID.
			CHARACTER_BRACKET_END
		);

		// append nodes to elements
		$h1_element->appendChild($h1_node);
		$span_element->appendChild($span_node);

		// append the span element to the h1 element
		$h1_element->appendChild($span_element);

		// return the current contents
		if ($current)
			return $this->_templates[0][TEMPLATE_CONTENTS];

		// check if the current contents is not empty and accessible from the DOM
		if (empty($this->_templates[0][TEMPLATE_CONTENTS]) && is_object($dom[$access_type][$block]))
		{
			// get access to the block element from the DOM
			$block_element = $dom[$access_type][$block];
			$block_children = &$block_element->childNodes;
			$node_count = $block_children->length;

			// check if the children of the current block should be removed
			if ($clean_up)
			{
				foreach ($block_children as $node)
					$nodes[] = $node;
	
				while (list($node_index, $node) = each($nodes))
					$block_element->removeChild($node);
			}

			switch ($block)
			{
				case BLOCK_CONTAINER:

					$appending_element_list[] = $block_node;
					$appending_element_list[] = $br_element;

						break;

				case BLOCK_CONTENTS:

					$appending_element_list[] = $h1_element;

				case BLOCK_FORM:
				case BLOCK_PAGE:

					$appending_element_list[] = $block_node;
	
						break;

				case BLOCK_HTML:

					$appending_element_list[] = $head_node;
					$appending_element_list[] = $body_node;

						break;
			}

			while (list($element_index, $element) = each($appending_element_list))
				$block_element->appendChild($element);

			// store the block source 
			$this->_templates[0][TEMPLATE_CONTENTS] = $block_element->ownerDocument->saveXML($block_element);
		}

		return $this->_templates[0][TEMPLATE_CONTENTS];
	}

    /**
    * Get the current document oject model
    *
    * @return  object	representing a document object model
    */	       
    public function get_dom()
    {
        return $this->_dom;
    }

    /**
    * Get a page
    *
    * @param	boolean	$current	indicating if the current page should be retrieved
    * @return 	mixed
	*/
    public function get_page($current = false)
    {
		if ($current)
			return	$this->_templates[0][TEMPLATE_PAGE];
	
        $contents = $this->_dom[0][TEMPLATE_CONTENTS];

		return $contents;
	}

    /**
    * Save a block
    *
    * @return  integer	representing a row of block attributes just inserted into the database
	*/	
	public function save_block()
	{
		$class_db = CLASS_DB;

		$class_db::closeConnection();

		$link = $class_db::getLink();

		$contents = &$this->get_contents(true);
		$block = &$this->get_block(true);
		$page = &$this->get_page(true);

		$file_system_contents = mysqli_real_escape_string($link, $contents);
		$file_system_hash = md5($contents);

		$insert_template_model = "
			INSERT INTO ".TABLE_TEMPLATE." (
				tpl_block,
				tpl_contents,
				tpl_hash,
				tpl_modification_date,
				tpl_page
			) VALUES (
				'{block}',
				'{contents}',
				'{hash}',
				NOW(),
				{page}
			)
		";

		$update_template_model = "
			UPDATE ".TABLE_TEMPLATE." SET 
				tpl_status = ".TEMPLATE_STATUS_INACTIVE."
			WHERE
				tpl_block = '{block}' AND
				tpl_id != {identifier} AND
				tpl_page = {page}
		";

		$insert_template = preg_replace(
			array(
				"/\{block\}/",
				"/\{contents\}/",
				"/\{hash\}/",
				"/\{page\}/"
			),
			array(
				$block,
				$file_system_contents,
				$file_system_hash,
				$page
			),
			$insert_template_model
		);

		$latest_identifier = $class_db::query($insert_template);
	
		$update_template = preg_replace(
			array(
				"/\{block\}/",						
				"/\{identifier\}/",
				"/\{page\}/"
			),
			array(
				$block,
				$latest_identifier,
				$page
			),
			$update_template_model
		);				

		self::save_template($page, $block, $contents);
		$class_db::query($update_template);

		return $latest_identifier;
	}

    /**
    * Get templates
    * 
    * @param	integer		$page		representing a page
	* @param	integer		$handler_id	representing a field handler
    * @param	string		$block_name	containing a block name
    * @param	array		$blocks		containing block names
    * @return 	mixed
    */
    public static function get_templates(
		$page = PAGE_UNDEFINED,
		$handler_id = FORM_UNDEFINED,
		$block_name = null,
		$blocks = null
	)
    {
		global $class_application;

		$condition_block = CHARACTER_EMPTY_STRING;

		$templates = array();

		$user_interface_builder = new self();

		$class_template_engine = $class_application::getTemplateEngineClass(); 

		// check the handler identifier
		switch ($handler_id)
		{
			default:

				$block_blanks = 
				$property_cursors = array(
					BLOCK_HTML
				);

				// check the block name argument
				if (!isset($block_name))

					// set the block name
					$block_pointer = BLOCK_HTML;

				else

					// set the block name
					$block_pointer = $block_name;

				// declare a new smarty template object
				$template_engine = new $class_template_engine();

				$template_start = TPL_DEFAULT_XHTML_TRANSITIONAL_START;

				$template_end = TPL_DEFAULT_XHTML_TRANSITIONAL_END;

				$template_engine->cache_lifetime = 0;

				// store the template start
				$templates[$block_pointer][TEMPLATE_SOURCE] =
					$template_engine->fetch( $template_start )
				;

				// store the template end
				$templates[$block_pointer][TEMPLATE_SOURCE] .=
					$template_engine->fetch( $template_end )
				;

				// loop on the blanks
				while (list($block_index, $block) = each($block_blanks))
				{
					// get block properties to retrieve list of blanks
					$properties = $user_interface_builder->get_block_properties(
						array(),
						$block,
						$page,
						$handler_id
					);

					// set the blanks
					$blanks = $properties[PROPERTY_BLANKS];
				
					// loop on the inner blanks of top level blocks
					while (list($blank_name, $blank_values) = each($blanks))
					{

						// loop on the cursor properties							
						while (list($cursor_index, $cursor) = each($property_cursors))
						{

							// add an item to the blank property of the current block
							if ($block_index <= $cursor_index)

								// set a blanks property
								$templates[$cursor][PROPERTY_BLANKS][] = $blank_name;
						}

						// reset internal pointer of the property cursor
						reset($property_cursors);
					}
				}
		}

		// check the block name
		if (!isset($block_name))

			// return the templates store
			return $templates;
		else
		
			// return the template of a block
			return $templates[$block_name];
	}

    /**
    * Refactor the user interface
    *
    * @param	integer	$page	representing a page
    * @param	array	$blocks	containing block names
    * @return 	nothing
	*/
	public static function refactor(
		$page = FORM_ARBITRARY,
		$blocks = NULL
	)
	{
		if (!isset($blocks))
			$blocks = array(
				BLOCK_BREADCRUMBS,
				BLOCK_CONTAINER,
				BLOCK_CONTENTS,
				BLOCK_FORM,
				BLOCK_FOOTER,
				BLOCK_HEAD,
				BLOCK_HEADER,
				BLOCK_HTML,
				BLOCK_LEFT,
				BLOCK_PAGE,
				BLOCK_RIGHT
			);

		while (list($block_index, $block) = each($blocks))
			$ui_builder = new self($page, $block);
	}

    /**
    * Save a template on disk
    *
    * @param	integer	$page		page
    * @param	string	$block		name of a block
    * @param	string	$contents	contents
    * @return 	nothing
	*/
	public static function save_template( $page, $block, $contents )
	{
		if (mb_detect_encoding($contents) != "UTF-8")
			$contents = utf8_encode($contents);

		$template_directory =
			DIR_PARENT_DIRECTORY.
			DIR_PARENT_DIRECTORY.
			CHARACTER_SLASH.
			DIR_TEMPLATES
		;

		$file_name = Lang_wd_WD.CHARACTER_FULL_STOP.NAMESPACE_UNIT_TESTING.
			CHARACTER_DOT.
				$block.
					EXTENSION_TPL
		;

		$file_path = dirname(__FILE__).$template_directory.$file_name;

		$handle = fopen($file_path , "w");
		fwrite($handle, $contents);
		fclose($handle);		
	}
}
?>