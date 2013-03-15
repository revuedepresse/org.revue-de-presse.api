<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsArrayList todo description.
 *
 * @package    YepSua
 * @subpackage CommonUtil
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsArrayList extends YsUtilAutoloader{

  private $items = array();


  public function getItems(){
    return $this->items;
  }

  public function add($key, $value){
    $this->items[$key] = $value;
    return $this;
  }

  public function get($key){
    return (isset($this->items[$key])) ? $this->items[$key] : null;
  }

  public function delete($key){
    $success = false;
    if(isset($this->items[$key])){
      unset($this->items[$key]);
      $success = true;
    }
    return $success;
  }
  
}