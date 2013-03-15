<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsJsFunction
 *
 * @package    YepSua
 * @subpackage CommonUtil
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsJsFunction {
    
  private $body;
  private $arguments;
  private $pattern = 'function(%s){%s}';

  const JAVASCRIPT_SINTAX_SEPARATOR = ';';
  const JAVASCRIPT_ARGUMENT_SEPARATOR = ',';
  

  public function  __construct($body= null, $arguments = null)
  {
    $this->setArguments($arguments);
    if($body instanceof self){
      $this->setPattern('%s%s');
      $this->setArguments(null);
    }
    $this->setBody($body);
  }

  public function getPattern(){
    return $this->pattern;
  }

  public function setPattern($pattern){
    $this->pattern = $pattern;
  }

  public function getBody(){
    return $this->body;
  }

  public function setBody($body){
    $this->body = $body;
  }

  public function getArguments(){
    return $this->arguments;
  }

  public function setArguments($arguments){
    $this->arguments = $arguments;
  }

  public function  __toString() {
    return sprintf($this->getPattern(), $this->getArguments(),$this->getBody());
  }

  public static function setInterval($function, $interval){
    return sprintf('setInterval(%s, %s);',$function,$interval);
  }

  public static function redirect($href){
    return sprintf('window.location = "%s"',$href);
  }

  public static function cleanSintax($sintax){
    $errorSintax = array(".()",";;",",}",",]");
    $realSintax = array("",";","}","]");
    $sintax = str_replace($errorSintax, $realSintax, $sintax);
    return $sintax;
  }

}
