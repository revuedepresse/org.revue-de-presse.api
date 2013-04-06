<?php

/**
* Author class
*
* Class for editing the qualities of an author
* @package  sefi
*/
class Author
{
    private $_author_id;    
    private $_biography;
    private $_birth_place;
    private $_birthday;
    private $_email;
    private $_first_name;
    private $_last_name;
    private $_middle_name;

    /**
    * Construct an Author instance
    * @param    integer $id representing an Author instance
    * 
    * @return   object  representing a Author instance
    */	    
    public function __construct($id = -1)
    {
        if (is_integer($id))
     
            $this->_author_id = $id;
        else
     
            throw new Exception("Data type error: a Author id has to be passed as an integer.");
    }

    /**
    * Get a string from a Member instance
    * 
    * @return	string	containing a full name
    */	    
    public function __toString()  {
        return $this->getFullName();        
    }

    /**
    * Get the biography of an Author instance
    * 
    * @return   string  containing a biography
    */	        
    public function getBiography() {
        return $this->_biography;
    }

    /**
    * Get the birth place of an Author instance
    * 
    * @return   object  representing a Location instance
    */	        
    public function getBirthPlace() {
        return $this->_birth_place;    
    }
    
    /**
    * Get the birthday of an Author instance
    * 
    * @return   string  containing a birthday
    */	        
    public function getBirthday() {
        return $this->_birthday;
    }

    /**
    * Get the main email address of an Author instance
    * 
    * @return   string  containing an email address
    */	        
    public function getEmail() {
        return $this->_email;
    }

    /**
    * Get the Author id of an Author instanace    
    * 
    * @return   integer representing an Author instance
    */	        
    public function getId() {
        return $this->_author_id;
    }    

    /**
    * Get the first name of an Author instance
    * 
    * @return   string  containing the first name of an Author instance
    */	        
    public function getFirstName() {
        return $this->_first_name;
    }
    
    /**
    * Get the last name of an Author instance
    * 
    * @return   string  containing the last name of an Author instance
    */	        
    public function getLastName() {
        return $this->_last_name;
    }

    /**
    * Get the middle name of an Author instance
    * 
    * @return   string  containing the middle name of an Author instance
    */	        
    public function getMiddleName() {
        return $this->_middle_name;
    }

    /**
    * Get the full name of an Author instance
    * 
    * @return   string  containing a full name
    */	        
    public function getFullName() {
        $full_name = $this->_first_name.' ';

        if ($this->_middle_name)
            $full_name .= $this->_middle_name.' ';
            
        $full_name .= $this->_last_name;
        
        return $full_name;
    }

    /**
    * Set the biography of an Author instance
    * @param    string  $biography  containing a biography
    * 
    * @return   nothing
    */	        
    public function setBiography($biography) {
        if (is_string($biography)) {
            $this->_biography = $biography;
        } else
            throw new Exception('Data type error: the biography of an Author instance has to be passed as a string');
    }

    /**
    * Set the birthday of an Author instance
    * @param    string   $birthday  containing a birtday
    * 
    * @return   nothing
    */	        
    public function setBirthday($birthday) {
        if (is_string($birthday)) {
            $this->_birthday = $birthday;
        } else
            throw new Exception('Data type error: a birthday has to be passed as a string');
    }

    /**
    * Set the birth place of an Author instance
    * @param    object    $birth_place  representing a Location instance
    * 
    * @return   nothing
    */	        
    public function setBirthPlace($birth_place) {
        if (is_object($birth_place) && get_class($birth_place) == CLASS_LOCATION) {
            $this->_birth_place = $birth_place;
        } else
            throw new Exception('Data type error: a birth place has to be passed as an object');
    }
    
    /**
    * Set the main email of an Author instance
    * @param    string  $email  containing an email 
    * 
    * @return   nothing
    */	    
    public function setEmail($email) {
        if (is_string($email))
            $this->_email = $email;
        else
            throw new Exception("Data type error: an email has has to be passed a string");
    }
    
    /**
    * Set the first name of an Author instance
    * @param    string $first_name  containing the first name of an Author instance
    * 
    * @return   nothing
    */	    
    public function setFirstName($first_name) {
        if (is_string($first_name))
            $this->_first_name = $first_name;
        else
            throw new Exception("Data type error: the first name of an Author instance has has to be passed a string");
    }

