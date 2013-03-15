<?php

/**
* Parser class
*
* @package  sefi
*/
class Parser extends File_Manager
{
    private $_contents = null;
    private $_sitemap = array();	

	/**
    * Construct a document type definition
	*
	* @param	string  $url  	containing a URL
    * @return	object  representing a document type definition
	*/
    public function __construct($url)
    {
		$test = preg_match_all('@^(http://)?([^/]+)@', $url, $sub_patterns);

		$this->_sitemap[0]['host'] = $sub_patterns[2][0];
		$this->_sitemap[0]['uri'] = $sub_patterns[0][0];

		$opts = array(
			'http' => array(
				'method'  => 'GET',
				'header' =>
					"User-Agent: Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; fr; rv:1.9.1.5) Gecko/20091102 Firefox/3.5.5 GTB6 FirePHP/0.3".
					"Accept-Language: en-us,en;q=0.8,fr;q=0.5,fr-fr;q=0.3"
			)
		);

		$context = stream_context_create($opts);
		$this->_contents = file_get_contents($url, false, $context);
	}

	/**
    * Get a document type definition
	*
    * @return	string  containing a document type definition
	*/
    public function get_contents()
    {
        return $this->_contents;
    }

	/**
    * Weave a sitemap
    * 
    * @param	integer	$depth	representing the depth of weaving
    * @param	integer	$level	representing a level of weaving
    * @return	nothing
	*/
    public function weave_sitemap($depth = 1, $level = 0)
    {
        $dom = new DOMDocument();

        $config = array(
            'indent' => TRUE,
            'output-xhtml' => TRUE,
            'wrap' => 200
        );

	    $tidy = tidy_parse_string($this->_contents, $config, 'latin1');

        $dom->loadHTML($tidy->value);

		list($tags['a']['nodes'], $tags['a']['length']) = array(
			$dom->getElementsByTagName('a'),
			$dom->getElementsByTagName('a')->length
		);

		$tag_index = 0;
		$tags['a']['elements'] = array();
		$tags['a']['targets'] = array();

		while ($tag_index < $tags['a']['length'])
		{
			$tags['a']['elements'][] = $tags['a']['nodes']->item($tag_index);

			$tag_index++;
		}

		$this->_sitemap[$level]['internal_links'] = array();
		$this->_sitemap[$level]['outgoing_links'] = array();

		$internal_links = &$this->_sitemap[$level]['internal_links'];
		$outgoing_links = &$this->_sitemap[$level]['outgoing_links'];

		while (list($element_index, $element) = each($tags['a']['elements']))
		{
			$current_uri = $element->getAttribute("href");

			if (!preg_match('#^/.*#', $current_uri) && !preg_match('/http:\/\/'.$this->_sitemap[$level]['host'].'/', $current_uri))
				$outgoing_links[] = array('uri' => $current_uri);
			else if (
				preg_match('#^/.*#', $current_uri) ||
				preg_match('/http:\/\/'.$this->_sitemap[$level]['host'].'/', $current_uri)
			)
			{
				$absolute_uri = PROTOCOL_HTTP.$this->_sitemap[$level]['host'].$current_uri;

				if (($absolute_uri) != PROTOCOL_HTTP.$this->_sitemap[$level]['host'].CHARACTER_SLASH)
					$internal_links[] = array('uri' => $absolute_uri);
			}
		}

		// Retrieve contents from root files
		$content_directory = dirname(__FILE__).DIR_PARENT_DIRECTORY.DIR_PARENT_DIRECTORY.DIR_CONTENTS;
		$root_file_name = $content_directory.$level."_0".EXTENSION_HTML;

		if ($level == 0)
			file_put_contents($root_file_name, $this->get_contents());

		if ($depth > 0)
		{
			$depth--;
			$level++;

			if (count($internal_links) > 0)
				while (list($link_index) = each($internal_links))
				{
					$parser = new parser($internal_links[$link_index]['uri']);
					$parser->weave_sitemap($depth, $level);
					file_put_contents($content_directory.$level.'_'.$link_index.EXTENSION_HTML, $parser->get_contents());
	
					$internal_links[$link_index][$level] = $parser->get_sitemap();
				}
			else
				return $this->get_contents(); 			
		}
		else
			return $this->get_sitemap(); 
    }

	/**
    * Get a sitemap
	*
    * @return	object	representing a sitemap
	*/
    public function get_sitemap()
    {
		return $this->_sitemap;
	}

