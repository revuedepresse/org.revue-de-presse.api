<?php

/// @cond DOC_SKIP

namespace api
{

/// @endcond
	
	/**
	* Facebook class
	*
	* Class for handling Facebook sdk
	* @package  api
	*/
	class Facebook extends \Api
	{
		/**
		* Append an item to a collection
		* 
		* @param   	array   $context    contextual parameters
		* @return	array	collection
		*/
		public static function appendGlobals( $context )
		{
			/**
			* Extract contextual parameters
			*
			* @tparam  $collection
			* @tparam  $facebook_api_key
			* @tparam  $globals
			*/
			extract( $context );
		
			foreach ( $globals as $global )
			{
				$_global = '_' . strtoupper( $global );
		
				if (
					isset( $GLOBALS[$_global] ) &&
					( $value = $GLOBALS[$_global] ) &&
					count( $value )
				)
				{
					if (
						( $global === 'session' ) &&
						isset( $value['fb_' . $facebook_api_key . '_access_token'] )
					)
					
						$value = array(
							'fb_' . $facebook_api_key . '_access_token' =>
								$value['fb_' . $facebook_api_key . '_access_token'],
		
							'fb_' . $facebook_api_key . '_user_id' =>
								$value['fb_' . $facebook_api_key . '_user_id'],
							POST_API_FACEBOOK_STORE_JSON_ID => ''
						);
		
					$collection = array_merge(
						$collection, array( $global => $value )
					);
				}
			}
		
			return $collection;
		}

		/**
		* Apply substitutions
		*
		* @param    string  $subject
		* @param    array   $substitutions
		* @return   string  subject to which substitutions have been applied
		*/
		public static function applySubstitutions( $subject, $substitutions )
		{
			$_subject = $subject;
		
			if ( is_array( $substitutions ) && ( count( $substitutions ) > 0 ) )
		
				foreach ( $substitutions as $name => $value )
			
					$_subject = str_replace( $name, $value, $_subject );
		
			return $_subject;
		}

		/**
		* Check client privileges
		*
		* @return	nothing
		*/
		public static function checkPrivileges()
		{
			if (
				$_SERVER['REMOTE_ADDR'] !== '## FILL IP ADDRESS ##' &&
				$_SERVER['REMOTE_ADDR'] !== '::1' &&
				$_SERVER['REMOTE_ADDR'] !== '127.0.0.1'
			) {
				exit( '[termination at line ' . __LINE__ . ']' );
			}
		}

		/**
		* Decorate a resource
		*
		* @param    array   $resource
		* @return   mixed   resource
		*/
		public static function decorateResource( $resource )
		{
			$resource['session'][POST_API_FACEBOOK_STORE_JSON_ID] =
				self::serializeResource(
					self::appendGlobals( array(
						'collection' => $resource,
						'facebook_api_key' =>
							self::getCredential( SETTING_API_KEY ),
						'globals' => array( 'session', 'get' )
					) )
				)->{PROPERTY_ID}
			;
			return $resource;
		}

		/**
		* Send header to client
		*
		* @param    array	context	contextual parameters
		* @return   mixed	resource
		*/
        public static function deliverResponse( $context )
        {
			$string_as_context = str_valid( $context );

			$extension = '.json';
			$mime_type = 'application/json';
			$name = 'resource';

			if (!$string_as_context) {
				/**
				* Extract contextual parameters
				*
				* @param    $encoding	(optional)
				* @param    $extension  (optional)
				* @param    $mime_type  (optional)
				* @param    $name       (optional)
				* @param    $resource   resource
				*/
				extract( $context);

				$_resource = self::encode( $resource, $encoding );
			} else {
				$_resource = $context;
			}

			$size = strlen( $_resource );
		
			header( 'Content-Transfer-Encoding: binary' );
			header( 'Content-Length: ' . $size );
			header( 'Content-type: ' . $mime_type );
			header('filename=' . $name . $extension . ';');

			echo $_resource;

            return $_resource;
        }

