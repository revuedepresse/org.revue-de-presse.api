<?php

/**
* Alpha class
* 
* Class to construct Alpha particles
* @package sefi
*/
class Alpha implements Particle
{
    protected $conditions;
    protected $quantities;

    /**
    * Spawn the default context
    *
    * @param    object  $conditions     	conditions
    * @param    object  $quantities     	quantities
    * @return   nothing
    */
    private function spawnDefaultContext( $conditions, $quantities )
    {
		global $build_jenkins, $class_application, $verbose_mode;

		$agent_context = $class_application::getContextAgent();
		$class_dumper = $class_application::getDumperClass();
		$class_entity = $class_application::getEntityAgent();
		$class_event = $class_application::getEventClass();
		$class_photo = $class_application::getPhotoClass();
		$class_toolbox = $class_application::getToolboxClass();
		$class_zip_archive = $class_application::getZipArchiveClass();

		$cli_mode = FALSE;

		if ( ! isset( $_SERVER['SERVER_NAME'] ) )
		
			$cli_mode = TRUE;

		$storage_type_file_system = $class_entity::getDefaultType(
			PROPERTY_VALUE,
			ENTITY_STORAGE
		);

		// check if the CLI mode is enabled
		if ( ! $build_jenkins && $cli_mode ) 
	
			echo 'operation date: '.date('Y-m-d_H:i:s')."\n";

        // check the quantities argument
        if ( isset( $quantities ) && is_object( $quantities ) )

            // set the quantities member attribute
            $this->quantities = $quantities;
        else 

            // construct the quantities as a new instance of the standard class
            $this->quantities = new stdClass();

        // check the conditions argument
        if ( isset( $conditions ) && is_object( $conditions ) )

            // set the quantities member attribute
            $this->conditions = $conditions;
        else 

            // construct the quantities as a new instance of the standard class
            $this->conditions = new stdClass();

		if ( is_null( $this->{CONDITION_CONTEXT} ) )

			$this->{CONDITION_CONTEXT} = DIR_DROPBOX;

		if ( is_null( $this->{CONDITION_CONTEXT_GEM_TYPE} ) )
		
			$this->{CONDITION_CONTEXT_GEM_TYPE} = EXTENSION_ZIP;

		if (is_null($this->{CONDITION_CONTEXT_TYPE} ) )
		
			$this->{CONDITION_CONTEXT_TYPE} = $storage_type_file_system;

		if ( is_null( $this->{CONDITION_WAREHOUSE} ) )
		
			$this->{CONDITION_WAREHOUSE} = DIR_PHOTOGRAPHS;

		$restriction = &$this->{CONDITION_CONTEXT};

		$context_type = &$this->{CONDITION_CONTEXT_TYPE};

		$gem_type = &$this->{CONDITION_CONTEXT_GEM_TYPE};

		$warehouse = &$this->{CONDITION_WAREHOUSE};

		// probe the context
		$context = $agent_context::probe(
			$gem_type,
			$restriction,
			$context_type
		);

        // check the population context
        if (
            is_array($context->{CONDITION_INHABITANTS}) &&
            count($context->{CONDITION_INHABITANTS}) != 0
        )
        {			
            // set a path to a directory
            $path_source = dirname( __FILE__ )."/../../".$restriction."/";

            $path_destination = dirname( __FILE__ )."/../../".$warehouse."/";

            // get a inhabitant
            while ( $inhabitant = array_pop( $context->{CONDITION_INHABITANTS} ) )
			{
				if ( $gem_type == EXTENSION_ZIP )
				{
					$pattern =
						'/^(?!'.PREFIX_STATUS_PROCESSED.')'.
						'([^-]+)-([^-]+)-((?:[0-9]+(?:\.)?)*)-(.*)(\.[^.]*)$/'
					;

					$match = preg_match($pattern, $inhabitant, $matches);
				}
				else

					$match = $matches = array( 5 => EXTENSION_JPG );

				if ( $match )

					switch ( $matches[5] )
					{
						case EXTENSION_JPG:

							$photograph_id =
								$class_photo::serialize($inhabitant)
							;
							
							// check if the CLI mode is enabled
							if ( $cli_mode )

								echo
									'last photograph insert id: ',
									$photograph_id."\n"
								;

								break;

						case ENTITY_OBJECT:

							// get a serialization
							$serialization = file_get_contents(
									$path_source.$inhabitant
							);

							if ( ! empty( $serialization ) )
							{
								// get an instance of particle 
								$particle = unserialize($serialization);
					
								if (
									is_object($particle) &&
									get_class($particle) == AGENT_ALPHA
								)
								{
									// set the conditions
									$this->setConditions($particle->getConditions());
						
									// set the qualities
									$this->setQualities($particle->getQualities());
								}
							}

								break;

						case EXTENSION_ZIP:

							$zip_archive = new $class_zip_archive;

							$result_opening =
								$zip_archive->open( $path_source.$inhabitant );

							if ($result_opening === TRUE)
							{
								$path_folder_country =
									$path_destination.'/'.
										strtolower($matches[2]);

								$path_folder_date =
									$path_folder_country.
										'/'.$matches[3];

								$path_folder_user =
									$path_folder_date.'/'.
										$class_toolbox::rewrite($matches[1]);

								if (
									! file_exists( $path_folder_country ) ||
									! is_dir( $path_folder_country )
								)

									mkdir($path_folder_country);

								if (
									! file_exists( $path_folder_date ) ||
									! is_dir( $path_folder_date )
								)

									mkdir( $path_folder_date );

								if (
									! file_exists( $path_folder_user ) ||
									! is_dir( $path_folder_user )
								)

									mkdir( $path_folder_user );

								if ( count( glob( $path_folder_user.'/*' ) ) == 0 )

									$zip_archive->extractTo( $path_folder_user );

								$zip_archive->close();

								if (
									file_exists( $path_source.$inhabitant ) &&
									! file_exists(
										$path_source.
											PREFIX_STATUS_PROCESSED.
												$inhabitant
									)
								)

									rename(
										$path_source.
											$inhabitant,
										$path_source.
											PREFIX_STATUS_PROCESSED.
												$inhabitant
									);
							}
							else
							{
								$event_type =
									EVENT_DESCRIPTION_ISSUE_WHILE_OPENING_ARCHIVE
								;

								$class_event::logEvent(
									array(
										PROPERTY_DESCRIPTION => sprintf(
											$event_type,
											$inhabitant,
											$result_opening
										),
										PROPERTY_TYPE => EVENT_TYPE_ARCHIVE_OPENING
									)
								);
							}

								break;
					}
			}
        }
        else if ( $this->{CONDITION_GROWTH} == ENTITY_STATUS_ACTIVE )
        {
            $this->setCondition( CONDITION_BIRTH_TIME, time() );
    
            // get the path to the current file         
            $path2file = explode("/", __FILE__);
    
            // get the file name
            $file_name = $path2file[count($path2file) - 1];
    
            // extract descriptors from the file name
            $file_descriptors = explode(".", $file_name);
    
            // set the hierarchical status
            $this->setCondition(
				CONDITION_HIERARCHICAL_STATUS,
				$file_descriptors[0]
			);
    
            // set the agent speciality
            $this->setCondition( CONDITION_SPECIALTY, $file_descriptors[1] );
    
            // set the agent identifier
            $this->setCondition(
                CONDITION_IDENTIFIER,
                $this->getCondition( CONDITION_SPECIALTY )."_".
					md5( $this->getCondition( CONDITION_BIRTH_TIME ) )
            );
        
            // put some contents in a folder
            file_put_contents(
                dirname( __FILE__ )."/../../".
					$restriction."/".
						md5( $this->getCondition( CONDITION_BIRTH_TIME ) ),
                serialize( $this )
            );
        }

		// check if the CLI mode is enabled
		if ( $cli_mode )
	
			echo "\n";		
    }

