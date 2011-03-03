<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsUIPanel Create a panel with the jquery.ui styles
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsUIPanel extends YsUICore {
 
  // DIALOG METHODS AND OPTIONS

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

  public static function initWidget($panelId, $htmlProperties = null){
    $htmlProperties = sprintf('id="%s" class="ui-panel-content ui-widget-content ui-corner-all" %s ',$panelId , $htmlProperties);
    $template = YsHTML::getTag(YsHTML::DIV, $htmlProperties);
    return $template;
  }

  public static function title($label, $icon = null, $htmlProperties = null){
    $htmlProperties = sprintf('%s class="ui-panel-title ui-widget-header ui-corner-all"', $htmlProperties);
    $template = YsHTML::getTag(YsHTML::H3, $htmlProperties);
    $template .= ($icon === null) ? $label : self::getLabel($label, $icon) ;
    $template .= YsHTML::getTagClosed(YsHTML::H3);
    return $template;
  }

  public static function initFooter($htmlProperties = null){
    $htmlProperties = sprintf('%s class="ui-panel-footer ui-widget-content ui-helper-clearfix"', $htmlProperties);
    $template = YsHTML::getTag(YsHTML::DIV, $htmlProperties);
    return $template . '<p class="ui-panel-footer-content">';
  }

  public static function endFooter(){
    return '</p>' . YsHTML::getTagClosed(YsHTML::DIV);
  }

  public static function endWidget(){
    return YsHTML::getTagClosed(YsHTML::DIV);
  }

  public static function initGrid($htmlProperties = null){
    return YsHTML::getTag(YsHTML::TABLE, $htmlProperties) . YsHTML::getTag(YsHTML::TR);
  }

  public static function initGridSection($htmlProperties = null){
    $htmlProperties = sprintf(' class="ui-panel-section" %s ', $htmlProperties);
    $template = YsHTML::getTag(YsHTML::TD, $htmlProperties);
    return $template;
  }

  public static function endGridSection(){
    return YsHTML::getTagClosed(YsHTML::TD);
  }

  public static function addRow(){
    return YsHTML::getTagClosed(YsHTML::TR) . YsHTML::getTag(YsHTML::TR);
  }

  public static function endGrid(){
    return YsHTML::getTagClosed(YsHTML::TR) . YsHTML::getTagClosed(YsHTML::TABLE);
  }

  public static function initContent($htmlProperties = null){
    $htmlProperties = sprintf(' style="%s" %s ', 'height:60%;overflow:auto;', $htmlProperties);
    $template = YsHTML::getTag(YsHTML::P, $htmlProperties);
    return $template;
  }

  public static function endContent(){
    return YsHTML::getTagClosed(YsHTML::P);
  }

  protected static function getTitleIconTemplate($icon, $align='left'){
    return sprintf('<span style="margin: 0px 5px 0pt 0pt; float: %s;" class="ui-icon %s"></span>', $align,$icon);
  }

  protected static function getLabel($label, $icon){
    $align = YsUIPosition::$LEFT_POSITION;
    $margin = '-25';
    if(is_array($icon)){
      $align = (isset($icon[1])) ? $icon[1] : YsUIPosition::$LEFT_POSITION ;
      $icon = (isset($icon[0])) ? $icon[0] : YsUIConstant::ICON_ALERT ;
      $margin = '0';
    }
    return self::getTitleIconTemplate($icon,$align) . '<span style="margin-left: '. $margin . 'px">' . $label . '</span>';
  }

}