<?php

/**
* Import the Symfony YAML class
*/	
if ( ! class_exists( CLASS_SYMFONY_YAML ) )

    require_once( 'sfYaml/sfYaml.php' );

/**
* Yaml class
*
* @package  sefi
*/
class Yaml extends sfYaml
{
    /**
    * Construct a YAML object
    *
    * @param    array  		$array   		containing properties
    * @param    integer		$inline 		representing the folding depth
    * @param    integer		$storage_model	representing a model of storage
    * @param    string      $file  			containing a file name
    * @param    string    	$access_mode  	containing a file access mode
    * @return   mixed
    */	
    public function __construct(
        $array,
        $inline = PROPERTY_YAML_FOLDING_DEPTH,
        $storage_model = null,
        $file = null,
        $access_mode = FILE_ACCESS_MODE_APPEND
    )
    {
        // check the array argument
        if (
            is_array($array) &&
            count($array) != 0
        )
            // translate the array argument into YAML contents
            $yaml = self::translate($array, ENTITY_YAML, ENTITY_PHP_VARIABLE, $inline);

        // check the storage model
        if (!isset($storage_model))

            // return the YAML contents
            return $yaml;

        // check if the storage model is a YAML store
        else

            // serialize the YAML contents
            self::save($yaml, $storage_model, $file, $access_mode);
    }

    /**
    * Deserialize
    *
    * @param    string      $resource  		containing a resource
    * @param	integer		$storage_model 	representing a model of storage
    * @return 	array   containing properties
    */
    public static function deserialize(
        $resource,
        $storage_model = STORE_YAML
    )
    {
        // switch from the storage model
        switch ($storage_model)
        {
            case STORE_YAML:

                // translate the resource argument into a php array
                $output = self::translate(
                    $resource,
                    ENTITY_PHP_VARIABLE,
                    ENTITY_YAML
                );

                    break;
        }

        // return the output
        return $output;
    }

    /**
    * Save
    *
    * @param	string      $contents  		containing contents
    * @param	integer		$storage_model 	representing a model of storage
    * @param    string      $file  			containing a file name
    * @param    string    	$access_mode  	containing a file access mode
    * @return 	nothing    
    */
    public static function save(
        $contents,
        $storage_model = STORE_YAML,
        $file = null,
        $access_mode = FILE_ACCESS_MODE_APPEND
    )
    {
        // check the contents
        if (!is_string($contents))

            throw new Exception(EXCEPTION_INVALID_CONTENTS);

        // check the file arguments
        if (!empty($file) && isset($access_mode))
        {
            if (!file_exists($file))
            {
                // get parent folders
                $folders = explode(CHARACTER_SLASH, $file);

                // unset the file
                unset($folders[count($folders) -1]);

                // set the path to the parent directory 
                $parent_directory = implode(CHARACTER_SLASH, $folders);

                // check the parent directory
                if (!file_exists($parent_directory))

                    // throw an exception
                    throw new Exception(EXCEPTION_INVALID_FILE_PATH);
            }

            // check the file type            
            else if (is_dir($file))

                    // throw an exception
                    throw Exception(EXCEPTION_INVALID_FILE_TYPE);

                // open the file
                $handle = fopen($file, $access_mode);

            // check the file access mode and the file size
            if (
                $access_mode == FILE_ACCESS_MODE_APPEND && 
                filesize($file) != 0
                )

                    // set the contents
                    $contents = CHARACTER_NEW_LINE.$contents;
        
            // check the file access mode
            else if (
                $access_mode != FILE_ACCESS_MODE_OVERWRITE &&
                $access_mode != FILE_ACCESS_MODE_WRITE
            )

                // throw an exception
                throw new Exception(EXCEPTION_INVALID_FILE_ACCESS_MODE);            

            // write YAML contents in the file
            fwrite($handle, $contents);

            // close the file
            fclose($handle);
        }
    }

    /**
    * Serialize
    *
    * @param    array       $array   		containing properties
    * @param    integer     $inline 		representing the folding depth
    * @param    integer		$storage_model 	representing a model of storage
    * @param    string      $file  			containing a file name
    * @param    string    	$access_mode  	containing a file access mode
    * @return 	nothing    
    */
    public static function serialize(
        $array,
        $inline = PROPERTY_YAML_FOLDING_DEPTH,
        $storage_model = null,
        $file = null,
        $access_mode = FILE_ACCESS_MODE_APPEND
    )
    {
        try {
            // construct a YAML object
            return new self($array, $inline, $storage_model, $file, $access_mode);
        }
        catch (Exception $exception)
        {
            // check the access mode argument
            if (
                $exception->getMessage() == EXCEPTION_INVALID_FILE_ACCESS_MODE &&
                $access_mode == FILE_ACCESS_MODE_APPEND
            )

                try {
                    // serialize with a different file access mode
                    self::serialize($array, $inline, $storage_model, $file, FILE_ACCESS_MODE_WRITE);
                }
                catch (Exception $nested_exception)
                {
                    throw $nested_exception;
                }
        }
    }

    /**
    * Translate an array into a YAML string
    *
    * @param    resource   $resource 	containing properties
    * @param	string 	   $to 			containing an output entity
    * @param	string	   $from		containing an input entity
    * @param    integer    $inline 		representing the folding depth
    * @return   string    containing a YAML string
    */	    
    public static function translate(
        $resource,
        $to = ENTITY_YAML,
        $from = ENTITY_PHP_VARIABLE,
        $inline = PROPERTY_YAML_FOLDING_DEPTH        
    )
    {
        // set the default YAML contents
        $output = CHARACTER_EMPTY_STRING;

        // switch from the input entity
        switch ($from)
        {
            case ENTITY_PHP_VARIABLE:

                // switch from the output entity
                switch ($to)
                {
                    case ENTITY_YAML:

                        // check the array
                        if (
                            is_array($resource) &&
                            count($resource) != 0
                        )
                    
                            // dump an array into a variable in the YAML format
                            $output = yaml::dump($resource, $inline);

                        break;
                }

                    break;

            case ENTITY_YAML:

                // switch from the output entity
                switch ($to)
                {
                    case ENTITY_PHP_VARIABLE:

                        // try to load a file
                        try
                        {
                          $output = yaml::load(file_get_contents($resource));
                        }
                        catch (InvalidArgumentException $exception)
                        {
                            $dumper = new dumper(						
                                __CLASS__,
                                __METHOD__,
                                array(
                                    'An exception has been caught while calling y a m l :: l o a d =>',
                                    $exception
                                ),
                                DEBUGGING_YAML_PARSING,
                                AFFORDANCE_CATCH_EXCEPTION
                            );
                        }

                        break;
                }

                    break;
        }

        // return the output
        return $output;
    }
}