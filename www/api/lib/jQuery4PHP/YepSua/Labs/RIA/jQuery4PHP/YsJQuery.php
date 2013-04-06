<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

include_once dirname(__FILE__) . '/../../CommonUtil/YsUtilAutoloader.php';
YsUtilAutoloader::register();

/**
 * YsJQuery todo description.
 *
 * @package    YepSua
 * @subpackage RIA/jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id
 */
class YsJQuery extends YsJQueryCore
{

  static protected $instance = null;

  public $jquery;

  /**
   * Construct function
   */
  public function  __construct()
  {
    $this->jquery = new YsJQuerySintax();
    $this->jquery->setSelector('document');
    $this->jquery->setEvent(YsJQueryConstant::READY_EVENT);
  }

  /**
   * Retrieves a instance of this class.
   * @return object self::$instance
   */
  public static function newInstance()
  {
    $object = __CLASS__;
    self::$instance = new $object();
    return self::$instance;
  }

  /**
   * Set the jquery selector and context to execute the current sintax.
   * @param string $selector jQuery selector
   * @param string $context Javascript Object
   * @return object
   */
  public function in($selector, $context = null)
  {
    $this->jquery->setSelector($selector);
    if($context !== null)
    {
      $this->jquery->setContext($context);
    }
    return $this;
  }

  public function inVar($selector, $context = null)
  {
    $this->in($selector, $context);
    $this->jquery->setIsSelectorUnquoted(true);
    return $this;
  }

  /**
   * Set the function to execute.
   * @param string $function The function code
   * @return object
   */
  public function setFunction($function)
  {
    $this->jquery->setArguments(new YsJsFunction($function));
    return $this;
  }

  /**
   * Magic
   */
  public function  __call($name, $arguments)
  {
    if(substr($name,0,2) == 'on')
    {
      $event = strtolower(substr($name,2,1)) . substr($name,3);
      $this->jquery->setEvent($event);
      return $this;
    }
    parent::__call($name, $arguments);
  }

  /**
   * Execute the function and rend the sintax
   * @param string $function
   * @return object
   */
  public function execute($function = null)
  {
    if(func_num_args() > 1){
      $this->clearJQueryList();
      $jqueryFinal = new YsJQuerySintax();
      $argAux = func_get_arg(0);
      $postSintax = null;
      for($i=1; $i < func_num_args(); $i++ ){
        $argNext =  func_get_arg($i);
        if($argNext instanceof YsJQueryCore){
          if($argNext->getSelector() == $argAux->getSelector() || $argNext->getSelector() == null){
            $argAux->addAccesorsWithPattern($argNext->getEvent(),$argNext->getArguments());
            }
            else{
            $argAux->addPostSintax($postSintax);
            $postSintax = null;
            $this->getJQueryList()->add($argAux->getSelector(), $argAux);
            $argAux = func_get_arg($i);
          }
        }else{
          $argAux->addPostSintax(YsJsFunction::JAVASCRIPT_SINTAX_SEPARATOR . $argNext);
        }
      }
      $this->getJQueryList()->add($argAux->getSelector(), $argAux);
      $sintax = '';
      foreach($this->getJQueryList()->getItems() as $jquery => $jquerySintax){
        $sintax .= $jquerySintax;
      }
      $this->setFunction($sintax);
    }
    else
    {
      $this->setFunction($function);
    }
    $this->jquery->render();
    return $this;
  }

  /**
   * Execute when the element is ready
   */
  public function executeOnReady($function = null)
  {
    if(func_num_args() > 1){
      $this->clearJQueryList();
      $jqueryFinal = new YsJQuerySintax();
      $argAux = func_get_arg(0);
      $postSintax = null;
      for($i=1; $i < func_num_args(); $i++ ){
        $argNext =  func_get_arg($i);
        if($argNext instanceof YsJQueryCore){
          if($argNext->getSelector() == $argAux->getSelector() || $argNext->getSelector() == null){
            $argAux->addAccesorsWithPattern($argNext->getEvent(),$argNext->getArguments());
            }
            else{
            $argAux->addPostSintax($postSintax);
            $postSintax = null;
            $this->getJQueryList()->add($argAux->getSelector(), $argAux);
            $argAux = func_get_arg($i);
          }
        }else{
          $argAux->addPostSintax(YsJsFunction::JAVASCRIPT_SINTAX_SEPARATOR . $argNext);
        }
      }
      $this->getJQueryList()->add($argAux->getSelector(), $argAux);
      $sintax = '';
      foreach($this->getJQueryList()->getItems() as $jquery => $jquerySintax){
        $sintax .= $jquerySintax;
      }
      $this->setFunction($sintax);
    }
    else
    {
      $this->setFunction($function);
    }
    return $this->jquery->executeOnReady();
  }

  /**
   * Get the jquery sintax
   * @return string jQuery sintax
   */
  public function getSintax()
  {
    return $this->jquery->getSintax();
  }

  public function getJQueryObject(){
    return $this->jquery;
  }

  /**
   * Magic
   * @return string This object
   */
  public function __toString()
  {
    return $this->jquery->renderWithJsTags();
  }

  public function confirmation($confirmation, $onFailure = null){
      $this->jquery->confirmation($confirmation,$onFailure);
      return $this;
    }

    public function condition($condition, $onFailure = null){
      $this->jquery->condition($condition,$onFailure);
      return $this;
    }

}