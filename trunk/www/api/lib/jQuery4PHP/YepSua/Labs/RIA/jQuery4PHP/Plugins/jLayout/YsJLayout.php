<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsJLayout todo description
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsJLayout extends YsJQueryPlugin {

  const VERSION = "0.17";
  const LICENSE = "BSD License";
  
  const CENTER = 'center';
  const EAST = 'east';
  const WEST = 'west';
  const NORTH = 'north';
  const SOUTH = 'south';

  const GRID_TYPE = 'grid';
  const BORDER_TYPE = 'border';
  const FLOW_TYPE = 'flow';

  const HGAP = 'hgap';
  const VGAP = 'vgap';
  const COLS = 'columns';
  const ROWS = 'rows';
  const ALIGNMENT = 'alignment';
  const RESIZE = 'resize';


  public static $event = 'layout';

  public function registerOptions(){
    return   array(
              //options
               '_hgap' =>  array('key' => 'hgap', 'is_quoted' => false)
              ,'_vgap' =>  array('key' => 'vgap', 'is_quoted' => false)
              ,'_west' =>  array('key' => 'west', 'is_quoted' => false)
              ,'_center' =>  array('key' => 'center', 'is_quoted' => false)
              ,'_north' =>  array('key' => 'north', 'is_quoted' => false)
              ,'_east' =>  array('key' => 'east', 'is_quoted' => false)
              ,'_west' =>  array('key' => 'west', 'is_quoted' => false)
              ,'_south' =>  array('key' => 'south', 'is_quoted' => false)
              ,'_type' =>  array('key' => 'type', 'is_quoted' => false)
              ,'_rows' =>  array('key' => 'rows', 'is_quoted' => false)
              ,'_columns' =>  array('key' => 'columns', 'is_quoted' => false)
              ,'_items' =>  array('key' => 'items', 'is_quoted' => false)
              ,'_fill' =>  array('key' => 'fill', 'is_quoted' => false)
              ,'_alignment' =>  array('key' => 'alignment', 'is_quoted' => false)
              ,'_resize' =>  array('key' => 'resize', 'is_quoted' => false)
              // events
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

  public static function initVerticalLayout($configuration, $htmlProperties = null){
    $configuration = array_merge($configuration, array(YsJLayout::COLS => 1));
    if (isset($configuration[YsJLayout::ROWS])){
       unset($configuration[YsJLayout::ROWS]);
    }

    return self::initLayout(self::GRID_TYPE, $configuration, $htmlProperties);
  }

  public static function endVerticalLayout(){
    return self::endLayout();
  }

  public static function initHorizontalLayout($configuration, $htmlProperties = null){
    $configuration = array_merge($configuration, array(YsJLayout::ROWS => 1));
    if (isset($configuration[YsJLayout::COLS])){
       unset($configuration[YsJLayout::COLS]);
    }

    return self::initLayout(self::GRID_TYPE, $configuration, $htmlProperties);
  }

  public static function endHorizontalLayout(){
    return self::endLayout();
  }

  public static function initBorderlLayout($configuration, $htmlProperties = null){
    if (isset($configuration[YsJLayout::COLS])){
       unset($configuration[YsJLayout::COLS]);
    }
    if (isset($configuration[YsJLayout::ROWS])){
       unset($configuration[YsJLayout::ROWS]);
    }

    return self::initLayout(self::BORDER_TYPE, $configuration, $htmlProperties);
  }

  public static function endBorderLayout(){
    return self::endLayout();
  }

  public static function initNorth($panelId, $htmlProperties = null){
    return self::initSection(YsJLayout::NORTH, $panelId, $htmlProperties);
  }

  public static function endNorth(){
    return self::endSection();
  }

  public static function initWest($panelId, $htmlProperties = null){
    return self::initSection(YsJLayout::WEST, $panelId, $htmlProperties);
  }

  public static function endWest(){
    return self::endSection();
  }

  public static function initSouth($panelId, $htmlProperties = null){
    return self::initSection(YsJLayout::SOUTH, $panelId, $htmlProperties);
  }

  public static function endSouth(){
    return self::endSection();
  }

  public static function initEast($panelId, $htmlProperties = null){
    return self::initSection(YsJLayout::EAST, $panelId, $htmlProperties);
  }

  public static function endEast(){
    return self::endSection();
  }

  public static function initCenter($panelId, $htmlProperties = null){
    return self::initSection(YsJLayout::CENTER, $panelId, $htmlProperties);
  }

  public static function endCenter(){
    return self::endSection();
  }


  public static function initSection($position, $panelId, $htmlProperties = null){
    $htmlProperties = sprintf('id="%s" class="%s ui-layout-panel-section ui-panel-content ui-widget-content ui-corner-all" %s ', $panelId , $position, $htmlProperties);
    $template = YsHTML::getTag(YsHTML::DIV, $htmlProperties);
    return $template;
  }

  public static function endSection(){
    return YsHTML::getTagClosed(YsHTML::DIV);
  }

  public static function initGridLayout($configuration, $htmlProperties = null){
    return self::initLayout(self::GRID_TYPE, $configuration, $htmlProperties);
  }

  public static function endGridLayout(){
    return self::endLayout();
  }

  public static function initFlowLayout($configuration, $htmlProperties = null){
    return self::initLayout(self::FLOW_TYPE, $configuration, $htmlProperties);
  }

  public static function endFlowLayout(){
    return self::endLayout();
  }

  public static function initLayout($type, $configuration, $htmlProperties = null){
    $configuration = self::buildWidgetConfiguration($type, $configuration);
    $htmlProperties = sprintf('class="%s %s" %s',$type , $configuration, $htmlProperties);
    return YsHTML::getTag(YsHTML::DIV, $htmlProperties);
  }

  public static function endLayout(){
    return YsHTML::getTagClosed(YsHTML::DIV);
  }

  private static function buildWidgetConfiguration($type, $configuration){
    $configuration = array_merge(array('type' => $type), $configuration);
    $config = array(
        'layout' => $configuration
    );
    $config = YsJSON::optionsToJson($type, $config);
    return str_replace('"', "'", $config);
  }

  public static function build($jquerySelector = null){
    $jquery = self::getInstance();
    $jquery->setEvent(self::$event);
    if($jquerySelector !== null) { $jquery->setSelector($jquerySelector); }
    return $jquery;
  }
  
  public static function doGridLayout($jquerySelector = null){
    $jquery = self::getInstance();
    $jquery->setEvent(self::GRID_TYPE);
    if($jquerySelector !== null) { $jquery->setSelector($jquerySelector); }
    return $jquery;
  }

  public static function toggleLayout($containerSelector,$layoutSelector,$options ,$duration = 500){
    $relayout = YsJLayout::relayout($containerSelector);
    $jquery = YsJQuery::animate(
                $options,
                array('duration' => $duration,
                      'complete' => YsJQuery::likeFunction($relayout),
                      'step' => YsJQuery::likeFunction($relayout)))
                ->in($layoutSelector);
    return $jquery;
  }

  public static function toggleNorthLayout($containerSelector,$layoutSelector,$duration = 500){
    return self::toggleLayout($containerSelector, $layoutSelector, array('height' => "toggle"), $duration);
  }

  public static function toggleSouthLayout($containerSelector,$layoutSelector,$duration = 500){
    return self::toggleLayout($containerSelector, $layoutSelector, array('height' => "toggle"), $duration);
  }

  public static function toggleEastLayout($containerSelector,$layoutSelector,$duration = 500){
    return self::toggleLayout($containerSelector, $layoutSelector, array('width' => "toggle"), $duration);
  }

  public static function toggleWestLayout($containerSelector,$layoutSelector,$duration = 500){
    return self::toggleLayout($containerSelector, $layoutSelector, array('width' => "toggle"), $duration);
  }

  public static function toggleCenterLayout($containerSelector,$layoutSelector,$duration = 500){
    return self::toggleLayout($containerSelector, $layoutSelector, array('height' => "toggle",'width' => "toggle"), $duration);
  }

  public static function toggleEffect($containerSelector,$layoutSelector,$effectName,$duration = 500){
    $relayout = YsJLayout::build($containerSelector)->_resize(false);
    $jquery = YsUIEffect::toggleEffect()
      ->in($layoutSelector)
      ->effectName($effectName)
      ->options(array(
                'complete' => YsJQuery::likeFunction($relayout),
                'step' => YsJQuery::likeFunction($relayout)))
      ->duration(500);
    return $jquery;
  }

  public static function effect($containerSelector,$layoutSelector,$effectName,$duration = 500){
    $relayout = YsJLayout::build($containerSelector)->_resize(false);
    $jquery = YsUIEffect::effect()
      ->in($layoutSelector)
      ->effectName($effectName)
      ->options(array(
                'complete' => YsJQuery::likeFunction($relayout),
                'step' => YsJQuery::likeFunction($relayout)))
      ->duration(500);
    return $jquery;
  }

  public static function hide($containerSelector,$layoutSelector,$effectName,$duration = 500){
    $relayout = YsJLayout::build($containerSelector)->_resize(false);
    $jquery = YsUIEffect::hide()
      ->in($layoutSelector)
      ->effectName($effectName)
      ->options(array(
                'complete' => YsJQuery::likeFunction($relayout),
                'step' => YsJQuery::likeFunction($relayout)))
      ->duration(500);
    return $jquery;
  }

  public static function relayout($containerSelector, $options = null){
    if($options != null || sizeof($options) > 0){
      foreach($options as $value => $option){
        $relayout = YsJLayout::build($containerSelector)->$option($value);
      }
    }else{
      $relayout = YsJLayout::build($containerSelector)->_resize(false);
    }
    return $relayout;
  }

  public static function show($containerSelector,$layoutSelector,$effectName,$duration = 500){
    $relayout = YsJLayout::build($containerSelector)->_resize(false);
    $jquery = YsUIEffect::show()
      ->in($layoutSelector)
      ->effectName($effectName)
      ->options(array(
                'complete' => YsJQuery::likeFunction($relayout),
                'step' => YsJQuery::likeFunction($relayout)))
      ->duration(500);
    return $jquery;
  }

}