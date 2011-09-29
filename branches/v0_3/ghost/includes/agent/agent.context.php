<?php

/**
* Context class
* 
* Class to construct context particles
* @package sefi
*/
class Context extends Alpha
{
    protected static $context;

    /**
    * Probe a context
    * 
    * @param    string  $file_extension file extension
    * @param    string  $restriction    restriction
    * @param    string  $storage_type   context type
    * @return   object  context
    */
    public static function probe(
        $file_extension = EXTENSION_ZIP,
        $restriction = DIR_DROPBOX,
        $storage_type = NULL
    )
    {
		global $class_application, $verbose_mode;

        $class_dumper = $class_application::getDumperAgent();

        $class_entity = $class_application::getEntityAgent();

        $default_storage_type =
            $class_entity::getDefaultType(
                PROPERTY_VALUE,
                ENTITY_STORAGE
            );

        // set the context as a new instance of the standard class
        self::$context = new stdClass();

        // set the conditions of population
        self::$context->{CONDITION_INHABITANTS} = array();

        if ( is_null( $storage_type ) )

            $storage_type = $default_storage_type;

        if ( $storage_type == $default_storage_type )

            switch ($restriction)
            {
                case DIR_CONTEXT:
    
                    // set a path to a directory
                    $path2directory =
                        dirname(__FILE__).
                        "/../../".$restriction."/"
                    ;
            
                    // Check a directory
                    if ( is_dir( $path2directory ) )
                    {
                        // open the directory
                        if ($directory_handle = opendir($path2directory))
                        {
                            // loop on files
                            while (
                                ( $file_name = readdir( $directory_handle ) )
                                    !== FALSE
                            )
    
                                // check if the file is not hidden
                                // and if it look likes md5 hash
                                if (
                                    ! preg_match( '/^\./', $file_name ) &&
                                    strlen( $file_name ) == 32
                                )

                                    // append the current file name
                                    // to the context of inhabitants
                                    self::$context->{CONDITION_INHABITANTS}[] =
                                        $file_name
                                    ;
    
                            // close the current directory 
                            closedir($directory_handle);
                        }
                    }

                        break;

                case DIR_DROPBOX:

                    $file_pattern =
                        glob( dirname(__FILE__).
                            "/../../".
                            $restriction."/*".
                            $file_extension
                        )
                    ;

                    foreach ( $file_pattern as $file_name)
                    {
                        $path_levels = explode('/', $file_name);

                        // append the current file name
                        // to the context of inhabitants
                        self::$context->{CONDITION_INHABITANTS}[] =
                            $path_levels[count($path_levels) - 1]
                        ;
                    }

                        break;

                case DIR_PHOTOGRAPHS:

                    $file_names_pattern =
                        glob( dirname(__FILE__ ).
                            '/../../'.
                                $restriction.
                                    '/*/*/*/*'.
                                        $file_extension
                        )
                    ;

                    foreach ( $file_names_pattern as $file_name )
                    {
                        $path_levels = explode('/', $file_name);

                        // append the current file name
                        // to the context of inhabitants
                        self::$context->{CONDITION_INHABITANTS}[] =
                            array(

                                PROPERTY_DATE_CREATION =>
                                    $path_levels[count($path_levels) - 3],

                                PROPERTY_LOCATION =>
                                    $path_levels[count($path_levels) - 4],

                                PROPERTY_NAME =>
                                    $path_levels[count($path_levels) - 1],

                                PROPERTY_AUTHOR =>
                                    $path_levels[count($path_levels) - 2]
                            );
                    }

                        break;
            }

        // return the context
        return self::$context;
    }
}
?>