    /**
    * Set the middle name of an Author instance
    * @param    string $middle_name  containing the middle name of an Author instance
    * 
    * @return   nothing
    */	    
    public function setMiddleName($middle_name) {
        if (is_string($middle_name))
            $this->_middle_name = $middle_name;
        else
            throw new Exception("Data type error: the middle name of an Author instance has to be passed a string");
    }    

    /**
    * Set the last  name of an Author instance
    * @param    string $last_name  containing the last name of an Author instance
    * 
    * @return   nothing
    */	    
    public function setLastName($last_name) {
        if (is_string($last_name))
            $this->_last_name = $last_name;
        else
            throw new Exception("Data type error: the last  name of an Author instance has to be passed a string");
    }
      
    private function insert() {
        $database_connection = new Database_Connection();
        
        $insert_author = '
            INSERT INTO  `'.TABLE_AUTHOR.'` (
                biography,
                birthday,
                birth_place_id,
                email,
                first_name,
                last_name,
                middle_name
            ) VALUES (
                "'.$this->_biography.'",
                "'.$this->_birthday.'",
                '.$this->_birth_place->getId().',
                "'.$this->_email.'",
                "'.$this->_first_name.'",
                "'.$this->_last_name.'",
                "'.$this->_middle_name.'"
        )';

        $retrieve_author = '
            SELECT
                author_id
            FROM
                `'.TABLE_AUTHOR.'`
            WHERE
                biography = "'.$this->_biography.'" AND
                birthday = "'.$this->_birthday.'" AND
                birth_place_id = '.$this->_birth_place->getId().' AND
                email =  "'.$this->_email.'" AND
                first_name = "'.$this->_first_name.'" AND
                last_name = "'.$this->_last_name.'" AND
                middle_name = "'.$this->_middle_name.'"
        ';

        $inserting_result = $database_connection->executeQuery($insert_author,false);
        
        if (!$inserting_result)
            throw new Exception('warning: an error occured while inserting data into the table '.TABLE_AUTHOR);
        else {
            $retrieving_result = $database_connection->executeQuery($retrieve_author,true);

            if (!$retrieving_result)
                throw new Exception('warning: an error occurent while retrieving data from the table '.TABLE_AUTHOR);
            else
                $this->_author_id = $retrieving_result['author_id'];
        }
    }

    /**
    * Save an Author instance
    *
    * @return  nothing
    */
    public function save() {

        $exceptions = NULL;
        
        if ($this->_author_id == -1)

            try {

                $this->insert();

            } catch ( Exception $inserting_exception ) {

                $exceptions .= $inserting_exception;

            }            

        else

            try {

                $this->update();

            } catch ( Exception $updating_exception ) {

                $exceptions .= $updating_exception;

            } 
    }

    /**
    * Convert an Author instance into an array of attributes
    * 
    * @return  array   containing attributes
    */	 
    public function toArray() {
        $exceptions = null;
        
        $author = array();

        $author['id'] = $this->_author_id;        
        $author['email'] = $this->_email;
        $author['first_name'] = $this->_first_name;

        if ($this->_birthday)
            $author['birthday'] = $this->_birthday;

        $author['biography'] = $this->_biography;
        $author['full_name'] = $this->getFullName();
        $author['last_name'] = $this->_last_name;        
    
        if (is_object($this->_birth_place) && get_class($this->_birth_place) == CLASS_LOCATION)
            $author['birth_place'] = $this->_birth_place->getName();

        return $author;        
    }
    
    /**
    * Check the attributes of an Author instance
    *
    * @return   array   containing the attributes of an Author instance
    */
    public static function checkAttributes() {
        global $lang;
    
        $exceptions = null;
        $error = array();

        foreach ($lang['author_fields'] as $author_field_id => $author_field)

            // check if the current field is mandatory
            if (
                isset($author_field['is_mandatory']) &&
                $author_field['is_mandatory'] &&
                !$_POST[$author_field_id]
            )
                $errors[$author_field_id] = true;
            else
                $author_attributes[$author_field_id] = $_POST[$author_field_id];
                
        if ($errors)

            self::loadAddAuthorForm($errors,$author_attributes);
        else

            return $author_attributes;
    }

