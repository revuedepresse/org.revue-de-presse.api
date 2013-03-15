<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsGridRecord todo description
 *
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsGridRecord {

  private $attributes;


  public function  __construct() {
    $this->removeAllAttributes();
  }

  public function setAttribute($id, $value){
    $this->attributes[$id] = $value;
  }

  public function removeAttribute($id){
    if(isset($this->attributes[$id])){
      unset($this->attributes[$id]);
    }
  }

  public function removeAllAttributes(){
    $this->attributes = null;
    $this->attributes = array();
  }

  public function getAttributes(){
    return $this->attributes;
  }

}