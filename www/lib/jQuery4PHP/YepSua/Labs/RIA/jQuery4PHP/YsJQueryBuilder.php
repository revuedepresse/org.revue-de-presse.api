<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsJQueryBuilder todo description.
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
 
class YsJQueryBuilder {

    const VERSION = "v1.1.0";
    const STATUS = 'BETA';
    const JQUERY_VERSION = 'v1.4.4';

    public static $jqueryVar = 'jQuery';

    private $listener;
    private $sintax;
    private $options = array();
    private $selector;
    private $pattern;
    private $event;
    private $arguments;
    private $internalSintax;
    private $accesors;
    private $context;
    private $preSintax;
    private $postSintax;
    private $jqueryList;
    private $argumentsBeforeOptions;
    private $argumentsAfterOptions;
    private $isOnlyAccesors = false;
    
    public function getJQueryList(){
      return $this->jqueryList;
    }

    public function setArgumentsBeforeOptions($argumentsBeforeOptions){
      $this->argumentsBeforeOptions = $argumentsBeforeOptions;
    }
    
    public function addArgumentsBeforeOptions($argumentsBeforeOptions){
      $this->argumentsBeforeOptions .= $argumentsBeforeOptions . YsJsFunction::JAVASCRIPT_ARGUMENT_SEPARATOR;
    }
    public function getArgumentsBeforeOptions(){
      return $this->argumentsBeforeOptions;
    }

    public function setArgumentsAfterOptions($argumentsAfterOptions){
      $this->argumentsAfterOptions = $argumentsAfterOptions;
    }

    public function addArgumentsAfterOptions($argumentsAfterOptions){
      $this->argumentsAfterOptions .= YsJsFunction::JAVASCRIPT_ARGUMENT_SEPARATOR . $argumentsAfterOptions;
    }
    public function getArgumentsAfterOptions(){
      return $this->argumentsAfterOptions;
    }

    public function setJQueryList($jqueryList){
      $this->jqueryList = $jqueryList;
    }

    public function  clearJQueryList() {
      $this->jqueryList = new YsArrayList();
    }

    public function getSintax()
    {
      if(isset($this->preSintax))
      {
        $this->setSintax($this->preSintax . YsJsFunction::JAVASCRIPT_SINTAX_SEPARATOR . $this->sintax);
      }
      if(isset($this->postSintax))
      {
        $this->setSintax($this->sintax . YsJsFunction::JAVASCRIPT_SINTAX_SEPARATOR . $this->postSintax);
      }
      return $this->cleanJQuerySintax($this->sintax);
    }

    public function getSelector()
    {
      return $this->selector;
    }

    public function getPattern()
    {
      return $this->pattern;
    }

    public function getArguments()
    {
      $arguments = substr($this->arguments, strlen($this->arguments) -1 , strlen($this->arguments));
      if(trim($arguments) == YsJsFunction::JAVASCRIPT_ARGUMENT_SEPARATOR){
        $arguments =  substr($this->arguments, 0  , strlen($this->arguments) -1);
      }else{
        $arguments = $this->arguments;
      }
      return $arguments;
    }

    public function getContext()
    {
      return $this->context;
    }

    public function setContext($context){
      $this->context = $context;
      return $this;
    }

    public function getAccesors()
    {
      return $this->accesors;
    }

    public function setAccesors($accesors)
    {
      $this->accesors = $accesors;
      return $this;
    }

    public function addAccesors($accesors)
    {
      $this->setAccesors($this->getAccesors() . $accesors);
      return $this;
    }

    public function addAccesorsWithPattern($accesor, $value, $pattern= '.%s(%s)')
    {
      $this->addAccesors(sprintf($pattern,$accesor,$value));
      return $this;
    }

    public function setPattern($pattern)
    {
      $this->pattern = $pattern;
      return $this;
    }

    public function setArguments($arguments)
    {
      $this->arguments = $arguments;
      $this->internalSintax .= $arguments;
      return $this;
    }

    public function getInternalSintax(){
      $sintax = substr($this->internalSintax, 11);
      $sintax = substr($sintax, 0 , strlen($sintax) - 1);
      return $sintax;
    }

