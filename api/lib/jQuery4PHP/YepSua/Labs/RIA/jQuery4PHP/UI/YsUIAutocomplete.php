<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsUIAutocomplete When added to an input field, enables users to quickly find
 * and select from a pre-populated list of values as they type, leveraging searching and filtering.
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsUIAutocomplete extends YsUICore {

  public static $uiEvent = YsUIConstant::AUTOCOMPLETE_WIDGET;

  /**
   * @return array options and events for this widget
   */
  public function registerOptions() {
    return   array(
               //options
               '_disabled' =>  array('key' => 'disabled', 'is_quoted' => false)
              ,'_delay' =>  array('key' => 'delay', 'is_quoted' => false)
              ,'_minLength' =>  array('key' => 'minLength', 'is_quoted' => false)
              ,'_source' =>  array('key' => 'source', 'is_quoted' => false)
               // events
              ,'_search' => array('key' => 'search', 'is_quoted' => false)
              ,'_open' => array('key' => 'open', 'is_quoted' => false)
              ,'_focus' => array('key' => 'focus', 'is_quoted' => false)
              ,'_select' => array('key' => 'select', 'is_quoted' => false)
              ,'_close' => array('key' => 'close', 'is_quoted' => false)
              ,'_change' => array('key' => 'change', 'is_quoted' => false));
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

  public static function build($jquerySelector = null){
    $jquery = self::getInstance();
    $jquery->setEvent(self::$uiEvent);
    if($jquerySelector !== null) { $jquery->setSelector($jquerySelector); }
    return $jquery;
  }

  // WIDGET METHODS

  /**
   * Triggers a search event, which, when data is available,
   * then will display the suggestions
   * @param string $value The value to search
   * @return object YsJQueryCore
   */
  public static function search($value = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::SEARCH_METHOD));
    if($value !== null){
      $jquery->value($value);
    }
    return $jquery;
  }

  /**
   * Close the Autocomplete menu. Useful in combination with the search method,
   * to close the open menu.
   * then will display the suggestions
   * @param string $jquerySelector A jQuery selector
   * @return object YsJQueryCore
   */
  public static function close($jquerySelector = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::CLOSE_METHOD));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    return $jquery;
  }
}