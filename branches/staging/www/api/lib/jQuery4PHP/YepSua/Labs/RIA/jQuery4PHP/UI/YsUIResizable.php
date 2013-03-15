<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsUIResizable Enable any DOM element to be resizable. With the cursor grab
 * the right or bottom border and drag to the desired width or height.
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsUIResizable extends YsUICore {

  public static $uiEvent = YsUIConstant::RESIZABLE_INTERACTION;

  /**
   * @return array options and events for this widget
   */
  public function registerOptions() {
    return   array(
               //options
               '_disabled' =>  array('key' => 'disabled', 'is_quoted' => false)
              ,'_alsoResize' =>  array('key' => 'alsoResize', 'is_quoted' => false)
              ,'_animate' =>  array('key' => 'animate', 'is_quoted' => false)
              ,'_animateDuration' =>  array('key' => 'animateDuration', 'is_quoted' => false)
              ,'_animateEasing' =>  array('key' => 'animateEasing', 'is_quoted' => false)
              ,'_aspectRatio' =>  array('key' => 'aspectRatio', 'is_quoted' => false)
              ,'_autoHide' =>  array('key' => 'autoHide', 'is_quoted' => false)
              ,'_cancel' =>  array('key' => 'cancel', 'is_quoted' => false)
              ,'_containment' =>  array('key' => 'containment', 'is_quoted' => false)
              ,'_delay' =>  array('key' => 'delay', 'is_quoted' => false)
              ,'_distance' =>  array('key' => 'distance', 'is_quoted' => false)
              ,'_ghost' =>  array('key' => 'ghost', 'is_quoted' => false)
              ,'_grid' =>  array('key' => 'grid', 'is_quoted' => false)
              ,'_handles' =>  array('key' => 'handles', 'is_quoted' => false)
              ,'_helper' =>  array('key' => 'helper', 'is_quoted' => false)
              ,'_maxHeight' =>  array('key' => 'maxHeight', 'is_quoted' => false)
              ,'_maxWidth' =>  array('key' => 'maxWidth', 'is_quoted' => false)
              ,'_minHeight' =>  array('key' => 'minHeight', 'is_quoted' => false)
              ,'_minWidth' =>  array('key' => 'minWidth', 'is_quoted' => false)
                // events
              ,'_start' => array('key' => 'start', 'is_quoted' => false)
              ,'_resize' => array('key' => 'resize', 'is_quoted' => false)
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
  public static function option($jquerySelector, $value = null)
  {
    $staticMethod = YsUIConstant::OPTION_METHOD;
    parent::$uiEvent = self::$uiEvent;
    return parent::$staticMethod($jquerySelector, $value);
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
   * Build the jQuery sintax to enable resizable functionality on any DOM
   * element
   * @param string $jquerySelector A jQuery selector
   * @return object SELF
   */
  public static function build($jquerySelector = null){
    $jquery = self::getInstance();
    $jquery->setEvent(self::$uiEvent);
    if($jquerySelector !== null) { $jquery->setSelector($jquerySelector); }
    return $jquery;
  }
 
  
}