    public function getAllSintax(){
      $sintax = substr($this->internalSintax, 11);
      $sintax = substr($sintax, 0 , strlen($sintax) - 1);
      if(isset($this->preSintax))
      {
        $sintax = $this->preSintax . YsJsFunction::JAVASCRIPT_SINTAX_SEPARATOR .$sintax;
      }
      if(isset($this->postSintax))
      {
        $sintax = $sintax . YsJsFunction::JAVASCRIPT_SINTAX_SEPARATOR . $this->postSintax;
      }
      return $sintax;
    }

    public function getEvent()
    {
      return $this->event;
    }

    public function setEvent($event)
    {
      $this->event = $event;
      return $this;
    }

    public function setSelector($selector)
    {
      $this->selector = $selector;
      return $this;
    }

    public function setSintax($sintax)
    {
      $this->sintax = $sintax;
    }

    public function setOptions($options)
    {
      $this->options = $options;
      return $this;
    }

    public function addOption($key,$option)
    {
      $this->options[$key] = $option;
      return $this;
    }

    public function getOption($key)
    {
      return isset($this->options[$key]) ? $this->options[$key] : null ;
    }

    public function getOptionValue($key)
    {
      return isset($this->options[$key]['value']) ? $this->options[$key]['value'] : null ;
    }

    public function isOptionQuoted($key)
    {
      return isset($this->options[$key]['value']) ? $this->options[$key]['is_quoted'] : null ;
    }

    public function setIsOptionQuoted($key, $value)
    {
      isset($this->options[$key]) ? $this->options[$key]['is_quoted'] = $value : null ;
    }

    public function isOnlyAccesors()
    {
      return $this->isOnlyAccesors;
    }

    public function setIsOnlyAccesors($boolean)
    {
      return $this->isOnlyAccesors = $boolean;
    }

    public function getOptions()
    {
      return $this->options;
    }

    public function appendInOption($key, $value){
      isset($this->options[$key]['value']) ? $this->options[$key]['value'] .= $value : null ;
    }

    public function prependInOption($key, $value){
      isset($this->options[$key]['value']) ? $this->options[$key]['value'] = $value . $this->options[$key]['value']  : null ;
    }
    
    public function getOptionsLikeJson()
    {
      return YsJSON::arrayToJson($this->getOptions());
      //return YsUtil::optionsToJson($this->getOptions());
    }

    public function getJQueryValues()
    {
      if($this->isOnlyAccesors()){
        return array(self::$jqueryVar,$this->getSelector(),$this->getContext(),$this->getEvent(),$this->getAccesors(), ';');
      }else{
        return array(self::$jqueryVar,$this->getSelector(),$this->getContext(),$this->getEvent(),$this->getArguments(),$this->getAccesors(), ';');
      }
    }

    public function setListener($listener)
    {
      $this->listener = $listener;
      return $this;
    }

    public function addPreSintax($pre_sintax){
      $this->setPreSintax($pre_sintax . YsJsFunction::JAVASCRIPT_SINTAX_SEPARATOR . $this->getPreSintax());
    }

    public function addPostSintax($post_sintax){
      if(is_array($post_sintax)){
        foreach($post_sintax as $sintax){
          $this->setPostSintax($this->getPostSintax() . $sintax . YsJsFunction::JAVASCRIPT_SINTAX_SEPARATOR);
        }
      }else{
        $this->setPostSintax($this->getPostSintax() . $post_sintax . YsJsFunction::JAVASCRIPT_SINTAX_SEPARATOR);
      }
    }

    public function addSintax($sintax){
      if(isset($this->sintax) && $this->sintax !== null){
        $this->setSintax($this->getSintax() . YsJsFunction::JAVASCRIPT_SINTAX_SEPARATOR . $sintax);
      }else{
        $this->setSintax($sintax);
      }
    }

    public function addArgument($argument){
      if($argument === null || trim($argument) !== ''){
        if(isset($this->arguments)){
          $this->setArguments($this->getArguments() . YsJsFunction::JAVASCRIPT_ARGUMENT_SEPARATOR . $argument);
        }else{
          $this->setArguments($argument);
        }
      }
    }

    public function getListener()
    {
      return $this->listener;
    }

    public function getPreSintax(){
      return $this->preSintax;
    }

