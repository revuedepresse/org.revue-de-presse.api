<?php

/**
* Smarty SEFI class
* 
* Class to construct Smarty Sefi
* @package sefi
*/
class Smarty_SEFI extends Smarty
{
    /**
    * Construct a Smarty template for SEFI
    *
    * @return  object   representing a Smarty template instance
    */	    
    public function __construct()
	{
        parent::__construct();

		$this->auto_literal = FALSE;

        $this->cache_dir = self::getFolder( ENTITY_CACHE );
        $this->compile_dir = self::getFolder( ENTITY_COMPILATION );
        $this->config_dir = self::getFolder( ENTITY_CONFIGURATION );
        $this->template_dir = self::getFolder();

        $this->caching = DEPLOYMENT_CACHING;

        $this->force_compile = !!!DEPLOYMENT_CACHING;
    }
    
    /**
    * Display an acknowledgment
    *
    * @return   nothing
    */
    public static function displayAcknowledgment()
	{
        global $lang, $class_application;

		$class_template_engine = $class_application::getTemplateEngineClass();

        $template = new $class_template_engine();

		$template_name = TPL_DIALOG_MESSAGE_ACKNOWLEDGMENT;

        switch ( $_GET[GET_ACKNOWLEDGMENT] ) {

            case RESPONSE_AUTHOR_SAVED:

                $template->assign('message_acknowledgment',$lang['acknowledgment']['add_author']);

                $template->display($template_name);
        
	                break;

            case RESPONSE_PHOTOS_SAVED:

                $template->assign('message_acknowledgment',$lang['acknowledgment']['upload_photos']);

                $template->display($template_name);

	                break;
        }

        $template->clear();
    }

    /**
    * Display a carroussel
    *
    * @return   nothing
    */
    public static function displayGrid()
	{
        global $class_application, $lang;

		$class_diaporama = $class_application::getDiaporamaClass();

		$class_diaporama::loadGrid( 10 );
    }

    /**
    * Get a folder
    *
    * @param	integer	$folder_type	type of folder
    * @return   string	path leading to a folder
    */
	public static function getFolder( $folder_type = NULL )
	{
		global $class_application;

		$class_dumper = $class_application::getDumperClass();
		$class_folder = $class_application::getFolderClass();

		$folder_prefix =
			dirname(__FILE__) .
			DIR_PARENT_DIRECTORY .
			DIR_PARENT_DIRECTORY
		;

		$library_prefix = DIR_LIBRARY.DIR_SMARTY.CHARACTER_SLASH;

		$folder_type_template = $class_folder::getDefaultType(PROPERTY_NAME);

		if ( is_null( $folder_type ) )

			$folder_type = $folder_type_template;

		$folder_name = $class_folder::getEntityTypeValue(
			array(
				PROPERTY_NAME => $folder_type,
				PROPERTY_ENTITY => ENTITY_FOLDER
			)
		);

		$dir_user = $folder_prefix . $library_prefix . USER_SYSTEM . CHARACTER_SLASH;

		if ( ! file_exists( $dir_user )  )
		
			mkdir( $dir_user );

		else if ( ! is_dir( $dir_user ) )

			throw new Exception( sprintf( EXCEPTION_INVALID_ENTITY, ENTITY_FOLDER ) );

		$folder = $dir_user . $folder_name;

		if ( ! file_exists( $folder )  )
		
			mkdir( $folder );

		else if ( ! is_dir( $folder ) )

			throw new Exception( sprintf( EXCEPTION_INVALID_ENTITY, ENTITY_FOLDER ) );

		return $folder;
	}
}

/**
*************
* Changes log
*
*************
* 2011 09 27
*************
*
* project :: wtw ::
*
* deployment :: template engine :: 
*
* Make cache and templates folder personal to each system user
* 
* SMARTY_SEFI :: getFolder
* 
* (revision 341)
*
*/