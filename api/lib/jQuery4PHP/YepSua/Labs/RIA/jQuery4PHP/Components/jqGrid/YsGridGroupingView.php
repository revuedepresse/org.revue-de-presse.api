<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsGridGroupingView todo description
 *
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsGridGroupingView {

  private $groupField;
  private $groupOrder;
  private $groupText;
  private $groupColumnShow;
  private $groupSummary;
  private $showSummaryOnHide;
  private $groupDataSorted;
  private $groupCollapse;
  private $plusicon;
  private $minusicon;
  private $groupingViewOptions;

  private function varsToUnset(){
    return array('groupingViewOptions');
  }

  public function getGroupField() {
    return $this->groupField;
  }

  /**
   * Defines the name from  colModel on which we group.
   * The first value is the first lavel, the second values is the second level
   * and etc.
   * @param array()/YsGridField $groupField
   */
  public function setGroupField($groupField) {
    $gridFields = array();
    $i=0;
    foreach(func_get_args() as $fields){
      if($fields instanceof YsGridField){
        $gridFields[$i++] = $fields->getIndex();
      }
    }
    $groupField = (sizeof($gridFields) > 0) ? $gridFields : $groupField;
    $this->groupField = $groupField;
  }

  public function getGroupOrder() {
    return $this->groupOrder;
  }

  /**
   * Defines the initial sort order of the group level.
   * Can be asc for ascending or desc for descending order.
   * If the grouping is enabled the default value is asc.
   * @param array()/string $groupOrder
   */
  public function setGroupOrder($groupOrder) {
    $groupOrder = (is_array($groupOrder)) ? $groupOrder : array($groupOrder);
    $this->groupOrder = $groupOrder;
  }

  public function getGroupText() {
    return $this->groupText;
  }

  /**
   * Defines the grouping header text for the group level that will be displayed
   * in the grid. By default if defined the value if {0} which means that the
   * group value name will be displayed. It is possible to specify another
   * value {1} which meant the the total cont of this group will be displayed
   * too. It is possible to set here any valid html content.
   * @param array()/string $groupText
   */
  public function setGroupText($groupText) {
    $groupText = (is_array($groupText)) ? $groupText : array($groupText);
    $this->groupText = $groupText;
  }

  public function getGroupColumnShow() {
    return $this->groupColumnShow;
  }

  /**
   * Show/Hide the column on which we group. The value here should be a boolean
   * true/false for the group level. If the grouping is enabled we set this
   * value to true.
   * @param array()/string $groupColumnShow
   */
  public function setGroupColumnShow($groupColumnShow) {
    $groupColumnShow = (is_array($groupColumnShow)) ? $groupColumnShow : array($groupColumnShow);
    $this->groupColumnShow = $groupColumnShow;
  }

  public function getGroupSummary() {
    return $this->groupSummary;
  }

  /**
   * Enable or disable the summary (footer) row of the current group level.
   * If grouping is set the default value for the group is false.
   * @param boolean $groupSummary
   */
  public function setGroupSummary($groupSummary) {
    $groupSummary = (is_array($groupSummary)) ? $groupSummary : array($groupSummary);
    $this->groupSummary = $groupSummary;
  }

  public function getShowSummaryOnHide() {
    return $this->showSummaryOnHide;
  }

  /**
   * Show or hide the summary (footer) row when we collapse the group.
   * @param boolean $showSummaryOnHide
   */
  public function setShowSummaryOnHide($showSummaryOnHide) {
    $this->showSummaryOnHide = $showSummaryOnHide;
  }

  public function getGroupDataSorted() {
    return $this->groupDataSorted;
  }

  /**
   * If this parameter is set to true we send a additional parameter to the
   * server in order to tell him to sort the data. This way all the sorting is
   * done at server leaving the grid only to display the grouped data.
   * If this parameter is false additionally before to display the data we
   * make our own sorting in order to support grouping. This of course slow
   * down the speed on relative big data. This parameter is not valid is the
   * datatype is local
   * @param boolean $groupDataSorted
   */
  public function setGroupDataSorted($groupDataSorted) {
    $this->groupDataSorted = $groupDataSorted;
  }

  public function getGroupCollapse() {
    return $this->groupCollapse;
  }

  /**
   * Defines if the initially the grid should show or hide the detailed rows of
   * the group.
   * @param boolean $groupCollapse
   */
  public function setGroupCollapse($groupCollapse) {
    $this->groupCollapse = $groupCollapse;
  }

  public function getPlusIcon() {
    return $this->plusicon;
  }
  
  /**
   * Set the icon from jQuery UI Theme Roller that will be used if the grouped
   * row is collapsed
   * @param string $plusicon
   */
  public function setPlusIcon($plusicon) {
    $this->plusicon = $plusicon;
  }

  public function getMinusIcon() {
    return $this->minusicon;
  }

  /**
   * Set the icon from jQuery UI Theme Roller that will be used if the grouped
   * row is expanded
   * @param <type> $minusicon
   */
  public function setMinusIcon($minusicon) {
    $this->minusicon = $minusicon;
  }

  public function getGroupingViewOptions() {
    $options = null;
    if(isset($this->groupingViewOptions) && $this->groupingViewOptions !== null){
      $options = $this->groupingViewOptions;
    }else{
      $options = $this->optionsToArray();
    }
    return $options;
  }

  public function setGroupingViewOptions($groupingViewOptions) {
    $this->groupingViewOptions = $groupingViewOptions;
  }

  private function optionsToArray(){
    $config = array();
    $vars = get_class_vars(__CLASS__);
    foreach($this->varsToUnset() as $value){
      unset($vars[$value]);
    }
    foreach($vars as $var => $value){
      if(isset($this->$var)){
        $config[$var] = $this->$var;
      }
    }
    return $config;
  }
}