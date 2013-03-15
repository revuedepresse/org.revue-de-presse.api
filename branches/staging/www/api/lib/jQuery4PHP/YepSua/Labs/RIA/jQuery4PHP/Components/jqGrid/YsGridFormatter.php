<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsGridFormatter todo description
 *
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsGridFormatter {


  private $type;
  private $decimalSeparator;
  private $thousandsSeparator;
  private $decimalPlaces;
  private $defaulValue;
  private $prefix;
  private $suffix;
  private $srcformat;
  private $newformat;
  private $target;
  private $baseLinkUrl;
  private $showAction;
  private $addParam;
  private $idName;
  private $disabled;
  private $keys;
  private $editbutton;
  private $delbutton;

  private $formatterOptions;

  function __construct($type = null, $formatterOptions= null) {
    if($type !== null){
      $this->setType($type);
    }
    if($formatterOptions !== null){
      $this->setFormatterOptions($formatterOptions);
    }
  }

  private function varsToUnset(){
    return array('type','formatterOptions');
  }

  public function getType() {
    return $this->type;
  }

  /**
   * The formatter type:
   * integer, number, currency, date, email,
   * link, showlink, checkbox, select, actions
   * @param string $type
   */
  public function setType($type) {
    $this->type = $type;
  }

  public function getDecimalseparator() {
    return $this->decimalSeparator;
  }

  /**
   * Determines the separator for the decimals
   * @param string $decimalSeparator
   */
  public function setDecimalseparator($decimalSeparator) {
    $this->decimalSeparator = $decimalSeparator;
  }

  public function getThousandsseparator() {
    return $this->thousandsSeparator;
  }

  /**
   * Determines the separator for the thousands.
   * @param string $thousandsSeparator
   */
  public function setThousandsseparator($thousandsSeparator) {
    $this->thousandsSeparator = $thousandsSeparator;
  }

  public function getDecimalplaces() {
    return $this->decimalPlaces;
  }

  /**
   * Determine how many decimal places we should have for the number
   * @param string $decimalPlaces
   */
  public function setDecimalplaces($decimalPlaces) {
    $this->decimalPlaces = $decimalPlaces;
  }

  public function getDefaulValue() {
    return $this->defaulValue;
  }

  public function setDefaulValue($defaulValue) {
    $this->defaulValue = $defaulValue;
  }

  public function getPrefix() {
    return $this->prefix;
  }

  /**
   * A text the is puted before the number
   * @param string $prefix
   */
  public function setPrefix($prefix) {
    $this->prefix = $prefix;
  }

  public function getSuffix() {
    return $this->suffix;
  }

  /**
   * The text that is added after the number
   * @param string $suffix
   */
  public function setSuffix($suffix) {
    $this->suffix = $suffix;
  }

  public function getSrcFormat() {
    return $this->srcformat;
  }

  /**
   * Is the source format - i.e. the format of the date that should be converted
   * @param string $srcformat
   */
  public function setSrcFormat($srcformat) {
    $this->srcformat = $srcformat;
  }

  public function getNewFormat() {
    return $this->newformat;
  }

  /**
   * Is the new output format
   * @param string $newformat
   */
  public function setNewFormat($newformat) {
    $this->newformat = $newformat;
  }

  public function getTarget() {
    return $this->target;
  }

  /**
   * The default value of the target options is null. When this options is set,
   * we construct a link with the target property set and the cell value put in
   * the href tag
   * @param string $target
   */
  public function setTarget($target) {
    $this->target = $target;
  }

  public function getBaseLinkUrl() {
    return $this->baseLinkUrl;
  }

  /**
   * The link
   * @param string $baseLinkUrl
   */
  public function setBaseLinkUrl($baseLinkUrl) {
    $this->baseLinkUrl = $baseLinkUrl;
  }

  public function getShowAction() {
    return $this->showAction;
  }

  /**
   * Is an additional value which is added after the baseLinkUrl
   * @param string $showAction
   */
  public function setShowAction($showAction) {
    $this->showAction = $showAction;
  }

  public function getAddParam() {
    return $this->addParam;
  }

  /**
   * Is an additional parameter that can be added after the idName property
   * @param string $addParam
   */
  public function setAddParam($addParam) {
    $this->addParam = $addParam;
  }

  public function getIdName() {
    return $this->idName;
  }

  /**
   * Is the first parameter that is added after the showAction
   * @param string $idName
   */
  public function setIdName($idName) {
    $this->idName = $idName;
  }

  public function getDisabled() {
    return $this->disabled;
  }

  /**
   * Determines if the checkbox can be changed
   * @param boolean $disabled
   */
  public function setDisabled($disabled) {
    $this->disabled = $disabled;
  }

  public function getKeys() {
    return $this->keys;
  }

  /**
   * The formatter is just experimental and will be described in next releases
   * @param string $keys
   */
  public function setKeys($keys) {
    $this->keys = $keys;
  }

  public function getEditButton() {
    return $this->editbutton;
  }

  /**
   * The formatter is just experimental and will be described in next releases
   * @param string $editbutton
   */
  public function setEditButton($editbutton) {
    $this->editbutton = $editbutton;
  }

  public function getDelButton() {
    return $this->delbutton;
  }

  /**
   * The formatter is just experimental and will be described in next releases
   * @param string $delbutton
   */
  public function setDelButton($delbutton) {
    $this->delbutton = $delbutton;
  }

  /**
   * The formatter is just experimental and will be described in next releases
   * @param string $formatterOptions
   */
  public function setFormatterOptions($formatterOptions){
    $this->formatterOptions = $formatterOptions;
  }

  public function getFormatterOptions(){
    $options = null;
    if(isset($this->formatterOptions) && $this->formatterOptions !== null){
      $options = $this->formatterOptions;
    }else{
      $options = $this->optionsToArray();
    }
    return $options;
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