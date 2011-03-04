<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of YsAligment
 *
 * @author oyepez
 */
class YsAlignment {
  
  public static $CENTER = 'center';
  public static $LEFT = 'left';
  public static $RIGHT = 'right';
  public static $TOP = 'top';
  public static $BOTTOM= 'bottom';

  public static $RIGHT_IN_BOTTOM= array('right','bottom');
  public static $RIGHT_IN_CENTER = array('right','center');
  public static $CENTER_IN_BOTTOM = array('center','bottom');
  public static $LEFT_IN_BOTTOM = array('left','bottom');
  public static $LEFT_IN_TOP = array('left','top');
  public static $RIGHT_IN_TOP = array('right','top');
  public static $CENTER_IN_TOP = array('center','top');
  public static $CENTER_IN_CENTER = array('center','center');
  public static $LEFT_IN_CENTER = array('left','center');

  public static $DIRECTION_LEFT_TO_RIGHT = 'ltr';
  public static $DIRECTION_RIGHT_TO_LEFT = 'rtl';

  public static $ORDER_ASC = 'asc';
  public static $ORDER_DESC = 'desc';
}