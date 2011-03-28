<?php
/**
*************
* Changes log
*
*************
* 2011 03 26
*************
*
* Implement methods to display wall of tweets
* from twitter timelines and favorites
*
* methods affected ::
*
* FEED_READER :: displayTwitterTimeline
* FEED_READER :: displayTwitterWall
* FEED_READER :: getTwitterTimeline
* 
* (branch 0.1 :: revision 630)
* (trunk :: revision :: 195)
*
*/

/**
* Feed reader class
*
* Class for opening feeds
* @package  sefi
*/
class Feed_Reader extends File_Manager
{    
    private $_contents;

    /**
    * Construct a feed reader
    *
    * @param    string    	$url  			url
    * @param	boolean		$extract_dom	DOM extraction flag
    * @param	boolean		$curl			cURL flag
    * @param	html		$html			HTML flag
    * @return   string  contents
    */
    private function __construct(
		$url = NULL,
		$extract_dom = TRUE,
		$curl = FALSE,
		$html = FALSE
	)
    {
        $_dom = &$this->getDOM();
        $_raw_contents = &$this->getRawContents();

		if (!$curl)
		{
			// set stream context options
			$opts = array(
				'http' => array(
					'method'  => 'GET',
					'header' =>
						"User-Agent: Mozilla/5.0 (Macintosh; U; ".
						"Intel Mac OS X 10.6; fr; rv:1.9.1.5) ".
						"Gecko/20091102 Firefox/3.5.5 GTB6 FirePHP/0.3".
						"Accept-Language: en-us,en;q=0.8,fr;q=0.5,fr-fr;q=0.3"
				)
			);

			$opts['http']['timeout'] = 3600;
			$opts['http']['ignore_errors'] = true;			

			// set the stream context
			$context = stream_context_create($opts);

			// check the URL argument
			if (empty($url) && isset($_SERVER['HTTP_HOST']))
			{
	
				// set the default URL
				$url = PROTOCOL_HTTP.$_SERVER['HTTP_HOST'] .
					PREFIX_USER_INTERFACE.ENTITY_FACTORY.
						EXTENSION_PHP
				;
		
				// set the contents
				$_raw_contents = file_get_contents($url, FILE_BINARY, $context);
			}
			else 

				// set the contents
				$_raw_contents = file_get_contents($url, FILE_BINARY, $context);
		}
		else
		{
			$resource = curl_init();

			curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);

			curl_setopt(
				$resource,
				CURLOPT_HTTPHEADER,
				array(
					"Host: www.## FILL BASE URL ##",
					"User-Agent: Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; fr; rv:1.9.2) Gecko/20100115 Firefox/3.6 FirePHP/0.4",
					"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
					"Accept-Language: en-us,en;q=0.8,fr;q=0.5,fr-fr;q=0.3",
					"Accept-Encoding: gzip,deflate",
					"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
					"Keep-Alive: 115",
					"Connection: keep-alive",
					"Referer: http://www.## FILL BASE URL ##/?signout=t",
					"Cookie: ".
					"s_cc=true; ".
					"ev1=%3Fsignout%3Dt; ".
					"__utma=199429675.1263086964.1266189015.1266189015.1266189015.1; ".
					"__utmb=199429675; ".
					"__utmc=199429675; ".
					"__utmz=199429675.1266189015.1.1.utmccn=(direct)|utmcsr=(direct)|utmcmd=(none); rdvm=detail"
				)
			);

			curl_setopt($resource, CURLOPT_URL, "https://www.## FILL BASE URL ##/login?signout=t");

			$post =
				"username=## FILL ME ##&".
				"password=## FILL ME ##'&".
				"_location=http%3A%2F%2Fwww.## FILL BASE URL ##%2F&_c=&".
				"_a=&_p=&_form_key=-3dlof3"
			;

			curl_setopt($resource, CURLOPT_POST, true);

			curl_setopt(
				$resource,
				CURLOPT_POSTFIELDS,
				$post
			);

			$authentication = curl_exec($resource);

			curl_setopt($resource, CURLOPT_URL, $url);

			$match = preg_match('/http:\/\/www.## FILL BASE URL ##\/(.*)/', $url, $matches);

			$header = array(
				"Host: www.## FILL BASE URL ##",
				"User-Agent: Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; fr; rv:1.9.2) Gecko/20100115 Firefox/3.6 FirePHP/0.4",
				"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
				"Accept-Language: en-us,en;q=0.8,fr;q=0.5,fr-fr;q=0.3",
				"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
				"Keep-Alive: 115",
				"Connection: keep-alive",
				"Cookie: ".
				"never-forget-where-youre-from=reader|index; ".
				"s_cc=true; ".
				"ev1=%3Fsignout%3Dt; ".
				"__utma=199429675.1263086964.1266189015.1266189015.1266189015.1; ".
				"__utmb=199429675; ".
				"__utmc=199429675; ".
				"__utmz=199429675.1266189015.1.1.utmccn=(direct)|utmcsr=(direct)|utmcmd=(none); ".
				"rdvm=detail; ".
				"i-think-i-am=a238m2rjhggv5k1f23c8yd6dhngnj|126cedff0c8|5a6a45f39d480b09cc0333b177e34ebcd97741b6"
			);

			curl_setopt(
				$resource,
				CURLOPT_HTTPHEADER,
				$header
			);

			dumper::log(
				__METHOD__,
				array($header),
				false
			);

			curl_setopt($resource, CURLOPT_HEADER, false);

			$_raw_contents = curl_exec($resource);

			dumper::log(
				__METHOD__,
				array(
					'contents:',
					$_raw_contents
				),
				false
			);

			curl_close($resource);
		}