	/**
    * Parse a resource
	*
	* @param	mixed		$resource		resource
	* @param	integer		$parsing_type	parsing type
	* @param	integer		$checksum		checksum
    * @return	mixed
	*/
	public static function prepare_data(
		&$resource,
		$parsing_type = PARSING_TYPE_DOMAIN,
		$checksum = null
	)
	{
		// check the resource is a string
		if (
			is_string($resource) &&
			$parsing_type != PARSING_TYPE_ASSIGNMENT &&
			$parsing_type != PARSING_TYPE_EXISTENCE_EVALUATION &&
			$parsing_type != PARSING_TYPE_FUNCTION_CALL
		)

			// declare an instance of the standard class
			$prepared_data = new stdClass();

		// check if the resource is an object
		else if (is_object($resource) || is_array($resource))
		{
			// check the parsing type
			if (			
				$parsing_type == PARSING_TYPE_FUNCTION_CALL ||
				$parsing_type == PARSING_TYPE_EXISTENCE_EVALUATION ||				
				$parsing_type == PARSING_TYPE_CONNECTOR &&
				$resource[RESOURCE_CONNECTOR] == SYMBOL_EQUAL
			)
				// declare an instance of the standard class
				$prepared_data = new stdClass();

			else 

				// declare an empty array
				$prepared_data = array();
		}

		// switch from a parsing type
		switch ($parsing_type)
		{
			case PARSING_TYPE_ASSIGNMENT:

				// set the assignment pattern
				$assignment_pattern = "/new\s(.*)/";				

				// set a new instance of the standard class as private members
				$_members = new stdClass();

				// look for the assignment pattern
				$assignment = preg_match($assignment_pattern, $resource, $matches);

				// check the assignment matches
				if ($assignment && !empty($matches[1]))

					$_members->{PROPERTY_OPERAND} = $matches[1];

				$prepared_data[SYMBOL_ASSIGNMENT] = $_members;

					break;

			case PARSING_TYPE_CONNECTOR:

				// set the connector
				$connector = $resource[RESOURCE_CONNECTOR];

				// set the expression
				$expression = $resource[RESOURCE_EXPRESSION];

				// set the member
				if (isset($resource[RESOURCE_MEMBER]))

					$member = $resource[RESOURCE_MEMBER];

				// check the connector
				switch ($connector)
				{
					case SYMBOL_IMPLIES:

						// set a connector alias
						$connector = SYMBOL_IMPLIES_ALIAS;

							break;
				}

				// check if the connector is equal or the expression is restricted to a definition domain
				if (
					$connector != SYMBOL_EQUAL ||
					strpos($expression, SYMBOL_DOMAIN) == false
				)
				{
					// set the prepared data
					$prepared_data = explode($connector, $expression);
	
					// loop on prepared data
					while (list($index, $value) = each($prepared_data))
					{
						// check if the current connector is the equal symbol
						if ($connector == SYMBOL_EQUAL)
						{
							// check if an array of private data has been declared
							if (!isset($_prepared_data))

								// set an instance of the standard class as private prepared data
								$_prepared_data = new stdClass();
	
							// set a literal index
							$_index = $index == 0 ? PROPERTY_LEFT_OPERAND : PROPERTY_RIGHT_OPERAND;

							// append the current value to the private data
							$_prepared_data->{$_index} = trim($value);

							// unset the current item of the prepared data
							unset($prepared_data[$index]);

							// check the length of the array of prepared data 
							if (count($prepared_data) == 0)

								// declare a new instance of the standard class
								$prepared_data = new stdClass();
						}
						else

							// trim the current item of the prepared data 
							$prepared_data[$index] = trim($value);
					}

					// check if there are some private array of prepared data
					if (isset($_prepared_data))

						// merge the private prepared data with the prepared data to be returned
						$prepared_data = (object)array_merge((array)$prepared_data, (array)$_prepared_data);
				}
				else
				{
					$pattern_domain =
						REGEXP_OPEN.
								REGEXP_CATCH_START.
									REGEXP_BLANK.REGEXP_FACULTATIVE.
									REGEXP_CATCH_START.
										"V?!?E?!?n?e?w?\s?[^\s^=".REGEXP_ESCAPE.SYMBOL_DOMAIN."]".REGEXP_ANY.
									REGEXP_CATCH_END.REGEXP_FACULTATIVE.
									REGEXP_BLANK.REGEXP_FACULTATIVE.
										SYMBOL_EQUAL.REGEXP_MANDATORY. 					// =!
									REGEXP_BLANK.REGEXP_FACULTATIVE.
									REGEXP_CATCH_START.
										"V?!?E?!?n?e?w?\s?[^\s^=".REGEXP_ESCAPE.SYMBOL_DOMAIN."]".REGEXP_ANY.
									REGEXP_CATCH_END.REGEXP_FACULTATIVE.
								REGEXP_CATCH_END.
								REGEXP_BLANK.REGEXP_FACULTATIVE.						
									REGEXP_ESCAPE.SYMBOL_DOMAIN.REGEXP_MANDATORY.		// D!
								REGEXP_BLANK.REGEXP_FACULTATIVE.
								REGEXP_CATCH_START.
									REGEXP_CATCH_START.
										"V?!?E?!?n?e?w?\s?[^\s^=".REGEXP_ESCAPE.SYMBOL_DOMAIN."]".REGEXP_ANY.
									REGEXP_CATCH_END.REGEXP_FACULTATIVE.
									REGEXP_BLANK.REGEXP_FACULTATIVE.
										SYMBOL_EQUAL.REGEXP_FACULTATIVE.				// =
									REGEXP_BLANK.REGEXP_FACULTATIVE.
									REGEXP_CATCH_START.
										"V?!?E?!?n?e?w?\s?[^\s^=".REGEXP_ESCAPE.SYMBOL_DOMAIN."]".REGEXP_ANY.
									REGEXP_CATCH_END.REGEXP_FACULTATIVE.
								REGEXP_CATCH_END.
								REGEXP_BLANK.REGEXP_FACULTATIVE.
							REGEXP_OR.
								REGEXP_CATCH_START.
									"V?!?E?!?\s?n?e?w?\s?[^\s^=".REGEXP_ESCAPE.SYMBOL_DOMAIN."]".REGEXP_ANY.
								REGEXP_CATCH_END.REGEXP_FACULTATIVE.
								REGEXP_BLANK.REGEXP_FACULTATIVE.
									REGEXP_ESCAPE.SYMBOL_DOMAIN.REGEXP_MANDATORY.		// D!
								REGEXP_BLANK.REGEXP_FACULTATIVE.
								REGEXP_CATCH_START.
									REGEXP_CATCH_START.
										"V?!?E?!?n?e?w?\s?[^\s^=".REGEXP_ESCAPE.SYMBOL_DOMAIN."]".REGEXP_ANY.
									REGEXP_CATCH_END.REGEXP_FACULTATIVE.
									REGEXP_BLANK.REGEXP_FACULTATIVE.
										SYMBOL_EQUAL.REGEXP_MANDATORY. 					// =!
									REGEXP_BLANK.REGEXP_FACULTATIVE.
									REGEXP_CATCH_START.
										"V?!?E?!?n?e?w?\s?[^\s^=".REGEXP_ESCAPE.SYMBOL_DOMAIN."]".REGEXP_ANY.
									REGEXP_CATCH_END.REGEXP_FACULTATIVE.
								REGEXP_CATCH_END.								
							REGEXP_OR.
								REGEXP_CATCH_START.
									REGEXP_WILDCARD.REGEXP_ANY.
								REGEXP_CATCH_END.
						REGEXP_CLOSE
					;

					// test the expression for matches with the domain symbol
					$domain_match = preg_match($pattern_domain, $expression, $matches);

					// display the matches
					dumper::log(
						__METHOD__,
						array($matches),
						DEBUGGING_DISPLAY_EXPRESSION_MATCHES
					);

					if (
						$domain_match &&
						!empty($matches[2]) &&
						!empty($matches[3]) &&
						!empty($matches[5]) &&
						!empty($matches[6])
					)
					{
						// construct a new instance of the standard class
						$_members = new stdClass;

						// declare an empty array
						$domain = array();

						// declare a new instance of the standard class
						$domain_pair = new stdClass();

						// set the current top level operator
						$operator = SYMBOL_EQUAL;

						// check if a match contains a member operator
						if (self::prepare_data($matches[5], PARSING_TYPE_MEMBER))
						{
							// loop for member operator
							$_prepared_members = self::prepare_data($matches[5], PARSING_TYPE_MEMBER);

							// set the left member property
							$_members->{PROPERTY_LEFT_OPERAND} = $_prepared_members;
						}
						else

							// set the left member property
							$_members->{PROPERTY_LEFT_OPERAND} = $matches[5];
						

						// check if a match contains a member operator
						if (self::prepare_data($matches[6], PARSING_TYPE_MEMBER))
						{
							// loop for member operator
							$_prepared_members = self::prepare_data($matches[6], PARSING_TYPE_MEMBER);

							// set the right member property
							$_members->{PROPERTY_RIGHT_OPERAND} = $_prepared_members;
						}
						else

							// set the right member property
							$_members->{PROPERTY_RIGHT_OPERAND} = $matches[6];

						// set the connector property
						$domain = array($connector => $_members);

						// check if a match contains a member operator
						if (self::prepare_data($matches[3], PARSING_TYPE_MEMBER))
						{
							// loop for member operator
							$_prepared_members = self::prepare_data($matches[3], PARSING_TYPE_MEMBER);

							// set the left member of the domain pair
							$domain_pair->{PROPERTY_LEFT_OPERAND} = $_prepared_members;
						}
						else

							// set the left member of the domain pair
							$domain_pair->{PROPERTY_LEFT_OPERAND} = $matches[3];

						// set the right member of the domain pair
						$domain_pair->{PROPERTY_RIGHT_OPERAND} = $domain;

						// check if a match contains a member operator
						if (self::prepare_data($matches[2], PARSING_TYPE_MEMBER))
						{
							// loop for member operator
							$_prepared_members = self::prepare_data($matches[2], PARSING_TYPE_MEMBER);

							// set the left member
							$prepared_data->{PROPERTY_LEFT_OPERAND} = $_prepared_members;
						}
						else

							// set the left member
							$prepared_data->{PROPERTY_LEFT_OPERAND} = $matches[2];

 						// set the right member
						$prepared_data->{PROPERTY_RIGHT_OPERAND} = array(
							SYMBOL_DOMAIN => $domain_pair
						);
					}
					else if (
						$domain_match &&
						!empty($matches[7]) &&
						!empty($matches[9]) &&
						!empty($matches[10])
					)
					{
						// construct a new instance of the standard class
						$_members = new stdClass;

						// construct a new instance of the standard class
						$domain = array();

						// set the current top level operator
						$operator = SYMBOL_DOMAIN;

						// check if a match contains a member operator
						if (self::prepare_data($matches[9], PARSING_TYPE_MEMBER))
						{
							// loop for member operator
							$_prepared_members = self::prepare_data($matches[9], PARSING_TYPE_MEMBER);

							// set the left member property
							$_members->{PROPERTY_LEFT_OPERAND} = $_prepared_members;
						}
						else 

							// set the left member property
							$_members->{PROPERTY_LEFT_OPERAND} = $matches[9];

						// check if a match contains a member operator
						if (self::prepare_data($matches[10], PARSING_TYPE_MEMBER))
						{
							// loop for member operator
							$_prepared_members = self::prepare_data($matches[10], PARSING_TYPE_MEMBER, 1024);

							// set the right member property
							$_members->{PROPERTY_RIGHT_OPERAND} = $_prepared_members;
						}
						else

							// set the right member property
							$_members->{PROPERTY_RIGHT_OPERAND} = $matches[10];

						// set the connector property
						$domain = array($connector => $_members);

						// check if a match contains a member operator
						if (self::prepare_data($matches[7], PARSING_TYPE_MEMBER))
						{
							// loop for member operator
							$_prepared_members = self::prepare_data($matches[7], PARSING_TYPE_MEMBER);

							// set the left member
							$prepared_data->{PROPERTY_LEFT_OPERAND} = $_prepared_members;
						}
						else

							// set the left member
							$prepared_data->{PROPERTY_LEFT_OPERAND} = $matches[7];

						// set the domain
						$prepared_data->{PROPERTY_RIGHT_OPERAND} = $domain;
					}

					// return the prepared data
					return array($operator => $prepared_data);
				}

					break;

			case PARSING_TYPE_DOMAIN:

				// set the domain pattern
				$domain_pattern =
					REGEXP_OPEN.
						REGEXP_CATCH_START.
							REGEXP_WILDCARD.REGEXP_ANY.
						REGEXP_CATCH_END.
						REGEXP_ESCAPE.SYMBOL_DOMAIN_START.
							REGEXP_CATCH_START.
								REGEXP_WILDCARD.REGEXP_ANY.
							REGEXP_CATCH_END.
						REGEXP_ESCAPE.SYMBOL_DOMAIN_END.
							REGEXP_OR.
						REGEXP_CATCH_START.
							REGEXP_WILDCARD.REGEXP_ANY.
						REGEXP_CATCH_END.
					REGEXP_CLOSE
				;

				// try to match the domain in the resource
				preg_match(
					$domain_pattern,
					$resource,
					$matches
				);

				// check the matches
				if (!empty($matches[2]) || !empty($matches[3]))
				{
					// set the domain property
					$prepared_data->{PROPERTY_DOMAIN} = !empty($matches[1]) ? $matches[1] : SYMBOL_DOMAIN_OMEGA;

					// check the domain property
					if (!empty($matches[1]) && !empty($matches[2]))

						// set the image property
						$prepared_data->{PROPERTY_IMAGE} = $matches[2];
				}

					break;

			case PARSING_TYPE_EXISTENCE_EVALUATION:

				// set the existence pattern
				$existence_pattern = "/(!?)E(!?)\s(.+)/";

				// set a new instance of the standard class as private members
				$_members = new stdClass();

				// look for the assignment pattern
				$existence = preg_match($existence_pattern, $resource, $matches);

				// check the assignment matches
				if ($existence && !empty($matches[3]))

					$_members->{PROPERTY_OPERAND} = self::prepare_data($matches[3], PARSING_TYPE_MEMBER);

				// check the matches
				if (!empty($matches[1]) || !empty($matches[2]))

					$prepared_data[SYMBOL_EXISTS_ONE_ONLY] = $_members;

				else 

					$prepared_data[SYMBOL_EXISTS] = $_members;

					break;
	
			case PARSING_TYPE_EXPRESSION:

				// check the private members
				if (!isset($_members))

					// construct an instance of the standard class
					$_members = new stdClass();

				// loop expression members
				while (list($member_position, $member) = each($resource))
				{
					if ($member_position != PROPERTY_CONNECTOR)
					{
						// set the private resource
						$_resource = array(
							RESOURCE_CONNECTOR => SYMBOL_EQUAL,
							RESOURCE_EXPRESSION => $member,
							RESOURCE_MEMBER => $member_position
						);

						// set the private prepared data					
						$_prepared_data = self::prepare_data($_resource, PARSING_TYPE_CONNECTOR);

						// set the default connector and expression
						$connector =
						$expression = null;

						// unset the private resource
						unset($_resource);

						// check the private prepared data
						if (
							is_array($_prepared_data) &&
							count($_prepared_data) == 1
						)

							// expression
							list($connector, $expression) = each($_prepared_data);

						// check the length private prepared data
						if (
							isset($expression) &&
							is_object($expression) &&
							count(get_object_vars($expression)) != 1
						)

							// set prepared data
							$_members->{$member_position} = $_prepared_data;

						else if (
							is_object($_prepared_data) &&
							count(get_object_vars($_prepared_data)) == 1
						)
						{							
							// get the member attributes
							$member_attributes = get_object_vars($_prepared_data);

							// get a member attribute
							$member_attribute = array_pop($member_attributes);

							// parse some prepared data for member access operator
							$prepared_members = self::prepare_data($member_attribute, PARSING_TYPE_MEMBER);

							// set the current resource member
							$_members->{$member_position} = $prepared_members;
						}
					}
				}

				$resource = $_members;

					break;

			case PARSING_TYPE_FUNCTION_CALL:

				// set the function call pattern
				$function_call_pattern = "/(.+)\((.+)\)/";

				// set a new instance of the standard class as private members
				$_members = new stdClass();

				// look for the assignment pattern
				$function_call = preg_match($function_call_pattern, $resource, $matches);

				// check the assignment matches
				if ($function_call && !empty($matches[1]) && !empty($matches[2]))
				{
					// set the arguments property
					$_members->{PROPERTY_ARGUMENTS} = self::prepare_data($matches[2], PARSING_TYPE_MEMBER);

					// set the callee property
					$_members->{PROPERTY_CALLEE} = $matches[1];
				}

				// set the left right arrow from bar members
				$prepared_data[SYMBOL_LEFT_RIGHT_ARROW_FROM_BAR] = $_members;

					break;

			case PARSING_TYPE_MEMBER:

				// set the member pattern
				$member_pattern = "/([^\.^|]*)\.([^\.^|]*)|(.*)/";

				// check if the resource argument contains a disjunction symbol
				if (
					is_string($resource) &&
					strpos($resource, SYMBOL_DISJUNCTION)
				)
				{
					// get the members
					$members = explode(SYMBOL_DISJUNCTION, $resource);

					// loop on members
					while (list($member_index, $member) = each($members))

						// set the members
						$members[$member_index] = self::prepare_data($member, PARSING_TYPE_MEMBER);

					// declare a new instance of standard class
					$_expression = new stdClass();

					// set the resource operands
					$_expression->{PROPERTY_OPERANDS} = $members;

					// set the prepared data to be returned
					return array(SYMBOL_DISJUNCTION => $_expression);
				}

				if (is_object($resource) || is_array($resource))

					foreach ($resource as $property => $value)
					{
						// check if the value doesn't contain a disjunction symbol
						if (strpos($value, SYMBOL_DISJUNCTION))
						{
							// set members
							$members = explode(SYMBOL_DISJUNCTION, $value);
	
							// loop on members
							while (list($member_id, $member) = each($members))
							{
								// construct an instance of the standard class
								$_prepared_data = new stdClass();
	
								// try to match the domain in the resource
								preg_match(
									$member_pattern,
									$member,
									$matches
								);
		
								// check the first possible matches
								if (!empty($matches[1]) && !empty($matches[2]))
								{
									// set the object property
									$_prepared_data->{PROPERTY_OBJECT} = $matches[1];
									
									// set the member property
									$_prepared_data->{PROPERTY_MEMBER} = $matches[2];
								}
				
								// check the last possible match
								else if (!empty($matches[3]))
				
									// set the member property
									$_prepared_data->{PROPERTY_OBJECT} = $matches[3];
			
								// append an element to the prepared data store
								$prepared_data[$property][$member_id] = $_prepared_data;
							}
						}	
						else
						{
							// construct an instance of the standard class
							$_prepared_data = new stdClass();						
	
							// try to match the domain in the resource
							preg_match(
								$member_pattern,
								$value,
								$matches
							);
	
							// check the first possible matches
							if (!empty($matches[1]) && !empty($matches[2]))
							{
								// set the object property
								$_prepared_data->{PROPERTY_OBJECT} = $matches[1];
								
								// set the member property
								$_prepared_data->{PROPERTY_MEMBER} = $matches[2];
							}
			
							// check the last possible match
							else if (!empty($matches[3]))
			
								// set the member property
								$_prepared_data->{PROPERTY_OBJECT} = $matches[3];
		
							// append an element to the prepared data store
							$prepared_data[$property] = $_prepared_data;
						}
					}

				else if (is_string($resource))
				{
					// set the assignment pattern
					$assignment_pattern = "/new\s/";

					// set the existence pattern
					$existence_pattern = "/!?E!?\s/";					

					// set the function pattern
					$function_pattern = "/\(.+\)/";

					// set the default parsing code
					$parsing_code = 0;

					// look for assignment matches
					$assignment = preg_match($assignment_pattern, $resource);

					// look for existence evaluation matches
					$existence_evaluation = preg_match($existence_pattern, $resource);					

					// look for function call matches
					$function_call = preg_match($function_pattern, $resource);

					// check the assignment matches
					if ($assignment)
						$parsing_code += 1;

					// check the existence evaluation matches
					if ($existence_evaluation)
						$parsing_code += 2;

					// check the function call matches
					if ($function_call)
						$parsing_code += 4;

					// switch from the parsing code
					switch ($parsing_code)
					{
						case 1:

							return self::prepare_data($resource, PARSING_TYPE_ASSIGNMENT);

								break;

						case 2:

							return self::prepare_data($resource, PARSING_TYPE_EXISTENCE_EVALUATION);

								break;

						case 4:

							return self::prepare_data($resource, PARSING_TYPE_FUNCTION_CALL);

								break;
					}

					// check if the resource contains a member attribute symbol
					if (
						false !== strpos($resource, SYMBOL_MEMBER_ATTRIBUTE) &&
						!$parsing_code
					)
					{
						// try to match the domain in the resource
						preg_match(
							$member_pattern,
							$resource,
							$matches
						);

						if (!empty($matches[1]) && !empty($matches[2]))
						{
							// declar private members as an instance of the standard class
							$_members = new stdClass();
	
							// set the left member property						
							$_members->{PROPERTY_LEFT_OPERAND} = $matches[1];

							// set the right member property
							$_members->{PROPERTY_RIGHT_OPERAND} = $matches[2];

							// set the connector property
							$prepared_data = array(
								SYMBOL_MEMBER_ATTRIBUTE => $_members
							);						
						}
					}
					else
						return false;
				}

					break;				
		}

		// return the prepared data
		return $prepared_data;
	}
}
?>