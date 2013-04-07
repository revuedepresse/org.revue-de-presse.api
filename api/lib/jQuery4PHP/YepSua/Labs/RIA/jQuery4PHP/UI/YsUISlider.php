<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsUISlider todo description.
 * 
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsUISlider extends YsUICore {

  public static $uiEvent = YsUIConstant::SLIDER_WIDGET;
  const MAX_RANGE = 'max';
  const MIN_RANGE = 'min';

  /**
   * @return array options and events for this widget
   */
  public function registerOptions() {
    return   array(
               //options
               '_disabled' =>  array('key' => 'disabled', 'is_quoted' => false)
              ,'_animate' =>  array('key' => 'animate', 'is_quoted' => false)
              ,'_max' =>  array('key' => 'max', 'is_quoted' => false)
              ,'_min' =>  array('key' => 'min', 'is_quoted' => false)
              ,'_orientation' =>  array('key' => 'orientation', 'is_quoted' => true)
              ,'_range' =>  array('key' => 'range', 'is_quoted' => false)
              ,'_step' =>  array('key' => 'step', 'is_quoted' => false)
              ,'_value' =>  array('key' => 'value', 'is_quoted' => false)
              ,'_values' =>  array('key' => 'values', 'is_quoted' => false)
               // events
               ,'_start' => array('key' => 'start', 'is_quoted' => false)
               ,'_slide' => array('key' => 'slide', 'is_quoted' => false)
               ,'_change' => array('key' => 'change', 'is_quoted' => false)
               ,'_stop' => array('key' => 'stop', 'is_quoted' => false)
               );
  }

  /**
   * Remove the autocomplete functionality completely.
   * This will return the element back to its pre-init state.
   * @param string $jquerySelector A jQuery selector
   * @return object YsJQueryCore
   */
  public static function destroyMethod($jquerySelector = null)
  {
    $staticMethod = YsUIConstant::DESTROY_METHOD;
    parent::$uiEvent = self::$uiEvent;
    return parent::destroyMethod($jquerySelector);
  }

  /**
   * Disable the functionality.
   * @param string $jquerySelector A jQuery selector
   * @return object YsJQueryCore
   */
  public static function disable($jquerySelector = null)
  {
    $staticMethod = YsUIConstant::DISABLE_METHOD;
    parent::$uiEvent = self::$uiEvent;
    return parent::$staticMethod($jquerySelector);
  }

  /**
   * Enable the functionality.
   * @param string $jquerySelector A jQuery selector
   * @return object YsJQueryCore
   */
  public static function enable($jquerySelector = null)
  {
    $staticMethod = YsUIConstant::ENABLE_METHOD;
    parent::$uiEvent = self::$uiEvent;
    return parent::$staticMethod($jquerySelector);
  }

  /**
   * Get or set any option.
   * If no value is specified, will act as a getter.
   * @param string/array $optionName The option name or a map(array) options
   * @param any $value The option value
   * @return object YsJQueryCore
   */
  public static function option($optionName, $value = null)
  {
    $staticMethod = YsUIConstant::OPTION_METHOD;
    parent::$uiEvent = self::$uiEvent;
    return parent::$staticMethod($optionName, $value);
  }

  /**
   * Return the widget element
   * @param string $jquerySelector A jQuery selector
   * @return object YsJQueryCore
   */
  public static function widget($jquerySelector = null)
  {
    $staticMethod = YsUIConstant::WIDGET_METHOD;
    parent::$uiEvent = self::$uiEvent;
    return parent::$staticMethod($jquerySelector);
  }

  /**
  * Retrieves a instance of this class.
  * @return object self::$instance
  */
  public static function getInstance()
  {
    $object = __CLASS__;
    self::$instance = new $object();
    return self::$instance;
  }
  
  // BUILDER

  /**
   * Build the jQuery sintax to create this widget.
   * @param string $jquerySelector A jQuery selector
   * @return object SELF
   */
  public static function build($jquerySelector = null){
    $jquery = self::getInstance();
    $jquery->setEvent(self::$uiEvent);
    if($jquerySelector !== null) { $jquery->setSelector($jquerySelector); }
    return $jquery;
  }

  //WIDGET METHODS

  /**
   * Gets or sets the value of the slider. For single handle sliders.
   * If no value is specified, will act as a getter.
   * @param string $jquerySelector A jQuery selector
   * @param string $value The value
   * @return object YsJQueryCore
   */
  public static function widgetValue($jquerySelector = null , $value = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::VALUE_EVENT));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    if($value !== null){
      $jquery->value($value);
    }
    return $jquery;
  }

  /**
   * Gets or sets the values of the slider. For multiple handle or range sliders.
   * If no value is specified, will act as a getter.
   * @param string $jquerySelector A jQuery selector
   * @param string $index The index to set the value
   * @param string $value The value
   * @return object YsJQueryCore
   */
  public static function widgetValues($jquerySelector = null , $index = null, $value = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::VALUES_EVENT));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    if($index !== null){
      $jquery->index($index);
    }
    if($value !== null){
      $jquery->value($value);
    }
    return $jquery;
  }

  //TEMPLATES

  /**
   * Starts the standar HTML tags for build this widget
   * @param string $sliderId The widget id
   * @param string $htmlProperties custom HTML properties like 'style="display:none"'
   * @return YsHTML HTML tags
   */
  public static function initWidget($sliderId, $htmlProperties = null){
    return YsHTML::getTag(YsHTML::DIV, sprintf('id="%s" %s ', $sliderId , $htmlProperties));
  }

  /**
   * Ends the standar HTML tags for build this widget
   * @return YsHTML HTML tags
   */
  public static function endWidget(){
    return YsHTML::getTagClosed(YsHTML::DIV);
  }
  
}