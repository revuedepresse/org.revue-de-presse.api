<?php

/**
	PHP Fat-Free Framework - Less Hype, More Meat.

	Fat-Free is a modular and lightweight PHP 5.3+ Web development framework
	designed to help build dynamic Web sites. This Core Pack provides the
	basic framework functionality, which includes a fast template engine,
	HTML forms processor, and a powerful cache. In one tiny package!

	The latest version of the software can be downloaded at:-

	http://sourceforge.net/projects/fatfree

	See the accompanying README.TXT file for detailed instructions on the
	use of the framework and HISTORY.TXT for information on the changes in
	this release.

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	If you feel that this software is one great weapon to have in your
	programming arsenal and it saves you a lot of time and money, please
	consider making a donation to the project.

	Copyright (c) 2009-2010 Fat-Free Factory
	Bong Cosca <bong.cosca@yahoo.com>

		@package Core
		@version 1.2.8
**/

//! Core Pack
final class F3 {

	//! Framework version
	const TEXT_Version='PHP Fat-Free Framework 1.2.8';

	//@{
	//! Locale-specific error/exception messages
	const TEXT_NotFound='The requested URL {@CONTEXT} was not found';
	const TEXT_Route='The route {@CONTEXT} cannot be resolved';
	const TEXT_Handler='The route handler {@CONTEXT} is invalid';
	const TEXT_NoHandler='No route handler for {@CONTEXT}';
	const TEXT_Template='The template {@CONTEXT} was not found';
	const TEXT_Form='The form field hander {@CONTEXT} is invalid';
	const TEXT_Object='The framework cannot be used in object context';
	const TEXT_Instance='The framework cannot be started more than once';
	const TEXT_HTTP='HTTP status code {@CONTEXT} is invalid';
	const TEXT_Class='Undefined class {@CONTEXT}';
	const TEXT_Method='Undefined method {@CONTEXT}';
	const TEXT_Variable='Framework variable must be specified';
	const TEXT_Attrib='Attribute {@CONTEXT} cannot be resolved';
	const TEXT_Trace='Stack trace';
	const TEXT_Unsupported='PHP version {@CONTEXT} is not supported';
	const TEXT_PCRELimit='PCRE backtrack/recurson limits set too low';
	//@}

	//@{
	//! HTTP/1.1 status (RFC 2616)
	const HTTP_100='Continue';
	const HTTP_101='Switching Protocols';
	const HTTP_200='OK';
	const HTTP_201='Created';
	const HTTP_202='Accepted';
	const HTTP_203='Non-Authorative Information';
	const HTTP_204='No Content';
	const HTTP_205='Reset Content';
	const HTTP_206='Partial Content';
	const HTTP_300='Multiple Choices';
	const HTTP_301='Moved Permanently';
	const HTTP_302='Found';
	const HTTP_303='See Other';
	const HTTP_304='Not Modified';
	const HTTP_305='Use Proxy';
	const HTTP_306='Temporary Redirect';
	const HTTP_400='Bad Request';
	const HTTP_401='Unauthorized';
	const HTTP_402='Payment Required';
	const HTTP_403='Forbidden';
	const HTTP_404='Not Found';
	const HTTP_405='Method Not Allowed';
	const HTTP_406='Not Acceptable';
	const HTTP_407='Proxy Authentication Required';
	const HTTP_408='Request Timeout';
	const HTTP_409='Conflict';
	const HTTP_410='Gone';
	const HTTP_411='Length Required';
	const HTTP_412='Precondition Failed';
	const HTTP_413='Request Entity Too Large';
	const HTTP_414='Request-URI Too Long';
	const HTTP_415='Unsupported Media Type';
	const HTTP_416='Requested Range Not Satisfiable';
	const HTTP_417='Expectation Failed';
	const HTTP_500='Internal Server Error';
	const HTTP_501='Not Implemented';
	const HTTP_502='Bad Gateway';
	const HTTP_503='Service Unavailable';
	const HTTP_504='Gateway Timeout';
	const HTTP_505='HTTP Version Not Supported';
	//@}

	//@{
	//! HTTP headers
	const HTTP_Host='Host';
	const HTTP_Agent='User-Agent';
	const HTTP_Content='Content-Type';
	const HTTP_Length='Content-Length';
	const HTTP_Disposition='Content-Disposition';
	const HTTP_Transfer='Content-Transfer-Encoding';
	const HTTP_Expires='Expires';
	const HTTP_Pragma='Pragma';
	const HTTP_Cache='Cache-Control';
	const HTTP_LastMod='Last-Modified';
	const HTTP_IfMod='If-Modified-Since';
	const HTTP_Powered='X-Powered-By';
	const HTTP_AcceptEnc='Accept-Encoding';
	const HTTP_Encoding='Content-Encoding';
	const HTTP_Connect='Connection';
	const HTTP_Location='Location';
	const HTTP_WebAuth='WWW-Authenticate';
	//@}

	//! DNS blacklist(s)
	const DNS_Blacklist='dnsbl.sorbs.net';

	//! Framework-mapped PHP globals
	const PHP_Globals='GET|POST|REQUEST|SESSION|COOKIE|FILES';

	//! HTTP methods for RESTful interface
	const HTTP_Methods='GET|HEAD|POST|PUT|DELETE';

	//! GZip compression level; Any higher just hogs CPU
	const GZIP_Compress=2;

	//! Container for Fat-Free global variables
	public static $global=array();

	//! Code profile table
	public static $profile=array();

	//! Framework symbol table
	private static $symtab=array();

	/**
		Send HTTP status header; Return text equivalent of status code
			@return mixed
			@param $_code integer
			@public
			@static
	**/
	public static function httpStatus($_code) {
		$_global=&self::$global;
		if (!defined('self::HTTP_'.$_code)) {
			// Invalid status code
			$_global['CONTEXT']=$_code;
			trigger_error(self::resolve(self::TEXT_HTTP));
			return FALSE;
		}
		// Get description
		$_response=constant('self::HTTP_'.$_code);
		// Send raw HTTP header
		if (!$_global['QUIET'] &&
			!preg_match('/cli/',$_global['MODE']) && !headers_sent())
				header('HTTP/1.1 '.$_code.' '.$_response);
		return $_response;
	}

