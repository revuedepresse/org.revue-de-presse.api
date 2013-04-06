<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsUIDatepicker The jQuery UI Datepicker is a highly configurable plugin that
 * adds datepicker functionality to your pages. You can customize the date
 * format and language, restrict the selectable date ranges and add in buttons
 * and other navigation options easily.
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsUIDatepicker  extends YsUICore {

  public static $uiEvent = YsUIConstant::DATEPICKER_WIDGET;

  /**
   * @return array options and events for this widget
   */
  public function registerOptions() {
    return   array(
               //options
               '_disabled' =>  array('key' => 'disabled', 'is_quoted' => false)
              ,'_altField' =>  array('key' => 'altField', 'is_quoted' => false)
              ,'_altFormat' =>  array('key' => 'altFormat', 'is_quoted' => false)
              ,'_appendText' =>  array('key' => 'appendText', 'is_quoted' => false)
              ,'_autoSize' =>  array('key' => 'autoSize', 'is_quoted' => false)
              ,'_buttonImage' =>  array('key' => 'buttonImage', 'is_quoted' => false)
              ,'_buttonImageOnly' =>  array('key' => 'buttonImageOnly', 'is_quoted' => false)
              ,'_buttonText' =>  array('key' => 'buttonText', 'is_quoted' => false)
              ,'_calculateWeek' =>  array('key' => 'calculateWeek', 'is_quoted' => false)
              ,'_changeMonth' =>  array('key' => 'changeMonth', 'is_quoted' => false)
              ,'_changeYear' =>  array('key' => 'changeYear', 'is_quoted' => false)
              ,'_closeText' =>  array('key' => 'closeText', 'is_quoted' => false)
              ,'_constrainInput' =>  array('key' => 'constrainInput', 'is_quoted' => false)
              ,'_currentText' =>  array('key' => 'currentText', 'is_quoted' => false)
              ,'_dateFormat' =>  array('key' => 'dateFormat', 'is_quoted' => false)
              ,'_dayNames' =>  array('key' => 'dayNames', 'is_quoted' => false)
              ,'_dayNamesMin' =>  array('key' => 'dayNamesMin', 'is_quoted' => false)
              ,'_dayNamesShort' =>  array('key' => 'dayNamesShort', 'is_quoted' => false)
              ,'_defaultDate' =>  array('key' => 'defaultDate', 'is_quoted' => false)
              ,'_duration' =>  array('key' => 'duration', 'is_quoted' => false)
              ,'_firstDay' =>  array('key' => 'firstDay', 'is_quoted' => false)
              ,'_gotoCurrent' =>  array('key' => 'gotoCurrent', 'is_quoted' => false)
              ,'_hideIfNoPrevNext' =>  array('key' => 'hideIfNoPrevNext', 'is_quoted' => false)
              ,'_isRTL' =>  array('key' => 'isRTL', 'is_quoted' => false)
              ,'_maxDate' =>  array('key' => 'maxDate', 'is_quoted' => false)
              ,'_minDate' =>  array('key' => 'minDate', 'is_quoted' => false)
              ,'_monthNames' =>  array('key' => 'monthNames', 'is_quoted' => false)
              ,'_monthNamesShort' =>  array('key' => 'monthNamesShort', 'is_quoted' => false)
              ,'_navigationAsDateFormat' =>  array('key' => 'navigationAsDateFormat', 'is_quoted' => false)
              ,'_nextText' =>  array('key' => 'nextText', 'is_quoted' => false)
              ,'_numberOfMonths' =>  array('key' => 'numberOfMonths', 'is_quoted' => false)
              ,'_prevText' =>  array('key' => 'prevText', 'is_quoted' => false)
              ,'_selectOtherMonths' =>  array('key' => 'selectOtherMonths', 'is_quoted' => false)
              ,'_shortYearCutoff' =>  array('key' => 'shortYearCutoff', 'is_quoted' => false)
              ,'_showAnim' =>  array('key' => 'showAnim', 'is_quoted' => false)
              ,'_showButtonPanel' =>  array('key' => 'showButtonPanel', 'is_quoted' => false)
              ,'_showCurrentAtPos' =>  array('key' => 'showCurrentAtPos', 'is_quoted' => false)
              ,'_showMonthAfterYear' =>  array('key' => 'showMonthAfterYear', 'is_quoted' => false)
              ,'_showOn' =>  array('key' => 'showOn', 'is_quoted' => false)
              ,'_showOptions' =>  array('key' => 'showOptions', 'is_quoted' => false)
              ,'_showOtherMonths' =>  array('key' => 'showOtherMonths', 'is_quoted' => false)
              ,'_showWeek' =>  array('key' => 'showWeek', 'is_quoted' => false)
              ,'_stepMonths' =>  array('key' => 'stepMonths', 'is_quoted' => false)
              ,'_weekHeader' =>  array('key' => 'weekHeader', 'is_quoted' => false)
              ,'_yearRange' =>  array('key' => 'yearRange', 'is_quoted' => false)
              ,'_yearSuffix' =>  array('key' => 'yearSuffix', 'is_quoted' => false)
              // events
              ,'_beforeShow' => array('key' => 'beforeShow', 'is_quoted' => false)
              ,'_beforeShowDay' => array('key' => 'beforeShowDay', 'is_quoted' => false)
              ,'_onChangeMonthYear' => array('key' => 'onChangeMonthYear', 'is_quoted' => false)
              ,'_onClose' => array('key' => 'onClose', 'is_quoted' => false)
              ,'_onSelect' => array('key' => 'onSelect', 'is_quoted' => false));
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
   * Open a datepicker in a "dialog" box.
   * @param string $date The initial date for the date picker as either a Date
   * or a string in the current date format.
   * @param string $onSelect A callback function when a date is selected.
   * The function receives the date text and date picker instance as parameters.
   * @param array $settings The new settings for the date picker. 
   * @param array $pos The position of the top/left of the dialog as array(x, y) or
   * a MouseEvent that contains the coordinates.
   * If not specified the dialog is centered on the screen.
   * @return object YsJQueryCore
   */
  public static function dialog($date , $onSelect = null, $settings = null,
                                $pos = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::DIALOG_WIDGET));
    if($date !== null){
      $jquery->value($date);
    }
    if($onSelect !== null){
      $jquery->value($onSelect);
    }
    if($settings !== null){
      $jquery->value($settings);
    }
    if($pos !== null){
      $jquery->value($pos);
    }
    return $jquery;
  }

  /**
   * Determine whether a date picker has been disabled.
   * @param string $jquerySelector A jQuery selector
   * @return object YsJQueryCore
   */
  public static function isDisabled($jquerySelector = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::IS_DISABLED));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    return $jquery;
  }

  /**
   * Close a previously opened date picker.
   * @param string $jquerySelector A jQuery selector
   * @return object YsJQueryCore
   */
  public static function hide($jquerySelector = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::HIDE_EFFECT));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    return $jquery;
  }

  /**
   * Call up a previously attached date picker.
   * @param string $jquerySelector A jQuery selector
   * @return object YsJQueryCore
   */
  public static function show($jquerySelector = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::SHOW_EFFECT));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    return $jquery;
  }

  /**
   * Redraw a date picker, after having made some external modifications.
   * @param string $jquerySelector A jQuery selector
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
   * Returns the current date for the datepicker or null if no date has been
   * selected.
   * @param string $jquerySelector A jQuery selector
   * @return object YsJQueryCore
   */
  public static function getDate($jquerySelector = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::GET_DATE_METHOD));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    return $jquery;
  }

  /**
   * Returns the current date for the datepicker or null if no date has been
   * selected.
   * @param string/number $date  A number of days from today (e.g. +7) or a
   * string of values and periods ('y' for years, 'm' for months, 'w' for weeks,
   * 'd' for days, e.g. '+1m +7d'), or null to clear the selected date.
   * @return object YsJQueryCore
   */
  public static function setDate($date = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::SET_DATE_METHOD));
    if($date !== null){
      $jquery->value($date);
    }
    return $jquery;
  }

  /**
   * Synchronize two datepickers,
   * should be used with the function ::doSynchronization()
   * @return string javascript sintax
   */
  public function synchronize(){
    return sprintf('var dates = %s', $this);
  }

  /**
   * Returns the javascript function to synchronize two datepickers
   * @param string $datePickerFromId The datepicker for MIN date
   * @param string $datePickerToId The datepicker for MAX date
   * @return object YsJsFunction
   */
  public static function doSynchronization($datePickerFromId, $datePickerToId){
    $body = self::getSynchronizeSintax($datePickerFromId, $datePickerToId);
    return new YsJsFunction($body, 'selectedDate');
  }

  /**
   * Protected internal function
   * Returns the javascript code to synchronize two datepickers
   * @param string $datePickerFromId The datepicker for MIN date
   * @param string $datePickerToId The datepicker for MAX date
   * @return string javascript sintax
   */
  protected static function getSynchronizeSintax($datePickerFromId, $datePickerToId){
    $pattern = 'var option = this.id == "%s" ? "minDate" : "maxDate";
        var option = this.id == "%s" ? "maxDate" : "minDate";
				var instance = $(this).data("datepicker");
				var date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
				dates.not(this).datepicker("option", option, date);';
    $pattern = sprintf($pattern,$datePickerFromId, $datePickerToId);
    $pattern = str_ireplace('$', self::$jqueryVar,$pattern);
    return $pattern;
  }

  //TEMPLATES

  /**
   * Returns the standar HTML tags for build this widget like a "input:text"
   * @param string $datepickerId The datepicker Id
   * @param string $htmlProperties custom HTML properties like 'style="display:none"'
   * @return YsHTML HTML tags
   */
  public static function input($datepickerId, $htmlProperties = null){
    return YsHTML::getTagClosed(YsHTML::INPUT, sprintf('id="%s" %s ', $datepickerId , $htmlProperties));
  }

  /**
   * Returns the standar HTML tags for build this widget inline
   * @param string $datepickerId The datepicker Id
   * @param string $htmlProperties custom HTML properties like 'style="display:none"'
   * @return YsHTML HTML tags
   */
  public static function inline($datepickerId, $htmlProperties = null){
    return YsHTML::getTagClosed(YsHTML::DIV, sprintf('id="%s" %s ', $datepickerId , $htmlProperties));
  }

}