		/**
		* Encode a resource
		*
		* @param	string	$resource
		* @param	mixed	$encoding 	(optional)
		* @return	resource
		*/
		public static function encode( $resource, $encoding = NULL )
		{
			$_resource = $resource;

			if ( str_valid( $encoding ) && function_exists( $encoding ) ) {
				$_resource = $encoding( $resource );
         }
		
			return $_resource;
		}

		/**
		* Get credential
		*
		* @param	string	$name		
		* @return	mixed	credentials
		*/
		public static function getCredential( $name = NULL )
		{
			$credential = NULL;
			$credentials = self::getCredentials();

			if ( str_valid( $name ) && isset( $credentials[$name] ) )

				$credential = $credentials[$name];
			
			return $credential;
		}
	
		/**
		* Get credentials
		*
		* @return	mixed	credentials
		*/
		public static function getCredentials()
		{
			return array(
				SETTING_API_KEY => API_FACEBOOK_API_KEY,
				SETTING_PASSWORD => API_FACEBOOK_SECRET
			);
		}

		/**
		* Get endpoint
		*
		* @param	mixed	$user_id	user id
		* @return	string	endpoint
		*/
		public static function getEndpoint( $user_id = FALSE )
		{
			$endpoint = self::getUserId();

			if ( $user_id ) $endpoint = '/' . $user_id;
			
			return $endpoint;
		}

		/**
		* Get a form
		*
		* @param   string  $store		object to be presented
		* @param   string  $encoding	encoding function
		* @return  string  form
		*/
		public static function getForm( $store, $encoding = 'json_encode' )
		{
			$_store = self::encode( $store, $encoding );

			$form = str_replace(
				'{textarea_value}', $_store, self::getTemplate( 'form' )
			);
			
			return $form;
		}

		/**
		* Get JSON Store
		*
		* @param    integer $store_id   
		* @return   object
		*/
		public static function getJson( $store_id = NULL )
		{
			global $class_application;
			$class_json = $class_application::getJsonClass();

			$json_store_id = self::getJsonId( $store_id );

			return $class_json::getById( $json_store_id );
		}

		/**
		* Get JSON Id
		*
		* @param    integer $store_id   
		* @return   integer
		*/
		public static function getJsonId( $store_id )
		{
			if ( is_null( $store_id ) ) $store_id = 295;

			return (
				isset( $_POST[POST_API_FACEBOOK_STORE_JSON_ID] )
				? $_POST[POST_API_FACEBOOK_STORE_JSON_ID] : $store_id
			);
		}

		/**
		* Get JSON Object
		*
		* @param    integer $store_id   
		* @return   object
		*/
		public static function getJsonObject( $store_id = NULL )
		{
			return json_decode( self::getJson( $store_id )->{PROPERTY_VALUE} );
		}

		/**
		* Get the creation time of a JSON object
		*
		* @param    integer $store_id 
		* @return   mixed
		*/
		public static function getJsonTimeCreation( $store_id = NULL )
		{
			$created_time = NULL;
		
			$json_object = self::getJsonObject( $store_id );
			$posts = $json_object->data;
			
			if ( isset( $posts[count( $posts ) - 1]->created_time ) )
				$created_time = $posts[count( $posts ) - 1]->created_time;
		
			return $created_time;
		}

		/**
		* Get middleware used to communicate with Facebook Graph API
		*
		* @return	mixed	middleware
		*/
		public static function getMiddleware()
		{
			global $class_application;
			$class_facebook = $class_application::getFacebookClass();
			
			$context = array(
				'appId'  => self::getCredential( SETTING_API_KEY ),
				'secret' => self::getCredential( SETTING_PASSWORD )
			);
		
			$middleware = new $class_facebook( $context );

			return $middleware;
		}

		/**
		* Get newsfeed
		*
		* @return   nothing
		*/
		public static function getNewsfeed()
		{
			self::checkPrivileges();
			$session = self::restoreSession();

			$template_document =
				self::getSetting( 'max_depth_recursivity' )
				? self::getTemplate( 'document' )
				: '{resource}'
			;

			if ( $session['user'] )
			{
				if (
					! isset( $_POST[POST_API_FACEBOOK_PARAMETERS] ) &&
					! isset( $_POST[POST_API_FACEBOOK_STORE_JSON_ID] )
				)
					$response = self::applySubstitutions(
						$template_document,
						self::getSubstitutions( $session )
					); 
				else
					$response = array(
						'encoding' => 'json_encode',
						'resource' =>
							self::getResourceDecorated( $session['middleware'] ),
						'name' => 'store'
					);
				
				self::deliverResponse( $response );
			}
		}

