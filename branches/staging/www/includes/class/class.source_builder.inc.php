<?php

namespace cg;

/**
* cg namespace created under code generation project 
*
* @package  cg
*/

/**
* Source_Builder class
*
* Class for building code sources
* @package  cg
*/
class Source_Builder
{
	/**
	* Build a class 
	*
	* @param	string 	$name 		class name
	* @param	string 	$namespace	package / namespace 
	* @return 	mixed	source
	*/
	public static function buildClass()
	{
		global $class_application;
		$class_source = $class_application::getSourceClass();

		$arguments = func_get_args();
		$source = NULL;

		if ( isset( $arguments[0] ) && is_string( $arguments[0] ) )

			$source = self::buildSourceFromModel( $arguments[0] );
		else
		{
			/**
			* Extract arguments
			*
			* @tparam	string	$name		class name
			* @tparam	string	$namespace	namespace
			*/
			extract( self::checkArguments( $arguments, __METHOD__ ) );
	
			$source = $class_source::getClassDefinition( $name, $namespace );
		}

		return $source;
	}

	/**
	* Build a class from a model
	*
	* @param	string	$model
	* @return	string	source
	*/
	public static function buildSourceFromModel( $model )
	{
		global $verbose_mode;
		$source = NULL;

		$properties = self::getModelProperties( $model );
		self::declarePersistencyLayer( $properties );
		$tokens_count_class_template = self::writeInStream( $properties );
		unset( $properties[PROPERTY_MODE_ACCESS] );
		
		if ( isset( $properties[PROPERTY_PATH_FILE] ) )

			$source = self::getFileContents( $properties[PROPERTY_PATH_FILE] );
	
		return $source;
	}

	/**
	* Check arguments
	*
	* @param	array	$arguments	arguments
	* @param	string	$method		method
	* @return 	nothing
	*/
	public static function checkArguments( $arguments, $method )
	{
		$callback_parameters = array();

		switch ( $method )
		{
			default:
			
				$namespace = NAMESPACE_SEMANTIC_FIDELITY;
		
				if ( ! isset( $arguments[0] ) )
				
					throw new Exception( sprintf(
						EXCEPTION_INVALID_PROPERTY, PROPERTY_NAME
					) );
				else
					$name = $arguments[0];
		
				if ( isset( $arguments[1] ) ) $namespace = $arguments[1];

				$callback_parameters = array(
					PROPERTY_NAME => $name,
					PROPERTY_NAMESPACE => $namespace
				);
		}
		
		return $callback_parameters;
	}

	/**
	* Declare persistency coordinates
	*
	* @param	mixed	$coordinates
	* @return 	nothing
	* @see		TOKENS_STREAM :: declarePersistencyCoordinates
	*/
	public static function declarePersistencyCoordinates()
	{
		global $class_application;
		$arguments = func_get_args();
		$ns = NAMESPACE_CID;
		$class_tokens_stream = $class_application::getTokensStreamClass( $ns );
		return call_user_func_array(
			array( $class_tokens_stream, __FUNCTION__ ), $arguments
		);
	}

	/**
	* Define coordinates to serialize data
	*
	* @param	array	$properties
	* @return 	nothing
	*/
	public static function declarePersistencyLayer( $properties )
	{
		if ( ! isset( $properties[PROPERTY_PERSISTENCY] ) )
		
			throw new Exception( sprintf(
				EXCEPTION_INVALID_ENTITY, ENTITY_PERSISTENCY
			) );

		else if ( ! isset( $properties[PROPERTY_ENTITY] ) )
		
			throw new Exception( sprintf(
				EXCEPTION_INVALID_ENTITY, PROPERTY_ENTITY
			) );
		else
		{
			$entity = $properties[PROPERTY_ENTITY];
			$persistency = $properties[PROPERTY_PERSISTENCY];
			self::declarePersistencyCoordinates( $persistency, $entity );
		}
	}

	/**
	* @see	FILE_MANAGER::getFileContents
	*/
	public static function getFileContents()
	{
		global $class_application;
		$file_manager = $class_application::getFileManagerClass();
		$arguments = func_get_args();
		return call_user_func_array(
			array( $file_manager, __FUNCTION__ ), $arguments
		);
	}