	/**
		Trigger an HTTP 404 error
			@public
			@static
	**/
	public static function http404() {
		self::$global['CONTEXT']=$_SERVER['REQUEST_URI'];
		self::error(
			self::resolve(self::TEXT_NotFound),404,debug_backtrace(FALSE)
		);
		exit(0);
	}

	/**
		Send HTTP header with expiration date (seconds from current time)
			@param $_secs integer
			@public
			@static
	**/
	public static function httpCache($_secs=0) {
		if (headers_sent())
			return;
		$_global=&self::$global;
		if (!$_global['QUIET'] &&
			!preg_match('/cli/',$_global['MODE']) && !headers_sent()) {
			if ($_secs) {
				header_remove(self::HTTP_Pragma);
				header(self::HTTP_Cache.': max-age='.$_secs);
				header(self::HTTP_Expires.': '.
					date('r',$_global['TIME']+$_secs));
			}
			else {
				header(self::HTTP_Pragma.': no-cache');
				header(self::HTTP_Cache.': no-cache, must-revalidate');
				header(self::HTTP_Expires.': '.date('r',$_global['TIME']));
			}
			header(self::HTTP_Powered.': '.self::TEXT_Version);
		}
	}

	/**
		Flatten array values and return as a comma-separated string
			@return string
			@param $_args array
			@private
			@static
	**/
	public static function listArgs($_args) {
		if (!is_array($_args))
			$_args=array($_args);
		$_str='';
		foreach ($_args as $_key=>$_val)
			if (!preg_match('/^GLOBALS\b/',$_key))
				$_str.=($_str?',':'').
					(is_string($_val)?
						('"'.self::convSlashes(self::convQuotes($_val)).'"'):
					(is_array($_val)?
						('array('.self::listArgs($_val).')'):
						(is_object($_val)?(get_class($_val).'()'):
							var_export($_val,TRUE))));
		return $_str;
	}

	/**
		Convert Windows double-backslashes to slashes
			@return string
			@param $_str string
			@public
			@static
	**/
	public static function convSlashes($_str) {
		return $_str?str_replace('\\','/',$_str):$_str;
	}

	/**
		Convert double quotes to equivalent XML entities (&#34;)
			@return string
			@param $_val string
			@public
			@static
	**/
	public static function convQuotes($_val) {
		if (is_array($_val))
			foreach ($_val as &$_item)
				$_item=self::convQuotes($_item);
		return is_string($_val)?
			str_replace('"','&#34;',$_val):$_val;
	}

	/**
		Display default error page; Use custom page if found
			@param $_str string
			@param $_code integer
			@param $_stack array
			@public
			@static
	**/
	public static function error($_str,$_code,array $_stack) {
		$_global=&self::$global;
		foreach ($_stack as $_level=>$_nexus)
			// Remove framework methods and extraneous data from stack trace
			if (!$_global['DEBUG'] && (!$_nexus['file'] ||
				strpos($_nexus['file'],'F3')!==FALSE) ||
				preg_match('/^(\{closure\}|call_user_func|'.
					'trigger_error|[ex]handler)/i',
						$_nexus['function']) || !isset($_nexus['file']))
				unset($_stack[$_level]);
		// Renumber stack trace
		$_stack=array_merge($_stack,array());
		krsort($_stack);
		// Generate internal server error if code is zero
		if (!$_code)
			$_code=500;
		// Save error details
		$_error=&$_global['ERROR'];
		$_error['code']=$_code;
		$_error['title']=self::httpStatus($_code);
		$_error['text']=$_str;
		// Stack trace
		$_trace='';
		foreach ($_stack as $_level=>$_nexus)
			$_trace.='['.$_level.'] '.
				self::convSlashes($_nexus['file']).':'.
				$_nexus['line'].' '.$_nexus['class'].$_nexus['type'].
				$_nexus['function'].
				'('.self::listArgs($_nexus['args']).')<br/>';
		$_error['trace']='';
		if (!$_global['RELEASE'] && trim($_trace))
			$_error['trace']=self::TEXT_Trace.':<br/>'.$_trace;
		if (!preg_match('/cli/',$_global['MODE'])) {
			// Write to server's error log (with complete stack trace)
			error_log($_error['text']);
			foreach (explode('<br/>',$_trace) as $_str)
				if ($_str)
					error_log($_str);
		}
		if ($_global['QUIET'])
			return;
		// Find template referenced by the global variable E<code>
		$_file=self::convSlashes($_global['E'.$_error['code']]);
		echo (!is_null($_file) && file_exists($_global['GUI'].$_file))?
			// Render custom template stored in E<code>
			self::serve($_file):
			// Use default HTML response page
			self::resolve(
				'<html>'.
					'<head>'.
						'<title>{@ERROR.code} {@ERROR.title}</title>'.
					'</head>'.
					'<body>'.
						'<h1>{@ERROR.title}</h1>'.
						'<p>{@ERROR.text}</p>'.
						'<p>{@ERROR.trace}</p>'.
					'</body>'.
				'</html>'
			);
	}

	/**
		Normalize array subscripts
			@return string
			@param $_str string
			@param $_f3var boolean
			@private
			@static
	**/
	private static function remix($_str,$_f3var=TRUE) {
		preg_match_all(
			'/[\'"]*(.+?)[\'"]*(?:[\.\[\]]|$)/',$_str,$_remix,PREG_SET_ORDER
		);
		$_out='';
		foreach ($_remix as $_fix) {
			$_item=$_fix[strlen($_fix[1])?1:2];
			if ($_f3var || $_out)
				$_item='['.$_item.']';
			$_out.=$_item;
		}
		return $_out;
	}

	/**
		Generate Base36/CRC32 hash code
			@return string
			@param $_str string
			@public
			@static
	**/
	public static function hashCode($_str) {
		return str_pad(
			base_convert(sprintf('%u',crc32($_str)),10,36),7,'0',STR_PAD_LEFT
		);
	}

	/**
		Store/Retrieve value of framework variable from symbol table
			@return mixed
			@param $_name string
			@param $_val mixed
			@private
			@static
	**/
	private static function stash($_name,$_val) {
		// Compute MD5 hash key
		$_key=self::hashCode($_name);
		// Check symbol table if variable already exists
		if (!array_key_exists($_key,self::$symtab) ||
			self::$symtab[$_key]!=$_val)
				self::$symtab[$_key]=$_val;
		return self::$symtab[$_key];
	}

