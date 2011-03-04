<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

include_once dirname(__FILE__) . '/../../CommonUtil/YsUtilAutoloader.php';
YsUtilAutoloader::register();

/**
 * YsJQueryUtil todo description.
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */

abstract class YsJQueryUtil extends YsJQuerySintax {

  static protected $pluginDir = null;
  static protected $componentDir = null;
  static protected $pluginFolderName =  'Plugins';
  static protected $componentFolderName =  'Components';

  public function  __call($name, $arguments) {
    $configurations = $this->registerOptions();
    if(array_key_exists($name,$configurations)){
      $configuration = $configurations[$name];
      $configuration['value'] = isset($arguments[0]) ? $arguments[0] : new YsArgument(null) ;
      $this->addOption($name, $configuration);
    }else{
      if(!method_exists($this, $name)){
        throw new Exception(sprintf('Call to undefined method %s::%s().',get_class($this) , $name));
      };
    }
    return $this;
  }

  public function eventType($event){
    $this->addArgument(new YsArgument($event));
    return $this;
  }

  public function name($name){
    $this->addArgument(new YsArgument($name));
    return $this;
  }

  public function value($value){
    $this->addArgument(new YsArgument($value, !is_object($value)));
    return $this;
  }

  public function target($object){
    $this->addArgument(new YsArgument($object));
    return $this;
  }

  public function key($key){
    $this->addArgument(new YsArgument($key, (!is_object($key) || !is_numeric($key))));
    return $this;
  }

  public function content($content){
    $this->addArgument(new YsArgument($content, !is_object($content)));
    return $this;
  }

  public function attributeName($attributeName){
    $this->addArgument(new YsArgument($attributeName));
    return $this;
  }

  public function propertyName($propertyName){
    $this->addArgument(new YsArgument($propertyName));
    return $this;
  }

  public function selector($selector){
    $this->addArgument(new YsArgument($selector, !is_object($selector)));
    return $this;
  }

  public function includeMargin($boolean){
    $this->addArgument(new YsArgument($boolean, false));
    return $this;
  }

  public function object($object){
    $this->addArgument(new YsArgument($object, false));
    return $this;
  }

  public function toString(){
    $this->addAccesors('.toString()');
    return $this;
  }

  public function toInteger(){
    $this->setPreSintax('parseInt(');
    $this->setPostSintax(')');
    return $this;
  }

  public function toFloat(){
    $this->setPreSintax('parseFloat(');
    $this->setPostSintax(')');
    return $this;
  }

  public function withDataAndEvents($boolean){
    $this->addArgument(new YsArgument($boolean));
    return $this;
  }

  public function context($context){
    $this->addArgument(new YsArgument($context, false));
    return $this;
  }

  public function start($value){
    $this->addArgument(new YsArgument($value, false));
    return $this;
  }

  public function ends($value){
    $this->addArgument(new YsArgument($value, false));
    return $this;
  }

  public function eventData($eventData){
    $this->addArgument(new YsArgument($eventData));
    return $this;
  }

  public function handler($handler){
    if($handler instanceof YsJsFunction){
      $this->addArgument($handler->__toString());
    }else{
      $this->addArgument(new YsJsFunction(new YsArgument($handler, false)));
    }
    return $this;
  }

  /**
   * Configure the speed in jquery events or effects
   * @param string/numeric $speed The spped
   * @example 'fast'|500
   * @return object
   */
  public function speed($speed){
    $this->addArgument(new YsArgument($speed, !is_numeric($speed)));
    return $this;
  }

  /**
   * Configure the opacity in jquery events or effects
   * @param numeric $opacity The opacity
   * @example 0.33
   * @return object
   */
  public function opacity($opacity){
    $this->addArgument(new YsArgument($opacity, !is_numeric($opacity)));
    return $this;
  }

  /**
   * Configure the CSS properties in jquery events or effects
   * @param  string/array $properties A map of CSS properties that the animation will move toward.
   * @return object
   */
  public function properties($properties){
    $this->addArgument(new YsArgument($properties, is_string($properties)));
    return $this;
  }

  /**
   * Configure the easing on jquery events or effects
   * @param  string $properties A string indicating which easing function to use for the transition.
   * @return object
   */
  public function easing($easing){
    $this->addArgument(new YsArgument($easing));
    return $this;
  }


  public function className($className){
    $this->addArgument(new YsArgument($className));
    return $this;
  }

  /**
   * Configure the duration in jquery events or effects
   * @param string/numeric $duration The speed
   * @return object
   */
  public function duration($duration){
    $this->addArgument(new YsArgument($duration, !is_numeric($duration)));
    return $this;
  }

  /**
   * Configure the delay (milliseconds) in jquery events or effects
   * @param string/numeric $duration The speed
   * @return object
   */
  public function delayTime($time){
    $this->addArgument(new YsArgument($time, !is_numeric($time)));
    return $this;
  }


  /**
   * Configure the queueName in jquery events or effects
   * @param string $queueName The queueName
   * @return object
   */
  public function queueName($queueName){
    $this->addArgument(new YsArgument($queueName));
    return $this;
  }

  /**
   * Configure a new Queue in jquery events or effects
   * @param string $queueName The new queue
   * @return object
   */
  public function newQueue($queue){
    $this->addArgument(new YsArgument($queue, false));
    return $this;
  }

  /**
   * Configure the options in jquery events or effects
   * @param string/array $speed The options
   * @return object
   */
  public function options($options){
    $this->addArgument(new YsArgument($options, is_string($options)));
    return $this;
  }

  /**
   * Configure the effect name in jquery events or effects
   * @param string $effect The effect name
   * @return object
   */
  public function effectName($effect){
    $this->addArgument(new YsArgument($effect, is_string($effect)));
    return $this;
  }

  /**
   * Configure the callback in jquery events or effects
   * @param string/array $speed The options
   * @return object
   */
  public function callback($callback){
    $this->addArgument($callback);
    return $this;
  }

  /**
   * Configure the search string in jqueryUI.autocomplete
   * @param string $search The string to search
   * @return object
   */
  public function searchString($search){
    $this->addArgument(new YsArgument($search, is_string($search)));
    return $this;
  }

  public static function getPluginFolderName(){
    return DIRECTORY_SEPARATOR . self::$pluginFolderName;
  }
  
  public static function getComponentFolderName(){
    return DIRECTORY_SEPARATOR . self::$componentFolderName;
  }

  public abstract function registerOptions();


  /**
   *
   *
   public static function getInstance(){
     $object = __CLASS__;
     self::$instance = new $object();
     return self::$instance;
   }
   *
   */
  public static function getInstance(){
     $object = __CLASS__;
     self::$instance = new $object();
     return self::$instance;
  }

  public static function usePlugin($pluginName){
    self::$pluginDir = dirname(__FILE__) . self::getPluginFolderName();
    $pluginDir = self::$pluginDir . DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR . '*';
    foreach (glob($pluginDir) as $file) { // remember the { and } are necessary!
      if(file_exists($file)){
          require_once $file;
      }
    }
  }

  /**
   * Include the files to use the components
   * @param <type> $componentName
   */
  public static function useComponent($componentName){
    self::$componentDir = dirname(__FILE__) . self::getComponentFolderName();
    $componentDir = self::$componentDir . DIRECTORY_SEPARATOR . $componentName . DIRECTORY_SEPARATOR . '*';
    foreach (glob($componentDir) as $file) { // remember the { and } are necessary!
      if(file_exists($file)){
          require_once $file;
      }
    }
  }

}