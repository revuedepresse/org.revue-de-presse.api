<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsUtil todo description.
 *
 * @package    YepSua
 * @subpackage CommonUtil
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsUtil extends YsUtilAutoloader{
    public static function init_javascript_tag()
    {
      return <<<EOF
        <script language="javascript" type="text/javascript">\n/* <![CDATA[ */\n\t
EOF;
    }

    public static function end_javascript_tag()
    {
      return <<<EOF
             \n/* ]]> */
            </script>
EOF;
    }

    public static function optionsToJson($options){
      $json = '';
      if(is_array($options)){
        foreach($options as $option){
          $json .= YsJSON::optionsToJson($option['key'], $option['value'], $option['is_quoted']);
        }
        $json = substr($json,0,(strlen($json)) - 1);
      }
      return sprintf('{%s}',$json);
    }

    public static function booleanForJavascript($bool){
      if (is_bool($bool))
      {
        return ($bool===true ? 'true' : 'false');
      }
      return $bool;
    }
}