	/**
		Remove HTML tags (except those enumerated) to protect against
		XSS/code injection attacks
			@return mixed
			@param $_var string
			@param $_tags string
			@public
			@static
	**/
	public static function scrub($_var,$_tags=NULL) {
		if (is_array($_var))
			foreach ($_var as $_key=>$_val)
				$_var[$_key]=self::scrub($_val,$_tags);
		if ($_tags)
			$_tags='<'.implode('><',explode('|',$_tags)).'>';
		return is_string($_var)?strip_tags($_var,$_tags):$_var;
	}

	/**
		Parse framework variable table and return value
			@return array
			@param $_name string
			@param $_true mixed
			@param $_false mixed
			@public
			@static
	**/
	private static function lookup($_name) {
		if (!$_name) {
			trigger_error(self::TEXT_Variable);
			return array(FALSE,NULL);
		}
		$_var=self::remix(self::resolve($_name));
		preg_match_all('/\[([^\]]*)\]/',$_var,$_matches,PREG_SET_ORDER);
		$_val=self::$global;
		foreach ($_matches as $_match) {
			if (!is_array($_val) || !array_key_exists($_match[1],$_val))
				return array(FALSE,NULL);
			$_val=$_val[$_match[1]];
		}
		return array(TRUE,$_val);
	}

	/**
		Return TRUE if framework variable has been assigned a value
			@return boolean
			@param $_name string
			@public
			@static
	**/
	public static function exists($_name) {
		$_var=self::lookup($_name);
		return $_var[0];
	}

	/**
		Return value of framework variable
			@return mixed
			@param $_name string
			@public
			@static
	**/
	public static function get($_name) {
		$_var=self::lookup($_name);
		return self::stash($_name,$_var[1]);
	}

	/**
		Bind value to framework variable
			@param $_name string
			@param $_val mixed
			@public
			@static
	**/
	public static function set($_name,$_val) {
		if (is_string($_val))
			// Evaluate as a template expression; Save in symbol table
			$_hashed=var_export(
				self::resolve(self::convQuotes($_val)),TRUE
			);
		else {
			if (is_array($_val))
				// Check each element if it's a string
				foreach ($_val as $_key=>$_element)
					if (is_string($_element))
						// Evaluate as a template expression
						$_val[$_key]=self::resolve(
							self::convQuotes($_element)
						);
			// Save in symbol table
			$_hashed=var_export(self::stash($_name,$_val),TRUE);
		}
		preg_match('/\[([^\]]*)\]/',self::remix($_name),$_match);
		if (preg_match('/^('.self::PHP_Globals.')\b/',$_match[1]))
			// Use eval; PHP doesn't allow global variable variables
			eval('$_'.self::remix($_name,FALSE).'='.$_hashed.';');
		eval('self::$global'.self::remix(self::resolve($_name)).'='.
			$_hashed.';');
	}

	/**
		Clear global variable and remove from symbol table
			@param $_name string
			@public
			@static
	**/
	public static function clear($_name) {
		eval('unset(self::$global'.self::remix(self::resolve($_name)).');');
		unset(self::$symtab[self::hashCode($_name)]);
	}

	/**
		Reroute to specified URI
			@param $_uri string
			@public
			@static
	**/
	public static function reroute($_uri) {
		session_commit();
		$_global=&self::$global;
		if (!$_global['QUIET'] &&
			!preg_match('/cli/',$_global['MODE']) && !headers_sent()) {
			self::httpStatus($_SERVER['REQUEST_METHOD']!='GET'?303:301);
			header(self::HTTP_Location.': '.self::resolve($_uri));
			exit(0);
		}
		else {
			$_SERVER['REQUEST_METHOD']='GET';
			$_SERVER['REQUEST_URI']=self::resolve($_uri);
			self::run();
		}
	}

	/**
		Validate route pattern and break it down into an array consisting
		of the request method and request URI
			@return mixed
			@param $_regex string
			@public
			@static
	**/
	public static function checkRoute($_regex) {
		if (!preg_match(
			'/('.self::HTTP_Methods.')\s+(.*)/i',$_regex,$_route)) {
			// Invalid route
			self::$global['CONTEXT']=$_regex;
			trigger_error(self::resolve(self::TEXT_Route));
			return FALSE;
		}
		return array_slice($_route,1);
	}

	/**
		Assign handler to route pattern
			@param $_pattern string
			@param $_funcs mixed
			@param $_ttl integer
			@public
			@static
	**/
	public static function route($_pattern,$_funcs,$_ttl=0) {
		$_global=&self::$global;
		// Check if valid route pattern
		$_route=self::checkRoute($_pattern);
		// Valid URI pattern
		if (is_string($_funcs)) {
			// String passed
			foreach (explode('|',$_funcs) as $_func)
				if (!is_callable($_func)) {
					// Not a lambda function
					if (!preg_match('/\:(.+)/i',$_func,$_match)) {
						// Invalid route handler
						$_global['CONTEXT']=$_func;
						trigger_error(self::resolve(self::TEXT_Handler));
						return;
					}
					// PHP include file specified
					$_include=self::convSlashes($_match[1]).'.php';
					if (!file_exists($_global['IMPORTS'].$_include)) {
						// Invalid route handler
						$_global['CONTEXT']=$_include;
						trigger_error(self::resolve(self::TEXT_Handler));
						return;
					}
				}
		}
		elseif (!is_callable($_funcs)) {
			// Invalid route handler
			$_global['CONTEXT']=$_funcs;
			trigger_error(self::resolve(self::TEXT_Handler));
			return;
		}
		// Use pattern and HTTP method as array indices
		$_global['ROUTES']
			['/^'.
				// Assign name to URI variable
				preg_replace(
					'/\{*@(\w+)\b\}*/i','(?P<$1>\w+)\b',
					// Wildcard character in URI
					str_replace('\*','(.*)',preg_quote($_route[1],'/'))
				).
			'(.*)$/i']
			// Save handlers and cache timeout
			[$_route[0]]=array($_funcs,$_ttl);
	}