		if ($extract_dom)
		{
			if (!$html)
				$_dom->loadXML($_raw_contents, LIBXML_DTDVALID);
			else
				$_dom->loadHTML($_raw_contents);
		}
    }

    /**
    * Get contents
    *
    * @return   object  contents
    */    
    public function &getContents()
    {
        // check the private member attribute contents
        if (
            !isset($this->_contents) ||
            !is_object($this->_contents)
        )

            // declare a new instance of the standard class
            $this->_contents = new stdClass();
        
        // return the contents
        return $this->_contents;
    }

    /**
    * Get an instance of the DOMDocument object
    *
    * @return   object  instance of the DOMDocument object
    */    
    public function &getDOM()
    {
        // check the private member attribute contents
        $_contents = &$this->getContents();

        // check the private member dom
        if (
            !isset($_contents->{ENTITY_DOM}) ||
            !is_object($_contents->{ENTITY_DOM})
        )

            $_contents->{ENTITY_DOM} = new DOMDocument();

        return $_contents->{ENTITY_DOM};
    }

    /**
    * Get raw contents
    *
    * @return   string  raw contents
    */    
    public function &getRawContents()
    {
        // check the private member attribute contents
        $_contents = &$this->getContents();

        // check the private member dom
        if (
            !isset($_contents->{ENTITY_RAW_CONTENTS}) ||
            !is_string($_contents->{ENTITY_RAW_CONTENTS})
        )

            $_contents->{ENTITY_RAW_CONTENTS} = '';
        
        return $_contents->{ENTITY_RAW_CONTENTS};
    }

    /**
    * Display a reading list
    *
    * @return   nothing
    */
	public static function displayReadingListFeed()
	{
		$file_names = array();
		
		$directory_feeds = dirname(__FILE__).'/../../'.DIR_FEEDS.'/'.DIR_READITLATER; 
		
		if ($handle = opendir($directory_feeds)) {
		
			while ( FALSE !== ( $file_name = readdir($handle) ) )
		
				$file_names[] = $file_name;
		
			closedir($handle);
		}
		
		if (count($file_names) != 2)
		{
			$latest_file = $file_names[count($file_names) - 1];
		
			$file_name = explode('.', $latest_file);
			
			$time = explode('_', $file_name[0]);
		
			$timestamp = mktime(
				substr($time[1], 0, 2),
				substr($time[1], 2, 2),
				0,
				substr($time[0], 4, 2),
				substr($time[0], 6, 2),
				substr($time[0], 0, 4)
			);
		
			$elapsed_time = (time() - $timestamp) / (3600 * 24);
		
			// the latest reading list were fetched more than 24 hours ago
			if ($elapsed_time > 1)
			{
				$username = READITLATER_USER_NAME;
				$password = READITLATER_PASSWORD;
				
				$api_key = READITLATER_API_KEY;
				
				$reading_list_endpoint = 'https://readitlaterlist.com/v2/get?'.
					'username='.$username.'&'.
					'password='.$password.'&'.
					'apikey='.$api_key.
					'&since=0'
				;
				
				$feed_reader = self::parse($reading_list_endpoint, FALSE);
				
				$raw_contents = $feed_reader->getRawContents();
		
				file_put_contents($directory_feeds.'/'.date('Ymd_Hi').EXTENSION_JSON, $raw_contents);
			}
			else
			{
				// send headers
				header(
					'Content-Type: '.MIME_TYPE_APPLICATION_JSON.'; charset='.I18N_CHARSET_UTF8,
					TRUE,
					HTML_RESPONSE_CODE_200
				);

				// It will be called downloaded.pdf
				header('Content-Disposition: attachment; filename="'.$latest_file.'"');

				echo file_get_contents($directory_feeds.'/'.$latest_file);
			}
		}
	}

	/**
	* Display the favorite statuses of a twitter user
	*
	* @param	string	$user_name	user name
	* @param	mixed	$options 	options 		
	* @return 	nothing
	*/
	public static function displayTwitterFavorite(
		$user_name,
		$options = NULL
	)
	{
		$results = self::getTwitterFavorite( $user_name, $options );

		echo '<pre>', print_r( $results, TRUE ), '</pre>';
	}

	/**
	* Display a kind of timeline 
	*
	* @param	string	$kind		kind of timeline
	* @param	mixed	$options 	options 		
	* @return 	nothing
	*/
	public static function displayTwitterTimeline(
		$kind,
		$options = NULL
	)
	{
		$results = self::getTwitterTimeline( $kind, $options );

		echo '<pre>', print_r( $results, TRUE ), '</pre>';
	}

	/**
	* Display a wall from tweets
	*
	* @param	string	$definition	tweets definition
	* @param	string	$resource	resource 
	* @param	boolean	$sorted		sorting flag
	* @return 	nothing
	*/
	public static function displayTwitterWall(
		$definition,
		$resource = NULL,
		$sorted = FALSE
	)
	{
		global $class_application, $verbose_mode;

		$class_api = $class_application::getApiClass();

		$class_dumper = $class_application::getDumperClass();

		$class_layout_manager = $class_application::getLayoutManagerClass();

		$class_user_handler = $class_application::getUserHandlerClass();

		$class_view_builder = $class_application::getViewBuilderClass();

		$_favorites = array();

		$context = new stdClass();

		$dumped_store = serialize( $_favorites );

		$error_404 = FALSE;

		$max_columns = 2;

		$page_index = 1;

		$options = array( PROPERTY_PAGE => $page_index );

		$regression = FALSE;

        $store = &self::initializeStore();

		if ( is_null( $resource ) )
		
			$resource = ENTITY_FAVORITE;

		// Set resource type from HTTP GET parameter
		if (
			isset( $_GET[GET_API_TWITTER_RESOURCE] ) &&
			is_string( $_GET[GET_API_TWITTER_RESOURCE] )
		)
		{
			if (
				in_array(
					$_GET[GET_API_TWITTER_RESOURCE],
					array(
						ENTITY_FAVORITE,
						ENTITY_TIMELINE
					)
				)
			)

				$resource = $_GET[GET_API_TWITTER_RESOURCE];
			else
			
				throw new Exception( EXCEPTION_INVALID_ARGUMENT );
		}

		$directory =
			dirname( __FILE__ ) . '/../../' .
			DIR_API. '/' .
			DIR_TWITTER . '/' .
			$resource . '/'
		;

		$method = 'getTwitter' . ucfirst( $resource );
	
		switch ( $resource )
		{
			case ENTITY_TIMELINE:

				// verify credentials validity
				$credentials = $class_api::verifyCredentials();

				$options[PROPERTY_COUNT] = 200;
				
				if (
					//! isset( $_GET[GET_USERNAME_TWITTER] ) &&
					isset( $credentials->{PROPERTY_SCREEN_NAME} )
				)

					$definition = $credentials->{PROPERTY_SCREEN_NAME} . '_';

				// Set user name from HTTP GET parameter
				else if (
					isset( $_GET[GET_USERNAME_TWITTER] ) &&
					is_string( $_GET[GET_USERNAME_TWITTER] )
				)
				{
					$options[PROPERTY_SCREEN_NAME] = $_GET[GET_USERNAME_TWITTER];

					$user_name = $_GET[GET_USERNAME_TWITTER] . '_';
				}

					break;

			case ENTITY_FAVORITE:
			default:

				$class_api::unserializeAccessTokens();

				// Set user name from HTTP GET parameter
				if (
					isset( $_GET[GET_USERNAME_TWITTER] ) &&
					is_string( $_GET[GET_USERNAME_TWITTER] )
				)

					$definition = $_GET[GET_USERNAME_TWITTER];

				// sanitize user names
				$definition = preg_replace(
					'#[^-_a-zA-Z0-9]#',
					'',
					$definition
				);
		}

		// Get data previously fetched and stored locally
		$file_prefix =
			$definition . '_' .
			$resource . '_' .
			(  isset( $user_name ) ? $user_name : '' )
		; 

        if ( is_null( $definition ) || ! strlen( $definition ) )

            $class_application::jumpTo( PREFIX_ROOT );

        $file_name =
            $directory .
            $file_prefix . 
            date( 'Y-m-d_H' )
        ;

        $file_matching = FALSE;

        if ( file_exists( $file_name ) )
        {
            $dumped_store = file_get_contents( $file_name );

            $file_matching = TRUE;
        }
        //else
        //{
        //    $pattern_file =
        //        $directory.
        //        $file_prefix .
        //        '*'
        //    ;
        //
        //    $matching_files = glob( $pattern_file );
        //
        //    if (
        //        is_array( $matching_files ) &&
        //        count( $matching_files )
        //    )
        //    {
        //        $_file_name = array_pop( $matching_files );
        //
        //        $dumped_store = file_get_contents( $_file_name );
        //
        //        $file_matching = TRUE;
        //    }
        //}

		// force data refresh 

        if (
			isset( $_GET[GET_API_TWITTER_REFRESH] ) &&
			$_GET[GET_API_TWITTER_REFRESH]
		)
        {
            unset( $store[$resource] );

            $file_matching = FALSE;
        }

        if ( ! isset( $store[$resource] ) )

            $store[$resource] = array();

		$store_resource = &$store[$resource];

		$sort_by_length =
			function ( $a, $b ) use ( $class_dumper )
			{
				if ( ! is_object( $a ) || ! is_object( $b ) )
	
					throw new Exception( EXCEPTION_INVALID_ARGUMENT );
				
				if (
					! isset( $a->{PROPERTY_TEXT} ) ||
					! isset( $b->{PROPERTY_TEXT} )
				)
				
					throw new Exception( EXCEPTION_INVALID_ARGUMENT );
					
				if (
					strlen( $b->{PROPERTY_TEXT } ) ==
						strlen( $a->{PROPERTY_TEXT } )
				)
					
					$result = 0;

				$result = 
						(
							strlen( $b->{PROPERTY_TEXT } ) -
								strlen( $a->{PROPERTY_TEXT } )
						) > 0
					?
						-1
					:
						1
				;

				return $result;
			}
		;

		if (
			! isset( $store_resource[$definition] ) ||
			! count( $store_resource[$definition] )
		)
		{
			if (
				(
					! $file_matching ||
					(
						strlen( $dumped_store ) ===
							strlen( serialize( array() ) )
					)
				) &&
				in_array(
					$method,
					get_class_methods( __CLASS__ )
				)
			)

				//while (
				//	$favorites_slice = self::$method(
				//		$definition,
				//		( object ) $options
				//	) &&
				//	! $error_404
				//)
				{
					$favorites_slice = self::$method(
						$definition,
						( object ) $options
					) ;

					if ( isset( $favorites_slice->{PROPERTY_ERROR} ) &&
						$favorites_slice->{PROPERTY_ERROR} === 'Not found'
					)

						$error_404 = TRUE;

					echo '404?' , $error_404;

					while ( list( $index, $favorite ) = each( $favorites_slice ) )
		
						$_favorites[md5( serialize( $favorite ) )] = $favorite;
		
					$page_index++;

					$options[PROPERTY_PAGE] = $page_index;
				}

			else

				$_favorites = unserialize( $dumped_store );

			$store_resource[$definition] = $_favorites;
		}
		else
		
			$_favorites = $store_resource[$definition];

		if ( $sorted )

			usort( $_favorites, $sort_by_length );

		if ( file_exists( $file_name ) )
		{			
			if (
				strlen( $dumped_store ) >
					strlen( serialize( $_favorites ) )
			)

				$regression = TRUE;
		}
		
		if ( ! $regression )
		{
			$file_handler = fopen(
				$file_name,
				FILE_ACCESS_MODE_OVERWRITE
			);

			fwrite( $file_handler, serialize( $_favorites ) );
	
			fclose( $file_handler );
		}

		$row_index =

		$count_rows = ceil( count( $_favorites ) / $max_columns );

		$parameters = array();

		$pattern_replacement = '<span class="pre"><a href="${1}">${1}</a></span>';

		$pattern_url = '#(http://[^\s]+)\s?#';

		while ( $row_index )
		{
			$column_index = 0;
			
			while ( $column_index < $max_columns )
			{
				list( $hash, $favorite ) = each( $_favorites );

				$tweet =
						is_object( $favorite ) && 
						isset( $favorite->{PROPERTY_TEXT} )
					?
						$favorite->{PROPERTY_TEXT}
					:
						''
				;

				if ( strlen( $tweet ) )

					$parameters[$row_index][$column_index] = 
						$class_application::shorten_sentence(
							preg_replace(
								$pattern_url,
								$pattern_replacement,
								$tweet
							),
							FALSE,
							140,
							NULL
						)
					;

				$column_index++;
			}

			$row_index--;
		}

		$body = $class_layout_manager::getLayout(
			TPL_BLOCK_TABLE,
			array( PROPERTY_TABLE => $parameters )
		);

		$context->{PLACEHOLDER_BODY} = $body;
		
		$context->{PROPERTY_CACHE_ID} = md5( time() );
		
		$context->{PROPERTY_CONTAINER} = array(
			HTML_ELEMENT_DIV => array(
				HTML_ATTRIBUTE_CLASS =>
					STYLE_CLASS_TABLE
			)
		);

		$context->{PROPERTY_ANONYMOUS} = TRUE;

		$view = $class_view_builder::displayView(
			$context,
			VIEW_TYPE_INJECTION
		);
	}

	/**
	* Get the favorites of a twitter user
	*
	* @param	string	$user_name	user name
	* @param	mixed	$options 	options 		
	* @return	string	favorites
	*/
	public static function getTwitterFavorite(
		$user_name,
		$options = NULL		
	)
	{
		global $class_application, $verbose_mode;

		$class_api = $class_application::getApiClass();

		$class_dumper = $class_application::getDumperClass();

		return $class_api::fetchFavorite(
			$user_name,
			NULL,
			$options			
		);
	}

	/**
	* Get the timeline of a twitter user
	*
	* @param	string	$kind		kind
	* @param	mixed	$options 	options 		
	* @return	string	favorites
	*/
	public static function getTwitterTimeline(
		$kind,
		$options = NULL		
	)
	{
		global $class_application, $verbose_mode;

		$class_api = $class_application::getApiClass();

		$class_dumper = $class_application::getDumperClass();

		return $class_api::fetchTimelineStatuses(
			$kind,
			NULL,
			$options			
		);
	}
	
	/**
	* Initialize a store
	*
	* @param	integer	$service		service
	* @param	string	$store_parent	store parent
	* @return	&array	reference to a store
	*/	
	public static function &initializeStore( $service = NULL, $store_parent = NULL )
	{
		global $class_application, $verbose_mode;
		
		$class_api = $class_application::getApiClass();
		
		$store = &$class_api::initializeStore( $service, $store_parent );

		return $store;
	}

    /**
    * Parse statically a resource
    *
    * @param    string    	$url  			url
    * @param	boolean		$extract_dom	DOM extraction flag
    * @param	boolean		$curl			cURL flag
    * @param	html		$html			HTML flag
    * @return   string  contents
    */
    public static function parse(
		$url = NULL,
		$extract_dom = TRUE,
		$curl = FALSE,
		$html = FALSE
	)
    {
        $feed_reader = new self($url, $extract_dom, $curl, $html);

        return $feed_reader;
    }


    /**
    * Parse some RDF contents
    *
    * @param    string    	$file_path	path leading to a RDF file
    * @return   mixed
    */
    public static function parseRDF( $file_path )
    {
		global $class_application, $verbose_mode;

		$class_dumper = $class_application::getDumperClass();

		$exception_missing_metadata_rdf = LOG_MESSAGE_UNAVAILABLE_METADATA_RDF;

		$title = '';

		$keywords = array();

		$class_inf_model = CLASS_INF_MODELF;

		if  ( isset( $_SERVER['HTTP_HOST'] ) )

			$base_url = $_SERVER['HTTP_HOST'];
		else
		
			$base_url = substr( BASE_URL, strlen( 'http://' ) );

		if ( ! class_exists( $class_inf_model ) )

			throw new Exception(
				sprintf(
					EXCEPTION_MISSING_ENTITY,
					ENTITY_CLASS.' ('.$class_inf_model.')'
				)
			);

		$inf_model = new $class_inf_model( 'http://'.$base_url );

		$inf_model->load( $file_path, 'rdf' );

		$resource = new Resource( RDF_SYNTAX.'Bag' );

		$triples = $inf_model->findForward(
			NULL,
			NULL,
			$resource
		)->triples;

		reset( $triples );

		// we assume the keywords are always put under the latest bag node
		end( $triples );
		list( , $triple ) = each( $triples );

		$next_triple = $inf_model->findForward(
			$triple->subj,
			NULL,
			NULL
		);

		if ( is_object( $next_triple ) )
		{
			$_triples = $next_triple->triples;
	
			reset( $_triples );
	
			while (list(, $triple) = each($_triples))
	
				if (isset($triple->obj->label))
				{
					if ( FALSE !== strpos( $triple->obj->label, 'title :: ' ) )
	
						$title = substr( $triple->obj->label, strlen( 'title :: ' ) );
	
					$keywords[] = $triple->obj->label;
				}
		}
		else if ( ! isset( $_SERVER['REQUEST_URI'] ) )

			echo $exception_missing_metadata_rdf;
		else

			throw new Exception( $$exception_missing_metadata_rdf );

		return array(
			METADATA_TYPE_KEYWORDS => implode(';', $keywords),
			METADATA_TYPE_TITLE => $title
		);
    }

    /**
    * Define the clone method
    *
    * @return   nothing
    */        
    public function __clone()
    {
        trigger_error('ouch');
    }
}