    /**
    * Load an add author form
    * @param    array  $errors              containing errors
    * @param    array  $author_attributes   containing the attributes of an Author instance
    * 
    * @return   nothing
    */
    public static function loadAddAuthorForm($errors = null,$author_attributes = array())
    {
        global $class_application, $errors, $lang;
        
        $class_dumper = $class_application::getDumperClass();

        $class_template_engine = $class_application::getTemplateEngineClass();

        $template = new $class_template_engine();

        $template->assign("action",SCRIPT_SAVE_AUTHOR);
        
        $template->assign("title",$lang['title']['form_add_author']);
        
        $template->assign("title_notes",$lang['title_notes']['form_add_author']);
        $template->assign("notes",$lang['notes']['form_add_author']);
        
        $template->assign("legend",$lang['legend']['form_add_author']);
                
        if (is_array($author_attributes) && count($author_attributes) != 0)

            foreach ($author_attributes as $field_name => $author_attribute)

                $lang['author_fields'][$field_name]['value'] = $author_attribute;

        if ( $errors && is_array( $errors ) && count( $errors ) != NULL )

            foreach ( $errors AS $field_name => $error )

                $lang['author_fields'][$field_name]['validity'] = $errors[$field_name];

        $template->assign( 'fields', $lang['author_fields'] );

        $template->display(TPL_FORM_ADD_AUTHOR);
        
        $template->clear();  
    }

    /**
    * Get an Author instance by providin an id
    *
    * @return   object  representing an Author instance
    */
    public static function getById($author_id) {
        $exceptions = null;
        $database_connection = new Database_Connection();
        $authors = array();
        
        $retrieve_author = '
            SELECT
                author_id,
                birth_place_id,
                birthday,
                biography,
                email,
                first_name,
                last_name,
                middle_name
            FROM
                `'.TABLE_AUTHOR.'`
            WHERE
                author_id = '.$author_id
            ;

        try {
            $retrieving_result = $database_connection->executeQuery($retrieve_author,true);
        } catch (Exception $retrieving_exception) {
            $exceptions .= $retrieving_exception;
        }

        if (!$retrieving_result)
            throw new Exception('warning: an error occured while retrieving data from '.TABLE_AUTHOR);
        else           
            foreach ($retrieving_result as $row) {
                $author = new Author((int)$row['author_id']);

                try {
                    $birth_place = Location::getById($row['birth_place_id']);
                } catch (Exception $retrieving_exception) {
                    $exceptions .= $retrieving_exception;    
                }
                
                try {
                    $author->setBiography($row['biography']);
                } catch (Exception $settting_exception) {
                    $exceptions .= $settting_exception;
                }

                try {
                    $author->setBirthday($row['birthday']);
                } catch (Exception $settting_exception) {
                    $exceptions .= $settting_exception;
                }
                
                if ($birth_place)
                    try {
                        $author->setBirthPlace($birth_place);
                    } catch (Exception $settting_exception) {
                        $exceptions .= $settting_exception;
                    }

                try {
                    $author->setEmail($row['email']);
                } catch (Exception $settting_exception) {
                    $exceptions .= $settting_exception;
                }

                try {
                    $author->setFirstName($row['first_name']);
                } catch (Exception $settting_exception) {
                    $exceptions .= $settting_exception;
                }
                
                try {
                    $author->setLastName($row['last_name']);
                } catch (Exception $settting_exception) {
                    $exceptions .= $settting_exception;
                }

                try {
                    $author->setMiddleName($row['middle_name']);
                } catch (Exception $settting_exception) {
                    $exceptions .= $settting_exception;
                }
            }

        return $author;
    }
            