	/**
		Provide REST interface by mapping URL to object/PHP class
			@param $_url string
			@param $_obj mixed
			@public
			@static
	**/
	public static function map($_url,$_obj) {
		foreach (explode('|',self::HTTP_Methods) as $_method)
			if (method_exists($_obj,$_method))
				self::route(
					$_method.' '.$_url,array($_obj,strtolower($_method))
				);
	}

	/**
		Retrieve from cache; or save all output generated by route
		if not previously rendered
			@return string
			@param $_proc array
			@private
			@static
	**/
	private static function urlCache(array $_proc) {
		$_global=&self::$global;
		// Get hash code for this Web page
		if (!file_exists($_global['CACHE']))
			// Create the framework's cache folder
			mkdir($_global['CACHE']);
		$_hash='url-'.self::hashCode(
			$_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI']
		);
		$_file=$_global['CACHE'].$_hash;
		// Regex pattern for Content-Type
		$_regex='/'.self::HTTP_Content.'.+/';
		// Get HTTP request headers
		$_request=array();
		if (!$_global['QUIET'] && !preg_match('/cli/',$_global['MODE']))
			if (function_exists('getallheaders'))
				// Apache server
				$_request=getallheaders();
			else
				// Workaround
				foreach ($_SERVER as $_key=>$_val)
					if (is_string(strstr($_key,'HTTP_')))
						$_request[str_replace(
							' ','-',
							ucwords(
								strtolower(
									str_replace('_',' ',substr($_key,5))
								)
							)
						)]=$_val;
		// Cached file header and content divider
		$_div=chr(0);
		// Reset PHP's stat cache
		clearstatcache();
		if (file_exists($_file) &&
			($_global['TIME']-filemtime($_file))<$_proc[1]) {
			// Gather cached file info for profiler
			self::$profile['FILES']['cache'][$_hash]=filesize($_file);
			// Activate cache timer
			self::httpCache(
				filemtime($_file)+$_proc[1]-$_global['TIME']
			);
			if (!$_request[self::HTTP_IfMod] ||
				filemtime($_file)>strtotime($_request[self::HTTP_IfMod])) {
				// Retrieve file from cache and decompress
				$_cached=gzinflate(file_get_contents($_file));
				$_type=strstr($_cached,$_div,TRUE);
				if (preg_match($_regex,$_type,$_match) &&
					!$_global['QUIET'] && 
					!preg_match('/cli/',$_global['MODE']) && !headers_sent())
						header($_match[0]);
				// Save response
				$_global['RESPONSE']=substr(strstr($_cached,$_div),1);
			}
			else
				// No need to serve page; client-side cache is fresh
				self::httpStatus(304);
		}
		else {
			// Cache this page
			ob_start();
			self::call($_proc[0]);
			$_cached=ob_get_contents();
			ob_end_clean();
			if (!$_global['ERROR'] && $_cached) {
				$_type='';
				foreach (headers_list() as $_hdr)
					if (preg_match($_regex,$_hdr,$_match))
						// Include Content-Type header in cached file
						$_type=$_match[0];
				// Compress and save to cache
				file_put_contents(
					$_file,gzdeflate($_type.$_div.$_cached),LOCK_EX
				);
				// Activate cache timer
				self::httpCache($_proc[1]);
				if (!$_global['QUIET'] &&
					!preg_match('/cli/',$_global['MODE']) && !headers_sent())
						header(self::HTTP_LastMod.': '.
							date('r',$_global['TIME']));
			}
			// Save response
			$_global['RESPONSE']=$_cached;
		}
	}

	/**
		Return TRUE if remote address is listed in spam database
			@return boolean
			@param $_addr string
			@public
			@static
	**/
	public static function spam($_addr) {
		// Convert to reverse IP dotted quad
		$_addr=implode('.',array_reverse(explode('.',$_addr)));
		foreach (explode('|',self::$global['DNSBL']) as $_list) {
			// Check against DNS blacklist
			if (gethostbyname($_addr.'.'.$_list)!=$_addr.'.'.$_list)
				return TRUE;
		}
		return FALSE;
	}

	/**
		Process routes based on incoming URI
			@public
			@static
	**/
	public static function run() {
		$_global=&self::$global;
		if (self::spam($_SERVER['REMOTE_ADDR']))
			if ($_global['SPAM'])
				// Spammer detected; Send to blackhole
				self::reroute($_global['SPAM']);
			else
				// HTTP 404 message
				self::http404();
		// Process routes
		krsort($_global['ROUTES']);
		foreach ($_global['ROUTES'] as $_regex=>$_route) {
			if (!preg_match($_regex,$_SERVER['REQUEST_URI'],$_args))
				continue;
			// Save named regex captures
			$_global['PARAMS']=$_args;
			// Inspect each defined route
			foreach ($_route as $_method=>$_proc) {
				if (!preg_match(
					'/'.$_method.'/i',$_SERVER['REQUEST_METHOD']))
						continue;
				// Default: Do not cache
				self::httpCache(0);
				unset($_global['TTL']);
				if (preg_match('/GET/i',$_method) && $_proc[1]) {
					$_global['TTL']=$_proc[1];
					// Save to/retrieve from cache
					self::urlCache($_proc);
				}
				else {
					// Capture output
					ob_start();
					self::call($_proc[0]);
					$_global['RESPONSE']=ob_get_contents();
					ob_end_clean();
				}
				if (F3::$global['THROTTLE']) {
					$_elapsed=microtime(TRUE)-F3::$global['TIME'];
					if ((F3::$global['THROTTLE']/1e3)>$_elapsed)
						usleep(1e6*(F3::$global['THROTTLE']/1e3-$_elapsed));
				}
				if ($_global['RESPONSE'] && !$_global['QUIET'])
					// Display response
					echo $_global['RESPONSE'];
				// Hail the conquering hero
				return;
			}
			// No route handler for this HTTP request
			$_global['CONTEXT']=$_SERVER['REQUEST_METHOD'].' '.
				$_SERVER['REQUEST_URI'];
			trigger_error(self::resolve(self::TEXT_NoHandler));
			return;
		}
		// No such Web page
		self::http404();
	}

