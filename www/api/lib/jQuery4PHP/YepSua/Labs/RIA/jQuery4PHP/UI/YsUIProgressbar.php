<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsUIProgressbar The progress bar is designed to simply display the current %
 * complete for a process.
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsUIProgressbar  extends YsUICore {

  public static $uiEvent = YsUIConstant::PROGRESSBAR_WIDGET;
  public static $intervalTime = 20;

  /**
   * @return array options and events for this widget
   */
  public function registerOptions() {
    return   array(
              //options
               '_disabled' =>  array('key' => 'disabled', 'is_quoted' => false)
              ,'_value' =>  array('key' => 'value', 'is_quoted' => false)
              // events
              ,'_change' =>  array('key' => 'change', 'is_quoted' => false)
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
  public static function build($jquerySelector = null, $value = null){
    $jquery = self::getInstance();
    $jquery->setEvent(self::$uiEvent);
    if($jquerySelector !== null) { $jquery->setSelector($jquerySelector); }
    if($value !== null){
      $jquery->value($value);
    }
    return $jquery;
  }

  /**
   * This method gets or sets the current value of the progressbar.
   * If no value is specified, will act as a getter.
   * @param string $jquerySelector A jQuery selector
   * @param string $value The value
   * @return object YsJQueryCore
   */
  public static function widgetValue($jquerySelector = null, $value = null){
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
   * This method sets the current value of the progressbar (animated).
   * @param string $jquerySelector A jQuery selector
   * @param string $value The value
   * @return string jQuery Sintax
   */
  public static function widgetValueAnimated($jquerySelector, $value){
    return self::getAnimatedPresintax($jquerySelector, $value);
  }

  //TEMPLATES

  /**
   * Starts the standar HTML tags for build this widget
   * @param string $progressbarId The widget id
   * @param string $htmlProperties custom HTML properties like 'style="display:none"'
   * @return YsHTML HTML tags
   */
  public static function initWidget($progressbarId , $htmlProperties = null){
    $htmlProperties = sprintf('id="%s" ', $progressbarId) . $htmlProperties;
    $template = YsHTML::getTag(YsHTML::DIV, $htmlProperties);
    return $template;
  }

  /**
   * Ends the standar HTML tags for build this widget
   * @return YsHTML HTML tags
   */
  public static function endWidget(){
    return YsHTML::getTagClosed(YsHTML::DIV);
  }

  /**
   * Protected internal function
   * Get the jQuery code to create the progressbar animation, when change the value.
   * @param string $jquerySelector A jQuery selector
   * @return string jQuery sintax
   */
  protected function getAnimatedIntervalSintax($jquerySelector){
    $newValue =  self::widgetValue($jquerySelector,YsArgument::likeVar('newValue'));
    return str_replace('$', self::$jqueryVar, "function(){
            widget = $('" . $jquerySelector . "');
            if(i <= newValue){
            support = widget.children().css('width' , i + '%')
            }else{
            support = clearInterval(interval);
            " . $newValue . ";
            }
            i++;}");
  }

  /**
   * Protected internal function
   * Get the jQuery code to create the progressbar animation, when change the value.
   * @param string $jquerySelector A jQuery selector
   * @param string $value The value
   * @return string jQuery sintax
   */
  protected static function getAnimatedPresintax($jquerySelector, $value){
    $template = 'var barVal = %s; var i = (%s <= barVal) ? 0 : barVal; var newValue = %s;var interval = %s';
    $template = sprintf($template,
      self::widgetValue($jquerySelector),
      $value,
      $value,
      YsJsFunction::setInterval(self::getAnimatedIntervalSintax($jquerySelector), self::$intervalTime)
    );
    return $template;
  }


}