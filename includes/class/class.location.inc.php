<?php

/**
* Location class
*
* Class for handling geolocalization
* @package  sefi
*/
class Location extends Toolbox
{
    private $_country_code;
    private $_location_id;
    private $_name;
    private $_position;
    private $_post_code;

    /**
    * Construct a Location instance
    * @param    integer $id representing a Location instance
    * 
    * @return   object  representing a Location instance
    */	    
    public function __construct($id = -1) {
        if (is_integer($id))
            $this->_location_id = $id;
        else
            throw new Exception("Data type error: a Location id has to be passed as an integer");
    }

    /**
    * Get the country code of a Location instance
    * 
    * @return   string  containing a country code
    */	        
    public function getCountryCode() {
        return $this->_country_code;
    }

    /**
    * Get the latitude of a Location instance
    * 
    * @return   string  containing a latitude
    */	        
    public function getLatitude() {
        return $this->_position['latitude'];
    }

    /**
    * Get the Location id of a Location instance
    * 
    * @return   integer representing a Location instance
    */	        
    public function getId() {
        return $this->_location_id;
    }
    
    /**
    * Get the longitude of a Location instance
    * 
    * @return   string  containing a longitude
    */	        
    public function getLongitude() {
        return $this->_position['longitude'];
    }

    /**
    * Get the name of a Location instance
    * 
    * @return   string  containing a name
    */	        
    public function getName() {
        return $this->_name;
    }
    
    /**
    * Get the country code of a Location instance
    * 
    * @return   string  containing a country code
    */	        
    public function getPosition() {
        return $this->_position;
    }    

    /**
    * Get the post code of a Location instance
    * 
    * @return   string  containing a post code
    */	        
    public function getPostCode() {
        return $this->_post_code;
    }

    /**
    * Set the country code of a Location instance
    * @param    string   $country_code  containing a country code
    * 
    * @return   nothing
    */	    
    public function setCountryCode($country_code) {
        if (is_string($country_code) && strlen($country_code) < 3)
            $this->_country_code = $country_code;
        else
            throw new Exception("Data type error: a country code has to be passed as a string");
    }
    
    /**
    * Set the city of a Location instance
    * @param    string   $city  containing a country code
    * 
    * @return   nothing
    */	    
    public function setCity($city) {
        if (is_string($city))
            $this->_city = $city;
        else
            throw new Exception("Data type error: a city has to be passed as a string");
    }
    
    /**
    * Set the latitude of a Location instance
    * @param    float   $latitude   representing a latitude
    * 
    * @return   nothing
    */	    
    public function setLatitude($latitude) {
        if (is_numeric($latitude))
            $this->_position['latitude'] = $latitude;
        else
            throw new Exception("Data type error: a latitude has to be passed as a numeric value");
    }

    /**
    * Set the longitude of a Location instance
    * @param    float  $longitude   representing a longitude
    * 
    * @return   nothing
    */	    
    public function setLongitude($longitude) {
        if (is_numeric($longitude))
            $this->_position['longitude'] = $longitude;
        else
            throw new Exception("Data type error: a longitude has to be passed as a numeric value");
    }

    /**
    * Set the name of a Location instance
    * @param    string   $name  containing a name
    * 
    * @return   nothing
    */	    
    public function setName($name) {
        if (is_string($name))
            $this->_name = $name;
        else
            throw new Exception("Data type error: a name has to be passed as a string");
    }
    
    /**
    * Set the position of a Location instance
    * @param    array   $position   containing  a position
    * 
    * @return   nothing
    */	    
    public function setPosition($position) {
        $exceptions = null;
        
        if (is_array($position)) {
            try {
                $this->setLatitude($position['latitude']);
            } catch (Exception $setting_exception) {
                $exceptions .= $setting_exception;
            }
    
            try {
                $this->setLongitude($position['longitude']);
            } catch (Exception $setting_exception) {
                $exceptions .= $setting_exception;
            }
        } else
            throw new Exception("Data type error: a position has to be passed as an array");
    }

    /**
    * Set the post code of a Location instance
    * @param    string  $post_code  containing a post code
    * 
    * @return   nothing
    */	    
    public function setPostCode($post_code) {
        if (is_string($post_code))
            $this->_post_code = $post_code;
        else
            throw new Exception("Data type error: a post code has to be passed as a string");
    }
    