    /**
    * Call a method inaccessible in the object context
    * 
    * @param    string  $name       name
    * @param    array   $arguments  arguments
    * @return   mixed   return value
    */
    public function __call( $name, $arguments )
    {
        // check if a function exists
        if ( function_exists( $name ) )

            // call a user function
            call_user_func_array( $name, $arguments );
    }

    /**
    * Construct a Alpha particle
    * 
    * @param    object  $quantities quantities
    * @param    object  $conditions conditions
    * @return   object  Alpha particle
    */
    public function __construct( $conditions = NULL, $quantities = NULL )
    {
        // spawn the default context
        $this->spawnDefaultContext( $conditions, $quantities );
    }

    /**
    * Call a method inaccessible in the static context
    * 
    * @param    string  $name       name
    * @param    array   $arguments  arguments
    * @return   mixed   return value
    */
    public static function __callStatic( $name, $arguments )
    {
        // check if a function exists
        if ( method_exists( static::getClass(), '_'.$name ) )

            // call a user function
            return call_user_func_array( array( __CLASS__, '_'.$name ), $arguments );
		else
		
			throw new Exception( EXCEPTION_FURTHER_IMPLEMENTATION_REQUIRED );
		
    }

    /**
    * Get a class name
    * 
    * @return   string  class name
    */
    public static function getClass()
    {
        // return the current class name
        return __CLASS__;
    }

