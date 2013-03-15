<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsUIDialog A dialog is a floating window that contains a title bar and a
 * content area. The dialog window can be moved, resized and closed with the
 * 'x' icon by default.
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsUIDialog extends YsUICore {

  public static $uiEvent = YsUIConstant::DIALOG_WIDGET;

  /**
   * @return array options and events for this widget
   */
  public function registerOptions() {
    return   array(//options
               '_disabled' =>  array('key' => 'disabled', 'is_quoted' => false),
               '_autoOpen' => array('key' => 'autoOpen', 'is_quoted' => false),
               '_buttons' => array('key' => 'buttons', 'is_quoted' => false),
               '_closeOnEscape' => array('key' => 'closeOnEscape', 'is_quoted' => false),
               '_closeText' => array('key' => 'closeText', 'is_quoted' => true),
               '_dialogClass' => array('key' => 'dialogClass', 'is_quoted' => true),
               '_draggable' => array('key' => 'draggable', 'is_quoted' => false),
               '_height' => array('key' => 'height', 'is_quoted' => false),
               '_hide' => array('key' => 'hide', 'is_quoted' => true),
               '_maxHeight' => array('key' => 'maxHeight', 'is_quoted' => false),
               '_maxWidth' => array('key' => 'maxWidth', 'is_quoted' => false),
               '_minHeight' => array('key' => 'minHeight', 'is_quoted' => false),
               '_minWidth' => array('key' => 'minWidth', 'is_quoted' => false),
               '_modal' => array('key' => 'modal', 'is_quoted' => false),
               '_position' => array('key' => 'position', 'is_quoted' => false),
               '_resizable' => array('key' => 'resizable', 'is_quoted' => false),
               '_show' => array('key' => 'show', 'is_quoted' => true),
               '_stack' => array('key' => 'stack', 'is_quoted' => false),
               '_title' => array('key' => 'title', 'is_quoted' => true),
               '_width' => array('key' => 'width', 'is_quoted' => false),
               '_zIndex' => array('key' => 'zIndex', 'is_quoted' => false),
               // events
               '_beforeclose' => array('key' => 'beforeclose', 'is_quoted' => false),
               '_open' => array('key' => 'open', 'is_quoted' => false),
               '_focus' => array('key' => 'focus', 'is_quoted' => false),
               '_dragStart' => array('key' => 'dragStart', 'is_quoted' => false),
               '_drag' => array('key' => 'drag', 'is_quoted' => false),
               '_dragStop' => array('key' => 'dragStop', 'is_quoted' => false),
               '_resizeStart' => array('key' => 'resizeStart', 'is_quoted' => false),
               '_resize' => array('key' => 'resize', 'is_quoted' => false),
               '_resizeStop' => array('key' => 'resizeStop', 'is_quoted' => false),
               '_close' => array('key' => 'close', 'is_quoted' => false)
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

  // WIDGET METHODS

  /**
   * Close the dialog.
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

  /**
   * Returns true if the dialog is currently open.
   * @param string $jquerySelector A jQuery selector
   * @return object YsJQueryCore
   */
  public static function isOpen($jquerySelector = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::IS_OPEN_METHOD));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    return $jquery;
  }

  /**
   * Move the dialog to the top of the dialogs stack.
   * @param string $jquerySelector A jQuery selector
   * @return object YsJQueryCore
   */
  public static function moveToTop($jquerySelector = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::MOVE_TO_TOP_METHOD));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    return $jquery;
  }

  /**
   * Open the dialog.
   * @param string $jquerySelector A jQuery selector
   * @return object YsJQueryCore
   */
  public static function open($jquerySelector = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::OPEN_METHOD));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    return $jquery;
  }

  //TEMPLATES

  /**
   * Starts the standar HTML tags for build this widget
   * @param string $dialogId The widget id
   * @param string $htmlProperties custom HTML properties like 'style="display:none"'
   * @return YsHTML HTML tags
   */
  public static function initWidget($dialogId, $htmlProperties = null){
    return YsHTML::getTag(YsHTML::DIV, sprintf('id="%s" %s ', $dialogId , $htmlProperties));
  }

  /**
   * Ends the standar HTML tags for build this widget
   * @return YsHTML HTML tags
   */
  public static function endWidget(){
    return YsHTML::getTagClosed(YsHTML::DIV);
  }

}