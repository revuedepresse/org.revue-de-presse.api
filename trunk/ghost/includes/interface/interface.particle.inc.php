<?php

/**
* Particle Interface
* 
* Interface to create a particle
* @package sefi
*/
interface Particle
{
    /**
    * Call a method inaccessible in the object context
    * @param    string  $name       name
    * @param    array   $arguments  arguments
    * @return   mixed   return value
    */
    public function __call($name, $arguments);

    /**
    * Call a method inaccessible in the static context
    * @param    string  $name       name
    * @param    array   $arguments  arguments
    * @return   mixed   return value
    */
    public static function __callStatic($name, $arguments);

    /**
    * Construct a particle
    * @param    object  $quantities quantities
    * @param    object  $conditions conditions
    * @return   mixed   value
    */
    public function __construct($quantities = null, $conditions = null);

    /**
    * Get a reference to a quantity inaccessible in the object context
    * @param    string  $name   name
    * @return   mixed   quantity
    */
    public function &__get($name);

    /**
    * Get a condition
    * @param    string  $name   name
    * @return   mixed   value
    */
    public function &getCondition($name);

    /**
    * Get conditions
    * @return   object  conditions
    */
    public function &getConditions();

    /**
    * Get quantities
    * @return   object  quantities
    */
    public function &getQuantities();

    /**
    * Get a quantity
    * @param    string  $name   name
    * @return   mixed   value
    */
    public function &getQuantity($name);

    /**
    * Set a condition
    * @param    string  $name   name
    * @param    mixed   $value  value
    * @return   nothing
    */
    public function setCondition($name, $value);

    /**
    * Set conditions
    * @param    object  $conditions conditions
    * @return   nothing
    */
    public function setConditions(stdClass $conditions);

    /**
    * Set quantities
    * @param    object  $quantities quantities
    * @return   nothing
    */
    public function setQuantities(stdClass $quantities);

    /**
    * Set a quantity
    * @param    string  $name   name
    * @param    mixed   $value  value
    * @return   nothing
    */
    public function setQuantity($name, $value);

    /**
    * Spawn a particle
    * @param    object  $quantities quantities
    * @return   object  particle
    */
    public static function spawnParticle($quantities = null);    
}
?>