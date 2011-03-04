<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsJQuerySintax todo description.
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */

class YsJQuerySintax extends YsJQueryBuilder{
    
    static protected $instance   = null;
    private $selectorUnquoted = false;

    public function  __construct()
    {
      $this->setPattern(new YsJQueryPattern());
    }

    /*public static function newInstance()
    {
      $object = __CLASS__;
      self::$instance = new $object();
      return self::$instance;
    }*/

    public function setIsSelectorUnquoted($boolean){
      $this->selectorUnquoted = $boolean;
      return self::$instance;
    }

    public function isSelectorUnquoted(){
      return $this->selectorUnquoted;
      return self::$instance;
    }
   
    public function  __destruct()
    {
      $this->destroy();
    }

    protected function destroy()
    {
      self::$instance = null;
    }

    protected function unquotedSelectors()
    {
      $trimSelector = strtolower(trim($this->getSelector()));
      $response = true;
      if($trimSelector === YsJQueryConstant::THIS || $trimSelector === YsJQueryConstant::DOCUMENT || $trimSelector === YsJQueryConstant::WINDOW || $this->isSelectorUnquoted()){
        $response = false;
      }
      return $response;
    }
}
