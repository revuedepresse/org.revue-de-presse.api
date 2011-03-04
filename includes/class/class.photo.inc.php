<?php

/**
* Photo class
*
* Class for photo properties edition
* @package  sefi
*/
class Photo extends Toolbox
{
    private $_author;
    private $_date_creation;
    private $_date_last_modification;
    private $_dimensions;
    private $_hash;
    private $_keywords;
    private $_location;
    private $_mime_type;
    private $_original_file_name;
    private $_photo_id;
    private $_size;
    private $_status;
    private $_title;

    /**
    * Construct a Photo instance
    * @param    integer $id representing a Photo instance
    * 
    * @return   object  representing a Photo instance
    */	    
    public function __construct($id = -1)
    {
        if (is_integer($id))
    
            $this->_photo_id = $id;
        else
    
            throw new Exception("Data type error: a Photo id has to be passed as an integer.");
    }

    /**
    * Get the Author instance of a Photo instance
    * 
    * @return   object  representing an Author instance
    */	        
    public function getAuthor() {
        return $this->_author;
    }
        
    /**
    * Get the date of creation of a Photo instance
    * 
    * @return   string    date
    */	        
    public function getCreationDate() {
        return $this->_date_creation;    
    }

    /**
    * Get the date of last modification of a Photo instance
    * 
    * @return   string    date
    */	        
    public function getLastModificationDate() {
        return $this->_date_last_modification;    
    }

    /**
    * Get the dimensions of a Photo instance
    * 
    * @return   array   containing dimensions
    */	        
    public function getDimensions() {
        return $this->_dimensions;    
    }

    /**
    * Get the Location instance of a Photo instance
    * 
    * @return   object  representing a Location instance
    */	        
    public function getLocation() {
        return $this->_location;
    }

    /**
    * Get the Photo id of a Photo instanace    
    * 
    * @return   integer representing a Photo instance
    */	        
    public function getId() {
        return $this->_photo_id;
    }    

    /**
    * Get the hash of a Photo instance
    * 
    * @return   string  containing the hash of a Photo instance
    */	        
    public function getHash() {
        return $this->_hash;
    }
    
    /**
    * Get the height of a Photo instance
    * 
    * @return   integer representing the height of a Photo instance
    */	        
    public function getHeight() {
        return $this->_dimensions['height'];
    }

    /**
    * Get the keywords of a Photo instance
    * 
    * @return   string  containing keywords
    */	        
    public function getKeywords() {
        return $this->_keywords;
    }

    /**
    * Get the mime type of a Photo instance
    * 
    * @return   string  representing a mime type
    */	        
    public function getMimeType() {
        return $this->_mime_type;
    }

    /**
    * Get the status
    * 
    * @return   integer photo status
    */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
    * Get the title of a Photo instance
    * 
    * @return   string  containing a title
    */	        
    public function getTitle() {
        return $this->_title;
    }

    /**
    * Get the original file name of a Photo instance
    * 
    * @return   string  containing a file name
    */	        
    public function getOriginalFileName() {
        return $this->_original_file_name;
    }
    
    /**
    * Get the size 
    * 
    * @return integer
    */	        
    public function getSize() {
        return $this->_size;
    }
    
    /**
    * Get the photo width
    * 
    * @return integer
    */	        
    public function getWidth() {
        return $this->_dimensions['width'];    
    }

    /**
    * Set the Author instance of a Photo instance
    * 
    * @param    object $author  representing an Author instance
    * @return   nothing
    */	        
    public function setAuthor($author) {
        if (is_object($author) && get_class($author) == CLASS_AUTHOR) {
            $this->_author = $author;
        } else
            throw new Exception('an Author instance has to be passed as an object');
    }
    
    /**
    * Set the date of creation of a Photo instance
    * 
    * @param    string   $date  date of creation
    * @return   nothing
    */	        
    public function setCreationDate($date) {
        if (is_string($date)) {
            $this->_date_creation = $date;
        } else
            throw new Exception('a date of creation has to be passed as a string');
    }

    /**
    * Set the dimensions of a Photo instance
    * 
    * @param    array $dimensions   containing dimensions
    * @return   nothing
    */	        
    public function setDimensions($dimensions) {
        $exceptions = null;

        if (is_array($dimensions)) {
            try {
                $this->setHeight($dimensions[1]);
            } catch (Exception $setting_exception) {
                $exceptions .= $setting_exception;
            }
            
            try {
                $this->setWidth($dimensions[0]);
            } catch (Exception $setting_exception) {
                $exceptions .= $setting_exception;
            }
        } else
            throw new Exception('the dimensions of a Photo instance have to be passed as an array');
    }
    