		/**
		* Get parameters
		*
		* @param    string  $name
		* @return   array   parameters
		*/
		public static function getParameters( $name = NULL )
		{
			if ( is_null( $name ) ) $name = POST_API_FACEBOOK_PARAMETERS;
			
			$parameters = array();
			
			if ( str_valid( $name ) && isset( $_POST[$name] ) )
			{
				$parameters = $_POST[$name];
				if ( ! isset( $parameters['metadata'] ) )
					$parameters['metadata'] = 1;
			}
			
			return $parameters;
		}

		/**
		* Get resource
		*
		* @param	object	$middleware
		* @return   mixed	resource
		*/
		public static function getResource( $middleware )
		{
			$parameters = self::getParameters();
			$query = self::getUrlQuery();
			$resource = NULL;
		
			try {
				$resource = $middleware->api( $query, $parameters );
			}
			catch ( FacebookApiException  $e )
			{
				$class_dumper::log( __METHOD__, array(
					$e->getMessage()	    
				), TRUE, TRUE );
			}
		
			return $resource;
		}

		/**
		* Get a decorated resource
		*
		* @param    object  middleware
		* @return   mixed   decorated resource
		*/
		public static function getResourceDecorated( $middleware )
		{
			return self::decorateResource( self::getResource( $middleware ) ); 
		}

		/**
		* Get setting
		*
		* @param	string	$name
		* @return   mixed	settings
		*/
		public static function getSetting( $name = NULL )
		{
			$setting = NULL;
			$settings = self::getSettings();

			if ( str_valid( $name ) && isset( $settings[$name] ))

				$setting = $settings[$name];

			return $setting;
		}

		/**
		* Get settings
		*
		* @return   array   settings
		*/
		public static function getSettings()
		{
			$max_depth_recursivity =
				isset( $_GET[GET_API_FACEBOOK_MAX_DEPTH_RECURSIVITY] ) &&
				$_GET[GET_API_FACEBOOK_MAX_DEPTH_RECURSIVITY]
				? $_GET[GET_API_FACEBOOK_MAX_DEPTH_RECURSIVITY] : 0
			;

			$mode_debug =
				isset( $_GET[GET_API_FACEBOOK_MODE_DEBUG] ) &&
				$_GET[GET_API_FACEBOOK_MODE_DEBUG]
				? !!$_GET[GET_API_FACEBOOK_MODE_DEBUG] : FALSE
			;

			$mode_fql =
				isset( $_GET[GET_API_FACEBOOK_MODE_FQL] ) &&
				$_GET[GET_API_FACEBOOK_MODE_FQL]
				? !!$_GET[GET_API_FACEBOOK_MODE_FQL] : FALSE
			;

			$max_item =
				isset( $_GET[GET_API_FACEBOOK_MAX_ITEMS] ) &&
				$_GET[GET_API_FACEBOOK_MAX_ITEMS]
				? !!$_GET[GET_API_FACEBOOK_MAX_ITEMS] : 100
			;

			$limit = '0,'.$max_item;

			return array(
				'limit' => $limit,
				'max_depth_recursivity' => $max_depth_recursivity,
				'mode_debug' => $mode_debug,
				'mode_fql' => $mode_fql
			);
		}
		
		/**
		* Get a signature
		*
		* @param	boolean	$namespace	namespace flag
		* @return	string	signature
		*/
		public static function getSignature( $namespace = FALSE )
		{
			$_class = __CLASS__;

			if ( ! $namespace )
			{	
				list( $_namespace, $_class ) = explode( '\\', __CLASS__ );
				self::$namespace = $_namespace;
			}
	
			return $_class;
		}
	
