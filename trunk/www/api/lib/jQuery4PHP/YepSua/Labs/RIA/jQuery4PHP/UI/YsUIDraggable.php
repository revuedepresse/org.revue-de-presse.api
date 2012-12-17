<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsUIDraggable Enable draggable functionality on any DOM element.
 * Move the draggable object by clicking on it with the mouse and dragging it
 * anywhere within the viewport.
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsUIDraggable extends YsUICore {

  public static $uiEvent = YsUIConstant::DRAGGABLE_INTERACTION;

  /**
   * @return array options and events for this functionality
   */
  public function registerOptions() {
    return   array(
               //options
               '_disabled' =>  array('key' => 'disabled', 'is_quoted' => false)
              ,'_addClasses' =>  array('key' => 'addClasses', 'is_quoted' => false)
              ,'_appendTo' =>  array('key' => 'appendTo', 'is_quoted' => false)
              ,'_axis' =>  array('key' => 'axis', 'is_quoted' => false)
              ,'_cancel' =>  array('key' => 'cancel', 'is_quoted' => false)
              ,'_connectToSortable' =>  array('key' => 'connectToSortable', 'is_quoted' => false)
              ,'_containment' =>  array('key' => 'containment', 'is_quoted' => false)
              ,'_cursor' =>  array('key' => 'cursor', 'is_quoted' => false)
              ,'_cursorAt' =>  array('key' => 'cursorAt', 'is_quoted' => false)
              ,'_delay' =>  array('key' => 'delay', 'is_quoted' => false)
              ,'_distance' =>  array('key' => 'distance', 'is_quoted' => false)
              ,'_grid' =>  array('key' => 'grid', 'is_quoted' => false)
              ,'_handle' =>  array('key' => 'handle', 'is_quoted' => false)
              ,'_helper' =>  array('key' => 'helper', 'is_quoted' => false)
              ,'_iframeFix' =>  array('key' => 'iframeFix', 'is_quoted' => false)
              ,'_opacity' =>  array('key' => 'opacity', 'is_quoted' => false)
              ,'_refreshPositions' =>  array('key' => 'refreshPositions', 'is_quoted' => false)
              ,'_revert' =>  array('key' => 'revert', 'is_quoted' => false)
              ,'_revertDuration' =>  array('key' => 'revertDuration', 'is_quoted' => false)
              ,'_scope' =>  array('key' => 'scope', 'is_quoted' => false)
              ,'_scroll' =>  array('key' => 'scroll', 'is_quoted' => false)
              ,'_scrollSensitivity' =>  array('key' => 'scrollSensitivity', 'is_quoted' => false)
              ,'_scrollSpeed' =>  array('key' => 'scrollSpeed', 'is_quoted' => false)
              ,'_snap' =>  array('key' => 'snap', 'is_quoted' => false)
              ,'_snapMode' =>  array('key' => 'snapMode', 'is_quoted' => false)
              ,'_snapTolerance' =>  array('key' => 'snapTolerance', 'is_quoted' => false)
              ,'_stack' =>  array('key' => 'stack', 'is_quoted' => false)
              ,'_zIndex' =>  array('key' => 'zIndex', 'is_quoted' => false)
               // events
              ,'_start' => array('key' => 'start', 'is_quoted' => false)
              ,'_drag' => array('key' => 'drag', 'is_quoted' => false)
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
   * Build the jQuery sintax to enable draggable functionality on any DOM
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