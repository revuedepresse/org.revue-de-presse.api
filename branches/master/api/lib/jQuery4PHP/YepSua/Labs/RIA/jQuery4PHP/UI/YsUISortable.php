<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsUISortable Enable a group of DOM elements to be sortable
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsUISortable extends YsUICore {

  public static $uiEvent = YsUIConstant::SORTABLE_INTERACTION;

  /**
   * @return array options and events for this widget
   */
  public function registerOptions() {
    return   array(
               //options
               '_disabled' =>  array('key' => 'disabled', 'is_quoted' => false)
              ,'_appendTo' =>  array('key' => 'appendTo', 'is_quoted' => false)
              ,'_axis' =>  array('key' => 'axis', 'is_quoted' => false)
              ,'_cancel' =>  array('key' => 'cancel', 'is_quoted' => false)
              ,'_connectWith' =>  array('key' => 'connectWith', 'is_quoted' => false)
              ,'_containment' =>  array('key' => 'containment', 'is_quoted' => false)
              ,'_cursor' =>  array('key' => 'cursor', 'is_quoted' => false)
              ,'_cursorAt' =>  array('key' => 'cursorAt', 'is_quoted' => false)
              ,'_delay' =>  array('key' => 'delay', 'is_quoted' => false)
              ,'_distance' =>  array('key' => 'distance', 'is_quoted' => false)
              ,'_dropOnEmpty' =>  array('key' => 'dropOnEmpty', 'is_quoted' => false)
              ,'_forceHelperSize' =>  array('key' => 'forceHelperSize', 'is_quoted' => false)
              ,'_forcePlaceholderSize' =>  array('key' => 'forcePlaceholderSize', 'is_quoted' => false)
              ,'_grid' =>  array('key' => 'grid', 'is_quoted' => false)
              ,'_handle' =>  array('key' => 'handle', 'is_quoted' => false)
              ,'_helper' =>  array('key' => 'helper', 'is_quoted' => false)
              ,'_items' =>  array('key' => 'items', 'is_quoted' => false)
              ,'_opacity' =>  array('key' => 'opacity', 'is_quoted' => false)
              ,'_placeholder' =>  array('key' => 'placeholder', 'is_quoted' => false)
              ,'_revert' =>  array('key' => 'revert', 'is_quoted' => false)
              ,'_scroll' =>  array('key' => 'scroll', 'is_quoted' => false)
              ,'_scrollSensitivity' =>  array('key' => 'scrollSensitivity', 'is_quoted' => false)
              ,'_scrollSpeed' =>  array('key' => 'scrollSpeed', 'is_quoted' => false)
              ,'_tolerance' =>  array('key' => 'tolerance', 'is_quoted' => false)
              ,'_zIndex' =>  array('key' => 'zIndex', 'is_quoted' => false)
               // events
              ,'_start' => array('key' => 'start', 'is_quoted' => false)
              ,'_sort' => array('key' => 'sort', 'is_quoted' => false)
              ,'_change' => array('key' => 'change', 'is_quoted' => false)
              ,'_beforeStop' => array('key' => 'beforeStop', 'is_quoted' => false)
              ,'_stop' => array('key' => 'stop', 'is_quoted' => false)
              ,'_update' => array('key' => 'update', 'is_quoted' => false)
              ,'_receive' => array('key' => 'receive', 'is_quoted' => false)
              ,'_remove' => array('key' => 'remove', 'is_quoted' => false)
              ,'_over' => array('key' => 'over', 'is_quoted' => false)
              ,'_out' => array('key' => 'out', 'is_quoted' => false)
              ,'_activate' => array('key' => 'activate', 'is_quoted' => false)
              ,'_deactivate' => array('key' => 'deactivate', 'is_quoted' => false));
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
   * Build the jQuery sintax to enable sortable functionality on any DOM
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

  //WIDGET METHODS

  /**
   * Serializes the sortable's item id's into a form/ajax submittable string.
   * Calling this method produces a hash that can be appended to any url
   * to easily submit a new item order back to the server.
   * @param string $jquerySelector A jQuery Selector
   * @param string/array $options The possible options are:
   *                        'key' (replaces part1[] with whatever you want),
   *                        'attribute' (test another attribute than 'id') and
   *                        'expression' (use your own regexp)
   * @return object YsJQueryCore
   */
  public static function serialize($jquerySelector = null, $options = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::SERIALIZE_EVENT));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    if($options !== null){
      $jquery->options($options);
    }
    return $jquery;
  }

  /**
   * Serializes the sortable's item id's into an array of string.
   * @param string $jquerySelector A jQuery Selector
   * @return object YsJQueryCore
   */
  public static function toArray($jquerySelector = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::TO_ARRAY_EVENT));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    return $jquery;
  }

  /**
   * Refresh the sortable items. Custom trigger the reloading of all sortable
   * items, causing new items to be recognized.
   * @param string $jquerySelector A jQuery Selector
   * @return object YsJQueryCore
   */
  public static function refresh($jquerySelector = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::REFRESH_METHOD));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    return $jquery;
  }

  /**
   * Refresh the cached positions of the sortables' items
   * @param string $jquerySelector A jQuery Selector
   * @return object YsJQueryCore
   */
  public static function refreshPositions($jquerySelector = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::REFRESH_POSITIONS_METHOD));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    return $jquery;
  }

  /**
   * Cancels a change in the current sortable and reverts it back to how it was
   * before the current sort started
   * @param string $jquerySelector A jQuery Selector
   * @return object YsJQueryCore
   */
  public static function cancel($jquerySelector = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::CANCEL_METHOD));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    return $jquery;
  }
   
}