    /**
    * Set the photo hash
    * 
    * @param    string   $hash  containing a hash
    * @return   nothing
    */	
    public function setHash($hash = null) {
        if (!$hash) {
            $chars = "abcdefghijkmnopqrstuvwxyz023456789";
            srand((double)microtime()*1000000);
            $i = 0;
            $hash = '' ;
            
            while ($i <= 33) {
                    $num = rand() % 33;
                    $tmp = substr($chars, $num, 1);
                    $hash = $hash.$tmp;
                    $i++;
            }
        } 
            
        $this->_hash = $hash;
    }
        
    /**
    * Set the height of a photo
    * 
    * @param    integer    $height	    height
    * @return nothing
    */	    
    public function setHeight($height) {
        if (is_integer($height))
            $this->_dimensions['height'] = $height;
        else
            throw new Exception("Data type error: the height of a Photo instance has to be an integer.");
    }

    /**
    * Set the Photo id of a Photo instance
    * 
    * @param    integer $id representing a Photo instance
    * @return   nothing
    */	        
    public function setId($id) { 
        if (is_integer($id)) {
            $this->_photo_id = $id;
        } else
            throw new Exception('an Photo id has to be passed as an integer');
    }
    
    /**
    * Set the keywords of a Photo instance
    * 
    * @param    string   $keywords  containing keywords
    * @returns  nothing
    */	        
    public function setKeywords($keywords) {
        if (is_string($keywords))
            $this->_keywords = $keywords;
        else
            throw new Exception("Data type error: keywords have to passed as an string.");
    }
    
    /**
    * Set the date of last modification of a Photo instance
    * 
    * @param    string   $date  date of last modification
    * @return   nothing
    */	        
    public function setLastModificationDate($date) {
        if (is_string($date)) {
            $this->_date_last_modification = $date;
        } else
            throw new Exception('a date of modification has to be passed as a string');
    }

    /**
    * Set the Location instance of a Photo instance
    * @param    object   $location  representing a Location instance
    * 
    * @return   nothing
    */	        
    public function setLocation($location) {
        if (is_object($location) && get_class($location) == CLASS_LOCATION) {
            $this->_location = $location;
        } else
            throw new Exception('a Location instance has to be passed as an object');
    }
        
    /**
    * Set the mime type of a Photo instance
    * @param    stringg  $mime_type containing a mime type
    * 
    * @return   nothing
    */	    
    public function setMimeType($mime_type) {
        if (is_string($mime_type))
            $this->_mime_type = $mime_type;
        else
            throw new Exception('Data type error: a mime type has to be passed as a string');                 
    }    

    /**
    * Set the status of a photograph
    * 
    * @param    integer    $status  status
    * @return   nothing
    */	    
    public function setStatus($status)
    {
        if (is_integer($status))

            $this->_status = $status;
        else

            throw new Exception("Data type error: the statis of a Photo instance has to be an integer.");
    }

    /**
    * Set the title of a Photo instance
    * @param    string  $title  containing a title
    * 
    * @return   nothing
    */	    
    public function setTitle($title) {
        if (is_string($title))
            $this->_title = $title;
        else
            throw new Exception("Data type error: a title has to be passed as a string.");
    }

    /**
    * Set the original file name of a Photo instance
    * 
    * @param    string     $original_file_name    containing a file name
    * @return   nothing
    */	    
    public function setOriginalFileName($original_file_name) {
        if (is_string($original_file_name))
            $this->_original_file_name = $original_file_name;
        else
            throw new Exception("Data type error: a file name has to be passed as a string.");
    }
    
    /**
    * Set the size of a photo
    * 
    * @param    integer    $size	the size
    * @return nothing
    */	    
    public function setSize($size) {
        if (is_integer($size))
            $this->_size = $size;
        else
            throw new Exception("Data type error: the size of a Photo instance has to be an integer.");
    }
    
    /**
    * Set the size of a photo
    * 
    * @param    integer  $width	the size
    * @return nothing
    */	    
    public function setWidth($width) {
        if (is_integer($width))
            $this->_dimensions['width'] = $width;
        else
            throw new Exception("Data type error: the width of a Photo instance has to be an integer.");
    }
    
