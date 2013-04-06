<?php

/**
* Diaporama class
*
* Class for handling a diaporama
* @package  sefi
*/
class Diaporama extends Photo
{
    private $_id;
    private $_photos;
    private $_display_mode;

    /**
    * Construct a new diaporama
    * 
    * @param    integer	    $id	    diaporama identifier 
    * @return   object
    */	    
    public function __construct($id = -1)
    {
        if (is_integer($id))
    
            $this->_id = $id;
        else
    
            throw new Exception("Data type error: a diaporama id has to be an integer.");
    }

    /**
    * Get the display mode 
    * 
    * @return integer
    */	        
    public function getDisplayMode() {
        return $this->_display_mode;
    }
    
    /**
    * Get the diaporama id 
    * 
    * @return integer
    */	        
    public function getId() {
        return $this->_id;
    }    
    
    /**
    * Get the collection of photos
    * 
    * @return array
    */	        
    public function getPhotos() {
        return $this->_photos;
    }

    /**
    * Set the display mode
    * 
    * @param    integer	$displayMode	    a display mode 
    * @return nothing
    */	        
    public function setDisplayMode($displayMode) {
        if (is_integer($displayMode))
            $this->_display_mode = $displayMode;
        else
            throw new Exception("Data type error: a display mode has to be an integer.");
    }

    /**
    * Set the photos to be loaded by the diaporama
    * 
    * @param    array     $photos	collection of photos
    * @return	nothing
    */	    
    public function setPhotos($photos) {
        if (is_array($photos))
            $this->_photos= $photos;
        else
            throw new Exception("Data type error: a range of photos has to be passed as an array.");
    }

    /**
    * Check all fields except from the upload field
    * 
    * @return	integer	representing an error code
    */
    public static function checkFields()
    {
        global $class_application;
        
        $class_dumper = $class_application::getDumperClass();

        $attributes = array();
        $errors = array();
        
        $form_index = 0;

        while ( $form_index < $_POST[POST_FORM_COUNT] ) {
            $errors[(int)$form_index] = array(); 

            if (!$form_index && $_POST[POST_AUTHOR] == '_blank')
                $errors[(int)$form_index][ERROR_AUTHOR] = true;
            else if (!$form_index)
                $attributes[(int)$form_index][ERROR_AUTHOR] = $_POST[POST_AUTHOR];

            if (!$_POST[POST_KEYWORDS.PUNCTUATION_UNDERSCORE.$form_index])
                $errors[(int)$form_index][ERROR_KEYWORDS] = true;
            else
                $attributes[(int)$form_index][ERROR_KEYWORDS] = $_POST[POST_KEYWORDS.PUNCTUATION_UNDERSCORE.$form_index];            

            if (!$_POST[POST_TITLE.PUNCTUATION_UNDERSCORE.$form_index])
                $errors[(int)$form_index][ERROR_TITLE] = true;
            else
                $attributes[(int)$form_index][ERROR_TITLE] = $_POST[POST_TITLE.PUNCTUATION_UNDERSCORE.$form_index];            
            
            $form_index++;
        }

        return array(
            'attributes' => $attributes,
            'errors' => $errors
        );
    }
    
    /**
    * Check a collection of uploaded file 
    * 
    * @return array    $collectionReview
    */	       
    public static function checkUploadedFiles() {
        $exceptions = null;
        $collection_review = array();

        $form_index = 0;

        while ($form_index < $_POST[POST_FORM_COUNT]) {
            if (isset($_FILES)) {
                
                if ($_FILES["file_".$form_index]["size"] > 15000000)
                    throw new Exception("warning: the file size exceeds the limit");
                
                $file_review[(int)$form_index] = array();

                $file_review[(int)$form_index]["file_name"] = $_FILES["file_".$form_index]["name"]; 
                $file_review[(int)$form_index]["file_path"] = $_FILES["file_".$form_index]["tmp_name"];
                $file_review[(int)$form_index]["mime_type"] = $_FILES["file_".$form_index]["type"];
                $file_review[(int)$form_index]["file_size"] = $_FILES["file_".$form_index]["size"];
                $file_review[(int)$form_index]["error"] = $_FILES["file_".$form_index]["error"];
            }
            
            $form_index++; 
        }

        return $file_review;        
    }
    
