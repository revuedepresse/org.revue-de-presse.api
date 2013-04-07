<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsArgument
 *
 * @package    YepSua
 * @subpackage CommonUtil
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsArgument
{
  private $isQuoted;
  private $value;

  public function  __construct($value = '', $isQuoted = true)
  {
    if($value instanceof self){
      $isQuoted = $value->isQuoted();
      $value = $value->getValue();
    }
    $this->setIsQuoted($isQuoted);
    $this->setValue($value);
  }

  public function setIsQuoted($value)
  {
    $this->isQuoted = $value;
  }

  public function isQuoted()
  {
    return $this->isQuoted;
  }

  public function setValue($value)
  {
    if(is_array($value)){
      $value = YsJSON::arrayToJson($value);
      $this->setIsQuoted(false);
    }
    if(is_bool($value)){
      $value = YsUtil::booleanForJavascript($value);
      $this->setIsQuoted(false);
    }
    if($value instanceof YsJsFunction || is_numeric($value) || is_object($value)){
      $this->setIsQuoted(false);
    }
    $this->value = $value;
  }

  public function getValue()
  {
    if($this->value === null)
    {
      return '';
    }
    return sprintf($this->valuePattern(), $this->value);
  }

  protected function valuePattern()
  {
    $pattern = ($this->isQuoted()) ? "'%s'" : "%s";
    return $pattern;
  }

  public function  __toString()
  {
    return $this->getValue();
  }

  public static function likeVar($varName){
    return new YsArgument($varName, false);
  }

  public static function value($value){
    return new YsArgument($value, false);
  }

}
