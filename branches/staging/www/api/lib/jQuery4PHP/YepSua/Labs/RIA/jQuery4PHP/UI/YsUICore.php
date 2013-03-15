<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsUICore The Core of jQueryUI
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsUICore extends YsJQueryPlugin {

  const JQUERY_UI_VERSION = "Stable (1.8.6: 1.3.2+)";
  public static $uiEvent;

  public function registerOptions(){}

  public static function getInstance(){}

  /**
   * Remove the autocomplete functionality completely.
   * This will return the element back to its pre-init state.
   * @param string $jquerySelector A jQuery selector
   * @return object YsJQueryCore
   */
  public static function destroyMethod($jquerySelector = null)
  {
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::DESTROY_METHOD));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    return $jquery;
  }

  /**
   * Disable the functionality.
   * @param string $jquerySelector A jQuery selector
   * @return object YsJQueryCore
   */
  public static function disable($jquerySelector = null)
  {
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::DISABLE_METHOD));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    return $jquery;
  }

  /**
   * Enable the functionality.
   * @param string $jquerySelector A jQuery selector
   * @return object YsJQueryCore
   */
  public static function enable($jquerySelector = null)
  {
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::ENABLE_METHOD));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    return $jquery;
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
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::OPTION_METHOD));
    $jquery->addArgument(new YsArgument($optionName));
    if($value !== null) { $jquery->value($value); }
    return $jquery;
  }

  /**
   * Return the widget element
   * @param string $jquerySelector A jQuery selector
   * @return object YsJQueryCore
   */
  public static function widget($jquerySelector = null)
  {
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$uiEvent);
    $jquery->addArgument(new YsArgument(YsUIConstant::WIDGET_METHOD));
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    return $jquery;
  }

  /**
   * Disable the mouse selection in a section
   * @param string $jquerySelector A jQuery selector
   * @return object YsJQueryCore
   */
  public static function disableSelection($jquerySelector = null)
  {
    $jquery = new YsJQueryCore();
    $jquery->setEvent(YsUIConstant::DISABLE_SELECTION_METHOD);
    if($jquerySelector !== null){
      $jquery->setSelector($jquerySelector);
    }
    return $jquery;
  }

  /**
   * Render function for jQueryUI functionality
   * @see YsJQueryUtil::render()
   * @return parent::render()
   */
  public function render(){
    if($this->getOptions() !== null){
      if($this->getArgumentsBeforeOptions() !== null || $this->getArgumentsAfterOptions() !== null){
        $this->setArguments($this->getArgumentsBeforeOptions() .  $this->getOptionsLikeJson() . $this->getArgumentsAfterOptions());
      }else{
        $this->setArguments($this->getOptionsLikeJson());
      }
    }
    return parent::render();
  }

  /**
   * @see this->in()
   * @param string $jquerySelector A jQuery selector
   */
  public function thisWidget($jquerySelector){
    $this->in($jquerySelector);
    return $this;
  }

  /**
   * @see this->in()
   * @param string $jquerySelector A jQuery selector
   */
  public function thisEffect($jquerySelector){
    $this->in($jquerySelector);
    return $this;
  }

  /**
   * @see this->in()
   * @param string $jquerySelector A jQuery selector
   */
  public function thisIteraction($jquerySelector){
    $this->in($jquerySelector);
    return $this;
  }

  /**
   * @see this->in()
   * @param string $jquerySelector A jQuery selector
   */
  public function thisUtility($jquerySelector){
    $this->in($jquerySelector);
    return $this;
  }

}