    private function insert() {
        $database_connection = new Database_Connection();
        
        $insert_location = '
            INSERT INTO `'.TABLE_LOCATION.'` (
                country_code,
                latitude,
                longitude,
                name,
                post_code
                ) VALUES (
                "'.$this->_country_code.'",
                "'.$this->_position['latitude'].'",
                "'.$this->_position['longitude'].'",
                "'.$this->_name.'",
                "'.$this->_post_code.'"
        )';

        $retrieve_location = '
            SELECT
                location_id
            FROM
                `'.TABLE_LOCATION.'`
            WHERE
                country_code = "'.$this->_country_code.'" AND
                latitude = "'.$this->_position['latitude'].'" AND
                longitude = "'.$this->_position['longitude'].'" AND
                name = "'.$this->_name.'" AND
                post_code = "'.$this->_post_code.'"
        ';
        
        $inserting_result = $database_connection->executeQuery($insert_location,false);
        
        if (!$inserting_result)
            throw new Exception('warning: an error occured while inserting data into the table'.TABLE_LOCATION);
        else {
            $retrieving_result = $database_connection->executeQuery($retrieve_location,true);

            if (!$retrieving_result)
                throw new Exception('warning: an error occurent while retrieving data from the tabble'.TABLE_LOCATION);
            else
                foreach ($retrieving_result as $row)
                    $this->_location_id = $row['location_id'];
        }
    }

    /**
    * Save a Photo instance
    *
    * @return  nothing
    */
    public function save() {
        $exceptions = null;        
        
        if ($this->_location_id == -1)
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
    * Get a Location instance by providing a Location id 
    *
    * @return   object  representing a Location instance
    */
    public static function getById() {
        $exceptions = null;
        $database_connection = new Database_Connection();
        
        $retrieve_location = '
            SELECT
                name,
                city,
                post_code,
                country_code,
                latitude,
                longitude
            FROM
                `'.TABLE_LOCATION.'`
            ';

        try {
            $retrieving_result = $database_connection->executeQuery($retrieve_location,true);
        } catch (Exception $retrieving_exception) {
            $exceptions .= $retrieving_exception;
        }

        if (!$retrieving_result)
            throw new Exception('warning: an error occured while retrieving data from '.TABLE_LOCATION);
        else

            foreach ($retrieving_result as $_row)
            {
                $row = (array) $_row;

                $row['location_id'] = isset($row['location_id']) ?  $row['location_id'] : 0;

                $location = new Location((int)$row['location_id']);
                
                try {
                    $location->setName($row['name']);
                } catch (Exception $settting_exception) {
                    $exceptions .= $settting_exception;
                }

                try {
                    $location->setCity($row['city']);
                } catch (Exception $settting_exception) {
                    $exceptions .= $settting_exception;
                }

                try {
                    $location->setCountryCode($row['country_code']);
                } catch (Exception $settting_exception) {
                    $exceptions .= $settting_exception;
                }

                try {
                    $location->setPostCode($row['post_code']);
                } catch (Exception $settting_exception) {
                    $exceptions .= $settting_exception;
                }
                
                try {
                    $location->setLatitude($row['latitude']);
                } catch (Exception $settting_exception) {
                    $exceptions .= $settting_exception;
                }

                try {
                    $location->setLongitude($row['longitude']);
                } catch (Exception $settting_exception) {
                    $exceptions .= $settting_exception;
                }
                
            }

        return $location;
    }
    
    /**
    * Load an upload photo form
    *
    * @return  nothing
    */
    public static function loadUploadForm()
    {
        global $class_application, $lang;

        // set the template engine class name
		$class_template_engine = $class_application::getTemplateEngineClass();
        
        $template->assign("title",$lang['title']['form_upload_photos']);        

        $template->assign("legend",$lang['legend']['form_upload_photos']);
        
        $template->assign("fields",$lang['photo_fields']);
        
        $template->assign("label_submit",$lang['button_labels']['selection']['label_value']);
        
        $template->display(TPL_FORM_UPLOAD_PHOTOS);
        
        $template->clear();  
    }    
}
?>