    /**
    * Generate a hash
    * 
    * @param    integer    $length              multiplier of a number of character
    * @param    integer    $character_count     character count
    * @return   string      containing a hash code
    */	
    public static function createHash($length = 1,$character_count = 13) {
            if ($length == 1) {
                $chars = "abcdefghijkmnopqrstuvwxyz023456789";
                srand((double)microtime()*1000000);
                $i = 0;
                $hash = '';
                
                $max = $character_count - 1;
                
                while ($i <= $max) {
                        $num = rand() % 33;
                        $tmp = substr($chars, $num, 1);
                        $hash = $hash . $tmp;
                        $i++;
                }
            } else
                while ($length > 0) {
                    $hash .= self::createHash(1,$character_count);
                    $length--;
                }
            
            return $hash;
    }
    
    private function insert() {
        $database_connection = new Database_Connection();
        
        $insert_photo = '
            INSERT INTO `'.TABLE_PHOTOGRAPH.'` (
                author_id,
                hash,
                height,
                keywords,
                mime_type,
                original_file_name,
                size,
                title,
                width
            ) VALUES (
                '.$this->_author->getId().',
                "'.$this->_hash.'",
                '.$this->_dimensions['height'].',
                "'.$this->_keywords.'",
                "'.$this->_mime_type.'",
                "'.$this->_original_file_name.'",
                '.$this->_size.',
                "'.$this->_title.'",
                '.$this->_dimensions['width'].'
            )';

        $retrieve_photo = '
            SELECT
                photo_id
            FROM
                `'.TABLE_PHOTOGRAPH.'`
            WHERE
                author_id = '.$this->_author->getId().' AND
                hash = "'.$this->_hash.'" AND
                height = '.$this->_dimensions['height'].' AND
                keywords = "'.$this->_keywords.'" AND
                mime_type = "'.$this->_mime_type.'" AND
                original_file_name =  "'.$this->_original_file_name.'" AND
                size = '.$this->_size.' AND
                title = "'.$this->_title.'" AND
                width = '.$this->_dimensions['width']
        ;

        $inserting_result = $database_connection->executeQuery($insert_photo,false);

        if (!$inserting_result)
            throw new Exception('warning: an error occured while inserting data into the table'.TABLE_PHOTOGRAPH);
        else {            
            $retrieving_result = $database_connection->executeQuery($retrieve_photo,true);

            if (!$retrieving_result)
                throw new Exception('warning: an error occurent while retrieving data from the table'.TABLE_PHOTOGRAPH);
            else
                foreach ($retrieving_result as $result) {
                    $this->setId((int)$result['photo_id']);
                } 
        }
    }

    /**
    * Save a Photo instance
    *
    * @return  nothing
    */
    public function save() {
        $exceptions = null;        
        
        if ($this->_photo_id == -1)
            try {
                $this->insert();
            } catch (Exception $inserting_exception) {
                $exceptions .= $inserting_exception;
            }            
        else
            try {
                $this->update();
            } catch (Exception $updating_exception) {
                $exceptions .= $updating_exception;
            }
    }

