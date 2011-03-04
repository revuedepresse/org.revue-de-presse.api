<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsGridResponse TODO description
 *
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsGridResponse {

  public static $ROOT_NODE_NAME = 'rows';
  public static $DEFAULT_ENCODING = '1.0';

  private $page;
  private $total;
  private $records;
  private $userdata;
  private $row;
  private $encoding;
  private $xmlVersion;


  private $response;

  private function varsToUnset(){
    return array('response','row','encoding','xmlVersion');
  }

  public function  __construct() {
    $this->renewUserData();
    $this->renewRows();
    $this->setEncoding(YsXML::$DEFAULT_ENCODING);
    $this->setXMLVersion(YsXML::$DEFAULT_XML_VERSION);
  }

  public function getPage() {
    return $this->page;
  }

  public function setPage($page) {
    $this->page = $page;
  }

  public function getTotal() {
    return $this->total;
  }

  public function setTotal($total) {
    $this->total = $total;
  }

  public function getRecords() {
    return $this->records;
  }

  public function setRecords($records) {
    $this->records = $records;
  }

  public function getUserdata() {
    return $this->userData;
  }

  public function setUserdata($userdata) {
    $this->userdata = $userData;
  }

  public function addUserData($key, $value){
    $this->userdata[$key] = $value;
  }

  public function renewUserData(){
    $this->userData = array();
  }

  public function getEncoding() {
    return $this->encoding;
  }

  public function setEncoding($encoding) {
    $this->encoding = $encoding;
  }

  public function getResponse() {
    return $this->response;
  }

  public function setResponse($response) {
    $this->response = $response;
  }

  public function getXMLVersion() {
    return $this->xmlVersion;
  }

  public function setXMLVersion($xmlVersion) {
    $this->xmlVersion = $xmlVersion;
  }

  public function getRow() {
    return $this->row;
  }

  public function setRow($row) {
    $this->row = $row;
  }
  
  public function addRow($key, $data = null){
    if(!is_numeric($key)){
      throw new YsException("",4001);
    }
    $this->row[$key] = $data;
  }
  
  public function addGridRow(YsGridRow $row){
    $this->row[$row->getId()] = $row->getCellsArray();
  }

  private function rowsToXML($rows = null){
    $rows = ($rows === null ) ? $this->buildRowsResponseForXML() : $rows;
    $xml = '';
    foreach($rows as $nodes => $node){
      foreach($node as $key => $value){
        if(is_array($value)){
          if($key == 'userdata'){
            foreach($value as $name => $userData){
              $xml .= sprintf('%s%s%s',YsXML::getTag($key, sprintf('name="%s"',$name)),$userData,YsXML::getTagClosed($key));
            }
          }
          if($key == 'row'){
            foreach($value as $id => $cells){
              $xmlCell = '';
              foreach($cells as $cell){
                $xmlCell .= sprintf('%s%s%s',YsXML::getTag('cell'),$cell,YsXML::getTagClosed('cell'));
              }
              $xml .= sprintf('%s%s%s',YsXML::getTag($key, sprintf('id="%s"',$id)),$xmlCell,YsXML::getTagClosed($key));
            }
          }
        }else{
          $xml .= sprintf('%s%s%s',YsXML::getTag($key),$value,YsXML::getTagClosed($key));
        }
      }
    }
    $xml = sprintf('%s%s%s',YsXML::getTag(self::$ROOT_NODE_NAME),$xml,YsXML::getTagClosed(self::$ROOT_NODE_NAME));
    $xml = sprintf('%s%s',YsXML::getHeaderDocument($this->getXMLVersion(),$this->getEncoding()),$xml);
    return $xml;
  }

  private function buildRowsResponseForXML(){
    $rows = array_merge($this->varsToArray(),array('row' => $this->row));
    return $rows = array(self::$ROOT_NODE_NAME => $rows);
  }

  private function buildRowsResponseForJSON(){
    $json = array();
    $rows = array();
    $i = 0;
    foreach($this->row as $key => $cell){
      $row = array();
      $row['id'] = $key;
      $row['cell'] = $cell;
      $rows[$i++] = $row;
    }
    //$rows = array_merge($this->varsToArray(),$rows);
    $json = array_merge($this->varsToArray(),array(self::$ROOT_NODE_NAME => $rows));
    return $json;
  }

  private function rowsToJSON(){
    return $this->buildRowsResponseForJSON();
  }

  public function renewRows(){
    $this->row = array();
  }

  public function buildResponseAsXML(){
    return $this->rowsToXML();
  }

  public function buildResponseAsJSON(){
    $json = YsJSON::arrayToJson($this->rowsToJSON());
    $json = YsJsFunction::cleanSintax($json);
    return $json;
  }

  private function varsToArray(){
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