		/**
		* Get Substitutions
		*
		* @param    $context    contextual parameters
		* @return   array       substitutions
		*/
		public static function getSubstitutions( $context )
		{
			$store_id = NULL;
	
			$max_depth_recursivity = self::getSetting( 'max_depth_recursivity' );

			/**
			* Extract contextual parameters
			*
			* @tparam  $block_authentication
			* @tparam  $middleware
			* @tparam  $store_id               (optional)
			*/
			extract( $context );
		
			$decorated_resource = self::getResourceDecorated( $middleware );

			$resource = $max_depth_recursivity
				? self::getForm( $decorated_resource )
				: self::encode( $decorated_resource, 'json_encode' )
			;
		
			$substitutions = array(
				'{block_authentication}' => $block_authentication,
				'{resource}' => $resource,
				'{script}' => self::getTemplate( 'javascript' ),
				'{max_depth_recursivity}' => $max_depth_recursivity,
				'{mode_debug}' => (
						self::getSetting( 'mode_debug' )
						? 'true' : 'false'
					),
				'{property_parameters}' =>
					POST_API_FACEBOOK_PARAMETERS.': parameters',
				'{property_store_json_id}' => POST_API_FACEBOOK_STORE_JSON_ID,
				'{store_id}' => self::getJsonId( $store_id ),
				'{title}' => 'Facebook Api Gateway',
				'{url_jquery}' => self::getUrlLibraryJquery(),
				'{url_self}' => self::getUrlCurrent()
			);
	
			return $substitutions;
		}

		/**
		* Get a template by its name
		*
		* @param		string	$name 	template name
		* @return	string	template
		*/
		public static function getTemplate( $name )
		{
			$template = NULL;

			switch ( $name )
			{
				case 'block_login':

					$template =
						'<div class="authentication">' .
						'  <a href="{attribute_href}">'.
						'   <img src="http://static.ak.fbcdn.net/rsrc.php/zB6N8/hash/4li2k73z.gif">'.
						'  </a>'.
						'</div>'
					;

						break;
					
				case 'block_logout':

					$template  =
						'<div class="authentication">' .
							'Thank you for having opened a Facebook session!<br />'.
							'<a href="{attribute_href}">{inner_html}</a>'.
						'</div>'
					;

						break;

				case 'javascript':

$template = <<<'EOT'
        <script language="JavaScript">
        debug = {mode_debug}
        json_store_id = {store_id};
        max_depth_resursivity = {max_depth_recursivity};
    
        function getQueryParams( qs ) {
            qs = qs.split("+").join(" ");
    
            var params = {},
                tokens,
                re = /(?:\?|(?:&amp;))?([^=]+)=([^&]*)/g;
        
            while (tokens = re.exec(qs)) {
                params[decodeURIComponent(tokens[1])]
                    = decodeURIComponent(tokens[2]);
            }
        
            return params;
        }
    
        function query_next_page( _map ) {
            var jtextarea = _map.element;
            var jstore = $.parseJSON( jtextarea.html() );
            var url_target = "{url_self}";
            parameters = null;
            if ( max_depth_resursivity > 0 )
            {
                if ( ( typeof( jstore  ) !== 'undefined' ) && ( jstore !== null ) )
                {
                    if ( debug )

                        console.log(
                            '[jstore state before using POST method]', jstore, "\n",
                            '[next page]', jstore.paging.next, "\n",
                            '[self url]',  url_target
                        );
        
                    if ( typeof( jstore.paging ) !== 'undefined' )
                    
                        parameters = getQueryParams( jstore.paging.next );
    
                    $.post( url_target, {
                            {property_store_json_id}: json_store_id,
							{property_parameters}
                        },
                        function ( data ) {
							var _data = data;

							if ( typeof( data ) === 'object' )
								_data = JSON.stringify( _data );
							else
								data = $.parseJSON( data );

                            $( '#store' ).html( _data );
                            json_store_id = data.session.{property_store_json_id};
                            query_next_page( map );
                            
                            if ( debug )
                            {
                                console.log( '[json store id]', json_store_id, "\n" );
                                console.log( 'data received by using POST method: ', data );
                            }
                        },
						'json'
                    );
                }
    
                max_depth_resursivity = max_depth_resursivity - 1;
            }
        }
        
        $( document ).ready( function() {
            var jtextarea = $( '#store' );
            map = { 'element': jtextarea };
        
            jtextarea.change( map, query_next_page );
            query_next_page( map );
        } );

        </script>
EOT;

					break;

				case 'document':

$template = <<<'EOT'
<!DOCTYPE html>
<html xmlns:fb="http://www.facebook.com/2008/fbml">
    <head>
        <title>{title}</title>
        <script type="text/javascript" src="{url_jquery}"></script>
		{script}<style>
            body {
              font-family: 'Lucida Grande', Verdana, Arial, sans-serif;
            }
            h1 a {
              text-decoration: none;
              color: #3b5998;
            }
            h1 a:hover {
              text-decoration: underline;
            }
        </style>
    </head>
    <body>
	{block_authentication}
    {resource}
    </body>
</html>
EOT;
	
						break;
			
				case 'form':
		
					$template =
						'<form method="post" action="/">' .
							'<textarea cols="100" id="store" name="store" rows="30" >' .
							'{textarea_value}' .
							'</textarea>' .
						'</form>'
					;
	
						break;
		
				case 'grant_privileges':
	
					$template = '<div><p><a href="{url}">{label}</a></p></div>';
					
						break;

				case 'query_fql':
					
					$template =
						'SELECT ' .
						'post_id,' .
						'viewer_id,' .
						'app_id,' .
						'updated_time,' .
						'created_time,' .
						'attribution,' .
						'actor_id,' .
						'target_id,' .
						'message,' .
						'app_data,' .
						'action_links,' .
						'attachment,' .
						'impressions,' .
						'comments,' .
						'likes,' .
						'privacy,' .
						'permalink,' .
						'tagged_ids,' .
						'message_tags,' .
						'description,' .
						'description_tags,' .
						'type ' .
						'FROM stream ' .
						'WHERE ' .
						'created_time < {until} AND ' .
						'source_id = me() ' . 
						'LIMIT {limit}'
					;

						break;

				case 'url_query_fql':
					
					$template = '/fql?q={query_fql}';

						break;

			}
			
			return $template;
		}	