    /**
    * Store a file content into a database
    *
    * @return  nothing
    */    
	public function storeFileContent()
    {
        global $class_application, $verbose_mode;
		
        $class_db = $class_application::getDbClass();;

        $class_dumper = $class_application::getDumperClass();

        // construct a new instance of the database connection
	    $database_connection = new $class_db();

        // set the snapshots directory
        $dir_snapshots = dirname(__FILE__)."/../../".DIR_SNAPSHOTS;

        list( $file_name_rewritten ) =
            self::rewriteFileName( $this->getOriginalFileName() )
        ;

        // set the path to the original file
		$path =
            DIRNAME_SNAPSHOTS.
                PUNCTUATION_SLASH.
                    $this->getHash().
                        $file_name_rewritten;
        ;

        // set the original image height
        $height = $this->getHeight();

        // set the original image width
        $width = $this->getWidth();

        // set the image status
        $status = $this->getStatus();

        // set the long edge
        $long_edge = $height > $width ?  $height : $width;

        $maximum_long_edge = (
                $status == PHOTOGRAPH_STATUS_AVATAR
            ?
                DIMENSION_MAXIMUM_AVATAR_LONG_EDGE
            :
                DIMENSION_MAXIMUM_LONG_EDGE
        );

        // set the ratio
        $ratio = $long_edge / $maximum_long_edge;    

        $_height = $height;

        $_width = $width;

        // check the ratio
        if ( $ratio != 0 )
        {
            // check the portrait flag 
            if ( $height > $width )
            {
                // set the ratio and size
                $size = $ratio = $height / $width;

                // set the height	
                $height = $maximum_long_edge * $ratio;

                // set the width
                $width = $maximum_long_edge;
            }
            else
            {
                // set the size
                $size = $long_edge / $height;

                // set the new height
                $height =  $height / $ratio;

                // set the new width
                $width = $maximum_long_edge; 
            }
        }
        else
        
            // jump to the root index
            self::jumpTo( PREFIX_ROOT );

        // switch from the size
        switch ($size)
        {
            case 2/3:				
            case 3/2:

                if ($size > 1)

                    $proportions = "3x2";
                else 

                    $proportions = "2x3";

                    break;

            case 3/4:
            case 4/3:

                if ($size > 1)

                    $proportions = "3x4";
                else 

                    $proportions = "3x4";

                    break;

            case 4/5:
            case 5/4:

                if ($size > 1)

                    $proportions = "5x4";
                else

                    $proportions = "4x5";

                    break;

            case 9/16:	
            case 16/9:

                if ($size > 1)

                    $proportions = "16x9";
                else

                    $proportions = "9x16";

                    break;
            
            case 1:

                $proportions = "1x1";

                    break;
            
            default:

                // round the size
                if (
                    round( $size, 2 ) == round( 3/2, 2 ) ||
                    round( $size, 1 ) == round( 3/2, 1 )
                )

                    $proportions = "3x2";

                else if (
                    round( $size, 2 ) == round( 2/3, 2 ) ||
                    round( $size, 1 ) == round( 2/3, 1 )
                )

                    $proportions = "2x3";

                else if (
                    round( $size, 2 ) == round( 4/3, 2 ) ||
                    round( $size, 1 ) == round( 4/3, 1 )
                )

                    $proportions = "4x3";
                
                else if (
                    round( $size, 2 ) == round( 4/5, 2 ) ||
                    round( $size, 1 ) == round( 4/5, 1 )
                )

                    $proportions = "4x5";

                else if (
                    round( $size, 2 ) == round( 5/4, 2 ) ||
                    round( $size, 1 ) == round( 5/4, 1 )
                )

                    $proportions = "5x4";

                else if (
                    round( $size, 2 ) == round( 16/9, 2 ) ||
                    round( $size, 1 ) == round( 16/9, 1 )
                )

                    $proportions = "16x9";

                else if (
                    round( $size, 2 ) == round( 9/16, 2 ) ||
                    round( $size, 1 ) == round( 9/16, 1 )
                )

                    $proportions = "9x16";
        }

        $directory_proportions =
            $dir_snapshots."/".$maximum_long_edge."_".$proportions."/"
        ;

        if ( ! file_exists( $directory_proportions ) )
        
            mkdir( $directory_proportions, 0755, TRUE );

        // set a path to a resized photograph
        $path_resized_photograph =
            $directory_proportions.
                $this->getId()
        ;

        // check if the file exists
        if ( file_exists( $path_resized_photograph ) )

            // return a file content
            return file_get_contents( $path_resized_photograph );
        else
        {
            $retrieve_file = '
                SELECT
                    photo_id,
                    bytes
                FROM
                    `'.TABLE_PHOTOGRAPH.'`
                WHERE
                    photo_id = '.$this->getId()
            ;

            $retrieving_result = $database_connection->executeQuery($retrieve_file, true);
    
            // check the binary contents stored in database to proceed with an update if necessary
            if ( empty( $retrieving_result[0]->bytes ) )
            {
                if ( file_exists( $path ) )
                {
                    $file = base64_encode(
                        file_get_contents( $path, FILE_BINARY )
                    );
    
                    $update_file = '
                        UPDATE `'.TABLE_PHOTOGRAPH.'` SET 
                            `bytes` = "'.$file.'" 
                        WHERE 
                            `photo_id` = '.$this->getId()
                        ;
            
                    $inserting_result = $database_connection->executeQuery(
                        $update_file, FALSE
                    );	
        
                    if ( ! $inserting_result )
            
                        throw new Exception(
                            'warning: an error occured '.
                            'while inserting data into the table'.
                            TABLE_PHOTOGRAPH
                        );
                    else 
        
                        $retrieving_result = $database_connection->executeQuery(
                            $retrieve_file,
                            TRUE
                        );
                }
            }
 
            // loop on result
            foreach ( $retrieving_result as $result )
            {
                if ( '' !== base64_decode( $result->bytes ) )
                {
                    $image = '';

                    $db_img = imagecreatefromstring(
                        base64_decode( $result->bytes )
                    );
        
                    $target = imagecreatetruecolor(
                        $width,
                        $height
                    );
                    
                    if ( file_exists( $path ) )
                    {
                        $source = imagecreatefromjpeg( $path );
    
                        imagecopyresampled(
                            $target,
                            $source,
                            0,
                            0,
                            0,
                            0,
                            floor( $width ),
                            floor( $height ),
                            $_width,
                            $_height
                        );
                        
                        // save the image to the file system
                        imagejpeg(
                            $target,
                            $path_resized_photograph,
                            IMAGE_JPEG_QUALITY
                        );
    
                        if ( file_exists( $path_resized_photograph ) )
    
                            $image = file_get_contents($path_resized_photograph);
                        else 
                        {
                            ob_start();
                            imagejpeg( $db_img, NULL, IMAGE_JPEG_QUALITY );
                            $image = ob_get_contents();
                            ob_end_clean();
                        }
                    }
                    else
                    
                        throw new Exception(
                            EXCEPTION_MISSING_RESOURCE .
                            ' ( ' . $path . ')'
                        );

                    return $image;
                }
                else
                {
                    $update_file = '
                        UPDATE `'.TABLE_PHOTOGRAPH.'` SET 
                            `pht_status` = "'.PHOTOGRAPH_STATUS_DISABLED.'" 
                        WHERE 
                            `photo_id` = '.$result->photo_id
                    ;

                    // get a link
                    $link = $class_db::getLink();

                    // prepare a statement
                    $statement = $link->prepare($update_file);

                    // execute a statement
                    $execution_result = $statement->execute();

                    $handler = fopen(
                        dirname(__FILE__).
                        DIR_PARENT_DIRECTORY.
                        DIR_PARENT_DIRECTORY."/".
                        DIR_ADMIN."/".
                        DIR_LOGS."/".
                        "logs_".date('Y-m-d').
                        EXTENSION_LOG,
                        "a+"
                    );
                    fwrite(
                        $handler, "issues encountered with the following photograph on the ".date('Y-m-d h:i').":".
                        " \n\t#".$result->photo_id."\n\n"
                    );
                    fclose($handler);
                }
            }
        }
	}