	/**
		Return XML translation table
			@return array
			@public
			@static
	**/
	public static function xmlTable() {
		$_xml=array();
		$_xl8=get_html_translation_table(HTML_ENTITIES,ENT_COMPAT);
		while (list($_key,)=each($_xl8))
			$_xml['&#'.ord($_key).';']=$_key;
		return $_xml;
	}

	/**
		Convert plain text to XML entities
			@return string
			@param $_str string
			@public
			@static
	**/
	public static function xmlEncode($_str) {
		return strtr($_str,array_flip(self::xmlTable()));
	}

	/**
		Convert XML entities to plain text
			@return string
			@param $_str string
			@public
			@static
	**/
	public static function xmlDecode($_str) {
		return strtr($_str,self::xmlTable());
	}

	/**
		Check for syntax error in eval'd code
			@return boolean
			@param $_expr string
			@private
			@static
	**/
	public static function checkSyntax($_expr) {
		return eval('return TRUE; '.$_expr.';');
	}

	/**
		Evaluate template expressions in string
			@return string
			@param $_str string
			@param $_ishtml boolean
			@public
			@static
	**/
	public static function resolve($_str,$_ishtml=TRUE) {
		$_global=self::$global;
		// Analyze string for correct framework expression syntax
		$_str=preg_replace_callback(
			// Expression
			'/\{('.
				// Capture group
				'(?:'.
					// Variable token
					'@\w+\b(?:\[[^\]]+\]|\.\w+\b)*|'.
					// Function/Parenthesized expression
					'\w*\h*[\(\,\)]|'.
					// Whitespace and operators
					'[\h\+\-\*\/\%\.\=\!\<\>\&\|\?\:]|'.
					// String
					'\'[^\']*\'|\"[^"]*\"|'.
					// Number
					'\d*\.*\d*(?:e\d+)*|'.
					// Boolean and null constant
					'TRUE|FALSE|NULL|'.
					// Data type converters
					'\(\h*(?:'.
						'int(?:eger)*|bool(?:ean)*|string|real|'.
						'float|double|binary|array|object|unset'.
					')\h*\)'.
				// End of captured string
				')+'.
			// End of expression
			')\}/i',
			// Evaluate expression; This will cause a syntax error
			// if framework is running in an old version of PHP!
			function($_expr) use($_global) {
				// Find and replace variables
				$_eval=preg_replace_callback(
					// Framework variable pattern
					'/@(\w+\b(?:\[[^\]]+\]|\.\w+\b)*)/',
					function($_var) {
						$_val=F3::get($_var[1]);
						if (is_array($_val) && !count($_val))
							$_val=NULL;
						// Retrieve variable contents
						return '('.var_export($_val,TRUE).')';
					},
					// Find and replace functions
					preg_replace_callback(
						'/(\w+)\h*\([^\)]*\)/',
						function($_func) use($_global) {
							// Check if function is allowed in template
							return preg_match('/array\(\h*\)/',$_func[0])?
								'\'\'':
								(in_array($_func[1],$_global['FUNCS'])?
									$_func[0]:('\''.$_func[0].'\''));
						},
						$_expr[1]
					)
				);
				// Evaluate expression
				return F3::checkSyntax($_eval)?
					eval('return '.$_eval.';'):$_eval;
			},
			rawurldecode($_str)
		);
		if (preg_last_error()!=PREG_NO_ERROR)
			trigger_error(self::TEXT_PCRELimit);
		return $_str;
	}

	/**
		Process <F3:include> directives
			@return string
			@param $_str string
			@param $_path string
			@private
			@static
	**/
	private static function link($_str,$_path) {
		// Retrieve variables
		$_global=self::$global;
		$_regex='/<(?:F3\:)*include\s*href\s*=\s*"([^"]+)"\s*\/>/';
		// Search/replace <F3:include> regex pattern
		return preg_match($_regex,$_str)?
			// Call recursively if included file also has <F3:include>
			self::link(
				preg_replace_callback(
					$_regex,
					function($_attrib) use($_global,$_path) {
						// Check href attribute
						$_file=F3::convSlashes(F3::resolve($_attrib[1]));
						// Load file if found; Error otherwise
						if (!file_exists($_path.$_file)) {
							// File not found
							$_global['CONTEXT']=$_file;
							trigger_error(F3::resolve(F3::TEXT_Template));
							return FALSE;
						}
						// Load file
						return file_get_contents($_path.$_file);
					},
					$_str
				),
				$_path
			):
			$_str;
	}