    /**
    * Save a collection of just uploaded files
    * 
    * @return	nothing
    */	 
    public static function saveUploadedFiles() {
        $exceptions = null;
        $directory = dirname(__FILE__).PUNCTUATION_SLASH.DIRNAME_SNAPSHOTS.PUNCTUATION_SLASH;
        $count_invalid_forms = 0;
        $form_index = 0;

        $results = self::checkFields();

		foreach ($results['errors'] as $error)
			if (isset($error[ERROR_AUTHOR]) || isset($error[ERROR_TITLE]) || isset($error[ERROR_KEYWORDS]))
				$count_invalid_forms++;

        if ($count_invalid_forms)        
            Photo::loadUploadForm($results['errors'],$results['attributes']);

        try {
            $file_review = self::checkUploadedFiles();
        } catch (Exception $checking_exception) {
            $exceptions .= $checking_exception;
        }

        while ($form_index < $_POST[POST_FORM_COUNT]) {
            $hash = Photo::createHash();
        
            $newname = $directory.$hash.trim($file_review[$form_index]["file_name"]);
            
            if (!$file_review[$form_index]["error"] && move_uploaded_file($file_review[$form_index]["file_path"],$newname)) {
                $photo = new Photo();
                
                $photo_dimensions = getimagesize($newname);
                
                try {
                    $photo->setDimensions($photo_dimensions);
                } catch (Exception $setting_exception) {
                    $exceptions .= $setting_exception;
                }
                
                try {
                    $photo->setHash($hash);
                } catch (Exception $setting_exception) {
                    $exceptions .= $setting_exception;
                }
    
                try {
                    $photo->setKeywords($_POST["keywords_".$form_index]);
                } catch (Exception $setting_exception) {
                    $exceptions .= $setting_exception;
                }
                
                try {
                    $photo->setSize($file_review[$form_index]["file_size"]);                    
                } catch (Exception $setting_exception) {
                    $exceptions .= $setting_exception;
                }
                
                try {
                    $photo->setMimeType($file_review[$form_index]["mime_type"]);
                } catch (Exception $setting_exception) {
                    $exceptions .= $setting_exception;
                }
                
                try {
                    $photo->setAuthor(Author::getById($_POST["author"]));
                } catch (Exception $setting_exception) {
                    $exceptions .= $setting_exception;
                }
                
                try {
                    $photo->setTitle($_POST["title_".$form_index]);
                } catch (Exception $setting_exception) {
                    $exceptions .= $setting_exception;
                }
                
                try {
                    $photo->setOriginalFileName($file_review[$form_index]["file_name"]);
                } catch (Exception $setting_exception) {
                    $exceptions .= $setting_exception;
                }
                
                $photo->save();
            } else if (!$count_invalid_forms) {
                        echo "<br />
                        Un téléchargement s'est pas déroulé correctement :( <br />
                        Réitérez svp l'opération ou contactez le webmaster (<a href='mailto:thierry.marianne@aporos.org'>thierry.marianne@aporos.org</a>).<br />";
            } else if (isset($photo) && is_object($photo) && !$photo->getId())
                die("une erreur s'est produite durant le téléchargement d'une des images");

            $form_index++;                
        }    

        if (!$count_invalid_forms && $form_index == $_POST[POST_FORM_COUNT]) 
            header('Location:'.HOSTNAME_TIFA.SCRIPT_DISPLAY_ACKNOWLEDGMENT.'?'.GET_ACKNOWLEDGMENT.'='.RESPONSE_PHOTOS_SAVED);            
    }