    /**
    * Display a photograph
    * 
    * @param    integer $id     identifier
    * @param    integer $avatar avatar size
    * @return   mixed
    */
    public static function displayPhotography($id, $avatar = FALSE)
    {
        echo
            call_user_func_array(
                array(
                    __CLASS__,
                    'loadPhotography'
                ),
                func_get_args()
            )
        ;
    }

    /**
    * Load a photograph
    * 
    * @param    integer $id     identifier
    * @param    integer $avatar avatar size
    * @return   mixed
    */
    public static function loadPhotography($id, $avatar = FALSE)
    {
        global $class_application;

        $class_data_fetcher = $class_application::getDataFetcherClass();

        $dimensions = new stdClass();

        $attributes = $class_data_fetcher::fetchPhotograph($id);

        if ( is_object( $attributes ) )
        {
            $dimensions->{PROPERTY_HEIGHT} = $attributes->getHeight();
        
            $dimensions->{PROPERTY_WIDTH} = $attributes->getWidth();
        }
        else
        {
            $dimensions->{PROPERTY_HEIGTH} = 

            $dimensions->{PROPERTY_WIDTH} = 0;
        }
            
        return $class_application::fetchPhotograph($id, $dimensions, $avatar);
    }
 
    /**
    * Load an upload photo form
    * @param    array   $errors         containing errors
    * @param    array   $posted_values  containing valid attributes
    *
    * @return   nothing
    */
    public static function loadUploadForm($errors = NULL,$posted_values = NULL)
    {
        global $class_application, $errors, $lang, $verbose_mode;

        // set the template engine class name
		$class_dumper = $class_application::getDumperClass();

        // set the template engine class name
		$class_template_engine = $class_application::getTemplateEngineClass();
        
        try {
            $authors = Author::getAuthors();
        } catch (Exception $retrieving_exception) {
            $exceptions .= $retrieving_exception;
        }

        if (count($authors) != null)

            foreach ($authors as $author)

                $attributes[$author->getId()] = $author->toArray();

        $template = new Smarty_SEFI();

        $template->assign("action_generate_upload_form", SCRIPT_LOAD_PHOTO_UPLOAD_FORM);
        $template->assign("legend_select_album_cardinal",$lang['legend']['form_select_album_cardinal']);        
        $template->assign("notes_select_album_cardinal",$lang['notes']['form_select_album_cardinal']);
        $template->assign("title_select_album_cardinal",$lang['title']['form_select_album_cardinal']);
        $template->assign("title_notes_select_album_cardinal",$lang['title_notes']['form_select_album_cardinal']);

        $template->assign("album_cardinal",$lang['album_settings']['album_cardinal']);

        $template->assign("action", SCRIPT_SAVE_PHOTOGRAPH);        
        $template->assign("legend",$lang['legend']['form_upload_photos']);
        $template->assign("notes",$lang['notes']['form_upload_photos']);
        $template->assign("title",$lang['title']['form_upload_photos']);
        $template->assign("title_notes",$lang['title_notes']['form_upload_photos']);

        $album_cardinal = 5;

        if ( isset( $_POST['album_cardinal'] ) )

            $album_cardinal = $_POST['album_cardinal'];

        $form_count = 0;

        if ( isset( $_POST['form_count'] ) )

            $album_cardinal = $_POST['form_count'];

        $forms = array();
    
        while ( $form_count < $album_cardinal )
        {
            $forms[$form_count] = array();
            
            $form_count++;
        }

        $template->assign("album_cardinal_value", $album_cardinal );

        if ( isset( $errors ) )

            foreach ($errors as $form_index => $error)
            { 
                if (isset($error[ERROR_AUTHOR]) && $error[ERROR_AUTHOR])

                    $forms[(int)$form_index]['exception_author'] = $errors['author'];

                if (isset($error[ERROR_KEYWORDS]) && $error[ERROR_KEYWORDS])

                    $forms[(int)$form_index]['exception_keywords'] = $errors['keywords'];

                if (isset($error[ERROR_TITLE]) && $error[ERROR_TITLE])

                    $forms[(int)$form_index]['exception_title'] = $errors['title'];

                if ( !isset( $forms[(int)$form_index]['validity'] ) )
                
                    $forms[(int)$form_index]['validity'] = TRUE;
            }

        if ( isset( $posted_values ) )
        { 
            $valid_attributes = array();

            foreach ($posted_values as $form_index => $attribute) { 
                if (isset($attribute[ERROR_AUTHOR]) && $attribute[ERROR_AUTHOR])
                    $valid_attributes['author'] = $attribute[ERROR_AUTHOR];
                    
                if (isset($attribute[ERROR_KEYWORDS]) && $attribute[ERROR_KEYWORDS])
                    $valid_attributes[(int)$form_index]['keywords'] = $attribute[ERROR_KEYWORDS];

                if (isset($attribute[ERROR_TITLE]) && $attribute[ERROR_TITLE])
                    $valid_attributes[(int)$form_index]['title'] = $attribute[ERROR_TITLE];
            }

            $template->assign("attributes", $valid_attributes);
        }

        $template->assign("forms",$forms);
        
        $template->assign("title_notes",$lang['title_notes']['form_upload_photos']);
        
        $template->assign("authors", $attributes);
        
        $template->assign("fields",$lang['photo_fields']);
        
        $template->assign("label_browse",$lang['button_labels']['selection']['label_value']);
    
        $template->display(TPL_FORM_UPLOAD_PHOTOS_TOP);

        $template->display(TPL_FIELDS_UPLOAD_PHOTOS_FORM);        

        $template->display(TPL_FORM_UPLOAD_PHOTOS_BOTTOM);
    }

    /**
    * Rewrite a file name
    * @param   string  $name   name
    * @return  string  rewritten name
    */
    public static function rewriteFileName($name)
    {
        global $class_application;

        // set the Toolbox class name
        $class_toolbox = $class_application::getToolboxClass();
    
        return $class_toolbox::rewriteFileName($name);
    }

    /**
    * Serialize a photograph
    * 
    * @param    array   $properties properties
    * @return   integer insertion id
    */
    public static function serialize($properties)
    {
		global $class_application, $verbose_mode;

        $class_dumper = $class_application::getDumperClass();

        $class_photo = $class_application::getPhotoClass();

        $class_serializer = $class_application::getSerializerClass();

        try
        {
            return $class_serializer::save( $properties, $class_photo );
        }
        catch ( Exception $exception )
        {
            $class_dumper::log(						
                __METHOD__,
                array(
                    $exception
                ),
                DEBUGGING_DISPLAY_EXCEPTION,
                AFFORDANCE_CATCH_EXCEPTION
            );
        }
    }
}
