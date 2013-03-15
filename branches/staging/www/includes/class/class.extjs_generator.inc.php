<?php

/**
* Extjs generator class
*
* Class to generate Extjs code snippets
* @package  sefi
*/
class Extjs_Generator extends Toolbox
{
    private $_file_id;
    private $_file_name;
    private $_file_path;
    private $_functions;
    private $_objects;
    private $_url;

	/**
    * Make a ExtJsGenerator
	*
	* @param	string  $file_name  a file name
    * @return	nothing
	*/  
    public function __construct($file_name)
	{
        $this->_file_name = $file_name;
        $this->_objects = array();

        return;
    }

	/**
    * Check the existence of a function
    *
	* @param	string	$name  containing a function name
    * @return	nothing
    */        
    public function checkFunction($name)
    {
        $store = &$this->_functions;

        if (isset($store[$name]))
			throw new Exception("a function with the same name, <span style='font-style:italic'>{$name}</span>, exists already");
    }

    /**
    * Check the existence of parents for a given object
    *
	* @param	string    $object_name  containing an object name
	* @param	string    $member 		containing a member
	* @param	string    $member_type  containing a class name
	* @param	string    $value  		containing a value
    * @return	nothing
    */        
    public function checkObjectParents($object_name, $member, $value, $member_type = JS_CLASS_OBJECT)
    {
        $parent_keys = array();
        $member_keys = explode(OPERATOR_GET_MEMBER, $object_name.OPERATOR_GET_MEMBER.$member);
        $separator_count = preg_match("/\./", $object_name.OPERATOR_GET_MEMBER.$member);
        $index = $separator_count + 1;
        $parent_key = $index;
        $store = &$this->_objects;

		if ($member_type == JS_CLASS_OBJECT)
			$start_key = 1;
		else 
			$start_key = 0;

        while ($parent_key >= $start_key)
        {
			$firephp = FirePHP::getInstance(true);

            $parent_keys[$index - $parent_key] = $member_keys[$index - $parent_key];
            $key = array_pop($parent_keys);

            if (!in_array($key, $store))
                $store[$key] = self::mirrorExtJsObject($key, JS_CLASS_OBJECT, SCOPE_GLOBAL);

            if ($parent_key > 0) 
                $store = &$store[$key]->_members;
            else
            {
                $store[$key]->_members[$member] = new stdClass();
                $store[$key]->_members[$member]->_instances = array();
                $store[$key]->_members[$member]->_instanceof = $member_type;
                $store[$key]->_members[$member]->_scope = SCOPE_GLOBAL;
                $store[$key]->_members[$member]->_instances[] =  $member;

                if ($member_type == JS_CLASS_SERIALIZED_XEON_MODULE)
                    $store[$key]->_members[$member]->_value = $value;
            }

            $parent_key--;
        }
    }

	/**
    * Make an anonymous function
	*
    * @param	array  	$arguments  	containing the function arguments
	* @param	string	$item_name  	containing an item name
	* @param	string	$suffix  		containing a function name suffix
	* @param	string	$panel_type  	containing a panel type
	* @param	string  $body  			containing a function body
    * @return	string  containing a function declaration
	*/
    public function ExtJsAnomFunction($arguments = array(), $item_name, $suffix, $panel_type, $body = "")
    {
		$name = PREFIX_ACTION_MAKE.ucfirst($item_name).ucfirst($suffix);
		$this->checkFunction($name);

        $prototype = self::ExtJsFunctionPrototype($arguments, $item_name, $suffix, $panel_type);

		if (!is_array($this->_functions))
			$this->_functions = array();

		$function = new stdClass();
		$function->_arguments = $arguments;
		$function->_body = $body;
		$function->_item_name = $item_name;
		$function->_name = $name;
		$function->_panel_type = $panel_type;		
		$function->_suffix = $suffix;

		$this->_functions[$name] = $function;

        return $prototype.PUNCTUATION_BRACKET_START.$body.PUNCTUATION_BRACKET_END.SPECIAL_CHARACTER_LF;
    }

