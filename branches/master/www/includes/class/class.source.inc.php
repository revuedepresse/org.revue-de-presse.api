<?php

/**
* Source class
*
* Class for source code management
* @package  sefi
*/
class Source extends Element
{
	/**
	* Get a signature
	*
	* @tparam	string	$name		class name 
	* @tparam	mixed	$namespace	namespace
	* @return	object source
	*/
	public static function getClassDefinition()
	{
		global $class_application;
		$class_source_builder = $class_application::getSourceBuilderClass();
		$class_template_engine = $class_application::getTemplateEngineClass();

		$arguments = func_get_args();
		$cache_id = md5( serialize( array( $arguments, __METHOD__ ) ) );
		$template_name = TPL_SOURCE_DEFINITION_CLASS;

		/**
		* Extract arguments
		*
		* @tparam	string	$name		class name
		* @tparam	string	$namespace	namespace
		*/
		extract( $class_source_builder::checkArguments( $arguments, __METHOD__ ) );

		// construct a new template engine
		$template_engine = new $class_template_engine();

		if (
			! (
				$cached_contents = $template_engine->is_cached(
					$template_name,
					$cache_id
				)
			)
		)
		{
			$template_engine->assign( 'name', $name );
			$template_engine->assign( 'namespace', $namespace );
			$template_engine->assign( 'day', date( 'd' ) );
			$template_engine->assign( 'description', '' );
			$template_engine->assign( 'month', date( 'm' ) );
			$template_engine->assign( 'project', $namespace );
			$template_engine->assign( 'revision', 819 );
			$template_engine->assign( 'year', date( 'Y' ) );
		}

		// fetch a class definition
		$class_definition = $template_engine->fetch( $template_name, $cache_id );

		return $class_definition;
	}	

	/**
	* Check last revision committed to source version control system
	*
	* @return	integer		revision
	*/
	public static function getLastRevision()
	{
		$current_directory = __DIR__;
		$results = array();
		$path_script ='/api/svn/api.svn.get_last_revision.php';
		$full_path_script = $current_directory . '/../../' . $path_script;
		$command_line = 'php -f ' . $full_path_script;
		$return = exec( $command_line, $results );
		return $results[0];
	}

	/**
	* Get a signature
	*
	* @param	boolean	$namespace	namespace flag
	* @return	string	signature
	*/
	public static function getSignature( $namespace = TRUE )
	{
		$_class = __CLASS__;

		if (!$namespace)

			list($_namespace, $_class) = explode('\\', __CLASS__);

		return $_class;
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
* method affected ::
*
* 	SOURCE::getClassDefinition
* 
* (revision 815)
*
*************
* 2012 05 08
*************
* 
* development :: api :: svn ::
*
* Implement last revision committed to version control system getter
*
* method affected ::
*
* 	SOURCE::getLastRevision
* 
* (revision 922)
*
*/