<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsUITabs todo description.
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsUITabs extends YsUICore {

  public static $uiEvent = YsUIConstant::TABS_WIDGET;

  /**
   * @return array options and events for this widget
   */
  public function registerOptions() {
    return   array(
              //options
               '_disabled' =>  array('key' => 'disabled', 'is_quoted' => false)
              ,'_ajaxOptions' =>  array('key' => 'ajaxOptions', 'is_quoted' => false)
              ,'_cache' =>  array('key' => 'cache', 'is_quoted' => false)
              ,'_collapsible' =>  array('key' => 'collapsible', 'is_quoted' => false)
              ,'_cookie' =>  array('key' => 'cookie', 'is_quoted' => false)
              ,'_deselectable' =>  array('key' => 'deselectable', 'is_quoted' => false)
              ,'_event' =>  array('key' => 'event', 'is_quoted' => true)
              ,'_fx' =>  array('key' => 'fx', 'is_quoted' => false)
              ,'_idPrefix' =>  array('key' => 'idPrefix', 'is_quoted' => true)
              ,'_panelTemplate' =>  array('key' => 'panelTemplate', 'is_quoted' => true)
              ,'_selected' =>  array('key' => 'selected', 'is_quoted' => false)
              ,'_spinner' =>  array('key' => 'spinner', 'is_quoted' => true)
              ,'_tabTemplate' =>  array('key' => 'tabTemplate', 'is_quoted' => true)
              // events
              ,'_select' =>  array('key' => 'select', 'is_quoted' => false)
              ,'_load' =>  array('key' => 'load', 'is_quoted' => false)
              ,'_show' =>  array('key' => 'show', 'is_quoted' => false)
              ,'_add' =>  array('key' => 'add', 'is_quoted' => false)
              ,'_remove' =>  array('key' => 'remove', 'is_quoted' => false)
              ,'_enable' =>  array('key' => 'enable', 'is_quoted' => false)
              ,'_disable' =>  array('key' => 'disable', 'is_quoted' => false)
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

  public function sortable($axis = 'x'){
    $this->addAccesors(self::getSortableSintax(YsJQueryConstant::THIS, $axis));
    return $this;
  }

  public static function makeSortable($jquerySelector = null,$axis='x'){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(YsUIConstant::TABS_WIDGET);
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    $jquery->addAccesors(self::getSortableSintax($jquerySelector, $axis));
    return $jquery;
  }

  /**
   * Add a new tab.
   * @param string $url is either a URL consisting of a fragment identifier only
   * to create an in-page tab or a full url (relative or absolute, no
   * cross-domain support) to turn the new tab into an Ajax (remote) tab
   * @param string $label The label of the new tab
   * @param integer $index Is the zero-based position where to insert the new tab.
   * Optional, by default a new tab is appended at the end.
   * @return object YsJQueryCore
   */
  public static function add($url = null, $label = null, $index = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::ADD_METHOD));
    $jquery->addArgument(new YsArgument($url));
    $jquery->addArgument(new YsArgument($label));
    if($index !== null){
      $jquery->addArgument(new YsArgument($index));
    }
    return $jquery;
  }

  /**
   * Remove a tab.
   * @param integer $index Is the zero-based index of the tab to be removed.
   * @return object YsJQueryCore
   */
  public static function remove($index){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::REMOVE_METHOD));
    $jquery->addArgument(new YsArgument($index));
    return $jquery;
  }

  /**
   * Enable a disabled tab.
   * @param integer $index is the zero-based index of the tab to be enabled.
   * @return object YsJQueryCore
   */
  public static function enableTab($index){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::ENABLE_METHOD));
    $jquery->addArgument(new YsArgument($index));
    return $jquery;
  }

  /**
   * Disable a tab.
   * @param integer $index Is the zero-based index of the tab to be disabled.
   * @return object YsJQueryCore
   */
  public static function disableTab($index){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::DISABLE_METHOD));
    $jquery->addArgument(new YsArgument($index));
    return $jquery;
  }

  /**
   * elect a tab, as if it were clicked.
   * @param integer $index Is the zero-based index of the tab to be selected
   * @return object YsJQueryCore
   */
  public static function select($index){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::SELECT_METHOD));
    $jquery->addArgument(new YsArgument($index));
    return $jquery;
  }

  /**
   * Reload the content of an Ajax tab programmatically.
   * @param integer $index Is the zero-based index of the tab to be reloaded.
   * @return object YsJQueryCore
   */
  public static function load($index){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::LOAD_EVENT));
    $jquery->addArgument(new YsArgument($index));
    return $jquery;
  }

  /**
   * Change the url from which an Ajax (remote) tab will be loaded.
   * @param string $index Is the zero-based index of the tab of which its URL
   * is to be updated
   * @param string $url Is a URL the content of the tab is loaded from.
   * @return object YsJQueryCore
   */
  public static function url($index, $url){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::URL_METHOD));
    $jquery->addArgument(new YsArgument($index));
    $jquery->addArgument(new YsArgument($url));
    return $jquery;
  }

  /**
   * Retrieve the number of tabs of the first matched tab pane.
   * @return object YsJQueryCore
   */
  public static function length(){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::LENGTH_METHOD));
    return $jquery;
  }

  /**
   * Terminate all running tab ajax requests and animations.
   * @return object YsJQueryCore
   */
  public static function abort(){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::ABORT_METHOD));
    return $jquery;
  }

  /**
   * Set up an automatic rotation through tabs of a tab pane.
   * @param integer $ms Is an amount of time in milliseconds until the next tab in the cycle gets activated
   * @param <type> $continuing
   * @return <type>
   */
  public static function rotate($ms,$continuing = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::ROTATE_METHOD));
    $jquery->addArgument(new YsArgument($ms));
    if($continuing !== null){
      $jquery->addArgument(new YsArgument($continuing));
    }
    return $jquery;
  }

  //TEMPLATES

  public static function initWidget($tabsId , $htmlProperties = null){
    $htmlProperties = sprintf('class="ui-tabs-header" id="%s" ', $tabsId) . $htmlProperties;
    $template = YsHTML::getTag(YsHTML::DIV, $htmlProperties);
    return $template;
  }

  public static function endWidget(){
    return  YsHTML::getTagClosed(YsHTML::DIV);
  }
  
  public static function initHeader(){
    return  YsHTML::getTag(YsHTML::UL);
  }

  public static function endHeader(){
    return  YsHTML::getTagClosed(YsHTML::UL);
  }
  
  public static function tab($label, $tabHref, $closable = false ,$htmlProperties = null){
    $htmlProperties = sprintf(' href="%s" ', $tabHref) . $htmlProperties;
    $template = YsHTML::getTag(YsHTML::A, $htmlProperties);
    $template .= $label;
    $template .= YsHTML::getTagClosed(YsHTML::A);
    if($closable){
      $template .= YsHTML::getTag(YsHTML::SPAN, ' class="ui-icon ui-icon-close" ',YsHTML::NBSP);
      $template .= self::getClosableSintax()->execute();
    }
    $template = YsHTML::getTag(YsHTML::LI, null , $template);

    return $template;
  }

  public static function initTabContent($tabId, $htmlProperties = null){
    $htmlProperties = sprintf(' id="%s" ', $tabId) . $htmlProperties;
    $template = YsHTML::getTag(YsHTML::DIV, $htmlProperties);
    return $template;
  }

  public static function endTabContent(){
    return YsHTML::getTagClosed(YsHTML::DIV);
  }

  protected static function getClosableSintax(){
    return YsJQuery::one()
            ->in('.ui-tabs-header li .ui-icon-close')
            ->eventType(YsJQueryConstant::CLICK_EVENT)
            ->handler(
              str_replace('$',YsJQuery::$jqueryVar,'$tabs = $("#" + $(this).parents("div .ui-tabs-header").attr("id"));
               var index = $("li",$tabs).index($(this).parent());
               $tabs.tabs("remove", index);')
            );
  }
  
  protected static function getSortableSintax($selector,$axis){
    if($selector == 'this'){
      $template = ".%s('.ui-tabs-nav', %s).%s({axis:'%s'});";
    }else{
      $template = ".%s('.ui-tabs-nav', '%s').%s({axis:'%s'});";
    }
    return sprintf($template
                   ,YsJQueryConstant::FIND_EVENT
                   ,$selector
                   ,YsUIConstant::SORTABLE_INTERACTION
                   ,$axis);
  }
}