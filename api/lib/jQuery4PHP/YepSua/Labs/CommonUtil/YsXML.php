<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsXML todo description.
 *
 * @package    YepSua
 * @subpackage CommonUtil
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsXML {
  private static $TAG_INITIATOR = '<';
  private static $TAG_FINALIZER = '>';
  private static $TAG_CLOSER_AT_FIRST = '</';
  private static $TAG_CLOSER_AT_END = ' />';

  public static $DEFAULT_ENCODING = 'utf-8';
  public static $DEFAULT_XML_VERSION = '1.0';

  const CDATA_TEMPLATE = '<![CDATA[%s]]>';
  const HEADER_TEMPLATE = "<?xml version='1.0' encoding='utf-8'?>";

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

  public static function getTagClosed($tag , $xmlProperties = null){
    return self::buildCloseTag($tag, $xmlProperties);
  }
 
  public static function getTag($tag , $xmlProperties = null, $xmlChilds = null){
    $template = $tag;
    if ($xmlChilds === null){
      $template = self::buildTag($tag , $xmlProperties);
    }else{
      $template = self::buildTag($tag , $xmlProperties) . $xmlChilds . self::buildCloseTag($tag);
    }
    return $template;
  }

  protected static function buildTag($tag , $xmlProperties = null){
    $pattern = ($xmlProperties === null) ? '%s' : '%s %s';
    return sprintf(self::$TAG_INITIATOR . $pattern . self::$TAG_FINALIZER, $tag, $xmlProperties);
  }

  protected static function buildCloseTag($tag , $xmlProperties = null){
    $tagClosed = $tag;
    if($xmlProperties === null){
      $tagClosed = sprintf(self::$TAG_CLOSER_AT_FIRST . '%s' . self::$TAG_FINALIZER , $tag);
    }else{
      $tagClosed = sprintf(self::$TAG_INITIATOR . '%s %s' . self::$TAG_CLOSER_AT_END, $tag , $xmlProperties);
    }
    return $tagClosed;
  }

  public static function cDATA($value){
    return html_entity_decode(sprintf(self::CDATA_TEMPLATE,$value));
  }

  public static function getHeaderDocument($xmlVersion = null, $encoding = null){
    $header = '';
    $xmlVersion = ($xmlVersion === null) ? self::$DEFAULT_XML_VERSION : $xmlVersion;
    $encoding = ($encoding === null) ? self::$DEFAULT_ENCODING : $encoding;
    $header = html_entity_decode(sprintf(self::HEADER_TEMPLATE,$xmlVersion, $encoding));
    return $header;
  }
}