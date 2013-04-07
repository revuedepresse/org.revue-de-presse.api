<?php

/**
* Environment class
* 
* Class to construct environment particles
* @package sefi
*/
class Environment extends Context
{
    /*
    * Challenge a visitor
    *
    * @return  nothing
    */
    public static function challengeVisitor()
    {
        // learn about the visitor
        self::learn();

        // load a challenge
        self::loadChallenge();
    }

    /*
    * Learn something about a target
    *
    * @param    integer $topic  topic
    * @param    integer $target target
    * @return   nothing
    */
    public static function learn( $topic = TOPIC_USER_AGENT, $target = NULL )
    {
		$class_entity = CLASS_ENTITY;

		$target_type_visitor = $class_entity::getTypeValue(
			array(
				PROPERTY_NAME => ENTITY_VISITOR,
				PROPERTY_ENTITY => ENTITY_TARGET
			)
		);

		if (is_null($target))
			
			$target = $target_type_visitor;
    }

    /*
    * Load a challenge
    *
    * @return   nothing
    */
    public static function loadChallenge()
    {
		// set the application class name
		$class_application = CLASS_APPLICATION;

        // jump to the challenge form
        $class_application::jumpTo(URI_ACTION_OFFER_CHALLENGE);
    }

    /*
    * Scan the dropbox
    *
    * @return   nothing
    */
    public static function scanDropbox()
    {
		parent::spawnParticle();
	}

    /*
    * Scan the warehouse
    *
    * @return   nothing
    */
    public static function scanWarehouse()
    {
		$conditions_jpg = new stdClass();

		$conditions_jpg->{CONDITION_CONTEXT} = DIR_PHOTOGRAPHS;
		
		$conditions_jpg->{CONDITION_CONTEXT_GEM_TYPE} = EXTENSION_JPG;

		parent::spawnParticle( $conditions_jpg );
	}
}