	/**
		Parse all directives and render HTML/XML template
			@return mixed
			@param $_file string
			@param $_ishtml boolean
			@param $_path string
			@public
			@static
	**/
	public static function serve($_file,$_ishtml=TRUE,$_path=NULL) {
		$_global=&self::$global;
		if (is_null($_path))
			$_path=$_global['GUI'];
		$_file=self::convSlashes($_file);
		if (!file_exists($_path.$_file)) {
			// File not found
			$_global['CONTEXT']=$_file;
			trigger_error(self::resolve(self::TEXT_Template));
			return FALSE;
		}
		// Gather template file info for profiler
		self::$profile['FILES']['templates']
			[self::convSlashes($_file)]=filesize($_path.$_file);
		// Initialize XML tree
		$_tree=new XMLTree;
		$_tree->preserveWhiteSpace=FALSE;
		$_tree->formatOutput=FALSE;
		$_tree->strictErrorChecking=FALSE;
		$_tree->encoding=$_global['ENCODING'];
		// Link included files
		$_contents=self::link(file_get_contents($_path.$_file),$_path);
		// Populate XML tree
		if ($_ishtml)
			@$_tree->loadHTML($_contents);
		else
			@$_tree->loadXML($_contents);
		// Prepare for XML tree traversal
		$_frag=$_tree->createDocumentFragment();
		// First pass
		$_tree->traverse(
			// Start at document root
			$_tree->documentElement,
			function(&$_node) use($_global,$_tree,$_frag) {
				$_token='/\{*@(\w+\b)\}*/';
				// Ignore text nodes
				if ($_node->nodeType!=XML_ELEMENT_NODE)
					return;
				// Suppress warning if text node
				$_tag=$_node->tagName;
				// Save relative nodes for later use
				$_parent=$_node->parentNode;
				$_next=NULL;
				// Process <F3:repeat> directive
				if ($_tag=='repeat') {
					// Get inner HTML contents of node
					$_inner=$_tree->innerHTML($_node);
					// Retrieve key token
					$_kvar=$_node->getAttribute('key');
					if ($_kvar) {
						preg_match($_token,$_kvar,$_kcap);
						if (!$_kcap[1]) {
							F3::$global['CONTEXT']=$_kvar;
							trigger_error(F3::resolve(F3::TEXT_Attrib));
							return;
						}
						$_kreg='/(\{.*?)@'.$_kcap[1].'(\b[^\}]*\})/';
					}
					// Retrieve index token
					$_ivar=$_node->getAttribute('index');
					preg_match($_token,$_ivar,$_icap);
					if (!$_icap[1]) {
						F3::$global['CONTEXT']=$_ivar;
						trigger_error(F3::resolve(F3::TEXT_Attrib));
						return;
					}
					$_ireg='/(\{.*?)@'.$_icap[1].'(\b[^\}]*\})/';
					// Retrieve group token
					$_gvar=$_node->getAttribute('group');
					preg_match(
						'/\{*@(\w+\b(?:\[[^\]]+\]|\.\w+\b)*)\}*/',
						$_gvar,$_gcap
					);
					if (!$_gcap[1]) {
						F3::$global['CONTEXT']=$_gvar;
						trigger_error(F3::resolve(F3::TEXT_Attrib));
						return;
					}
					$_group=F3::get($_gcap[1]);
					if ($_inner && is_array($_group) && count($_group)) {
						$_block='';
						// Iterate thru group elements
						while (list($_key,)=each($_group)) {
							$_block.=preg_replace(
								$_ireg,
								// Replace index token
								'$1@'.$_gcap[1].(strlen($_key)?
									// Use regular notation for empty keys
									('.'.$_key):('[\''.$_key.'\']')).'$2',
								$_kreg?
									// Replace key token
									preg_replace(
										$_kreg,'$1\''.$_key.'\'$2',$_inner
									):$_inner
							);
						}
						if (strlen($_block)) {
							$_frag->appendXML($_block);
							// Insert fragment before current node
							$_next=$_parent->insertBefore($_frag,$_node);
						}
					}
				}
				// Code common to both <F3:repeat> and <F3:exclude>
				if (preg_match('/repeat|exclude/',$_tag)) {
					// Find next node
					if (!$_next)
						$_next=$_node->nextSibling?
							$_node->nextSibling:$_parent;
					// Remove current node
					$_parent->removeChild($_node);
					// Replace with next node
					$_node=$_next;
				}
			}
		);
		unset($_frag);
		if ($_ishtml) {
			// HTML template
			$_contents=self::resolve(html_entity_decode($_tree->saveHTML()));
			$_tree->loadHTML($_contents);
		}
		else {
			// XML template
			$_contents=self::resolve(self::xmlDecode($_tree->saveXML()));
			$_tree->loadXML($_contents);
		}
		if (preg_match('/<(?:F3\:)*check\b.+>/',$_contents)) {
			// Template contains <F3:check> directive
			$_frag=$_tree->createDocumentFragment();
			// Second pass
			$_tree->traverse(
				$_tree->documentElement,
				function(&$_node) use($_global,$_tree,$_frag) {
					// Ignore text nodes
					if ($_node->nodeType!=XML_ELEMENT_NODE)
						return;
					$_tag=$_node->tagName;
					// Process <F3:check> directive
					if ($_tag=='check') {
						// Save parent node
						$_parent=$_node->parentNode;
						$_cond=var_export(
							(boolean)$_node->getAttribute('if'),TRUE
						);
						$_block='';
						foreach ($_node->childNodes as $_child)
							if ($_child->nodeType!=XML_TEXT_NODE &&
								$_child->tagName==$_cond) {
								$_inner=$_tree->innerHTML($_child);
								if ($_inner)
									// Replacement
									$_block.=$_inner;
							}
						if (strlen($_block)) {
							$_frag->appendXML($_block);
							$_parent->insertBefore($_frag,$_node);
						}
						// Remove current node
						$_parent->removeChild($_node);
						// Re-process parent node
						$_node=$_parent;
					}
				}
			);
			unset($_frag);
		}
		return preg_replace('/<((?:area|base|br|col|frame|hr|img|'.
			'input|isindex|link|meta|param).*?)(?!\/)>/','<$1/>',
			$_ishtml?$_tree->saveHTML():$_tree->saveXML());
	}

	/**
		Call form field handler
			@param $_field string
			@param $_funcs mixed
			@param $_tags string
			@public
			@static
	**/
	public static function input($_field,$_funcs,$_tags=NULL) {
		$_global=&self::$global;
		if (array_key_exists($_field,$_global['FILES']))
			$_input=&$_global['FILES'][$_field];
		else
			$_input=&$_global['REQUEST'][$_field];
		$_input=self::scrub($_input,$_tags);
		if (is_string($_funcs)) {
			// String passed
			foreach (explode('|',$_funcs) as $_func) {
				if (!is_callable($_func)) {
					// Invalid handler
					$_global['CONTEXT']=$_include;
					trigger_error(self::resolve(self::TEXT_Form));
				}
				else
					// Call lambda function
					call_user_func($_func,$_input);
			}
		}
		else {
			// Closure
			if (!is_callable($_funcs)) {
				// Invalid handler
				$_global['CONTEXT']=$_funcs;
				trigger_error(self::resolve(self::TEXT_Form));
			}
			else
				// Call lambda function
				call_user_func($_funcs,$_input);
		}
	}

	/**
		Transmit a file for downloading by HTTP client;
		Return TRUE if successful, FALSE otherwise
			@param $_file string
			@public
			@static
	**/
	public static function send($_file) {
		$_file=self::resolve($_file);
		if (!file_exists($_file))
			return FALSE;
		$_global=&self::$global;
		if (!$_global['QUIET'] &&
			!preg_match('/cli/',$_global['MODE']) && !headers_sent()) {
			header(self::HTTP_Content.': application/octet-stream');
			header(self::HTTP_Disposition.': '.
				'attachment; filename='.basename($_file));
			header(self::HTTP_Length.': '.filesize($_file));
			self::httpCache(0);
		}
		readfile($_file);
		return TRUE;
	}

