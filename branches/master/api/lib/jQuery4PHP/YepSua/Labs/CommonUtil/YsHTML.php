<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsHTML todo description.
 *
 * @package    YepSua
 * @subpackage CommonUtil
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsHTML {
  private static $TAG_INITIATOR = '<';
  private static $TAG_FINALIZER = '>';
  private static $TAG_CLOSER_AT_FIRST = '</';
  private static $TAG_CLOSER_AT_END = ' />';

  const NBSP = '&nbsp';

  const A = 'a';
  const P = 'p';
  const DIV = 'div';
  const SPAN = 'span';
  const H1 = 'h1';
  const H2 = 'h2';
  const H3 = 'h3';
  const H4 = 'h4';
  const H5 = 'h5';
  const H6 = 'h6';
  const LABEL = 'label';
  const INPUT = 'input';
  const BUTTON = 'button';
  const UL = 'ul';
  const LI = 'li';
  const INPUT_CHECKBOX = 'input type="checkbox"';
  const INPUT_RADIO = 'input type="radio"';
  const TABLE = 'table';
  const TR = 'tr';
  const TH = 'th';
  const TD = 'td';
  const TBODY = 'tbody';
  const TFOOTER = 'tfooter';


  private $template;

  public function getTemplate(){
    return $this->template;
  }

  public function setTemplate($template){
    $this->template = $template;
  }

  public function addToTemplate($template){
    $this->template .= $template;
  }

  public static function getTagClosed($tag , $htmlProperties = null){
    return self::buildCloseTag($tag, $htmlProperties);
  }
 
  public static function getTag($tag , $htmlProperties = null, $htmlChilds = null){
    $template = $tag;
    if ($htmlChilds === null){
      $template = self::buildTag($tag , $htmlProperties);
    }else{
      $template = self::buildTag($tag , $htmlProperties) . $htmlChilds . self::buildCloseTag($tag);
    }
    return $template;
  }

  protected static function buildTag($tag , $htmlProperties = null){
    return sprintf(self::$TAG_INITIATOR . '%s %s' . self::$TAG_FINALIZER, $tag, $htmlProperties);
  }

  protected static function buildCloseTag($tag , $htmlProperties = null){
    $tagClosed = $tag;
    if($htmlProperties === null){
      $tagClosed = sprintf(self::$TAG_CLOSER_AT_FIRST . '%s' . self::$TAG_FINALIZER , $tag);
    }else{
      $tagClosed = sprintf(self::$TAG_INITIATOR . '%s %s' . self::$TAG_CLOSER_AT_END, $tag , $htmlProperties);
    }
    return $tagClosed;
  }
  
}
