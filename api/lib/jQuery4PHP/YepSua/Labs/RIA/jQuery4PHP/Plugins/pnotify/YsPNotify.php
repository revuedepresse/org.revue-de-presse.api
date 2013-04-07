<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsPnotify todo description
 *
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsPNotify extends YsJQueryPlugin {

  const VERSION = "1.0.1";
  const LICENSE = "GNU Affero GPL";

  const ERROR_TYPE = "error";
  const NOTICE_TYPE = "notice";

  const TITLE = 'pnotify_title';
  const TEXT = 'pnotify_text';
  const TYPE = 'pnotify_type';
  const STACK = 'pnotify_stack';
  const ADDCLASS = 'pnotify_addclass';
  const WIDTH = 'pnotify_width';
  const HEIGHT = 'pnotify_height';
  const INSERT_BRS = 'pnotify_insert_brs';
  const NONBLOCK = 'pnotify_nonblock';
  const NONBLOCK_OPACITY = 'pnotify_nonblock_opacity';
  const MOUSE_RESET = 'pnotify_mouse_reset';
  const HIDE = 'pnotify_hide';
  const CLOSER = 'pnotify_closer';
  const HISTORY = 'pnotify_history';
  const ANIMATED_SPEED = 'pnotify_animate_speed';
  const OPACITY = 'pnotify_opacity';
  const NOTICE_ICON = 'pnotify_notice_icon';
  const ERROR_ICON = 'pnotify_error_icon';
  const SHADOW = 'pnotify_shadow';
  const MIN_HEIGHT = 'pnotify_min_height';
  const MAX_HEIGHT = 'pnotify_max_height';
  const MIN_WIDTH = 'pnotify_min_width';
  const MAX_WIDTH = 'pnotify_max_width';
  const ANIMATION = 'pnotify_animation';
  const DELAY = 'pnotify_delay';
  const X = 'x';
  const Y = 'y';

  const EFFECT_IN = 'effect_in';
  const EFFECT_OUT = 'effect_out';

  const DIR1= 'dir1';
  const DIR2= 'dir2';
  const PUSH= 'push';
  const FIRSTPOS1= 'firstpos1';
  const FIRSTPOS2= 'firstpos2';

  const BEFORE_OPEN = 'pnotify_before_open';
  const AFTER_OPEN = 'pnotify_after_open';
  const BEFORE_CLOSE = 'pnotify_before_close';
  const AFTER_CLOSE = 'pnotify_after_close';
  const BEFORE_INIT = 'pnotify_before_init';
  const AFTER_INIT = 'pnotify_after_init';
  const QUEUE_REMOVE = 'pnotify_queue_remove';

  
  public static $event = 'pnotify';
  public static $eventRemoveAll = 'pnotify_remove_all';
  public static $eventDefault = '.defaults';
  public static $eventDisplay = 'pnotify_display';
  public static $eventRemove = 'pnotify_remove';


  public function registerOptions(){
    return   array(
              //options
               '_pnotify_title' =>  array('key' => 'pnotify_title', 'is_quoted' => false),
               '_pnotify_text' =>  array('key' => 'pnotify_text', 'is_quoted' => false),
               '_pnotify_type' =>  array('key' => 'pnotify_type', 'is_quoted' => false),
               '_pnotify_stack' =>  array('key' => 'pnotify_stack', 'is_quoted' => false),
               '_pnotify_addclass' =>  array('key' => 'pnotify_addclass', 'is_quoted' => false),
               '_pnotify_width' =>  array('key' => 'pnotify_width', 'is_quoted' => false),
               '_pnotify_height' =>  array('key' => 'pnotify_height', 'is_quoted' => false),
               '_pnotify_insert_brs' =>  array('key' => 'pnotify_insert_brs', 'is_quoted' => false),
               '_pnotify_nonblock' =>  array('key' => 'pnotify_nonblock', 'is_quoted' => false),
               '_pnotify_nonblock_opacity' =>  array('key' => 'pnotify_nonblock_opacity', 'is_quoted' => false),
               '_pnotify_mouse_reset' =>  array('key' => 'pnotify_mouse_reset', 'is_quoted' => false),
               '_pnotify_hide' =>  array('key' => 'pnotify_hide', 'is_quoted' => false),
               '_pnotify_closer' =>  array('key' => 'pnotify_closer', 'is_quoted' => false),
               '_pnotify_history' =>  array('key' => 'pnotify_history', 'is_quoted' => false),
               '_pnotify_animate_speed' =>  array('key' => 'pnotify_animate_speed', 'is_quoted' => false),
               '_pnotify_opacity' =>  array('key' => 'pnotify_opacity', 'is_quoted' => false),
               '_pnotify_notice_icon' =>  array('key' => 'pnotify_notice_icon', 'is_quoted' => false),
               '_pnotify_error_icon' =>  array('key' => 'pnotify_error_icon', 'is_quoted' => false),
               '_pnotify_shadow' =>  array('key' => 'pnotify_shadow', 'is_quoted' => false),
               '_pnotify_min_height' =>  array('key' => 'pnotify_min_height', 'is_quoted' => false),
               '_pnotify_max_height' =>  array('key' => 'pnotify_max_height', 'is_quoted' => false),
               '_pnotify_min_width' =>  array('key' => 'pnotify_min_width', 'is_quoted' => false),
               '_pnotify_max_width' =>  array('key' => 'pnotify_max_width', 'is_quoted' => false),
               '_pnotify_animation' =>  array('key' => 'pnotify_animation', 'is_quoted' => false),
               '_pnotify_delay' =>  array('key' => 'pnotify_delay', 'is_quoted' => false),
              // events
              '_pnotify_before_open' =>  array('key' => 'pnotify_before_open', 'is_quoted' => false),
              '_pnotify_after_open' =>  array('key' => 'pnotify_after_open', 'is_quoted' => false),
              '_pnotify_before_close' =>  array('key' => 'pnotify_before_close', 'is_quoted' => false),
              '_pnotify_after_close' =>  array('key' => 'pnotify_after_close', 'is_quoted' => false),
              '_pnotify_before_init' =>  array('key' => 'pnotify_before_init', 'is_quoted' => false),
              '_pnotify_after_init' =>  array('key' => 'pnotify_after_init', 'is_quoted' => false),
              '_pnotify_queue_remove' =>  array('key' => 'pnotify_queue_remove', 'is_quoted' => false),

              );
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

  public static function build($options = null){
    $jquery = self::getInstance();
    $jquery->setEvent(self::$event);
    if($options !== null){
      if(!is_array($options)){
        $options = array(self::TEXT => $options);
      }
      $jquery->setOptions($options);
    }
    return $jquery;
  }

  public static function say($options = null){
    $buildOptions = array();
    if(!is_array($options)){
      $buildOptions[self::TEXT] = $options;
      $buildOptions[self::NOTICE_ICON] = '';
    }else{
      $buildOptions[self::NOTICE_ICON] = '';
    }
    return self::build($buildOptions);
  }

  public static function error($options = null){
    $buildOptions[self::TYPE] = self::ERROR_TYPE;
    if(!is_array($options)){
      $buildOptions[self::TEXT] = $options;
    }
    return self::build($buildOptions);
  }

  public static function notice($options = null){
    $buildOptions[self::TYPE] = self::NOTICE_TYPE;
    if(!is_array($options)){
      $buildOptions[self::TEXT] = $options;
    }
    return self::build($buildOptions);
  }

  public static function alarm($options = null){
    $buildOptions[self::TYPE] = self::ERROR_TYPE;
    if(!is_array($options)){
      $buildOptions[self::TEXT] = $options;
      $buildOptions[self::ERROR_ICON] = self::getIcon(YsUIConstant::ICON_COMMENT);
    }else{
      if(!isset($options[self::ERROR_ICON])){
        $buildOptions[self::ERROR_ICON] = self::getIcon(YsUIConstant::ICON_COMMENT);
      }else{
        $buildOptions[self::ERROR_ICON] = $options[self::ERROR_ICON];
      }
    }
    return self::build($buildOptions);
  }

  public static function defaults($options = null){
    $sintaxArray = array();
    $jqueryDynamic = new YsJQueryDynamic();
    if($options !== null){
      if(is_array($options)){
        $i = 0;
        foreach ($options as $key => $value){
          $jquery = self::getInstance();
          $jquery->setIsOnlyAccesors(true);
          $jquery->setEvent(self::$event . self::$eventDefault);
          $jquery->addAccesorsWithPattern($key, new YsArgument($value), '.%s = %s');
          $sintaxArray[$i++] = $jquery;
        }
        $jqueryDynamic->build($sintaxArray);
      }
    }
    return $jqueryDynamic;
  }

  public static function tooltip($jquerySelector , $message = null, $options = null){
    $tooltipVar = str_replace(array('#','.','<','>'), "" , $jquerySelector);
    $x = isset($options[self::X]) ? $options[self::X] : "+12";
    $y = isset($options[self::Y]) ? $options[self::Y] : "+12";
    $sintax = new YsJQueryDynamic(
        YsJQuery::mouseout()->handler(sprintf('%s.pnotify_remove()',$tooltipVar))->in($jquerySelector)
       ,YsJQuery::mousemove()->handler(
          new YsJsFunction(sprintf('%s.css({"top": event.clientY%s, "left": event.clientX%s})',$tooltipVar, $x ,$y), 'event'))->in($jquerySelector)
       ,YsJQuery::mouseover()->handler(sprintf('%s.pnotify_display()',$tooltipVar))->in($jquerySelector)
      );
    $jquery = $sintax->getJQueryObject();
    if(!isset($options[self::TEXT])){
      if($message !== null){
        $options[self::TEXT] = $message;
      }else{
        $options[self::TEXT] = YsJQuery::attr('title')->in($jquerySelector);
      }
    }
    $options = array_merge($options, self::getOptionForTooltip());
    $jquery->setPreSintax(sprintf('var %s = %s;',$tooltipVar,YsPNotify::build($options),$tooltipVar));
    return $jquery->getAllSintax();
  }

  public static function getIcon($icoName){
    return sprintf('%s %s', YsUIConstant::ICON_CLASS ,$icoName);
  }

  private static function getOptionForTooltip(){
    return array(
      self::HIDE => false,
      self::CLOSER => false,
      self::ANIMATED_SPEED => 100,
      self::HISTORY => false,
      self::OPACITY => 0.9,
      self::NOTICE_ICON => ' ',
      self::STACK => false,
      self::AFTER_INIT => self::getAfterInitTooltip(),
      self::BEFORE_OPEN => self::getBeforeOpenTooltip()
    );
  }

  private static function getAfterInitTooltip(){
    $function = new YsJsFunction();
    $function->setArguments('pnotify');
    $function->setBody('pnotify.mouseout(function(){pnotify.pnotify_remove();});');
    return $function;
  }

  private static function getBeforeOpenTooltip(){
    $function = new YsJsFunction();
    $function->setArguments('pnotify');
    $function->setBody('pnotify.pnotify({pnotify_before_open: null});return false;');
    return $function;
  }

  public static function removeAll(){
    $jquery = self::getInstance();
    $jquery->setEvent(self::$eventRemoveAll);
    return $jquery;
  }

  public static function display($varName){
    $jquery = self::getInstance();
    $jquery->setEvent(self::$eventRemoveAll);
    return $jquery;
  }

  public static function remove($varName){
    $jquery = self::getInstance();
    $jquery->setEvent(self::$eventRemove);
    return $jquery;
  }

}