	/**
		Convenience method for executing scoped function/script within
		its own ecosystem
			@param $_funcs mixed
			@public
			@static
	**/
	public static function call($_funcs) {
		Runtime::call($_funcs);
	}

	/**
		Return array of runtime performance analysis data
			@return array
			@public
			@static
	**/
	public static function profile() {
		$_global=&self::$global;
		$_profile=&self::$profile;
		// Compute elapsed time
		$_profile['TIME']['elapsed']=microtime(TRUE)-$_global['TIME'];
		// Reset PHP's stat cache
		clearstatcache();
		foreach (get_included_files() as $_file)
			// Gather includes
			$_profile['FILES']['includes']
				[basename($_file)]=filesize($_file);
		// Compute memory consumption
		$_profile['MEMORY']['current']=memory_get_usage();
		$_profile['MEMORY']['peak']=memory_get_peak_usage();
		return $_profile;
	}

	/**
		Allow functions defined in PHP and methods in user-defined
		objects to be used in templates
			@param $_str string
			@public
			@static
	**/
	public static function allow($_str='') {
		$_global=&self::$global;
		// Create lookup table of functions allowed in templates
		$_global['FUNCS']=array();
		// Get list of all defined functions
		$_def=get_defined_functions();
		foreach (explode('|',$_str) as $_ext) {
			if ($_ext=='user')
				$_load=$_def['user'];
			elseif (in_array($_ext,get_loaded_extensions()))
				$_load=get_extension_funcs($_ext);
			elseif (class_exists($_ext))
				$_load=get_class_methods($_ext);
			$_global['FUNCS']=array_merge($_global['FUNCS'],$_load);
		}
		// Functions that should NEVER be used in template expressions
		$_illegal='/^('.
			'apache_|call|chdir|env|escape|exec|extract|fclose|fflush|'.
			'fget|file_put|flock|fopen|fprint|fput|fread|fseek|fscanf|'.
			'fseek|fsockopen|fstat|ftell|ftp_|ftrunc|get|header|http_|'.
			'import|ini_|ldap_|link|log_|magic|mail|mcrypt_|mkdir|ob_|'.
			'php|popen|posix_|proc|rename|rmdir|rpc|set_|sleep|stream|'.
			'sys|thru|unreg'.
		')/';
		// Iterate thru function list
		foreach ($_global['FUNCS'] as $_index=>$_func)
			if (preg_match($_illegal,$_func))
				// Remove function from list
				unset($_global['FUNCS'][$_index]);
		// PHP language constructs that may be used in expressions
		$_global['FUNCS']=array_merge(
			$_global['FUNCS'],explode('|','array|isset')
		);
		// Sort the list
		sort($_global['FUNCS']);
	}

	/**
		Convert engineering-notated string to bytes
			@return integer
			@param $_str string
			@public
			@static
	**/
	public static function bytes($_str) {
		$_tags='KMG';
		$_count=strlen($_tags);
		for ($_i=0;$_i<$_count;$_i++)
			$_suffix[$_tags[$_i]]=pow(1024,$_i+1);
		preg_match('/(\d+)(['.$_tags.'])/i',trim($_str),$_matches);
		return $_matches[1]*$_suffix[$_matches[2]];
	}

	/**
		Framework error handler
			@param $_errno integer
			@param $_errstr string
			@public
			@static
	**/
	public static function ehandler($_errno,$_errstr) {
		// Check if error suppression operator is set
		if (error_reporting())
			F3::error($_errstr,500,debug_backtrace(FALSE));
	}

	/**
		Framework exception handler
			@param $_xcept object
			@public
			@static
	**/
	public static function xhandler($_xcept) {
		F3::error(
			$_xcept->getMessage(),$_xcept->getCode(),
				count($_xcept->getTrace())?
					$_xcept->getTrace():debug_backtrace(FALSE)
		);
		// PHP aborts at this point
	}