    /**
    * Load a diaporama
    * 
    * @param	integer	$scale	representing a scale ratio 
    * @return	nothing
    */	 
    public static function loadGrid( $scale )
    {
        global $class_application, $errors, $lang;

        $class_dumper = $class_application::getDumperClass();

        $class_template_engine = $class_application::getTemplateEngineClass();

        $exceptions = '';

		$smarty_photos = array();

        $template = new $class_template_engine();

        if ( ! empty($lang['title']['diaporama']['grande_chaloupe'] ) )

            $template->assign("title", $lang['title']['diaporama']['grande_chaloupe']);		

		try
        {
            $authors = Author::getAuthors();
        }
        catch ( Exception $retrieving_exception )
        {
            $exceptions .= $retrieving_exception;
        }

        if ( count( $authors ) )

            foreach ( $authors as $author )
            {
				$author_id = $author->getId();

                if ( $author_id === 1 )
                {
                    $smarty_photos[(int)$author_id]	= array();

                    try
                    {
                        $photos = self::loadPhotosByAuthorId( $author_id );
                    }
                    catch ( Exception $retrieving_exception )
                    {
                        $exceptions .= $retrieving_exception;
                    }
                    
                    if ( count( $photos ) )
    
                        while ( list( $photo_id ) = each( $photos ) )
    
                            if (  $photos[$photo_id]->getStatus() )
                            {
                                $encoded_path = FALSE;
        
                                $exception_message = NULL;
        
                                list( $file_name_rewritten ) = self::rewriteFileName(
                                    $photos[$photo_id]->getOriginalFileName()
                                );
        
                                $path =
                                    DIRNAME_SNAPSHOTS.PUNCTUATION_SLASH.
                                        $photos[$photo_id]->getHash().
                                            $file_name_rewritten
                                ;
        
                                $temporary_path =
                                    URI_LOAD_PHOTOGRAPH_REWRITTEN.
                                    $photo_id                   
                                ;
                                
                                if (
                                    ! file_exists( $path ) &&
                                    file_exists( utf8_decode( $path ) )
                                )
                                {
                                    $path = utf8_decode( $path );
        
                                    $temporary_path = utf8_decode( $temporary_path );
                                    
                                    $encoded_path = TRUE;
                                } 
                                
                                $display_width = ceil(
                                    $photos[$photo_id]->getWidth() / $scale
                                );
        
                                $display_height = ceil(
                                    $photos[$photo_id]->getHeight() /
                                    $scale
                                );
        
                                if ( ! file_exists( $temporary_path ) )
                                {
                                    if (
                                        ! file_exists( $path ) &&
                                        ! file_exists(
                                            $path = str_replace( '_', '-', $path )
                                        )
                                    )
                                    {
                                        $exception_message = sprintf(
                                                EXCEPTION_INVALID_ENTITY,
                                                ENTITY_PATH.' ('.$path.')'
                                            )
                                        ;
        
                                        if ( isset( $verbose_mode ) && $verbose_mode )
        
                                            throw new Exception( $exception_message );
                                    }
                                    
                                    if ( is_null( $exception_message) )
                                    {
                                        $thumbnail = imagecreatetruecolor(
                                            $display_width,
                                            $display_height
                                        );
            
                                        $original_file = imagecreatefromjpeg( $path );
            
                                        imagecopyresampled(
                                            $thumbnail,
                                            $original_file,
                                            0,
                                            0,
                                            0,
                                            0,
                                            $display_width,
                                            $display_height,
                                            (int)$photos[$photo_id]->getWidth(),
                                            (int)$photos[$photo_id]->getHeight()
                                        );
            
                                        imagejpeg( $thumbnail, $temporary_path, 100 );
                                    }
                                }
        
                                $smarty_photos
                                    [(int)$author_id]
                                        [(int)$photo_id]['height'] = $display_height;		
        
                                $smarty_photos
                                    [(int)$author_id]
                                        [(int)$photo_id]['path'] =
                                                $encoded_path
                                            ?
                                                utf8_encode( $temporary_path )
                                            :
                                                $temporary_path
                                        ;
        
                                $smarty_photos
                                    [(int)$author_id]
                                        [(int)$photo_id]['title'] =
                                            $photos[$photo_id]->getTitle();
        
                                $smarty_photos
                                    [(int)$author_id]
                                        [(int)$photo_id]['width'] = $display_width;
                            }

                }
			}

		$template->assign( 'photos', $smarty_photos );

        $template->display( TPL_PRESENTATION_GRID );
        
        $template->clear();  
    }
	
