<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Custom Buttons are a way to define your own button and action in the pager.
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsGridCustomButton {
    
  private $caption;
  private $buttonicon;
  private $onClickButton;
  private $position;
  private $title;
  private $cursor;
  private $id;
  private $sepclass;
  private $sepcontent;

  private $isSeparator;

  private function varsToUnset(){
    return array( 'isSeparator');
  }

  public function getCaption() {
    return $this->caption;
  }

  /**
   * @param string $caption The caption of the button, can be a empty string.
   */
  public function setCaption($caption) {
    $this->caption = $caption;
  }

  public function getButtonIcon() {
    return $this->buttonicon;
  }

  /**
   * @param string $buttonicon Is the ui icon name from UI theme icon set.
   * If this option is set to “none” only the text appear.
   */
  public function setButtonIcon($buttonicon) {
    $this->buttonicon = $buttonicon;
  }

  public function getOnClickButton() {
    return $this->onClickButton;
  }

  /**
   * Action to be performed when a button is clicked.
   * @param YsJsFunction $onClickButton Default null
   */
  public function setOnClickButton($onClickButton) {
    $this->onClickButton = $onClickButton;
  }

  public function getPosition() {
    return $this->position;
  }

  /**
   * @param string $position (“first” or “last”) The position where the button
   * will be added (i.e., before or after the standard buttons).
   */
  public function setPosition($position) {
    $this->position = $position;
  }

  public function getTitle() {
    return $this->title;
  }

  /**
   * A tooltip for the button.
   * @param string $title The tooltip.
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  public function getCursor() {
    return $this->cursor;
  }

  /**
   * Determines the cursor when we mouseover the element.
   * @param <type> $cursor (default pointer) 
   */
  public function setCursor($cursor) {
    $this->cursor = $cursor;
  }

  public function getId() {
    return $this->id;
  }

  /**
   * If set defines the id of the button (actually the id of TD element)
   * for future manipulation.
   * @param string $id The button id.
   */
  public function setId($id) {
    $this->id = $id;
  }

  public function getSeparatorCclass() {
    return $this->sepclass;
  }

  /**
   * Represent the separator class defined in ui-jqgrid.
   * You cancustomize your own class.
   * Use only if the button is a separator.
   * @param string $sepclass The class name.
   */
  public function setSeparatorClass($sepclass) {
    $this->sepclass = $sepclass;
  }

  public function getSeparatorContent() {
    return $this->sepcontent;
  }

  /**
   * Represent the content that can be put in the separator element
   * @param string $sepcontent The content
   */
  public function setSeparatorContent($sepcontent) {
    $this->sepcontent = $sepcontent;
  }

  public function getIsSeparator() {
    return $this->isSeparator;
  }

  /**
   * True si the buton is a separator, Default false.
   * @param boolean $isSeparator The boolean value
   */
  public function setIsSeparator($isSeparator) {
    $this->isSeparator = $isSeparator;
  }

  /**
   * @param string $jQuerySelector
   * @param string $pager
   * @return YsJQuery The YsJQuery object
   */
  public function render($jQuerySelector,$pager){
    if($this->isSeparator){
       $render = YsJQGrid::navSeparatorAdd($pager,$this->optionsToArray());
    }else{
       $render = YsJQGrid::navButtonAdd($pager,$this->optionsToArray());
    }
    $render->in($jQuerySelector);
    return $render;
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