    /**
    * Assign a value to a variable
	*
	* @param	string  $variable_name  containing a variable name
	* @param	string  $variable_type  containing a class name
	* @param	string  $value 			containing a value
	* @param	integer $scope 			representing the scope of a variable
    * @return	string  containing an value assignment
	*/
    public function ExtJsAssignVariableValue($variable_name, $value = null, $variable_type = JS_CLASS_STRING, $scope = SCOPE_LOCAL)
    {
		global $class_application;

		$class_template_engine = $class_application::getTemplateEngineClass();

		$template_engine = new $class_template_engine;

        switch ($variable_type)
        {
            case JS_CLASS_STRING:
                $value = PUNCTUATION_DOUBLE_QUOTE.$value.PUNCTUATION_DOUBLE_QUOTE;

                break;
            case JS_CLASS_STRING:
                $value = PUNCTUATION_BRACKET_START.SPECIAL_CHARACTER_LF.SPECIAL_CHARACTER_HT.$value.SPECIAL_CHARACTER_LF.PUNCTUATION_BRACKET_END;

            case JS_CLASS_OBJECT:        
                break;
        }

        $value .= PUNCTUATION_SEMI_COLUMN;

        $template_engine->assign("equal", OPERATOR_EQUAL);
        $template_engine->assign("variable_name", $variable_name);
        $template_engine->assign("value", $value);

        $this->_objects[$variable_name] = new stdClass();
        $this->_objects[$variable_name]->_instanceof = $variable_type;
        $this->_objects[$variable_name]->_members = array();

        if ($scope)
        {
            $template_engine->assign("var", KEYWORD_VAR." ");            
            $this->_objects[$variable_name]->_scope = SCOPE_LOCAL;
        }
        else
            $this->_objects[$variable_name]->_scope = SCOPE_GLOBAL;

        ob_start();
        $template_engine->display(TPL_ASSIGN_VARIABLE_VALUE);
        $buffer_content = ob_get_contents();
        ob_end_clean();
        
        $template_engine->clear();
        
        return $buffer_content;
    }

	/**
    * Make a function prototype 
	*
    * @param	array  	$arguments  	containing the function arguments
	* @param	string  $item_name  	containing an item name
	* @param	string	$suffix  		containing a function name suffix
	* @param	string 	$panel_type  	containing a panel type
    * @return	string  containing a function prototype 
	*/    
    public static function ExtJsFunctionPrototype($arguments = array(), $item_name, $suffix, $panel_type)
    {
		global $class_application;

		$class_template_engine = $class_application::getTemplateEngineClass();

        $template = new $class_template_engine();

        $function_name = "make".ucfirst(strtolower($item_name)).ucfirst(strtolower($suffix));
    
        $template->assign("arguments", $arguments);
        $template->assign("directive_function", DIRECTIVE_FUNCTION);
        $template->assign("function_name", $function_name);
        
        $template->assign("comma", PUNCTUATION_COMMA);
        $template->assign("column", PUNCTUATION_COLUMN);
        $template->assign("bracket_start", PUNCTUATION_BRACKET_START);
        $template->assign("bracket_end", PUNCTUATION_BRACKET_END);
        $template->assign("parenthesis_start", PUNCTUATION_PARENTHESIS_START);
        $template->assign("parenthesis_end", PUNCTUATION_PARENTHESIS_END);
        $template->assign("semi_column", PUNCTUATION_SEMI_COLUMN);
        $template->assign("square_bracket_start", PUNCTUATION_BRACKET_START);
        $template->assign("square_bracket_end", PUNCTUATION_BRACKET_END);
        
        $buffer_content = $template->fetch(TPL_PROTOTYPE_FUNCTION);
        
        $template->clear();
        
        return $buffer_content;
    }

	/**
    * Make an instantiation prototype
	*
	* @param	string	$class_name  	containing a class name
	* @param	string	$object_name  	containing an object name
	* @param	integer	$scope 			representing the scope of a variable
    * @return	string  containing  a instantiation prototype
	*/
    public function ExtJsObjectPrototype($class_name, $object_name, $scope = DEFAULT_ARGUMENT_LOCAL)
    {
		global $class_application;

		$class_template_engine = $class_application::getTemplateEngineClass();

		$template_engine = new $class_template_engine;

        if (in_array($object_name, $this->_objects))

            throw new Exception(
				"a variable with the same name, ".
				"<span style='font-style:italic'>{$object_name}</span>, exists already"
			);

        $template_engine->assign("class_name", $class_name);
        $template_engine->assign("equal", OPERATOR_EQUAL);
        $template_engine->assign("new", KEYWORD_NEW);
        $template_engine->assign("variable_name", $object_name);

        if ($scope)

            $template_engine->assign("var", KEYWORD_VAR." ");

        $object = self::mirrorExtJsObject($object_name, $class_name, $scope);

        $this->_objects[$object_name] = $object;

        $buffer_content = $template_engine->fetch(TPL_INSTANTIATE_OBJECT);

        $template_engine->clear();

        return $buffer_content;
    }