    /**
    * Get Author instances
    *
    * @return   array   containing Author instances
    */
    public static function getAuthors() {
        $exceptions = null;
        $database_connection = new Database_Connection();
        $authors = array();
        
        $retrieve_authors = '
            SELECT
                author_id,
                birth_place_id,
                birthday,
                biography,
                email,
                first_name,
                last_name,
                middle_name
            FROM
                `'.TABLE_AUTHOR.'`
            ';

        try {
            $retrieving_result = $database_connection->executeQuery($retrieve_authors,true);
        } catch (Exception $retrieving_exception) {
            $exceptions .= $retrieving_exception;
        }

        if (!$retrieving_result)
            throw new Exception('warning: an error occured while retrieving data from '.TABLE_AUTHOR);
        else

            foreach ( $retrieving_result as $_row )
            {
                $row = (array) $_row;

                $author = new Author( ( int ) $row['author_id'] );

                try {
                    $birth_place = Location::getById($row['birth_place_id']);
                } catch (Exception $retrieving_exception) {
                    $exceptions .= $retrieving_exception;    
                }
                
                try {
                    $author->setBiography($row['biography']);
                } catch (Exception $settting_exception) {
                    $exceptions .= $settting_exception;
                }

                try {
                    $author->setBirthday($row['birthday']);
                } catch (Exception $settting_exception) {
                    $exceptions .= $settting_exception;
                }
                
                if ($birth_place)
                    try {
                        $author->setBirthPlace($birth_place);
                    } catch (Exception $settting_exception) {
                        $exceptions .= $settting_exception;
                    }

                try {
                    $author->setEmail($row['email']);
                } catch (Exception $settting_exception) {
                    $exceptions .= $settting_exception;
                }

                try {
                    $author->setFirstName($row['first_name']);
                } catch (Exception $settting_exception) {
                    $exceptions .= $settting_exception;
                }
                
                try {
                    $author->setLastName($row['last_name']);
                } catch (Exception $settting_exception) {
                    $exceptions .= $settting_exception;
                }

                try {
                    $author->setMiddleName($row['middle_name']);
                } catch (Exception $settting_exception) {
                    $exceptions .= $settting_exception;
                }
                
                $authors[(int)$row['author_id']] = $author; 
            }

        return $authors;
    }
    
    /**
    * Parse attributes to construct an Author instance
    * 
    * @param    array  $attributes   containing attributes
    * @return   object  representing an Author instance
    */
    public static function parseAttributes($attributes) {
        $exceptions = null;
        
        $author = new Author();
        $location = new Location();

        if ($attributes['birth_place']) {
            try {
                $location->setName($attributes['birth_place']);    
            } catch (Exception $setting_exception) {
                $exceptions .= $setting_exception;
            }
            
            try {
                $location->save();    
            } catch (Exception $saving_exception) {
                $exceptions .= $saving_exception;
            } 
        }

        try {
            $author->setBiography($attributes['biography']);
        } catch (Exception $setting_exception) {
            $exceptions .= $setting_exception;
        }

        try {
            $author->setBirthPlace($location);
        } catch (Exception $setting_exception) {
            $exceptions .= $setting_exception;
        }

        if ($attributes['year'] && $attributes['day'] && $attributes['month']) {
            $timestamp = mktime(0,0,0,$attributes['month'],$attributes['day'],$attributes['year']);
            $birthday = date('Y-m-d',$timestamp);

            try {
                $author->setBirthday($birthday);
            } catch (Exception $setting_exception) {
                $exceptions .= $setting_exception;
            }
        } else
            try {
                $author->setBirthday('0000-00-00');
            } catch (Exception $setting_exception) {
                $exceptions .= $setting_exception;
            } 

        try {
            $author->setEmail($attributes['email']);
        } catch (Exception $setting_exception) {
            $exceptions .= $setting_exception;
        }
        
        try {
            $author->setFirstName($attributes['first_name']);
        } catch (Exception $setting_exception) {
            $exceptions .= $setting_exception;
        }
        
        try {
            $author->setLastName($attributes['last_name']);
        } catch (Exception $setting_exception) {
            $exceptions .= $setting_exception;
        }        

        try {
            $author->setMiddleName($attributes['middle_name']);
        } catch (Exception $setting_exception) {
            $exceptions .= $setting_exception;
        }

        return $author;
    }
    
    /**
    * Save an Author instance
    * 
    * @return nothing
    */	 
    public static function saveAuthor()
    {
        $exceptions = null;
        
        $author = new Author();
        
        try {

            $author_attributes = self::checkAttributes();

        } catch ( Exception $checking_exception )
        {
            $exceptions .= $checking_exception;
        }
        
        if ($author_attributes)
        {
            try {
                $author = self::parseAttributes($author_attributes);
            } catch (Exception $parsing_exeption) {
                $exceptions .= $parsing_exception;
            }

            try {        
                $author->save();
            } catch (Exception $saving_exception) {
                $exceptions .= $saving_exception;
            } 

            if ($author->getId() != -1)
                header('Location:'.BASE_URL.SCRIPT_DISPLAY_ACKNOWLEDGMENT.'?'.GET_ACKNOWLEDGMENT.'='.RESPONSE_AUTHOR_SAVED);
        }
    }
}
?>