	/**
	* @see	TOKENS_STREAM::getHost
	*/
	public static function getHost()
	{
		global $class_application;
		$ns = NAMESPACE_CID;
		$class_tokens_stream = $class_application::getTokensStreamClass( $ns );
		return call_user_func( array( $class_tokens_stream, __FUNCTION__ ) );	
	}

	/**
	* Get a model in JSON
	*
	* @param	string	$model
	* @param	string	$subset
	* @return	mixed	model
	*/
	public static function getJsonModel( $model, $subset = NULL )
	{
		$json_model =
		$json_model_subset = NULL;

		if ( is_null( $subset ) ) $subset = SUBSET_TYPE_DEFINITION;

		/**
		* Extract repositories
		*
		* @include_repository
		* @json_repository
		* @snippet_repository
		*/
		extract( self::loadRepositories() );

		$directory_sandbox = self::getRootDirectory();
		$directory_model_json = $directory_sandbox . '/../' . $json_repository;
		$path_model = $directory_model_json . '/' . $model ;

		if ( file_exists( $path_model ) )
			
			$json_model = json_decode(
				$file_contents = self::getFileContents( $path_model )
			);
		else

			throw new \Exception( sprintf(
				EXCEPTION_INVALID_ENTITY, ENTITY_MODEL_JSON .
				' (' . $path_model . ')'
			) );

		if ( ! isset( $json_model->$subset ) )

			throw new \Exception( sprintf(
				EXCEPTION_INVALID_ENTITY, ENTITY_SUBSET
			) );
		else
		
			$json_model_subset = $json_model->$subset;
			
		return $json_model_subset;
	}

	/**
	* Get a member template properties
	*
	* @param	array	$method
	* @param	mixed	$template
	*/
	public static function getMemberTemplate( $method )
	{
		$template = NULL;
	
		if ( ! isset( $method->{PROPERTY_SOURCE} ) )

			throw new Exception( sprintf( EXCEPTION_ENTITY, ENTITY_SOURCE ) );
		else
		{
			$source = $method->{PROPERTY_SOURCE};
			$file_name = self::getMethodTemplateFileName( $source );
			// @todo
			//global $class_application, $verbose_mode;
			//$class_dumper = $class_application::getDumperClass();
			//$class_dumper::log( __METHOD__, array(
			//	$file_name
			//), TRUE );
		}

		return $template;
	}

	/**
	* Get members templates
	*
	* @param	string	$model
	* @param	mixed	templates
	*/
	public static function getMembersTemplates( $model )
	{
		$templates = array();

		if ( ! isset( $model->{PROPERTY_METHODS} ) )
		
			throw new Exception(
				EXCEPTION_INVALID_ARGUMENT . ' ('. PROPERTY_METHODS . ')'
			);
		else
		{
			$methods = $model->{PROPERTY_METHODS};
			
			if ( is_array( $methods ) && count( $methods ) )

				foreach ( $methods as $method )

					$templates[] = self::getMemberTemplate( $method );
		}
		
		return $templates;
	}

	/**
	* Get the properties of a model
	*
	* @param	string	$model
	* @param	mixed	properties
	*/
	public static function getModelProperties( $model )
	{
		$definition_subset = self::getJsonModel( $model );
		$persistency_subset = self::getJsonModel( $model, SUBSET_TYPE_PERSISTENCY );
		$properties = self::getSourceCoordinates( $model );
		$properties[PROPERTY_ENTITY] = $definition_subset->{PROPERTY_ENTITY};
		$properties[PROPERTY_METHODS] = self::getMembersTemplates(
			$definition_subset
		);
		$properties[PROPERTY_MODE_ACCESS] = FILE_ACCESS_MODE_WRITE_ONLY;
		$properties[PROPERTY_PERSISTENCY] = $persistency_subset;
		$properties[PROPERTY_SIGNAL] = self::getModelTemplate(
			$properties[PROPERTY_SOURCE_TYPE]
		);
		$properties[PROPERTY_SUBSTITUTIONS] = self::loadSubstitutions(
			$definition_subset
		);
		return $properties;
	}

	/**
	* Get a model template 
	*
	* @param	$model
	* @return	$template
	*/
	public static function getModelTemplate( $model )
	{
		return self::getFileContents(
			self::getSnippetRepository() .
				self::getModelTemplateFileName( $model )
		);
	}