		/**
		* Get current URL
		*
		* @return   current URL
		*/
		public static function getUrlCurrent()
		{
			return 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
		}

		/**
		* Get JQuery Library URL
		*
		* @return	string	URL
		*/
		public static function getUrlLibraryJQuery()
		{
			return
				'http://ajax.googleapis.com/' .
				'ajax/libs/jquery/1.7.2/jquery.js'
			;	
		}

		/**
		* Get a query URL
		*
		* @return   string  query
		*/
		public static function getUrlQuery()
		{
			$query = '/me/home';
		
			if ( self::getSetting( 'mode_sql' ) )
			{
				$limit = self::getSetting( 'limit' );
				$since = mktime( 7, 30, 01, 9, 21, 2007 );
			
				$creation_time = self::getJsonTimeCreation();

				if ( isset( $creation_time ) ) $until = $creation_time;
			
				$query_model = self::getTemplate( 'url_query_fql' );
		
				$query = str_replace(
					array( '{query_fql}' ),
					array(
						str_replace( ' ', '+', str_replace(
							array( '{limit}', '{since}', '{until}' ),
							array( $limit, $since, $until ),
							self::getTemplate( 'query_fql' )
						) )
					),
					$query_model
				);
			}
			
			return $query;
		}

		/**
		* Get user
		*
		* @param	mixed	$user_id
		* @param	mixed	$middleware
		* @return	mixed	user
		*/
		public static function getUser( $middleware = NULL, $user_id = FALSE )
		{
			global $class_application;
			$class_dumper = $class_application::getDumperClass();
		
			$user = NULL;
			
			if ( is_null( $middleware ) || ! is_object( $middleware ) )

				$middleware = self::getMiddleware();

			if ( $middleware->getUser() )

				try {
					$user = $middleware->api( self::getEndpoint( $user_id ) );
				} catch ( FacebookApiException $e )
				{
					$class_dumper::log( __METHOD__, array( $e ), TRUE, TRUE );
				}

			return $user;
		}

