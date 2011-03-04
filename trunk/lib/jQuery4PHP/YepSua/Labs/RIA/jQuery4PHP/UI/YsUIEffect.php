<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsUIEffect Support to jquery.ui effects
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsUIEffect extends YsUICore {

  public static $uiEvent = YsUIConstant::ACCORDION_WIDGET;

  /**
   * @return array options and events for this widget
   */
  public function registerOptions() {
    return null;
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

  /**
   * The jQuery UI effects core extends the animate function to be able to
   * animate colors as well
   * @param string/aray $properties A map of CSS properties that the animation will move toward.
   * @param string/number $duration A string or number determining how long the animation will run.
   * @param string $easing A string indicating which easing function to use for the transition.
   * @param string/YsJsFunction $callback A function to call once the animation is complete.
   * @see YsJQuery::animate()
   * @return object YsJQueryCore
   */
  public static function animate($properties = null, $duration = null,  $easing = null, $callback= null){
    $jquery = new YsJQueryCore();
    if($properties !== null) $jquery->properties($properties);
    if($duration !== null) $jquery->duration($duration);
    if($easing !== null) $jquery->easing($easing);
    if($callback !== null) $jquery->callback($callback);
    $jquery->setEvent(YsJQueryConstant::ANIMATE_EFFECT);
    return $jquery;
  }

  /**
   * Adds the specified class if it is not present, and removes the specified
   * class if it is present, using an optional transition.
   * @param string $class The class name
   * @param integer $duration The duration
   * @return object YsJQueryCore
   */
  public static function toggleClass($class = null, $duration = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(YsJQueryConstant::TOGGLE_CLASS_EVENT);
    if($class !== null) $jquery->className($class);
    if($duration !== null) $jquery->duration($duration);
    return $jquery;
  }

  /**
   * Adds the specified class to each of the set of matched elements with an
   * optional transition between the states.
   * @param string $class The class name
   * @param integer $duration The duration
   * @return object YsJQueryCore
   */
  public static function addClass($class = null, $duration = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(YsJQueryConstant::ADD_CLASS_EVENT);
    if($class !== null) $jquery->className($class);
    if($duration !== null) $jquery->duration($duration);
    return $jquery;
  }

  /**
   * Removes all or specified class from each of the set of matched
   * elements with an optional transition between the states.
   * @param string $class The class name
   * @param integer $duration The duration
   * @return object YsJQueryCore
   */
  public static function removeClass($class = null, $duration = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(YsJQueryConstant::REMOVE_CLASS_EVENT);
    if($class !== null) $jquery->className($class);
    if($duration !== null) $jquery->duration($duration);
    return $jquery;
  }

  /**
   * Switches from the class defined in the first argument to the class defined
   * as second argument, using an optional transition.
   * @param string $removeClass The CSS class that will be removed.
   * @param string $addClass The CSS class that will be added.
   * @param string/integer $duration A string representing one of the three
   * predefined speeds ("slow", "normal", or "fast") or the number
   * of milliseconds to run the animation (e.g. 1000).
   * @return object YsJQueryCore
   */
  public static function switchClass($removeClass = null, $addClass = null,$duration = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(YsJQueryConstant::SWITCH_CLASS_EVENT);
    if($removeClass !== null) $jquery->className($removeClass);
    if($addClass !== null) $jquery->className($addClass);
    if($duration !== null) $jquery->duration($duration);
    return $jquery;
  }

  /**
   * Uses a specific effect on an element (without the show/hide logic).
   * @param string $effect The effect to be used. Possible values: 'blind',
   * 'bounce', 'clip', 'drop', 'explode', 'fold', 'highlight', 'puff',
   * 'pulsate', 'scale', 'shake', 'size', 'slide', 'transfer'.
   * @param string/array $options A object/hash including specific options for
   * the effect.
   * @param string/integer $duration A string representing one of the three
   * predefined speeds ("slow", "normal", or "fast") or the number
   * of milliseconds to run the animation (e.g. 1000).
   * @param string/YsJsFunction $callback A function that is called after the
   * effect is completed.
   * @return object YsJQueryCore
   */
  public static function effect($effectName = null, $options = null, $duration = null,$callback = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(YsJQueryConstant::EFFECT_EVENT);
    if($effectName !== null) $jquery->effectName($effectName);
    if($options !== null) $jquery->options($options);
    if($duration !== null) $jquery->duration($duration);
    if($callback !== null) $jquery->callback($callback);
    return $jquery;
  }

  /**
   * The enhanced toggle method optionally accepts jQuery UI advanced effects.
   * @param string $effect The effect to be used. Possible values: 'blind',
   * 'bounce', 'clip', 'drop', 'explode', 'fold', 'highlight', 'puff',
   * 'pulsate', 'scale', 'shake', 'size', 'slide', 'transfer'.
   * @param string/array $options A object/hash including specific options for
   * the effect.
   * @param string/integer $duration A string representing one of the three
   * predefined speeds ("slow", "normal", or "fast") or the number
   * of milliseconds to run the animation (e.g. 1000).
   * @param string/YsJsFunction $callback A function that is called after the
   * effect is completed.
   * @return object YsJQueryCore
   */
  public static function toggleEffect($effectName = null, $options = null, $duration = null,$callback = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(YsJQueryConstant::TOGGLE_EFFECT);
    if($effectName !== null) $jquery->effectName($effectName);
    if($options !== null) $jquery->options($options);
    if($duration !== null) $jquery->duration($duration);
    if($callback !== null) $jquery->callback($callback);
    return $jquery;
  }

  /**
   * The enhanced hide method optionally accepts jQuery UI advanced effects.
   * @param string $effect The effect to be used. Possible values: 'blind',
   * 'bounce', 'clip', 'drop', 'explode', 'fold', 'highlight', 'puff',
   * 'pulsate', 'scale', 'shake', 'size', 'slide', 'transfer'.
   * @param string/array $options A object/hash including specific options for
   * the effect.
   * @param string/integer $duration A string representing one of the three
   * predefined speeds ("slow", "normal", or "fast") or the number
   * of milliseconds to run the animation (e.g. 1000).
   * @param string/YsJsFunction $callback A function that is called after the
   * effect is completed.
   * @return object YsJQueryCore
   */
  public static function hide($effectName = null, $options = null, $duration = null,$callback = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(YsJQueryConstant::HIDE_EFFECT);
    if($effectName !== null) $jquery->effectName($effectName);
    if($options !== null) $jquery->options($options);
    if($duration !== null) $jquery->duration($duration);
    if($callback !== null) $jquery->callback($callback);
    return $jquery;
  }

  /**
   * The enhanced show method optionally accepts jQuery UI advanced effects.
   * @param string $effect The effect to be used. Possible values: 'blind',
   * 'bounce', 'clip', 'drop', 'explode', 'fold', 'highlight', 'puff',
   * 'pulsate', 'scale', 'shake', 'size', 'slide', 'transfer'.
   * @param string/array $options A object/hash including specific options for
   * the effect.
   * @param string/integer $duration A string representing one of the three
   * predefined speeds ("slow", "normal", or "fast") or the number
   * of milliseconds to run the animation (e.g. 1000).
   * @param string/YsJsFunction $callback A function that is called after the
   * effect is completed.
   * @return object YsJQueryCore
   */
  public static function show($effectName = null, $options = null, $duration = null,$callback = null){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(YsJQueryConstant::SHOW_EFFECT);
    if($effectName !== null) $jquery->effectName($effectName);
    if($options !== null) $jquery->options($options);
    if($duration !== null) $jquery->duration($duration);
    if($callback !== null) $jquery->callback($callback);
    return $jquery;
  }

}