	/**
		Kickstart the framework
			@public
			@static
	**/
	public static function start() {
		// Disable error display
		ini_set('display_errors','Off');
		// Enable logging
		ini_set('log_errors','On');
		// Report all errors except notices
		ini_set('error_reporting',E_ALL^E_NOTICE);
		// Intercept errors and send output to browser
		set_error_handler(array('F3','ehandler'),error_reporting());
		// Send output to browser when exception occurs
		set_exception_handler(array('F3','xhandler'));
		$_global=&self::$global;
		// Check the version first
		if ((float)
			implode('.',array_slice(explode('.',PHP_VERSION),0,2))<5.3) {
			// Framework doesn't run on old PHP versions
			$_global['CONTEXT']=PHP_VERSION;
			trigger_error(self::resolve(self::TEXT_Unsupported));
			return;
		}
		if (isset($_global['GUID'])) {
			// Multiple framework instances not allowed
			trigger_error(self::TEXT_Instance);
			return;
		}
		if (preg_match('/cli/',php_sapi_name())) {
			// Command-line mode; Hydrate PHP globals
			$_SERVER['DOCUMENT_ROOT']='.';
			// Parse GET variables in URL
			preg_match_all(
				'/[\?&]([^=]+)=([^&$]*)/',$_SERVER['REQUEST_URI'],
				$_matches,PREG_SET_ORDER
			);
			foreach ($_matches as $_match) {
				$_REQUEST[$_match[1]]=$_match[2];
				$_GET[$_match[1]]=$_match[2];
			}
		}
		// Convert incoming URI to human-readable string
		$_SERVER['REQUEST_URI']=rawurldecode($_SERVER['REQUEST_URI']);
		// Hydrate framework variables
		$_base=$_SERVER['DOCUMENT_ROOT'].'/';
		$_global['AUTOLOAD']=$_base.'autoload/';
		$_global['CACHE']=$_base.'cache/';
		$_global['DNSBL']=self::DNS_Blacklist;
		$_global['ENCODING']='UTF-8';
		$_global['ERROR']=NULL;
		$_global['FONTS']=$_base;
		$_global['GUI']=$_base;
		$_global['GUID']='9f8e597d-fce6-404d-906c-1f719fe05fc9';
		$_global['IMPORTS']=$_base;
		$_global['MAXSIZE']=self::bytes(ini_get('post_max_size'));
		$_global['MODE']=php_sapi_name();
		$_global['QUIET']=FALSE;
		$_global['RELEASE']=FALSE;
		$_global['SPAM']=NULL;
		$_global['TIME']=microtime(TRUE);
		$_global['VERSION']=self::TEXT_Version;
		// Initialize profiler
		self::$profile['TIME']['start']=$_global['TIME'];
		self::$profile['MEMORY']['start']=memory_get_usage();
		// Allow functions defined in PHP's standard, date/time and
		// PCRE extensions to be used in templates, except functions
		// known to be dangerous
		self::allow('standard|date|pcre');
		// Activate GZip compression if (1) ZLib extension is available,
		// (2) output compression is not set in PHP.INI, (3) browser
		// supports GZip-encoded data, and (4) if using Apache, mod_deflate
		// is not active
		if (extension_loaded('zlib') && 
			!ini_get('zlib.output_compression') &&
			preg_match('/gzip/',$_SERVER['HTTP_ACCEPT_ENCODING']) &&
			function_exists('apache_get_modules') &&
			!in_array('mod_deflate',apache_get_modules())) {
			ini_set('zlib.output_compression_level',self::GZIP_Compress);
			ob_start('ob_gzhandler');
		}
		else
			ob_start();
		// Start session
		session_start();
		// Create convenience containers for PHP globals
		foreach (explode('|',self::PHP_Globals) as $_var)
			eval('$_global[\''.$_var.'\']=$_'.$_var.';');
		// Suppress errors caused by invalid HTML/XML structures
		libxml_use_internal_errors(TRUE);
		// Prepare autoload stack
		spl_autoload_register('F3::__autoload');
		$_path=self::convSlashes(__DIR__).'/';
		// Check file prefix
		$_files=array_diff(
			glob($_path.'F3*.php'),array(self::convSlashes(__FILE__))
		);
		foreach ($_files as $_file) {
			// Load expansion pack
			include_once $_file;
			// Extract class name
			preg_match('/.+(?=\.php)/',basename($_file),$_ext);
			$_global['PACKS'][]=$_ext[0];
			if (method_exists($_ext[0],'onLoad'))
				call_user_func(array($_ext[0],'onLoad'));
		}
		// Sniff headers for real IP address
		if ($_SERVER['HTTP_CLIENT_IP'])
			$_SERVER['REMOTE_ADDR']=$_SERVER['HTTP_CLIENT_IP'];
		elseif (preg_match(
			'/^(\d+\.){3}\d+/',$_SERVER['HTTP_X_FORWARDED_FOR'],$_match))
			$_SERVER['REMOTE_ADDR']=$_match[0];
		if (preg_match('/cli/',$_global['MODE']))
			self::mock('GET '.$_SERVER['argv'][1]);
	}

	/**
		Intercept instantiation of objects in undefined classes
			@param $_class string
			@private
			@static
	**/
	private static function __autoload($_class) {
		$_file=self::$global['AUTOLOAD'].self::convSlashes($_class).'.php';
		if (file_exists($_file)) {
			require_once $_file;
			if (method_exists($_class,'onLoad'))
				return call_user_func(array($_class,'onLoad'));
		}
		else {
			self::$global['CONTEXT']=$_class;
			trigger_error(self::resolve(self::TEXT_Class));
		}
	}

	/**
		Intercept calls to undefined static methods
			@return mixed
			@param $_func string
			@param $_args array
			@public
			@static
	**/
	public static function __callStatic($_func,array $_args) {
		$_global=&self::$global;
		foreach ($_global['PACKS'] as $_pack)
			if (method_exists($_pack,$_func))
				// Proxy for method in expansion pack
				return call_user_func_array(array($_pack,$_func),$_args);
		$_global['CONTEXT']=$_func;
		trigger_error(self::resolve(self::TEXT_Method));
	}

	/**
		Class constructor
			@public
	**/
	public function __construct() {
		// Prohibit use of framework as an object
		trigger_error(self::TEXT_Object);
	}

}

//! Run-time services
final class Runtime {

	/**
		Provide sandbox for PHP "include" script so it has no direct
		access to framework internals and other scripts
			@param $_funcs mixed
			@public
			@static
	**/
	public static function call($_funcs) {
		if (is_string($_funcs)) {
			// Call each code segment
			foreach (explode('|',$_funcs) as $_func) {
				if (preg_match('/(?<=^\:).+/',$_func,$_inc))
					// Run external PHP script
					include_once F3::get('IMPORTS').$_inc[0].'.php';
				else
					// Call lambda function
					call_user_func($_func);
			}
		}
		else
			// Call lambda function
			call_user_func($_funcs);
	}

}

//! PHP DOMDocument extension
class XMLTree extends DOMDocument {

	/**
		Get inner HTML contents of node
			@return string
			@param $_node DOMElement
			@public
	**/
	public function innerHTML($_node) {
		return preg_replace(
			'/^<(\w+)\b[^>]*>(.*)<\/\1?>/s','$2',
			$_node->ownerDocument->saveXML($_node)
		);
	}

	/**
		General-purpose XML tree traversal
			@param $_root DOMElement
			@param $_pre mixed
			@param $_post mixed
			@public
	**/
	public function traverse($_root,$_pre=NULL,$_post=NULL) {
		// Start at document root
		$_flag=FALSE;
		$_node=$_root;
		while (TRUE) {
			if (!$_flag) {
				// Pre-order sequence
				if ($_pre)
					$_pre($_node);
				if ($_node->firstChild) {
					// Descend to branch
					$_flag=FALSE;
					$_node=$_node->firstChild;
					continue;
				}
			}
			// Post-order sequence
			if ($_post)
				$_post($_node);
			if ($_node->isSameNode($_root))
				// Root node reached; Exit loop
				break;
			// Post-order sequence
			if ($_node->nextSibling) {
				// Stay on same level
				$_flag=FALSE;
				$_node=$_node->nextSibling;
			}
			else {
				// Ascend to parent node
				$_flag=TRUE;
				$_node=$_node->parentNode;
			}
		}
	}

}

/**
	Quietly initialize the framework
	Do NOT use elsewhere!
**/
F3::start();

?>
