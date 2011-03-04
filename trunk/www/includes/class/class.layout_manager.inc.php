<?php

/**
* Layout manager class
*
* Class for layout management
* @package  sefi
*/
class Layout_Manager extends View_Builder
{
    /**
    * Beautify a snippet of code
    *
    * @param	string		$source             source
    * @param	boolean		$clean_source       clean up flag
    * @param	boolean	    $declare_doctype    doctype flag
    * @param	boolean		$plant_tree	        XML root element flag
    * @param	array		$config 	        configuration
    * @return  	string	    tidied portion of code
    */	
    public static function beautify_source(
        $source = null,
        $clean_source = VALIDATE_TIDY_SOURCE,
        $declare_doctype = VALIDATE_DOCTYPE_DECLARATION,
        $plant_tree = VALIDATE_TREE_PLANTING,
        $config = null
    )
    {
        // check the config
        if (!isset($config))

            $_config = array(
                TIDY_OPTION_INDENT => VALIDATE_TIDY_AUTO_INDENT,
                TIDY_OPTION_MARKUP => VALIDATE_TIDY_MARKUP,			
                TIDY_OPTION_OUTPUT_XHTML => VALIDATE_TIDY_OUTPUT_HTML,
                TIDY_OPTION_WRAP => VALIDATE_TIDY_WRAP,
                TIDY_OPTION_BODY_ONLY => TIDY_FLAG_BODY_ONLY
            );
        else

            $_config = $config;

        if ($clean_source && function_exists(FUNCTION_TIDY_PARSE_STRING))
        {
            $tidy = tidy_parse_string($source, $_config, 'UTF8');
            $source = $tidy->value;
        }

        // check the doctype flag
        if ($declare_doctype)

            $source = DOCTYPE_XHTML_TRANSITIONAL.$source;

        // check the XML root element flag
        if ($plant_tree)

            $source = DOCUMENT_ROOT_XML.$source;

        // return a beautified source
        return $source;
    }

    /* 
    * Display tabs
    * 
    * @param	array  $configuration  configuration
    * @return 	string
    */
    public static function buildLayout($configuration)
    {
        // set the application class name
		global $class_application, $verbose_mode;

        // declare an empty array of layouts
        $layouts = array();

        // get the layout type and its configuration
        list($layout_type, $layout_configuration) = each($configuration);
        
        // switch from the configuration layout
        switch ($layout_type)
        {
            case LAYOUT_TYPE_TABS:

                // loop on the layout configuration elements
                while (list($index, $configuration) = each($layout_configuration))
                {
                    // switch from the configuration type
                    switch ($configuration[PROPERTY_TYPE])
                    {
                        case ENTITY_FORM:

                            // display a form
                            $layouts[] = $class_application::fetchForm(
                                $configuration[PROPERTY_AFFORDANCE],
                                BLOCK_FORM,
                                PAGE_UNDEFINED
                            );

                                break;
                    }
                }

                    break;
        }

        // return the layouts
        return $layouts;
    }

    /*
    * Display tabs
    * 
    * @param	string	    $affordance		affordance
    * @param	integer		$page		    page
    * @param	string		$block		    block name
    * @param	array	    $variables		variables
    * @return 	nothing
    */	
    public static function displayTabs(
        $affordance,
        $page = PAGE_UNDEFINED,
        $block = BLOCK_HTML,
        $variables = null		        
    )
    {
		global $verbose_mode;

		$class_dumper = self::getDumperClass();

		$class_template_engine = self::getTemplateEngineClass();

        // construct a new instance of the template engine class
        $template_engine = new $class_template_engine();

        $template_name = TPL_DEFAULT_XHTML_STRICT_LAYOUT;

        $cache_id = md5( time() );

        if (
            ! (
                $cached =
                    $template_engine->is_cached(
                        $template_name,
                        $cache_id
                    )
            )
        )
		{
            // fetch tabs
            $template_engine->assign(
                PLACEHOLDER_BODY,
                self::fetchTabs( $affordance, $page, $block, $variables )
            );

            // Assign a footer to the template
            $template_engine->assign(
                PLACEHOLDER_FOOTER,
                self::getFooter()
            );			
		}

        // fetch the tabs            
        $tabs = $template_engine->fetch( $template_name, $cache_id );

        // return beautified tabs
        echo self::beautify_source($tabs);

        // clear all cache
        $template_engine->clear();
    }

   /*
    * Fetch tabs
    * 
    * @param	string	    $affordance		affordance
    * @param	integer		$page		    page
    * @param	string		$block		    block name
    * @param	array	    $variables		variables
    * @return 	string
    */	        
    public static function fetchTabs(
        $affordance,
        $page = PAGE_UNDEFINED,
        $block = BLOCK_HTML,
        $variables = null
    )
    {
        // set the file manager class name
        $class_file_manager = self::getFileManagerClass();

        // set the template engine class name
		$class_template_engine = self::getTemplateEngineClass();

        // set the view builder class name
        $class_view_builder = self::getViewBuilderClass();

        // construct a new instance of the template engine class
        $template_engine = new $class_template_engine();

        $template_name = TPL_LAYOUT_TABS;

		// set the configuration file name
		$config_file = PREFIX_TABS.$affordance.EXTENSION_YAML;

        // load a configuration file
        $configuration = $class_file_manager::load_configuration( $config_file );

        $cache_id = md5( time() );

        if (
            ! (
                $cached =
                    $template_engine->is_cached(
                        $template_name,
                        $cache_id
                    )
            )
        )
        {
            // build a layout
            $layout_tabs = self::buildLayout( $configuration );
    
            // build the left block
            $layout_left = $class_view_builder::buildBlock(
                PAGE_ANY,
                BLOCK_LEFT,
                $configuration
            );
    
            // build the common header
            $layout_header = $class_view_builder::buildBlock(PAGE_HOMEPAGE, BLOCK_HEADER);
    
            // assign the left block layout to the template engine
            $template_engine->assign(PLACEHOLDER_COLUMN_LEFT, $layout_left);
    
            // assign the layout header to the template engine
            $template_engine->assign(PLACEHOLDER_HEADER, $layout_header);
    
            // assign the layout tabs to the template engine
            $template_engine->assign(PLACEHOLDER_TABS, $layout_tabs);
        }

        // get the tabs
        $tabs = $template_engine->fetch( $template_name, $cache_id );

        // clear all cache
        $template_engine->clear();

        // return the tabs
        return $tabs;
    }

	/*
    * Get a footer
    * 
    * @return 	string	footer
    */	        
    public static function getFooter()
    {
		global $class_application, $verbose_mode;

		$class_view_builder = $class_application::getViewBuilderClass();
		
		return $class_view_builder::getFooter();
	}
}