	/**
	* @see	TOKENS_STREAM::getRootDirectory
	*/
	public static function getRootDirectory()
	{
		global $class_application;
		$ns = NAMESPACE_CID;
		$class_tokens_stream = $class_application::getTokensStreamClass( $ns );
		return call_user_func( array( $class_tokens_stream, __FUNCTION__ ) );	
	}

	/**
	* Get the snippet repository
	*
	* @return	string
	*/
	public static function getSnippetRepository()
	{
		/**
		* Extract repositories
		*
		* @snippet_repository
		*/
		extract( self::loadRepositories() );
		return self::getRootDirectory() . '/../' .
			$snippet_repository . '/'
		;
	}

	/**
	* Load repositories
	*
	* @return	$repositories
	*/
	public static function loadRepositories()
	{
		return array(
			REPOSITORY_INCLUDE => 'includes',
			REPOSITORY_JSON => 'model.json',
			REPOSITORY_SNIPPET => 'snippets'
		);
	}

	/**
	* Get a method template file name 
	*
	* @param	$method
	* @return	string
	*/
	public static function getMethodTemplateFileName( $method )
	{
		$prefix_method = 'method' . '.';
		$file_name_template =
			$prefix_method . $method . self::getPhpTemplateExtension()
		;
		return $file_name_template;
	}

	/**
	* Get a model template file name 
	*
	* @param	$model
	* @return	string
	*/
	public static function getModelTemplateFileName( $model )
	{
		$file_name_template = $model . '.model' . self::getPhpTemplateExtension();
		return $file_name_template;
	}

	/**
	* Get a PHP template extension
	*
	* @return	string
	*/
	public static function getPhpTemplateExtension()
	{
		$extension_php = EXTENSION_PHP;
		return EXTENSION_TPL  .$extension_php;
	}

	/**
	* Get a source location 
	*
	* @param	string	model
	* @return	string
	*/
	public static function getSourceCoordinates( $model )
	{
		/**
		* Extract repositories
		*
		* @tparam	$include_repository
		*/
		extract( self::loadRepositories() );

		$directory_sandbox = self::getRootDirectory();
		$extension_php = EXTENSION_PHP;
		$file_name_class = $model . $extension_php;
		$host = self::getHost();
		$json_model = self::getJsonModel( $model );
		$prefix_source_type =
		$source_type_repository = '';
	
		if ( isset( $json_model->{PROPERTY_SOURCE_TYPE} ) )
		{
			$source_type = $json_model->{PROPERTY_SOURCE_TYPE};
			$prefix_source_type = $source_type . '.';
		}
		else
			throw new \Exception( str_replace( '_', ' ', sprintf( 
				EXCEPTION_INVALID_ENTITY, ENTITY_SOURCE_TYPE
			) ) );
	
		if ( isset( $json_model->{PROPERTY_SOURCE} ) )

			$file_name_class = $prefix_source_type . $file_name_class;
		else

			throw new Exception( sprintf( 
				EXCEPTION_INVALID_ENTITY, ENTITY_SOURCE
			) );
	
		if ( isset( $source_type ) ) $source_type_repository = $source_type . '/';
		$location =
			$include_repository. '/' . $source_type_repository . $file_name_class
		;

		return array(
			PROPERTY_LOCATION => $location,
			PROPERTY_PATH => PROTOCOL_TOKEN . '://' . $host . '/' . $location,			
			PROPERTY_PATH_FILE => $directory_sandbox . '/' . $location,
			PROPERTY_SOURCE_TYPE => $source_type
		);
	}

	/**
	* Get default substitutions for accepted placeholders
	*
	* @param	nothing
	* @return	substitutions
	*/
	public static function getSubstitutionsModel()
	{
		return array(
			'class' => NULL,
			'description' => NULL,
			'entity' => NULL,
			'namespace' => NULL,
			'parent' => NULL
		);
	}

	/**
	* Load substitutions
	*
	* @param	$model
	* @return	$substitutions
	*/
	public static function loadSubstitutions( $model )
	{
		$substitutions = self::getSubstitutionsModel();
		$placeholders = array_keys( $substitutions );
		
		foreach( $model as $name => $value )
		{
			$_name = $name . '__';

			if (
				in_array( $name, $placeholders ) &&
				is_null( $substitutions[$name] ) &&
				is_string( $value ) || is_integer( $value )
			)
			{
				unset( $substitutions[$name] );
				$substitutions[$_name] = $value;
			}
		}

		return $substitutions;
	}