	/**
    * Set an object member
	*
	* @param	string	$object_name  	containing an object name
	* @param	string	$member  		containing a member
	* @param	string	$member_type  	containing a class name
	* @param	string	$value  		containing a value
    * @return	string  containing an object member setting
	*/
    public function ExtJsSetObjectMember($object_name, $member, $value = null, $member_type = JS_CLASS_OBJECT)
    {
		global $class_application;

		$class_template_engine = $class_application::getTemplateEngineClass();

		$template_engine = new $class_template_engine;
        
        $this->checkObjectParents($object_name, $member, $value, $member_type);
        
        switch ($member_type)
        {
            case JS_CLASS_STRING:
                $value = PUNCTUATION_DOUBLE_QUOTE.$value.PUNCTUATION_DOUBLE_QUOTE;
                
                break;
            case JS_CLASS_SERIALIZED_XEON_MODULE:
                $value = PUNCTUATION_BRACKET_START.SPECIAL_CHARACTER_LF.SPECIAL_CHARACTER_HT.$value.SPECIAL_CHARACTER_LF.PUNCTUATION_BRACKET_END;

            case JS_CLASS_OBJECT:        
                break;
        }

        $template_engine->assign("lf", SPECIAL_CHARACTER_LF);        
        $template_engine->assign("equal", OPERATOR_EQUAL);
        $template_engine->assign("get_member", OPERATOR_GET_MEMBER);
        $template_engine->assign("ht", SPECIAL_CHARACTER_HT);        
        $template_engine->assign("member", $member);
        $template_engine->assign("object", $object_name);
        $template_engine->assign("semi_column", PUNCTUATION_SEMI_COLUMN);
        $template_engine->assign("value", $value);

        $buffer_content = $template_engine->fetch(TPL_SET_OBJECT_MEMBER);
        
        $template_engine->clear();
        
        return $buffer_content;
    }

	/**
    * Make an anonymous function
	*
	* @param	string	$class_name  	containing a class name
	* @param	string	$object_name  	containing an object name
	* @param	string	$framing  		containing items passed to the class constructor
	* @param	integer	$scope 			representing the scope of a variable
    * @return	string  containing a class instantiation
	*/
    public function ExtJsInstantiateClass($class_name, $object_name, $framing = "", $scope = DEFAULT_ARGUMENT_LOCAL)
    {    
        $prototype = $this->ExtJsObjectPrototype($class_name, $object_name, $scope);

        return $prototype.PUNCTUATION_PARENTHESIS_START.$framing.PUNCTUATION_PARENTHESIS_END.PUNCTUATION_SEMI_COLUMN.SPECIAL_CHARACTER_LF;
    }

    /**
    * Get functions
	*
    * @return	array   containing instantiated functions in the generated javascript source
	*/
    public function getFunctions()
    {
        return $this->_functions;
    }

    /**
    * Get objects
	*
    * @return	array   containing instantiated objects in the generated javascript source
	*/
    public function getObjects()
    {
        return $this->_objects;
    }

	/**
    * Mirror a javascript object instantiation in PHP
	*
	* @param	string	$class_name  	containing a class name
	* @param	string	$object_name  	containing an object name
	* @param	integer	$scope 			representing the scope of a variable
    * @return	string  containing  a instantiation prototype
    */
    public static function mirrorExtJsObject($object_name, $class_name, $scope = SCOPE_LOCAL)
    {
        $object = new stdClass();
        $object->_instanceof = $class_name;
        $object->_members = array();
        $object->_instances = array();

        $object->_instances[] = $object_name;

        if ($scope)
            $object->_scope = SCOPE_LOCAL;
        else
            $object->_scope = SCOPE_GLOBAL;
        
        return $object;
    }    
}
?>