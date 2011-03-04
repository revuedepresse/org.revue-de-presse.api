<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsJQueryPattern todo description.
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsJQueryPattern{
    const JQUERY_VAR = '%%JQUERY_VAR%%';
    const JQUERY_EVENT = '%%JQUERY_EVENT%%';
    const JQUERY_SELECTOR = '%%JQUERY_SELECTOR%%';
    const JQUERY_CONTEXT = '%%JQUERY_CONTEXT%%';
    const JQUERY_ARGS = '%%JQUERY_ARGS%%';
    const JQUERY_ACCESORS = '%%JQUERY_ACCESORS%%';

    private $is_string_selector = true;
    private $is_in_context = false;
    private $is_set_selector = true;
    private $is_set_arguments = false;
    private $isOnlyAccesors = false;
    private $pattern;

    public function  __construct()
    {   }

    public function isOnlyAccesors()
    {
      return $this->isOnlyAccesors;
    }

    public function setIsOnlyAccesors($boolean)
    {
      return $this->isOnlyAccesors = $boolean;
    }

    public function isStringSelector()
    {
      return $this->is_string_selector;
    }

    public function setIsStringSelector($boolean)
    {
      $this->is_string_selector = $boolean;
    }

    public function isSetSelector()
    {
      return $this->is_set_selector;
    }

    public function setIsSetSelector($boolean)
    {
      $this->is_set_selector = $boolean;
    }

    public function isSetArguments()
    {
      return $this->is_set_selector;
    }

    public function setIsSetArguments($boolean)
    {
      $this->is_set_selector = $boolean;
    }

    public function isInContext()
    {
      return $this->is_in_context;
    }
    
    public function setIsInContext($boolean)
    {
      $this->is_in_context = $boolean;
    }


    public function  __toString()
    {
      return $this->getPattern();
    }

    public function getPattern()
    {
      $this->pattern = sprintf($this->getDecoratedPattern(),self::JQUERY_VAR,
                                             self::JQUERY_SELECTOR,
                                             self::JQUERY_CONTEXT,
                                             self::JQUERY_EVENT,
                                             self::JQUERY_ARGS,
                                             self::JQUERY_ACCESORS);
      return $this->pattern;
    }

    public function setPattern($pattern)
    {
      $this->pattern = $pattern;
    }

    public static function getJQueryVarsPattern()
    {
      return array (YsJQueryPattern::JQUERY_VAR,
                    YsJQueryPattern::JQUERY_SELECTOR,
                    YsJQueryPattern::JQUERY_CONTEXT,
                    YsJQueryPattern::JQUERY_EVENT,
                    YsJQueryPattern::JQUERY_ARGS,
                    YsJQueryPattern::JQUERY_ACCESORS);
    }

    public function getDecoratedPattern()
    {
      $pattern = '';
      if($this->is_set_selector)
      {
        if($this->isInContext())
        {
          if($this->isStringSelector())
          {
            $pattern = "%s('%s', %s).%s(%s)%s";
          }
          else
          {
            $pattern = "%s(%s, %s).%s(%s)%s";
          }
        }
        else
        {
          if($this->isStringSelector()){
            $pattern = "%s('%s'%s).%s(%s)%s";
          }
          else
          {
            $pattern = "%s(%s%s).%s(%s)%s";
          }
        }
      }
      else
      {
        $pattern = "%s.%s%s%s(%s)%s";
      }
      if($this->isOnlyAccesors){
        $pattern = str_replace(array('(',')'),array('','') , $pattern);
      }

      return $pattern;
    }

}