	/**
	* @see		CLASS_DUMPER :: log
	*/
	public static function log()
	{
		global $class_application, $verbose_mode;
		$class_dumper = $class_application::getDumperClass();
		$arguments = func_get_args();
		return call_user_func_array(
			array( $class_dumper, __FUNCTION__ ),
			$arguments
		);
	}

	/**
	* Save a class definition class onto a disk
	*
	* @param	string 	$name 		class name
	* @param	string 	$namespace	package / namespace 
	* @return	boolean	operation success
	*/
	public static function saveClass()
	{
		global $class_application, $verbose_mode;

		$arguments = func_get_args();

		/**
		* Extract arguments
		*
		* @tparam	string	$name		class name
		* @tparam	string	$namespace	namespace
		*/
		extract( self::checkArguments( $arguments, __FUNCTION__ ) );
		
		$class_name = strtolower( $namespace ) . '.' . strtolower( $name );
		
		$path_to_source =
			dirname( __FILE__ ) .
			'/' . 'class.' . $class_name . '.inc' . EXTENSION_PHP
		;

		self::writeOnDisk(
			$path_to_source, $source = self::buildClass( $name, $namespace )
		);

		return $path_to_source;
	}

	/**
	* @see TOKENS_STREAM::writeInStream
	*/
	public static function writeInStream()
	{
		global $class_application;
		$ns = NAMESPACE_CID;
		$class_tokens_stream = $class_application::getTokensStreamClass( $ns );
		$arguments = func_get_args();
		return call_user_func_array(
			array( $class_tokens_stream, __FUNCTION__ ),
			$arguments
		);
	}

	/**
	* Write a file on a disk
	*
	* @param	string	$path		path leading to a file
	* @param	string	$content	file content
	* @return	boolean	operation success
	*/
	public static function writeOnDisk()
	{
		$class_file_manager = $class_application::getFileManagerClass();

		$arguments = func_get_args();
		
		$results = call_user_func_array(
			array( $class_file_manager, __FUNCTION__ ), $arguments
		);

		return $results;
	}
}

/**
*************
* Changes log
*
*************
* 2011 11 01
*************
* 
* development :: code generation ::
*
* Start implementing Source Builder class
*
* methods affected ::
*
* 	SOURCE_BUILDER :: buildClass
* 	SOURCE_BUILDER :: checkArguments
* 	SOURCE_BUILDER :: saveSource
* 	SOURCE_BUILDER :: writeOnDisk
*
* (branch 0.1 :: revision :: 815)
*
*************
* 2012 05 08
*************
* 
* development :: code generation ::
*
* Encapsulate SOURCE_BUILDER in Code Generation namespace
* Implement class build from JSON model
*
* methods affected ::
*
* 	SOURCE_BUILDER :: buildSourceFromModel
* 	SOURCE_BUILDER :: getHost
* 	SOURCE_BUILDER :: getFileContents
* 	SOURCE_BUILDER :: getJsonModel
* 	SOURCE_BUILDER :: getMethodTemplateFileName
* 	SOURCE_BUILDER :: getMemberTemplate
* 	SOURCE_BUILDER :: getMembersTemplates
* 	SOURCE_BUILDER :: getModelTemplate
* 	SOURCE_BUILDER :: getModelTemplateFileName
* 	SOURCE_BUILDER :: getModelProperties
* 	SOURCE_BUILDER :: getPhpTemplateExtension
* 	SOURCE_BUILDER :: getRootDirectory
* 	SOURCE_BUILDER :: getSnippetRepository
* 	SOURCE_BUILDER :: getSourceCoordinates
* 	SOURCE_BUILDER :: getSubstitutionsModel
* 	SOURCE_BUILDER :: loadRepositories
* 	SOURCE_BUILDER :: loadSubstitutions
* 	SOURCE_BUILDER :: log
* 	SOURCE_BUILDER :: writeInStream
*
* (branch 0.1 :: revision :: 915)
*
*************
* 2012 05 09
*************
* 
* development :: code generation ::
*
* Declare table, table column, prefix and default table alias
* from entity specification model formulated in JSON
*
* methods affected ::
*
*	SOURCE_BUILDER :: declarePersistencyCoordinates
* 	SOURCE_BUILDER :: declarePersistencyLayer
*
* (branch 0.1 :: revision :: 926)
*
*/