    /**
    * Get photos by providing an Author id 
    *
    * @param    integer     $author_id          author identifier
    * @param    boolean     $accept_avatars     accept avatars
    * @param    mixed       $conditions         conditions
    * @param    mixed       &$calculated_rows   rows calculated
    * @return	array	    containing Photo instances
    */	 
    public static function loadPhotosByAuthorId(
        $author_id,
        $accept_avatars = TRUE,
        $conditions = NULL,
        &$calculated_rows = NULL
    )
    {
		global $class_application, $verbose_mode;

        $class_data_fetcher = $class_application::getDataFetcherClass();

		$exceptions = NULL;

		$photos = array();

        $photographs_results = $class_data_fetcher::fetchPhotographs(
            $author_id,
            $accept_avatars,
            $conditions,
            $calculated_rows
        );

        try {
            if ( ! $photographs_results )
    
                throw new Exception(
                    'warning: an error occured '.
                        'while retrieving data from the table' .
                            TABLE_PHOTOGRAPH
                );
    
            else
            {
                foreach ( $photographs_results as $result )
                {
                    $photo = new Photo((int)$result->photo_id);                
                    $dimensions = array((int)$result->width,(int)$result->height);
    
                    try {
                        $photo->setDimensions($dimensions);
                    } catch (Exception $setting_exception) {
                        $exceptions .= $setting_exception;
                    }
                
                    try {
                        $photo->setHash($result->hash);
                    } catch (Exception $setting_exception) {
                        $exceptions .= $setting_exception;
                    }
        
                    try {
                        $photo->setKeywords($result->keywords);
                    } catch (Exception $setting_exception) {
                        $exceptions .= $setting_exception;
                    }
                    
                    try {
                        $photo->setSize((int)$result->size);                    
                    } catch (Exception $setting_exception) {
                        $exceptions .= $setting_exception;
                    }
                    
                    try {
                        $photo->setMimeType($result->mime_type);
                    } catch (Exception $setting_exception) {
                        $exceptions .= $setting_exception;
                    }
                    
                    try {
                        $photo->setAuthor( ( int ) $result->author_id );
                    } catch (Exception $setting_exception) {
                        $exceptions .= $setting_exception;
                    }
    
                    try {
                        $photo->setStatus( ( int ) $result->pht_status );
                    } catch (Exception $setting_exception) {
                        $exceptions .= $setting_exception;
                    }
    
                    try {
                        $photo->setTitle($result->title);
                    } catch (Exception $setting_exception) {
                        $exceptions .= $setting_exception;
                    }
                    
                    try {
                        $photo->setOriginalFileName($result->original_file_name);
                    } catch (Exception $setting_exception) {
                        $exceptions .= $setting_exception;
                    }
    
                    try {
                        $photo->setCreationDate($result->pht_date_creation);
                    } catch (Exception $setting_exception) {
                        $exceptions .= $setting_exception;
                    }
                    
                    try {
                        $photo->setLastModificationDate($result->pht_date_last_modification);
                    } catch (Exception $setting_exception) {
                        $exceptions .= $setting_exception;
                    }
                    
                    $photos[(int)$result->photo_id] = $photo;
                } 
            }
        }
        catch ( Exception $exception )
        {
            /**
            *
            * FIXME
            *
            */
        }
		
		return $photos;
    }	
}
