<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of YsGridRow
 *
 * @author oyepez
 */
class YsGridRow {

  private $id;
  private $cells;
  
  private $levelField;
  private $leftField;
  private $rightField;
  private $isLeaf;
  private $isExpanded;
  private $isTreeResponse;

  public function  __construct() {
    $this->cells = new ArrayObject();
    $this->isExpanded = false;
    $this->isLeaf = false;
    $this->isTreeResponse = false;
  }

  public function getId() {
    return $this->id;
  }

  public function setId($id) {
    $this->id = $id;
  }

  public function getCells() {
    return $this->cells;
  }

  public function setCells(ArrayObject $cells) {
    $this->cells = $cells;
  }
    
  public function getCellsArray(){
    if($this->isTreeResponse){
      $this->buildCellsForTreeResponse();
    }
    return $this->cells->getArrayCopy();
  }

  private function buildCellsForTreeResponse(){
    if(isset($this->levelField)){ $this->newCell($this->levelField);  }
    if(isset($this->leftField)){  $this->newCell($this->leftField); }
    if(isset($this->rightField)){ $this->newCell($this->rightField);  }
    if(isset($this->isLeaf)){  $this->newCell($this->isLeaf); }
    if(isset($this->isExpanded)){ $this->newCell($this->isExpanded);  }
  }

  public function newCell($value){
    if($this->isTreeResponse){
      $arg = new YsArgument($value, false);
      $value = $arg->getValue();
    }
    $this->cells->append($value);
  }

  /** TREE RESPONSE **/

  public function getLevelField() {
    return $this->levelField;
  }

  public function setLevelField($levelField) {
    $this->levelField = $levelField;
  }

  public function getLeftfield() {
    return $this->leftField;
  }

  public function setLeftField($leftField) {
    $this->leftField = $leftField;
  }

  public function getRightField() {
    return $this->rightField;
  }

  public function setRightField($rightField) {
    $this->rightField = $rightField;
  }

  public function isLeaf() {
    return $this->isLeaf;
  }

  public function setIsLeaf($isLeaf) {
    $this->isLeaf = $isLeaf;
  }

  public function isExpanded() {
    return $this->isExpanded;
  }

  public function setIsExpanded($isExpanded) {
    $this->isExpanded = $isExpanded;
  }

  public function isTreeResponse() {
    return $this->isTreeResponse;
  }

  public function setIsTreeResponse($isTreeResponse) {
    $this->isTreeResponse = $isTreeResponse;
  }

}
