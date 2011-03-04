<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsGridFilterToolbar todo description
 *
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsGridFilterToolbar{

  private $autosearch;
  private $beforeSearch;
  private $afterSearch;
  private $beforeClear;
  private $afterClear;
  private $searchOnEnter;
  private $stringResult;
  private $groupOp;
  private $defaultSearch;
  private $triggerToolbar;
  private $clearToolbar;
  private $toggleToolbar;


  public function getAutosearch() {
    return $this->autosearch;
  }

  /**
   * Search is performed according to the following rules:
   * for text element when a Enter key is pressed while inputting values
   * and search is performed. For select element when the value changes.
   * The search parameter in grid is set to true and ajax call is made.
   * @param boolean $autosearch
   */
  public function setAutosearch($autosearch) {
    $this->autosearch = $autosearch;
  }

  public function getBeforeSearch() {
    return $this->beforeSearch;
  }

  /**
   * Event which fires before a search.
   * It is called before triggering the grid.
   * If the event return true triggering does not occur.
   * In this case you can construct your own search parameters and trigger
   * the grid to search the data. Any other return value causes triggering.
   * @param YsJsFunction $beforeSearch
   */
  public function setBeforeSearch($beforeSearch) {
    $this->beforeSearch = $beforeSearch;
  }

  public function getAfterSearch() {
    return $this->afterSearch;
  }

  /**
   * Event which fires after a search
   * @param YsJsFunction $afterSearch
   */
  public function setAfterSearch($afterSearch) {
    $this->afterSearch = $afterSearch;
  }

  public function getBeforeClear() {
    return $this->beforeClear;
  }

  /**
   * Event which fires before clearing entered values
   * (i.e.,clearToolbar is activated).It is called before clearing the data
   * from search elements. If the event return true triggering does not occur.
   * In this case you can construct your own search parameters and trigger
   * the grid. Any other return value causes triggering.
   * @param YsJsFunction $beforeClear
   */
  public function setBeforeClear($beforeClear) {
    $this->beforeClear = $beforeClear;
  }

  public function getAfterClear() {
    return $this->afterClear;
  }

  /**
   * Event which fires after clearing entered values
   * (i.e., clearToolbar activated)
   * @param YsJsFunction $afterClear
   */
  public function setAfterClear($afterClear) {
    $this->afterClear = $afterClear;
  }

  public function getSearchOnEnter() {
    return $this->searchOnEnter;
  }

  /**
   * Determines how the search should be applied.
   * If this option is true see the autosearch option.
   * If the option is false then the search is performed immediately when the
   * user pres some character
   * @param boolean $searchOnEnter
   */
  public function setSearchOnEnter($searchOnEnter) {
    $this->searchOnEnter = $searchOnEnter;
  }

  public function getStringResult() {
    return $this->stringResult;
  }

  /**
   * Determines how to post the data on which we perform searching.
   * When the this option is false the posted data is in key:value pair,
   * if the option is true, the posted data is equal on those as
   * in searchGrid method
   * @param boolean $stringResult
   */
  public function setStringResult($stringResult) {
    $this->stringResult = $stringResult;
  }

  public function getGroupOp() {
    return $this->groupOp;
  }

  /**
   * This option is valid only if the option stringReasult is set to true and
   * determines the group operation. Can have values AND and OR
   * @param string $groupOp
   */
  public function setGroupOp($groupOp) {
    $this->groupOp = $groupOp;
  }

  public function getDefaultSearch() {
    return $this->defaultSearch;
  }
  
  /**
   * The option determines the default search operator when a search is
   * performed. If any valid option is set, then it will be used for the default
   * operator in all fields
   * @param string $defaultSearch
   */
  public function setDefaultSearch($defaultSearch) {
    $this->defaultSearch = $defaultSearch;
  }

  public function getTriggerToolbar() {
      return $this->triggerToolbar;
  }

  /**
   * When this method is called a search is performed, the search parameter in
   * grid becomes true and ajax call is made to the server
   * @param YsJsFunction $triggerToolbar
   */
  public function setTriggerToolbar($triggerToolbar) {
      $this->triggerToolbar = $triggerToolbar;
  }

  public function getClearToolbar() {
      return $this->clearToolbar;
  }

  /**
   * When called clear the search values send a request with search option set
   * to false and set the default one if available
   * @param YsJsFunction $clearToolbar
   */
  public function setClearToolbar($clearToolbar) {
      $this->clearToolbar = $clearToolbar;
  }

  public function getToggleToolbar() {
      return $this->toggleToolbar;
  }

  /**
   * Toggeles the toolbar with the search elements
   * @param YsJsFunction $toggleToolbar
   */
  public function setToggleToolbar($toggleToolbar) {
      $this->toggleToolbar = $toggleToolbar;
  }

  public function render($jQuerySelector, $newApi =  false){
    $render = YsJQGrid::filterGrid($this->optionsToArray(), $newApi);
    $render->in($jQuerySelector);
    return $render;
  }

  public function optionsToArray(){
    $config = array();
    $vars = get_class_vars(__CLASS__);
    foreach($vars as $var => $value){
      if(isset($this->$var)){
        $config[$var] = $this->$var;
      }
    }
    return $config;
  }

}