		/**
		* Get user id
		*
		* @return	integer	user id
		*/
		public static function getUserId()
		{
			return API_FACEBOOK_USER_ID;
		}

		/**
		* Restore a session
		*
		* @param    integer $store_id 
		* @return   nothing
		*/
		public static function restoreSession( $store_id = NULL )
		{
			global $class_application, $verbose_mode;
			$class_dumper = $class_application::getDumperClass();

			$json_object = self::getJsonObject( $store_id );

			if ( is_object( $json_object ) && isset( $json_object->session ) )
			
				foreach( $json_object->session as $key => $value )
				
					$_SESSION[$key] = $value;    

			$search = array( '{attribute_href}' );

			if ( $user = self::getUser( $middleware = self::getMiddleware() ) )
			{
				$template_name = 'block_logout';
				$search[] = '{inner_html}';
				$replace = array(
					$middleware->getLogoutUrl(),
					'Logout'
				);
			}
			else
			{
				$template_name = 'block_login';
				$replace = array(
					$middleware->getLoginUrl()
				);
			}   
			
			$block_authentication = str_replace(
				$search, $replace, self::getTemplate( $template_name )
			);

			return array(
				'block_authentication' => $block_authentication,
				'middleware' => $middleware,
				'user' => $user
			);
		}

		/**
		* Serialize a resource
		*
		* @param    array   $resource    resource
		* @return   object  json
		*/
		public static function serializeResource( $resource )
		{
			global $class_application;
			$class_json = $class_application::getJsonClass();

			$store = json_encode( $resource );
			$json = $class_json::make( $store );

			return $json;
		}	
	}
}

/**
*************
* Changes log
*
*************
* 2012 04 04
*************
* 
* project :: api ::
*
* development :: facebook ::
*
* Make Json files retrieved from graph API persistent
*
* (branch 0.1 :: revision 424)
*
*************
* 2012 04 13
*************
* 
* project :: api ::
*
* development :: facebook ::
*
* Play around
*
* (branch 0.1 :: revision 424)
*
*************
* 2012 04 17
*************
* 
* project :: api ::
*
* development :: facebook ::
*
* Start implementing recursive data retrieval
*
* (branch 0.1 :: revision 844)
*
*************
* 2012 05 01
*************
*
* development :: api :: facebook ::
*
* Implement Facebook in api namespace
*
* method affected ::
*
* API :: FACEBOOK :: appendGlobals
* API :: FACEBOOK :: applySubstitutions
* API :: FACEBOOK :: checkPrivileges
* API :: FACEBOOK :: decorateResource
* API :: FACEBOOK :: deliverResponse
* API :: FACEBOOK :: encode
* API :: FACEBOOK :: getCredential
* API :: FACEBOOK :: getCredentials
* API :: FACEBOOK :: getEndpoint
* API :: FACEBOOK :: getForm 
* API :: FACEBOOK :: getJson
* API :: FACEBOOK :: getJsonId
* API :: FACEBOOK :: getJsonObject
* API :: FACEBOOK :: getJsonTimeCreation
* API :: FACEBOOK :: getMiddleware
* API :: FACEBOOK :: getNewsfeed
* API :: FACEBOOK :: getResource
* API :: FACEBOOK :: getResourceDecorated
* API :: FACEBOOK :: getSetting
* API :: FACEBOOK :: getSettings
* API :: FACEBOOK :: getSignature
* API :: FACEBOOK :: getSubstitutions
* API :: FACEBOOK :: getTemplate
* API :: FACEBOOK :: getUrlCurrent
* API :: FACEBOOK :: getUrlLibraryJQuery
* API :: FACEBOOK :: getUrlQuery
* API :: FACEBOOK :: getUser
* API :: FACEBOOK :: getUserId
* API :: FACEBOOK :: restoreSession
* API :: FACEBOOK :: serializeResource
* 
* (branch 0.1 :: revision 861)
*
*/
