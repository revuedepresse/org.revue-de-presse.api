<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsJQueryCore todo description.
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsJQueryCore extends YsJQueryUtil {

    private static  $type;
    private $frequency;
    private static $arguments;
    private static $selector;

    /**
     * Perform an asynchronous HTTP (Ajax) request.
     * Configure the options with an underscore at the beginning of the option name
     * See the options here: http://api.jquery.com/jQuery.ajax/
     * @return object The object instance.
     */
    public static function ajax()
    {
      $jquery = self::getInstance();
      $jquery->setEvent(YsJQueryConstant::AJAX_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Set default values for future Ajax requests.
     * Configure the options with an underscore at the beginning of the option name
     * See the options here: http://api.jquery.com/jQuery.ajax/
     * @return object The object instance.
     */
    public static function ajaxSetup()
    {
      $jquery = self::getInstance();
      $jquery->setEvent(YsJQueryConstant::AJAX_SETUP_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Register a handler to be called when Ajax requests complete.
     * This is an Ajax Event.
     * @param string/YsJsFunction $handler The function to be invoked.
     * @return object The object instance.
     */
    public static function ajaxComplete($handler = null)
    {
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::AJAX_COMPLETE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Show a message before an Ajax request is sent.
     * This is an Ajax Event.
     * @param string/YsJsFunction $handler The function to be invoked.
     * @return object The object instance.
     */
    public static function ajaxSend($handler = null)
    {
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::AJAX_SEND_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Hide a loading message after all the Ajax requests have stopped.
     * This is an Ajax Event.
     * @param string/YsJsFunction $handler The function to be invoked.
     * @return object The object instance.
     */
    public static function ajaxStop($handler = null)
    {
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::AJAX_STOP_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Show a message when an Ajax request completes successfull.
     * This is an Ajax Event.
     * @param string/YsJsFunction $handler The function to be invoked.
     * @return object The object instance.
     */
    public static function ajaxSuccess($handler = null)
    {
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::AJAX_SUCCESS_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Register a handler to be called when the first Ajax request begins.
     * This is an Ajax Event.
     * @param string/YsJsFunction $handler The function to be invoked.
     * @return object The object instance.
     */
    public static function ajaxStart($handler = null)
    {
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::AJAX_START_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Register a handler to be called when Ajax requests complete with an error.
     * This is an Ajax Event.
     * @return object The object instance.
     */
    public static function ajaxError($handler = null)
    {
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::AJAX_ERROR_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "load" JavaScript event.
     * Load data from the server and place the returned HTML into the matched element.
     * @param string $url_or_handler A string containing the URL to which the request is sent or a function to execute when the event is triggered..
     * @param string/json $data A map or string that is sent to the server with the request.
     * @param string $callback A callback function that is executed when the request completes.
     * @return object The object instance.
     */
    public static function load($url_or_handler = null, $data = null, $callback = null)
    {
        $jquery = self::getInstance();
        if($url_or_handler !== null){ $jquery->addArgument(new YsArgument($url_or_handler)); }
        if($data !== null){  $jquery->addArgument(new YsArgument($data)); }
        if($callback !== null){ $jquery->addArgument(new YsArgument($callback)); }
        $jquery->setEvent(YsJQueryConstant::LOAD_EVENT);
        self::$type = $jquery->getEvent();
        return $jquery;
    }

    /**
     * Load data from the server using a HTTP GET request.
     * @param string $url A string containing the URL to which the request is sent.
     * @param string/json $data A map or string that is sent to the server with the request.
     * @param string $callback A callback function that is executed if the request succeeds.
     * @param string $dataType The type of data expected from the server.
     * @return object The object instance.
     *
     */
    public static function get($url = null, $data = null, $callback = null , $dataType = null)
    {
      $jquery = self::getInstance();
      if($url !== null){ $jquery->addArgument(new YsArgument($url)); }
      if($data !== null){  $jquery->addArgument(new YsArgument($data)); }
      if($callback !== null){ $jquery->addArgument(new YsArgument($callback)); }
      if($dataType !== null){ $jquery->addArgument(new YsArgument($dataType)); }
      $jquery->setEvent(YsJQueryConstant::GET_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Load data from the server using a HTTP POST request.
     * @param string $url A string containing the URL to which the request is sent.
     * @param string/json $data A map or string that is sent to the server with the request.
     * @param string $callback A callback function that is executed if the request succeeds.
     * @param string $dataType The type of data expected from the server.
     * @return object The object instance.
     */
    public static function post($url, $data = null, $callback = null , $dataType = null)
    {
      $jquery = self::getInstance();
      if($url !== null){ $jquery->addArgument(new YsArgument($url)); }
      if($data !== null){  $jquery->addArgument(new YsArgument($data)); }
      if($callback !== null){ $jquery->addArgument(new YsArgument($callback)); }
      if($dataType !== null){ $jquery->addArgument(new YsArgument($dataType)); }
      $jquery->setEvent(YsJQueryConstant::POST_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Load JSON-encoded data from the server using a GET HTTP request.
     * @param string $url A string containing the URL to which the request is sent.
     * @param string/json $data A map or string that is sent to the server with the request.
     * @param string $callback A callback function that is executed if the request succeeds.
     * @return object The object instance.
     */
    public static function getJSON($url, $data = null, $callback = null)
    {
      $jquery = self::getInstance();
      if($url !== null){ $jquery->addArgument(new YsArgument($url)); }
      if($data !== null){  $jquery->addArgument(new YsArgument($data)); }
      if($callback !== null){ $jquery->addArgument(new YsArgument($callback)); }
      $jquery->setEvent(YsJQueryConstant::GET_JSON_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Load a JavaScript file from the server using a GET HTTP request,
     * then execute it.
     * @param string $url A string containing the URL to which the request is sent.
     * @param string $callback A callback function that is executed if the request succeeds.
     * @return object The object instance.
     */
    public static function getScript($url, $callback = null)
    {
      $jquery = self::getInstance();
      if($url !== null){ $jquery->addArgument(new YsArgument($url)); }
      if($callback !== null){ $jquery->addArgument(new YsArgument($callback)); }
      $jquery->setEvent(YsJQueryConstant::GET_SCRIPT_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Submit a html form via Ajax.
     * @param string $jQuerySelector jQuery selector that identifies the form
     * @param object(YsJQuery::ajax) $ajax The ajax call.
     * @return object The object instance.
     */
    public static function submitFormWithAjax($jQuerySelector, $ajax){
      $ajax->prependInOption('data', 'dataForm + "&" +');
      $ajax->addPreSintax(sprintf("var dataForm = %s('%s').serialize()" , self::$jqueryVar, $jQuerySelector ));
      self::$arguments = new YsJsFunction($ajax->__toString() . '; return false');
      self::$type = YsJQueryConstant::SUBMIT_EVENT;
      self::$selector = $jQuerySelector;
      return self::getInstance();
    }

    /**
     *
     * Create a serialized representation of an array or object,
     * suitable for use in a URL query string or Ajax request.
     * @param string/array $params An array or object to serialize.
     * @param boolean $traditional A Boolean indicating whether to perform a traditional "shallow" serialization.
     * @return object The object instance.
     */
    public static function param($params, $traditional = null)
    {
        $jquery = self::getInstance();
        if($params !== null){ $jquery->addArgument(new YsArgument($params)); }
        if($traditional !== null){ $jquery->addArgument(new YsArgument($traditional)); }
        $jquery->setEvent(YsJQueryConstant::PARAM_EVENT);
        self::$type = $jquery->getEvent();
        return $jquery;
    }


    /**
     * Encode a set of form elements as a string for submission.
     * @return object The object instance.
     */
    public static function serialize(){
      $jquery = self::getInstance();
      $jquery->setEvent(YsJQueryConstant::SERIALIZE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Encode a set of form elements as an array of names and values.
     * @return object The object instance.
     */
    public static function serializeArray(){
      $jquery = self::getInstance();
      $jquery->setEvent(YsJQueryConstant::SERIALIZE_ARRAY_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Retrieves a instance of this class.
     * @return object The object instance.
     */
    public static function getInstance()
    {
      $object = __CLASS__;
      self::$instance = new $object();
      return self::$instance;
    }

    /**
     * Hide the matched elements by fading them to transparent.
     * @param string/number $duration A string or number determining how long the animation will run.
     * @param string/YsJsFunction $callback A function to call once the animation is complete.
     * @return object The object instance.
     */
    public static function fadeOut($duration = null, $callback = null){
      $jquery = self::getInstance();
      if($duration !== null){  $jquery->duration($duration); }
      if($callback !== null){ $jquery->callback($callback); }
      $jquery->setEvent(YsJQueryConstant::FADEOUT_EFFECT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Adjust the opacity of the matched elements.
     * @param string/number A string or number determining how long the animation will run.
     * @param number $opacity A number between 0 and 1 denoting the target opacity.
     * @param string/YsJsFunction $callback A function to call once the animation is complete.
     * @return object The object instance.
     */
    public static function fadeTo($duration = null, $opacity = null , $callback = null){
      $jquery = self::getInstance();
      if($duration !== null){  $jquery->duration($duration); }
      if($opacity !== null){   $jquery->opacity($opacity); }
      if($callback !== null){  $jquery->callback($callback); }
      $jquery->setEvent(YsJQueryConstant::FADETO_EFFECT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Display or hide the matched elements by animating their opacity.
     * @param <type> $duration
     * @param <type> $easing
     * @param <type> $callback
     * @return <type> 
     */
    public static function fadeToggle($duration = null, $easing  = null , $callback = null){
      $jquery = self::getInstance();
      if($duration !== null){  $jquery->duration($duration); }
      if($easing !== null){   $jquery->easing($easing); }
      if($callback !== null){  $jquery->callback($callback); }
      $jquery->setEvent(YsJQueryConstant::FADE_TOGGLE_EFFECT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Hide the matched elements.
     * @param string/number $duration A string or number determining how long the animation will run.
     * @param string/YsJsFunction A function to call once the animation is complete.
     * @return object The object instance.
     */
    public static function hide($duration = null, $callback = null){
      $jquery = self::getInstance();
      if($duration !== null){  $jquery->duration($duration); }
      if($callback !== null){ $jquery->callback($callback); }
      $jquery->setEvent(YsJQueryConstant::HIDE_EFFECT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Display the matched elements.
     * @param string/number $duration A string or number determining how long the animation will run.
     * @param string/YsJsFunction A function to call once the animation is complete.
     * @return object The object instance.
     */
    public static function show($duration = null, $callback = null){
      $jquery = self::getInstance();
      if($duration !== null){  $jquery->duration($duration); }
      if($callback !== null){ $jquery->callback($callback); }
      $jquery->setEvent(YsJQueryConstant::SHOW_EFFECT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Display the matched elements with a sliding motion.
     * @param string/number $duration A string or number determining how long the animation will run.
     * @param string/YsJsFunction A function to call once the animation is complete.
     * @return object The object instance.
     */
    public static function slideDown($duration = null, $callback = null){
      $jquery = self::getInstance();
      if($duration !== null){  $jquery->duration($duration); }
      if($callback !== null){ $jquery->callback($callback); }
      $jquery->setEvent(YsJQueryConstant::SLIDE_DOWN_EFFECT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Display or hide the matched elements with a sliding motion.
     * @param string/number $duration A string or number determining how long the animation will run.
     * @param string/YsJsFunction A function to call once the animation is complete.
     * @return object The object instance.
     */
    public static function slideToggle($duration = null, $callback = null){
      $jquery = self::getInstance();
      if($duration !== null){  $jquery->duration($duration); }
      if($callback !== null){ $jquery->callback($callback); }
      $jquery->setEvent(YsJQueryConstant::SLIDE_TOGGLE_EFFECT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Globally disable all animations.
     * @return string jQuery.fx.off
     */
    public static function fxOff (){
      return sprintf('%s.fx.off',self::$jqueryVar);
    }

    /**
     * Display the matched elements by fading them to opaque.
     * @param string/number $duration A string or number determining how long the animation will run.
     * @param string/YsJsFunction $callback A function to call once the animation is complete.
     * @return object The object instance.
     */
    public static function fadeIn($duration = null, $callback = null){
      $jquery = self::getInstance();
      if($duration !== null){  $jquery->duration($duration); }
      if($callback !== null){ $jquery->callback($callback); }
      $jquery->setEvent(YsJQueryConstant::FADEIN_EFFECT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Hide the matched elements with a sliding motion.
     * @param string/number $duration A string or number determining how long the animation will run.
     * @param string/YsJsFunction $callback A function to call once the animation is complete.
     * @return object The object instance.
     */
    public static function slideUp($duration = null, $callback = null){
      $jquery = self::getInstance();
      if($duration !== null){  $jquery->duration($duration); }
      if($callback !== null){ $jquery->callback($callback); }
      $jquery->setEvent(YsJQueryConstant::SLIDE_UP_EFFECT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Perform a custom animation of a set of CSS properties.
     * @param string/aray $properties A map of CSS properties that the animation will move toward.
     * @param string/number $duration A string or number determining how long the animation will run.
     * @param string $easing A string indicating which easing function to use for the transition.
     * @param string/YsJsFunction $callback A function to call once the animation is complete.
     * @return object The object instance.
     */
    public static function animate($properties = null, $duration = null,  $easing = null, $callback= null){
      $jquery = self::getInstance();
      if($properties !== null) $jquery->properties($properties);
      if($duration !== null) $jquery->duration($duration);
      if($easing !== null) $jquery->easing($easing);
      if($callback !== null) $jquery->callback($callback);
      $jquery->setEvent(YsJQueryConstant::ANIMATE_EFFECT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Remove from the queue all items that have not yet been run.
     * @param string $queueName A string containing the name of the queue. Defaults to fx, the standard effects queue.
     * @return object The object instance.
     */
    public static function clearQueue($queueName = null){
      $jquery = self::getInstance();
      if($queueName !== null) $jquery->queueName($queueName);
      self::$type = YsJQueryConstant::CLEAR_QUEUE_EFFECT;
      return $jquery;
    }

    /**
     * Execute the next function on the queue for the matched elements.
     * @param string $queueName A string containing the name of the queue. Defaults to fx, the standard effects queue.
     * @return object The object instance.
     */
    public static function dequeue($queueName = null){
      $jquery = self::getInstance();
      if($queueName !== null) $jquery->queueName($queueName);
      $jquery->setEvent(YsJQueryConstant::DEQUEUE_EFFECT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Show the queue of functions to be executed on the matched elements.
     * @param string $queueName A string containing the name of the queue. Defaults to fx, the standard effects queue.
     * @return object The object instance.
     */
    public static function queue($queueName = null, $newQueue = null){
      $jquery = self::getInstance();
      if($queueName !== null) $jquery->queueName($queueName);
      if($queueName !== null) $jquery->newQueue($newQueue);
      $jquery->setEvent(YsJQueryConstant::QUEUE_EFFECT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Set a timer to delay execution of subsequent items in the queue.
     * @param integer $duration An integer indicating the number of milliseconds to delay execution of the next item in the queue.
     * @param string $queueName A string containing the name of the queue. Defaults to fx, the standard effects queue.
     * @return object The object instance.
     */
    public static function delay($duration= null , $queueName = null){
      $jquery = self::getInstance();
      if($duration !== null){  $jquery->duration($duration); }
      if($queueName !== null) $jquery->queueName($queueName);
      $jquery->setEvent(YsJQueryConstant::DELAY_EFFECT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Stop the currently-running animation on the matched elements.
     * @param boolean A Boolean indicating whether to remove queued animation as well. Defaults to false.
     * @param boolean A Boolean indicating whether to complete the current animation immediately. Defaults to false.
     * @return object The object instance.
     */
    public static function stopAnimation($clearQueue = null, $jumpToEnd = null){
      $jquery = self::getInstance();
      if($clearQueue !== null){  $jquery->addArgument(new YsArgument($clearQueue)); }
      if($clearQueue !== null){ $jquery->addArgument(new YsArgument($clearQueue)); }
      $jquery->setEvent(YsJQueryConstant::STOP);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     *
     * @return array The options for jQuery.ajax
     */
    public function registerOptions() {
      return  array(
                '_async' =>  array('key' => 'async', 'is_quoted' => false)
                ,'_beforeSend' =>  array('key' => 'beforeSend', 'is_quoted' => false)
                ,'_cache' =>  array('key' => 'cache', 'is_quoted' => false)
                ,'_complete' =>  array('key' => 'complete', 'is_quoted' => false)
                ,'_contentType' =>  array('key' => 'contentType', 'is_quoted' => true)
                ,'_context' =>  array('key' => 'context', 'is_quoted' => false)
                ,'_data' =>  array('key' => 'data', 'is_quoted' => false)
                ,'_dataFilter' =>  array('key' => 'dataFilter', 'is_quoted' => false)
                ,'_dataType' =>  array('key' => 'dataType', 'is_quoted' => true)
                ,'_error' =>  array('key' => 'error', 'is_quoted' => false)
                ,'_global' =>  array('key' => 'global', 'is_quoted' => false)
                ,'_ifModified' =>  array('key' => 'ifModified', 'is_quoted' => false)
                ,'_jsonp' =>  array('key' => 'jsonp', 'is_quoted' => true)
                ,'_jsonpCallback' =>  array('key' => 'jsonpCallback', 'is_quoted' => true)
                ,'_password' =>  array('key' => 'password', 'is_quoted' => true)
                ,'_processData' =>  array('key' => 'processData', 'is_quoted' => false)
                ,'_success' =>  array('key' => 'success', 'is_quoted' => false)
                ,'_scriptCharset' =>  array('key' => 'scriptCharset', 'is_quoted' => true)
                ,'_timeout' =>  array('key' => 'timeout', 'is_quoted' => false)
                ,'_traditional' =>  array('key' => 'traditional', 'is_quoted' => false)
                ,'_type' =>  array('key' => 'type', 'is_quoted' => true)
                ,'_url' =>  array('key' => 'url', 'is_quoted' => true)
                ,'_urlVar' =>  array('key' => 'url', 'is_quoted' => false)
                ,'_username' =>  array('key' => 'username', 'is_quoted' => false)
                ,'_xhr' =>  array('key' => 'xhr', 'is_quoted' => false));
    }

    /**
     * The render function / overwrited
     * @return string The jQuerySintax
     */
    public function render()
    {
      switch (self::$type) {
        case YsJQueryConstant::AJAX_EVENT:
        case YsJQueryConstant::AJAX_SETUP_EVENT:
          $this->configureAjax();
          if(isset($this->frequency))
          {
            return sprintf('setInterval(function(){%s},%s)',parent::render(), $this->getFrequencyInSeconds());
          }
          break;
      default:
        $this->configureMultipleArguments();
        //$this->setEvent(self::$type);
        break;
      }
      return parent::render();
    }

    /**
     * Set the Frequency for a periodically function.
     * @param numeric $frequency The frequency
     * @return object $this
     */
    public function setFrequency($frequency){
      $this->frequency = $frequency;
      return $this;
    }

    /**
     * Get the Frequency
     * @return integer
     */
    public function getFrequency(){
      return $this->frequency;
    }


    /**
     * Get the Frequency in seconds => 5000 = 5 sec.
     * @return object
     */
    public function getFrequencyInSeconds(){
      return isset($this->frequency ) ? $this->frequency * 1000 : 0 ;
    }

    /**
     * Internal function: todo description
     */
    protected function configureAjax(){
      $this->setEvent(self::$type);
      $this->setArguments($this->getOptionsLikeJson());
    }

    /**
     * Internal function: todo description
     */
    protected function configureMultipleArguments(){
      if(isset(self::$selector)){$this->setSelector(self::$selector);}
      if(is_array(self::$arguments))
      {
        foreach(self::$arguments as $argument)
        {
          $this->addArgument($argument);
        }
      }else{
        $this->addArgument(self::$arguments);
      }
    }

    /**
     * Insert content, specified by the parameter,
     * to the end of each element in the set of matched elements.
     * @param string/YsJQuery $content An element, HTML string, or jQuery object to insert at the end of each element in the set of matched elements.
     * @return object The object instance.
     */
    public static function append($content = null)
    {
      $jquery = self::getInstance();
      if($content !== null){ $jquery->content($content); }
      $jquery->setEvent(YsJQueryConstant::APPEND_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the HTML contents of the first element in the set of matched elements.
     * Set the HTML contents of each element in the set of matched elements.
     * @param string $htmlString A string of HTML to set as the content of each matched element.
     * @return object The object instance.
     */
    public static function html($htmlString = null)
    {
      $jquery = self::getInstance();
      if($htmlString !== null){ $jquery->content($htmlString); }
      $jquery->setEvent(YsJQueryConstant::HTML);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Adds the specified class(es) to each of the set of matched elements.
     * @param string $className One or more class names to be added to the class attribute of each matched element.
     * @return object The object instance.
     */
    public static function addClass($className = null)
    {
      $jquery = self::getInstance();
      if($className !== null){ $jquery->addArgument(new YsArgument($className)); }
      $jquery->setEvent(YsJQueryConstant::ADD_CLASS_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Insert content, specified by the parameter, after each element in the set of matched elements.
     * @param string/YsJQuery $content An element, HTML string, or jQuery object to insert after each element in the set of matched elements.
     * @return object The object instance.
     */
    public static function after($content = null)
    {
      $jquery = self::getInstance();
      if($content !== null){ $jquery->addArgument(new YsArgument($content)); }
      $jquery->setEvent(YsJQueryConstant::AFTER_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Insert every element in the set of matched elements to the end of the target.
     * @param string/YsJQuery $target A selector, element, HTML string, or jQuery object; the matched set of elements will be inserted at the end of the element(s) specified by this parameter.
     * @return object The object instance.
     */
    public static function appendTo($target = null)
    {
      $jquery = self::getInstance();
      if($target !== null){ $jquery->addArgument(new YsArgument($target)); }
      $jquery->setEvent(YsJQueryConstant::APPEND_TO_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the value of an attribute for the first element in the set of matched elements.
     * Set one or more attributes for the set of matched elements.
     * @param string/array $attributeName The name of the attribute to set. A map of attribute-value pairs to set.
     * @param string $value A value to set for the attribute.
     * @return object The object instance.
     */
    public static function attr($attributeName = null, $value = null)
    {
      $jquery = self::getInstance();
      if($attributeName !== null){ $jquery->attributeName($attributeName); }
      if($value !== null){ $jquery->value($value); }
      $jquery->setEvent(YsJQueryConstant::ATTRIBUTE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Insert content, specified by the parameter, before each element in the set of matched elements.
     * @param string/YsJQuery $content An element, HTML string, or jQuery object to insert before each element in the set of matched elements.
     * @return object The object instance.
     */
    public static function before($content = null)
    {
      $jquery = self::getInstance();
      if($content !== null){ $jquery->content($content); }
      $jquery->setEvent(YsJQueryConstant::BEFORE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Create a copy of the set of matched elements.
     * @param boolean $withDataAndEvents A Boolean indicating whether event handlers should be copied along with the elements.
     * @return object The object instance.
     */
    public static function clonation($withDataAndEvents = null)
    {
      $jquery = self::getInstance();
      if($withDataAndEvents !== null){ $jquery->addArgument(new YsArgument($withDataAndEvents)); }
      $jquery->setEvent(YsJQueryConstant::CLONE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the value of a style property for the first element in the set of matched elements.
     * Set one or more CSS properties for the set of matched elements.
     * @param string/array $propertyName A CSS property name. A map of property-value pairs to set.
     * @param string $value A value to set for the property.
     * @return object The object instance.
     */
    public static function css($propertyName = null, $value = null)
    {
      $jquery = self::getInstance();
      if($propertyName !== null){ $jquery->attributeName($propertyName); }
      if($value !== null){ $jquery->value($value); }
      $jquery->setEvent(YsJQueryConstant::CSS_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Remove the set of matched elements from the DOM.
     * @param string $selector A selector expression that filters the set of matched elements to be removed.
     * @return object The object instance.
     */
    public static function detach($selector = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      $jquery->setEvent(YsJQueryConstant::DETACH_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Remove all child nodes of the set of matched elements from the DOM.
     * @return object The object instance.
     */
    public static function emptyEvent()
    {
      $jquery = self::getInstance();
      $jquery->setEvent(YsJQueryConstant::EMPTY_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Determine whether any of the matched elements are assigned the given class.
     * @param string $className The class name to search for.
     * @return object The object instance.
     */
    public static function hasClass($className = null)
    {
      $jquery = self::getInstance();
      if($className !== null){ $jquery->addArgument(new YsArgument($className)); }
      $jquery->setEvent(YsJQueryConstant::HAS_CLASS_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the current computed height for the first element in the set of matched elements.
     * Set the CSS height of every matched element.
     * @param integer $value An integer representing the number of pixels, or an integer with an optional unit of measure appended (as a string).
     * @return object The object instance.
     */
    public static function height($value = null)
    {
      $jquery = self::getInstance();
      if($value !== null){ $jquery->value($value); }
      $jquery->setEvent(YsJQueryConstant::HEIGHT_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the current value of the first element in the set of matched elements.
     * Set the value of each element in the set of matched elements.
     * @param string $value A string of text or an array of strings to set as the value property of each matched element.
     * @return object The object instance.
     */
    public static function val($value = null)
    {
      $jquery = self::getInstance();
      if($value !== null){ $jquery->value($value); }
      $jquery->setEvent(YsJQueryConstant::VAL_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the current computed height for the first element in the set of matched elements, including padding but not border.
     * @return object The object instance.
     */
    public static function innerHeight()
    {
      $jquery = self::getInstance();
      $jquery->setEvent(YsJQueryConstant::INNER_HEIGHT_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the current computed width for the first element in the set of matched elements, including padding but not border.
     * @return object The object instance.
     */
    public static function innerWidth()
    {
      $jquery = self::getInstance();
      $jquery->setEvent(YsJQueryConstant::INNER_WIDTH_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Insert every element in the set of matched elements after the target.
     * @param string/YsJQuery $target A selector, element, HTML string, or jQuery object; the matched set of elements will be inserted after the element(s) specified by this parameter.
     * @return object The object instance.
     */
    public static function insertAfter($target = null)
    {
      $jquery = self::getInstance();
      if($target !== null){ $jquery->target($target); }
      $jquery->setEvent(YsJQueryConstant::INSERT_AFTER_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Insert every element in the set of matched elements before the target.
     * @param string/YsJQuery $target A selector, element, HTML string, or jQuery object; the matched set of elements will be inserted before the element(s) specified by this parameter.
     * @return object The object instance.
     */
    public static function insertBefore($target = null)
    {
      $jquery = self::getInstance();
      if($target !== null){ $jquery->target($target); }
      $jquery->setEvent(YsJQueryConstant::INSERT_BEFORE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the current coordinates of the first element in the set of matched elements, relative to the document.
     * Set the current coordinates of every element in the set of matched elements, relative to the document.
     * @param string/array/YsJSON $value An object containing the properties top and left, which are integers indicating the new top and left coordinates for the elements.
     * @return object The object instance.
     */
    public static function offset($coordinates = null)
    {
      $jquery = self::getInstance();
      if($coordinates !== null){ $jquery->value($coordinates); }
      $jquery->setEvent(YsJQueryConstant::OFFSET_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the current computed height for the first element in the set of matched elements, including padding and border.
     * @param boolean $includeMargin A Boolean indicating whether to include the element's margin in the calculation.
     * @return object The object instance.
     */
    public static function outerHeight($includeMargin = null)
    {
      $jquery = self::getInstance();
      if($includeMargin !== null){ $jquery->includeMargin($includeMargin); }
      $jquery->setEvent(YsJQueryConstant::OUTER_HEIGHT_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the current computed width for the first element in the set of matched elements, including padding and border.
     * @param boolean $includeMargin A Boolean indicating whether to include the element's margin in the calculation.
     * @return object The object instance.
     */
    public static function outerWidth($includeMargin = null)
    {
      $jquery = self::getInstance();
      if($includeMargin !== null){ $jquery->includeMargin($includeMargin); }
      $jquery->setEvent(YsJQueryConstant::OUTER_WIDTH_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the current coordinates of the first element in the set of matched elements, relative to the offset parent.
     * @return object The object instance.
     */
    public static function position()
    {
      $jquery = self::getInstance();
      $jquery->setEvent(YsJQueryConstant::POSITION_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Insert content, specified by the parameter, to the beginning of each element in the set of matched elements.
     * @param string/YsJQuery $content An element, HTML string, or jQuery object to insert at the beginning of each element in the set of matched elements.
     * @return object The object instance.
     */
    public static function prepend($content = null)
    {
      $jquery = self::getInstance();
      if($content !== null){ $jquery->content($content); }
      $jquery->setEvent(YsJQueryConstant::PREPPEND_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Insert every element in the set of matched elements to the beginning of the target.
     * @param string/YsJQuery $target A selector, element, HTML string, or jQuery object; the matched set of elements will be inserted at the beginning of the element(s) specified by this parameter.
     * @return object The object instance.
     */
    public static function prependTo($target = null)
    {
      $jquery = self::getInstance();
      if($target !== null){ $jquery->target($target); }
      $jquery->setEvent(YsJQueryConstant::PREPPEND_TO_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Remove the set of matched elements from the DOM.
     * @param string $selector A selector expression that filters the set of matched elements to be removed.
     * @return object The object instance.
     */
    public static function remove($selector = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      $jquery->setEvent(YsJQueryConstant::REMOVE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Remove an attribute from each element in the set of matched elements.
     * @param string $attributeName An attribute to remove.
     * @return object The object instance.
     */
    public static function removeAttr($attributeName = null)
    {
      $jquery = self::getInstance();
      if($attributeName !== null){ $jquery->attributeName($attributeName); }
      $jquery->setEvent(YsJQueryConstant::REMOVE_ATTRIBUTE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Remove a single class, multiple classes, or all classes from each element in the set of matched elements.
     * @param string $className A class name to be removed from the class attribute of each matched element.
     * @return object The object instance.
     */
    public static function removeClass($className = null)
    {
      $jquery = self::getInstance();
      if($className !== null){ $jquery->className($className); }
      $jquery->setEvent(YsJQueryConstant::REMOVE_CLASS_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Replace each target element with the set of matched elements.
     * @param string $selector A selector expression that filters the set of matched elements to be removed.
     * @return object The object instance.
     */
    public static function replaceAll($selector = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      $jquery->setEvent(YsJQueryConstant::REPLACE_ALL_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Replace each element in the set of matched elements with the provided new content.
     * @param string/YsJQuery $content The content to insert. May be an HTML string, DOM element, or jQuery object.
     * @return object The object instance.
     */
    public static function replaceWith($content = null)
    {
      $jquery = self::getInstance();
      if($content !== null){ $jquery->content($content); }
      $jquery->setEvent(YsJQueryConstant::REPLACE_WITH_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the current horizontal position of the scroll bar for the first element in the set of matched elements.
     * Set the current horizontal position of the scroll bar for each of the set of matched elements.
     * @param integer $value An integer indicating the new position to set the scroll bar to.
     * @return object The object instance.
     */
    public static function scrollLeft($value = null)
    {
      $jquery = self::getInstance();
      if($value !== null){ $jquery->value($value); }
      $jquery->setEvent(YsJQueryConstant::SCROLL_LEFT_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the current vertical position of the scroll bar for the first element in the set of matched elements.
     * Set the current vertical position of the scroll bar for each of the set of matched elements.
     * @param integer $value An integer indicating the new position to set the scroll bar to.
     * @return object The object instance.
     */
    public static function scrollTop($value = null)
    {
      $jquery = self::getInstance();
      if($value !== null){ $jquery->value($value); }
      $jquery->setEvent(YsJQueryConstant::SCROLL_TOP_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the combined text contents of each element in the set of matched elements, including their descendants.
     * Set the content of each element in the set of matched elements to the specified text.
     * @param string $textString A string of text to set as the content of each matched element.
     * @return object The object instance.
     */
    public static function text($textString  = null)
    {
      $jquery = self::getInstance();
      if($textString  !== null){ $jquery->content($textString ); }
      $jquery->setEvent(YsJQueryConstant::TEXT_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Add or remove one or more classes from each element in the set of matched
     * elements, depending on either the class's presence or the value of the
     * switch argument.
     * @param string $className One or more class names (separated by spaces) to be toggled for each element in the matched set.
     * @return object The object instance.
     */
    public static function toggleClass($className = null)
    {
      $jquery = self::getInstance();
      if($className !== null){ $jquery->className($className); }
      $jquery->setEvent(YsJQueryConstant::TOGGLE_CLASS_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Wrap an HTML structure around each element in the set of matched elements.
     * @param string/YsJsFunction $wrappingElement An HTML snippet, selector expression, jQuery object, or DOM element specifying the structure to wrap around the matched elements.
     * @return object The object instance.
     */
    public static function wrap($wrappingElement  = null)
    {
      $jquery = self::getInstance();
      if($wrappingElement  !== null){ $jquery->target($wrappingElement ); }
      $jquery->setEvent(YsJQueryConstant::WRAP_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Remove the parents of the set of matched elements from the DOM, leaving the matched elements in their place.
     * @return object The object instance.
     */
    public static function unwrap()
    {
      $jquery = self::getInstance();
      $jquery->setEvent(YsJQueryConstant::UNWRAP_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the current computed width for the first element in the set of matched elements.
     * Set the CSS width of each element in the set of matched elements.
     * @param integer $value An integer representing the number of pixels, or an integer along with an optional unit of measure appended (as a string).
     * @return object The object instance.
     */
    public static function width($value = null)
    {
      $jquery = self::getInstance();
      if($value !== null){ $jquery->value($value); }
      $jquery->setEvent(YsJQueryConstant::WIDTH_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Wrap an HTML structure around all elements in the set of matched elements.
     * @param string/YsJsFunction $wrappingElement An HTML snippet, selector expression, jQuery object, or DOM element specifying the structure to wrap around the matched elements.
     * @return object The object instance.
     */
    public static function wrapAll($wrappingElement  = null)
    {
      $jquery = self::getInstance();
      if($wrappingElement  !== null){ $jquery->target($wrappingElement ); }
      $jquery->setEvent(YsJQueryConstant::WRAP_ALL_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Wrap an HTML structure around the content of each element in the set of matched elements.
     * @param string/YsJsFunction $wrappingElement An HTML snippet, selector expression, jQuery object, or DOM element specifying the structure to wrap around the matched elements.
     * @return object The object instance.
     */
    public static function wrapInner($wrappingElement  = null)
    {
      $jquery = self::getInstance();
      if($wrappingElement  !== null){ $jquery->target($wrappingElement ); }
      $jquery->setEvent(YsJQueryConstant::WRAP_INNER_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Store arbitrary data associated with the matched elements.
     * @param string $key A string naming the piece of data to set.
     * @param string $value The new data value; it can be any Javascript type including Array or Object.
     * @return object The object instance.
     */
    public static function data($key = null, $value = null)
    {
      $jquery = self::getInstance();
      if($key  !== null){ $jquery->selector($key ); }
      if($key  !== null){ $jquery->value($key ); }
      $jquery->setEvent(YsJQueryConstant::DATA_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Iterate over a jQuery object, executing a function for each matched element.
     * @param string/YsJsFunction $function A function to execute for each matched element.
     * @return object The object instance.
     */
    public static function each($function = null)
    {
      $jquery = self::getInstance();
      if($function  !== null){ $jquery->object($function ); }
      $jquery->setEvent(YsJQueryConstant::EACH_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Search for a given element from among the matched elements.
     * @param string $selector A selector representing a jQuery collection in which to look for an element.
     * @return object The object instance.
     */
    public static function index($selector = null)
    {
      $jquery = self::getInstance();
      if($selector  !== null){ $jquery->selector($selector ); }
      $jquery->setEvent(YsJQueryConstant::INDEX_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Remove a previously-stored piece of data.
     * @param string $key A string naming the piece of data to delete.
     * @return object The object instance.
     */
    public static function removeData($key = null)
    {
      $jquery = self::getInstance();
      if($key  !== null){ $jquery->selector($key ); }
      $jquery->setEvent(YsJQueryConstant::REMOVE_DATA_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Return the number of DOM elements matched by the jQuery object.
     * @return object The object instance.
     */
    public static function size()
    {
      $jquery = self::getInstance();
      $jquery->setEvent(YsJQueryConstant::SIZE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Retrieve all the DOM elements contained in the jQuery set, as an array.
     * @return object The object instance.
     */
    public static function toArray()
    {
      $jquery = self::getInstance();
      $jquery->setEvent(YsJQueryConstant::TO_ARRAY_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Add elements to the set of matched elements.
     * @param string $selector A string containing a selector expression to match additional elements against.
     * @param string $context Add some elements rooted against the specified context.
     * @return object The object instance.
     */
    public static function add($selector = null, $context = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::ADD_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Add the previous set of elements on the stack to the current set.
     * @return object The object instance.
     */
    public static function andSelf()
    {
      $jquery = self::getInstance();
      $jquery->setEvent(YsJQueryConstant::AND_SELF_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the descendants of each element in the current set of matched elements, filtered by a selector.
     * @param string $selector A string containing a selector expression to match elements against.
     * @param string $context A DOM element within which a matching element may be found. If no context is passed in then the context of the jQuery set will be used instead.
     * @return object The object instance.
     */
    public static function find($selector = null, $context = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::FIND_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the children of each element in the set of matched elements, optionally filtered by a selector.
     * @param string $selector A string containing a selector expression to match elements against.
     * @param string $context A DOM element within which a matching element may be found. If no context is passed in then the context of the jQuery set will be used instead.
     * @return object The object instance.
     */
    public static function children($selector = null, $context = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::CHILDREN_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the first ancestor element that matches the selector, beginning at the current element and progressing up through the DOM tree.
     * @param string $selector A string containing a selector expression to match elements against.
     * @param string $context A DOM element within which a matching element may be found. If no context is passed in then the context of the jQuery set will be used instead.
     * @return object The object instance.
     */
    public static function closest($selector = null, $context = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::CLOSEST_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the children of each element in the set of matched elements, including text nodes.
     * @return object The object instance.
     */
    public static function contents()
    {
      $jquery = self::getInstance();
      $jquery->setEvent(YsJQueryConstant::CONTENTS_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * End the most recent filtering operation in the current chain and return the set of matched elements to its previous state.
     * @return object The object instance.
     */
    public static function end()
    {
      $jquery = self::getInstance();
      $jquery->setEvent(YsJQueryConstant::END_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Reduce the set of matched elements to the one at the specified index.
     * @param integer $index An integer indicating the 0-based position of the element.
     * @return object The object instance.
     */
    public static function eq($index)
    {
      $jquery = self::getInstance();
      if($index !== null){ $jquery->key($index); }
      $jquery->setEvent(YsJQueryConstant::EQ_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Reduce the set of matched elements to those that match the selector or pass the function's test.
     * @param string $selector A string containing a selector expression to match elements against.
     * @param string $context A DOM element within which a matching element may be found. If no context is passed in then the context of the jQuery set will be used instead.
     * @return object The object instance.
     */
    public static function filter($selector = null, $context = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::FILTER_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Reduce the set of matched elements to the first in the set.
     * @return object The object instance.
     */
    public static function first()
    {
      $jquery = self::getInstance();
      $jquery->setEvent(YsJQueryConstant::FIRST_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Reduce the set of matched elements to those that have a descendant that matches the selector or DOM element.
     * @param string $selector A string containing a selector expression to match elements against.
     * @param string $context A DOM element within which a matching element may be found. If no context is passed in then the context of the jQuery set will be used instead.
     * @return object The object instance.
     */
    public static function has($selector = null, $context = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::HAS_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Check the current matched set of elements against a selector and return true if at least one of these elements matches the selector.
     * @param string $selector A string containing a selector expression to match elements against.
     * @param string $context A DOM element within which a matching element may be found. If no context is passed in then the context of the jQuery set will be used instead.
     * @return object The object instance.
     */
    public static function is($selector = null, $context = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::IS_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Reduce the set of matched elements to the final one in the set.
     * @return object The object instance.
     */
    public static function last()
    {
      $jquery = self::getInstance();
      $jquery->setEvent(YsJQueryConstant::LAST_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }
    /**
     * Todo description.
     * @param <type> $value
     * @return object The object instance.
     */
    public static function join($value = null)
    {
      $jquery = self::getInstance();
      if($value !== null){ $jquery->value($value); }
      $jquery->setEvent(YsJQueryConstant::JOIN_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Pass each element in the current matched set through a function, producing a new jQuery object containing the return values.
     * @param string/YsJsFunction $callback A function object that will be invoked for each element in the current set.
     * @return object The object instance.
     */
    public static function map($callback = null)
    {
      $jquery = self::getInstance();
      if($callback !== null){ $jquery->callback($callback); }
      $jquery->setEvent(YsJQueryConstant::MAP_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the immediately following sibling of each element in the set of matched elements, optionally filtered by a selector.
     * @param string $selector A string containing a selector expression to match elements against.
     * @param string $context A DOM element within which a matching element may be found. If no context is passed in then the context of the jQuery set will be used instead.
     * @return object The object instance.
     */
    public static function next($selector = null, $context = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::NEXT_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get all following siblings of each element in the set of matched elements, optionally filtered by a selector.
     * @param string $selector A string containing a selector expression to match elements against.
     * @param string $context A DOM element within which a matching element may be found. If no context is passed in then the context of the jQuery set will be used instead.
     * @return object The object instance.
     */
    public static function nextAll($selector = null, $context = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::NEXT_ALL_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get all following siblings of each element up to but not including the element matched by the selector.
     * @param string $selector A string containing a selector expression to match elements against.
     * @param string $context A DOM element within which a matching element may be found. If no context is passed in then the context of the jQuery set will be used instead.
     * @return object The object instance.
     */
    public static function nextUntil($selector = null, $context = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::NEXT_UNTIL_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Remove elements from the set of matched elements.
     * @param string $selector A string containing a selector expression to match elements against.
     * @param string $context A DOM element within which a matching element may be found. If no context is passed in then the context of the jQuery set will be used instead.
     * @return object The object instance.
     */
    public static function not($selector = null, $context = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::NOT_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the closest ancestor element that is positioned.
     * @return object The object instance.
     */
    public static function offsetParent()
    {
      $jquery = self::getInstance();
      $jquery->setEvent(YsJQueryConstant::OFFSET_PARENT_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the parent of each element in the current set of matched elements, optionally filtered by a selector.
     * @param string $selector A string containing a selector expression to match elements against.
     * @param string $context A DOM element within which a matching element may be found. If no context is passed in then the context of the jQuery set will be used instead.
     * @return object The object instance.
     */
    public static function parent($selector = null, $context = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::PARENT_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the ancestors of each element in the current set of matched elements, optionally filtered by a selector.
     * @param string $selector A string containing a selector expression to match elements against.
     * @param string $context A DOM element within which a matching element may be found. If no context is passed in then the context of the jQuery set will be used instead.
     * @return object The object instance.
     */
    public static function parents($selector = null, $context = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::PARENTS_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the ancestors of each element in the current set of matched elements, up to but not including the element matched by the selector.
     * @param string $selector A string containing a selector expression to match elements against.
     * @param string $context A DOM element within which a matching element may be found. If no context is passed in then the context of the jQuery set will be used instead.
     * @return object The object instance.
     */
    public static function parentsUntil($selector = null, $context = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::PARENTS_UNTIL_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the immediately preceding sibling of each element in the set of matched elements, optionally filtered by a selector.
     * @param string $selector A string containing a selector expression to match elements against.
     * @param string $context A DOM element within which a matching element may be found. If no context is passed in then the context of the jQuery set will be used instead.
     * @return object The object instance.
     */
    public static function prev($selector = null, $context = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::PREV_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get all preceding siblings of each element in the set of matched elements, optionally filtered by a selector.
     * @param string $selector A string containing a selector expression to match elements against.
     * @param string $context A DOM element within which a matching element may be found. If no context is passed in then the context of the jQuery set will be used instead.
     * @return object The object instance.
     */
    public static function prevAll($selector = null, $context = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::PREV_ALL_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get all preceding siblings of each element up to but not including the element matched by the selector.
     * @param string $selector A string containing a selector expression to match elements against.
     * @param string $context A DOM element within which a matching element may be found. If no context is passed in then the context of the jQuery set will be used instead.
     * @return object The object instance.
     */
    public static function prevUntil($selector = null, $context = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::PREV_UNTIL_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Get the siblings of each element in the set of matched elements, optionally filtered by a selector.
     * @param string $selector A string containing a selector expression to match elements against.
     * @param string $context A DOM element within which a matching element may be found. If no context is passed in then the context of the jQuery set will be used instead.
     * @return object The object instance.
     */
    public static function siblings($selector = null, $context = null)
    {
      $jquery = self::getInstance();
      if($selector !== null){ $jquery->selector($selector); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::SIBLINGS_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Reduce the set of matched elements to a subset specified by a range of indices.
     * @param integer $start An integer indicating the 0-based position after which the elements are selected. If negative, it indicates an offset from the end of the set.
     * @param integer $end An integer indicating the 0-based position before which the elements stop being selected. If negative, it indicates an offset from the end of the set. If omitted, the range continues until the end of the set.
     * @return object The object instance.
     */
    public static function slice($start = null, $end = null)
    {
      $jquery = self::getInstance();
      if($start !== null){ $jquery->start($start); }
      if($end !== null){ $jquery->ends($end); }
      $jquery->setEvent(YsJQueryConstant::SLICE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Attach a handler to an event for the elements.
     * @param string $eventType A string containing one or more JavaScript event types, such as "click" or "submit," or custom event names.
     * @param string/array $eventData A map of data that will be passed to the event handler.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function bind($eventType = null,$eventData = null,$handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      if($eventData !== null){ $jquery->eventData($eventData); }
      if($eventType !== null){ $jquery->eventType($eventType); }
      $jquery->setEvent(YsJQueryConstant::BIND_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "change" JavaScript event, or trigger that event on an element.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function change($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::CHANGE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "click" JavaScript event, or trigger that event on an element.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function click($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::CLICK_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "dblclick" JavaScript event, or trigger that event on an element.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function dblclick($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::DBLCLICK_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Remove all event handlers previously attached using .live() from the elements.
     * @param string $eventType A string containing a JavaScript event type, such as click or keydown.
     * @param string/YsJsFunction $handler The function that is to be no longer executed.
     * @return object The object instance.
     */
    public static function dieEvent($eventType = null,$handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      if($eventType !== null){ $jquery->eventType($eventType); }
      $jquery->setEvent(YsJQueryConstant::DIE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "error" JavaScript event.
     * @param string/YsJsFunction $handler A function to execute when the event is triggered.
     * @return object The object instance.
     */
    public static function error($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::ERROR_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Attach a handler to one or more events for all elements that match the selector, now or in the future, based on a specific set of root elements.
     * @param string $selector A selector to filter the elements that trigger the event.
     * @param string $eventType A string containing one or more space-separated JavaScript event types, such as "click" or "keydown," or custom event names.
     * @param string $handler A function to execute at the time the event is triggered.
     * @return object The object instance.
     */
    public static function delegate($selector = null, $eventType = null,$handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      if($eventType !== null){ $jquery->eventType($eventType); }
      if($selector !== null){ $jquery->selector($selector); }
      $jquery->setEvent(YsJQueryConstant::DELEGATE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "focus" JavaScript event, or trigger that event on an element.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function focus($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::FOCUS_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "focusin" JavaScript event.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function focusin($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::FOCUSIN_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "focusout" JavaScript event.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function focusout($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::FOCUSOUT_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind two handlers to the matched elements, to be executed when the mouse pointer enters and leaves the elements.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function hover($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::HOVER_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "keydown" JavaScript event, or trigger that event on an element.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function keydown($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::KEYDOWN_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "keypress" JavaScript event, or trigger that event on an element.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function keypress($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::KEYPRESS_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "keyup" JavaScript event, or trigger that event on an element.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function keyup($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::KEYUP_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Attach a handler to the event for all elements which match the current selector, now or in the future.
     * @param string $eventType A string containing a JavaScript event type, such as "click" or "keydown."
     * @param string/YsJsFunction $handler A function to execute at the time the event is triggered.
     * @return object The object instance.
     */
    public static function live($eventType = null, $handler = null){
      $jquery = self::getInstance();
      if($eventType !== null){ $jquery->eventType($eventType); }
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::LIVE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "mousedown" JavaScript event, or trigger that event on an element.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function mousedown($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::MOUSEDOWN_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to be fired when the mouse enters an element, or trigger that handler on an element.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function mouseenter($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::MOUSEENTER_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to be fired when the mouse leaves an element, or trigger that handler on an element.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function mouseleave($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::MOUSELEAVE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "mousemove" JavaScript event, or trigger that event on an element.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function mousemove($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::MOUSEMOVE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "mouseout" JavaScript event, or trigger that event on an element.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function mouseout($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::MOUSEOUT_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "mouseover" JavaScript event, or trigger that event on an element.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function mouseover($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::MOUSEOVER_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "mouseup" JavaScript event, or trigger that event on an element.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function mouseup($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::MOUSEUP_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Attach a handler to an event for the elements. The handler is executed at most once per element.
     * @param string $eventType A string containing one or more JavaScript event types, such as "click" or "submit," or custom event names.
     * @param string/array $eventData A map of data that will be passed to the event handler.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function one($eventType = null,$eventData = null,$handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      if($eventData !== null){ $jquery->eventData($eventData); }
      if($eventType !== null){ $jquery->eventType($eventType); }
      $jquery->setEvent(YsJQueryConstant::ONE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Takes a function and returns a new one that will always have a particular context.
     * @param string $function The function whose context will be changed.
     * @param string $context The object to which the context ('this') of the function should be set.
     * @return object The object instance.
     */
    public static function proxy($function = null, $context = null)
    {
      $jquery = self::getInstance();
      if($function !== null){ $jquery->handler($function); }
      if($context !== null){ $jquery->context($context); }
      $jquery->setEvent(YsJQueryConstant::PROXY_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Specify a function to execute when the DOM is fully loaded.
     * @param string/YsJsFunction $handler A function to execute after the DOM is ready.
     * @return object The object instance.
     */
    public static function ready($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::READY_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "resize" JavaScript event, or trigger that event on an element.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function resize($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::RESIZE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "scroll" JavaScript event, or trigger that event on an element.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function scroll($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::SCROLL_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "select" JavaScript event, or trigger that event on an element.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function select($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::SELECT_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "submit" JavaScript event, or trigger that event on an element.
     * @param string/YsJsFunction $handler A function to execute each time the event is triggered.
     * @return object The object instance.
     */
    public static function submit($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::SUBMIT_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind two or more handlers to the matched elements, to be executed on alternate clicks.
     * @param string/YsJsFunction $handler A function to execute every even time the element is clicked.
     * @return object The object instance.
     */
    public static function toggle($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::TOGGLE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Execute all handlers and behaviors attached to the matched elements for the given event type.
     * @param string $eventType A string containing a JavaScript event type, such as click or submit.
     * @param string/array $extraParameters An array of additional parameters to pass along to the event handler.
     * @return object The object instance.
     */
    public static function trigger($eventType = null, $extraParameters = null){
      $jquery = self::getInstance();
      if($eventType !== null){ $jquery->eventType($eventType); }
      if($extraParameters !== null){ $jquery->properties($extraParameters); }
      $jquery->setEvent(YsJQueryConstant::TRIGGER_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Execute all handlers attached to an element for an event.
     * @param string $eventType A string containing a JavaScript event type, such as click or submit.
     * @param string/array $extraParameters An array of additional parameters to pass along to the event handler.
     * @return object The object instance.
     */
    public static function triggerHandler($eventType = null, $extraParameters = null){
      $jquery = self::getInstance();
      if($eventType !== null){ $jquery->eventType($eventType); }
      if($extraParameters !== null){ $jquery->properties($extraParameters); }
      $jquery->setEvent(YsJQueryConstant::TRIGGER_HANDLER_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Remove a previously-attached event handler from the elements.
     * @param string $eventType A string containing a JavaScript event type, such as click or submit.
     * @param string/YsJsFunction $handler The function that is to be no longer executed.
     * @return object The object instance.
     */
    public static function unbind($eventType = null, $handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      if($eventType !== null){ $jquery->eventType($eventType); }
      $jquery->setEvent(YsJQueryConstant::UNBIND_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Remove a handler from the event for all elements which match the current selector, now or in the future, based upon a specific set of root elements.
     * @param string $selector A selector which will be used to filter the event results.
     * @param string $eventType A string containing a JavaScript event type, such as "click" or "keydown"
     * @param string/YsJsFunction $handler A function to execute at the time the event is triggered.
     * @return object The object instance.
     */
    public static function undelegate($selector = null, $eventType = null,$handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      if($eventType !== null){ $jquery->eventType($eventType); }
      if($selector !== null){ $jquery->selector($selector); }
      $jquery->setEvent(YsJQueryConstant::UNDELEGATE_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

    /**
     * Bind an event handler to the "unload" JavaScript event.
     * @param string/YsJsFunction $handler A function to execute when the event is triggered.
     * @return object The object instance. 
     */
    public static function unload($handler = null){
      $jquery = self::getInstance();
      if($handler !== null){ $jquery->handler($handler); }
      $jquery->setEvent(YsJQueryConstant::UNLOAD_EVENT);
      self::$type = $jquery->getEvent();
      return $jquery;
    }

}