    public function setPreSintax($pre_sintax){
      $this->preSintax = $pre_sintax;
      return $this;
    }

    public function getPostSintax(){
      return $this->postSintax;
    }

    public function setPostSintax($post_sintax){
      $this->postSintax = $post_sintax;
      return $this;
    }


    public function render()
    {
      if($this->getPattern() instanceof YsJQueryPattern){
        $this->getPattern()->setIsStringSelector($this->unquotedSelectors());
        $this->getPattern()->setIsSetSelector(isset($this->selector));
        $this->getPattern()->setIsInContext(isset($this->context));
        if(!isset($this->arguments) && $this->event == 'ready'){
          $this->getPattern()->setIsSetArguments(false);
          $this->event = null;
        }
        $this->getPattern()->setIsOnlyAccesors($this->isOnlyAccesors);
      }
      $jqueryVars = YsJQueryPattern::getJQueryVarsPattern();
      $this->destroy();
      $sintax = '';

      if(is_array($this->getEvent())){
        $i = 0;
        foreach($this->getEvent() as $event => $arguments){
          if($i == 0 ){
            $this->setEvent($event);
            $this->setArguments($arguments);
          }else{
            $this->addAccesorsWithPattern($event,$arguments);
          }
          $i++;
        }
        $sintax = str_replace($jqueryVars, $this->getJQueryValues(), $this->getPattern()) . ';';
      }else{
        $sintax = str_replace($jqueryVars, $this->getJQueryValues(), $this->getPattern());
      }
      $this->setSintax($sintax);
      return $this->getSintax();
    }

    public function renderWithJsTags(){
      $response  = YsUtil::init_javascript_tag();
        $response .= $this->render();
      $response .= YsUtil::end_javascript_tag();
      return $response;
    }

    public function executeOnLoad($selector = 'document', $context = null){
      $onLoad = new YsJQuery();
      return $onLoad->onLoad()->in($selector,$context)->execute($this->render());
    }

    public function executeOnReady($function = null){
      $onLoad = new YsJQuery();
      return $onLoad->onReady()->in($this->getSelector())->execute($this->render());
    }

    public function executeNow(){
      $this->selector = 'document';
      $this->event = YsJQueryConstant::READY_EVENT;
      return $this->renderWithJsTags();
    }

    public function execute($function = null){
      return $this->renderWithJsTags();
    }

    public function in($selector, $context = null){
      $this->setSelector($selector);
      if($context !== null){
        $this->setContext($context);
      }
      return $this;
    }

    public function inVar($selector, $context = null){
      $this->in($selector, $context);
      $this->setIsSelectorUnquoted(true);
      return $this;
    }

    public function setFunction($function){
      $this->setArguments($function);
      return $this;
    }

    public function confirmation($confirmation, $onFailure = null){
      if($onFailure === null){
        $confirmation = sprintf("if (!confirm('%s')) {return false}",$confirmation);
      }else{
        $confirmation = sprintf("if (!confirm('%s')) { %s; return false} ",$confirmation, $onFailure);
      }
      $this->addPreSintax($confirmation);
      return $this;
    }

    public function condition($condition, $onFailure = null){
      if($onFailure === null){
        $condition = sprintf("if (!%s) {return false}",$condition);
      }else{
        $condition = sprintf("if (!%s) { %s; return false} ",$condition, $onFailure);
      }
      $this->addPreSintax($condition);
      return $this;
    }

    public function interval($function = null, $time = 5000){
      $function = ($function === null) ? $this : $function;
      $sintax = sprintf("var i=0;setInterval(%s,%s){",$function,$time);
      $this->setPreSintax($sintax);
      $this->setPostSintax('})');
      return $this;
    }

    public static function likeFunction($bodyFuntion, $arguments = null)
    {
      return new YsJsFunction($bodyFuntion, $arguments);
    }

    public function write(){
      echo $this;
    }

    public function  __toString() {
      return $this->cleanJQuerySintax($this->render());
    }

    private function cleanJQuerySintax($sintax){
      $errorSintax = array(".()",";;",",}",",]");
      $realSintax = array("",";","}","]");
      $sintax = str_replace($errorSintax, $realSintax, $sintax);
      return $sintax;
    }

}