<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsUIAccordion Make the selected elements Accordion widgets.
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsUIAccordion extends YsUICore {

  public static $uiEvent = YsUIConstant::ACCORDION_WIDGET;

  /**
   * @return array options and events for this widget
   */
  public function registerOptions() {
    return   array(
               //options
               '_disabled' =>  array('key' => 'disabled', 'is_quoted' => false)
               ,'_active' => array('key' => 'active', 'is_quoted' => false)
               ,'_animated' => array('key' => 'animated', 'is_quoted' => false)
               ,'_autoHeight' => array('key' => 'autoHeight', 'is_quoted' => false)
               ,'_clearStyle' => array('key' => 'clearStyle', 'is_quoted' => true)
               ,'_collapsible' => array('key' => 'collapsible', 'is_quoted' => true)
               ,'_event' => array('key' => 'event', 'is_quoted' => false)
               ,'_fillSpace' => array('key' => 'fillSpace', 'is_quoted' => false)
               ,'_header' => array('key' => 'header', 'is_quoted' => true)
               ,'_icons' => array('key' => 'icons', 'is_quoted' => false)
               ,'_navigation' => array('key' => 'navigation', 'is_quoted' => false)
               ,'_navigationFilter' => array('key' => 'navigationFilter', 'is_quoted' => false)
               // events
               ,'_change' => array('key' => 'change', 'is_quoted' => false)
               ,'_changestart' => array('key' => 'changestart', 'is_quoted' => false)
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
   * Build the jQuery sintax to create this widget
   * @param string $jquerySelector A jQuery selector
   * @return object SELF
   */
  public static function build($jquerySelector = null){
    $jquery = self::getInstance();
    $jquery->setEvent(self::$uiEvent);
    if($jquerySelector !== null) { $jquery->setSelector($jquerySelector); }
    return $jquery;
  }

  // WIDGET METHODS

  /**
   * Activate a content part of the Accordion programmatically
   * @param integer $index The index can be a zero-indexed number to match the
   * position of the header to close or a Selector matching an element.
   * Pass false to close all (only possible with collapsible:true)
   * @return object YsJQueryCore
   */
  public static function activate($index = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::ACTIVATE_METHOD));
    if($index !== null){
      $jquery->value($index);
    }
    return $jquery;
  }

  /**
   * Recompute heights of the accordion contents when using the fillSpace
   * option and the container height changed. For example, when the container
   * is a resizable, this method should be called by its resize-event.
   * @param string $jquerySelector A jQuery selector
   * @return object YsJQueryCore
   */
  public static function resize($jquerySelector = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::RESIZE_METHOD));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    return $jquery;
  }

  //TEMPLATES

  /**
   * Starts the standar HTML tags for build this widget
   * @param string $accordionId The widget id
   * @param string $htmlProperties custom HTML properties like 'style="display:none"'
   * @return YsHTML HTML tags
   */
  public static function initWidget($accordionId, $htmlProperties = null){
    return YsHTML::getTag(YsHTML::DIV, sprintf('id="%s" %s ', $accordionId , $htmlProperties));
  }

  /**
   * Starts the standar HTML tags for build the section of an accordion
   * @param string $label The label of a section of the accordion
   * @param string $htmlProperties custom HTML properties like 'style="display:none"'
   * @return YsHTML HTML tags
   */
  public static function initSection($label = null,$htmlProperties = null){
    $template = new YsHTML();
    $template->addToTemplate(YsHTML::getTag(YsHTML::H3, $htmlProperties, YsHTML::getTag(YsHTML::A, 'href="#"', $label)));
    $template->addToTemplate(YsHTML::getTag(YsHTML::DIV));
    return $template->getTemplate();
  }

  /**
   * Ends the standar HTML tags for build the section
   * @return YsHTML HTML tags
   */
  public static function endSection(){
    return YsHTML::getTagClosed(YsHTML::DIV);
  }

  /**
   * Ends the standar HTML tags for build this widget
   * @return YsHTML HTML tags
   */
  public static function endWidget(){
    return YsHTML::getTagClosed(YsHTML::DIV);
  }

  /**
   * Customize the header icons with the icons option,
   * which accepts classes for the header's default and selected (open) state
   * @param string $headerIcon The header icon
   * @param string $headerSelectedIcon The header icon when the section
   * has been selected
   * @return array array('header' => $headerIcon,
   *                     'headerSelected' => $headerSelectedIcon)
   */
  public static function configureIcons($headerIcon, $headerSelectedIcon){
    return array(
          'header' => $headerIcon,
          'headerSelected' => $headerSelectedIcon
        );
  }
  
}