    /**
    * Get a reference to a condition inaccessible in the object context
    * 
    * @param    object  $name   name
    * @return   mixed   condition
    */
    public function &__get( $name )
    {
        // return a protected condition
        $condition = &$this->getCondition($name);

        // return a reference to a quantity
        return $condition;
    }

    /**
    * Check if a condition has been set
    * 
    * @param    object  $name   name
    * @return   boolean	setting
    */
    public function __isset( $name )
    {
		if ( $name === 'condition' )

			throw new Exception(
				sprintf( EXCEPTION_KEYWORD_RESERVED, $name )
			);

        // return a protected condition
        $condition = $this->getCondition( $name );

        // return existence status of a condition
        return ! is_null( $condition );
    }

    /**
    * Set the value of a condition inaccessible in the object context
    * 
    * @param    object  $name   name
    * @param	mixed	$value	value
    * @return   mixed   condition
    */
    public function __set($name, $value)
    {
        // return a protected condition
        $_value= &$this->__get($name);

        // replace the current the value of a condition
        $_value = $value;
    }

    /**
    * Unset a condition
    * 
    * @param    object  $name   name
    * @return   boolean	setting
    */
    public function __unset( $name )
    {
		$conditions = &$this->getConditions();

		$conditions->$name = NULL;

		if ( isset( $conditions->$name ) )
		
			unset( $conditions->$name );
    }

    /**
    * Get a condition
    * 
    * @param    string  $name   name
    * @return   mixed   value
    */
    public function &getCondition( $name )
    {
		$conditions = &$this->getConditions();

		if (
			is_string( $name ) &&
			! isset( $conditions->$name )
		)

			$conditions->$name = NULL;

		return $conditions->$name;
    }

    /**
    * Get conditions
    * @return   object  conditions
    */
    public function &getConditions()
    {
		if ( isset( $this->conditions ) )
		{
	        // get conditions
	        $conditions = &$this->conditions;
	
	        // return conditions
	        return $conditions;
		}
		else
		
			throw new Exception( sprintf( EXCEPTION_INVALID_INITIALZATION ) );
    }

    /**
    * Get quantities
    * 
    * @return   object  quantities
    */
    public function &getQuantities()
    {
        // get quantities
        $quantities= &$this->quantities;

        // return quantities
        return $quantities;
    }

    /**
    * Get a quantity
    * 
    * @param    string  $name   name
    * @return   mixed   value
    */
    public function &getQuantity($name)
    {
        // check if the name argument is a string
        if (
            is_string($name) &&
            !isset($this->quantities->$name)
        )

            // initialize a quantity from the name argument
            $this->quantities->$name = null;

        // return a quantity
        return $this->quantities->$name;
    }

    /**
    * Set a condition
    * 
    * @param    string  $name   name
    * @param    mixed   $value  value
    * @return   nothing
    */
    public function setCondition($name, $value)
    {
        // get a condition
        $condition = &$this->getCondition($name);

        // set the value of a condition
        $condition = $value;
    }

    /**
    * Set conditions
    * @param    object  $conditions conditions
    * @return   nothing
    */
    public function setConditions(stdClass $conditions)
    {
        // get the member conditions attribute
        $_conditions = &$this->getConditions();

        // set the conditions 
        $_conditions = $conditions;
    }

    /**
    * Set quantities
    * 
    * @param    object  $quantities     quantities
    * @return   nothing
    */
    public function setQuantities(stdClass $quantities)
    {
        // get the member quantities attribute
        $_quantities = &$this->getQuantities();

        // set the quantities
        $_quantities = $quantities;
    }

    /**
    * Set a quantity
    * 
    * @param    string  $name   name
    * @param    mixed   $value  value
    * @return   nothing
    */
    public function setQuantity($name, $value)
    {
        // get a quantity
        $quantity = &$this->getQuantity($name);

        // set the value of a quantity
        $quantity = $value;
    }

    /**
    * Spawn a particle
    *
    * @param    object  $conditions		conditions
    * @param    object  $quantities  	quantities
    * @return   object  Alpha particle
    */
    public static function spawnParticle(
		$conditions = NULL,
		$quantities = NULL
	)
    {
        // return a new Alpha particle from quantities        
        return new self( $conditions, $quantities );
    }
}

/**
*************
* Changes log
*
*************
* 2011 09 03
*************
* 
* Revise accessor functions
*
* methods affected ::
*
* ALPHA->__unset()
* ALPHA->__isset()
* 
* (branch 0.1 :: revision :: 665)
* (branch 0.2 :: revision :: 373)
*
*/
