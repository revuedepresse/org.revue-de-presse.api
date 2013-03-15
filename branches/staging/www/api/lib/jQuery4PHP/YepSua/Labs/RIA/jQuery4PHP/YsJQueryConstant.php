<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsJQueryConstant todo description.
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsJQueryConstant{

  const  THIS = 'this';
  const  DOCUMENT = 'document';
  const  WINDOW = 'window';

  const  AJAX_EVENT =  'ajax';
  const  ADD_EVENT = 'add';
  const  ADD_CLASS_EVENT = 'addClass';
  const  AFTER_EVENT = 'after';
  const  AJAX_SETUP_EVENT =  'ajaxSetup';
  const  ATTRIBUTE_EVENT =  'attr';
  const  AND_SELF_EVENT =  'andSelf';
  const  APPEND_EVENT =  'append';
  const  APPEND_TO_EVENT =  'appendTo';
  const  BIND_EVENT =  'bind';
  const  BLUR_EVENT = 'blur';
  const  BEFORE_EVENT = 'before';
  const  CHANGE_EVENT = 'change';
  const  CLICK_EVENT = 'click';
  const  CLONE_EVENT = 'clone';
  const  CHILDREN_EVENT = 'children';
  const  CLOSEST_EVENT = 'closest';
  const  CONTENTS_EVENT = 'contents';
  const  CSS_EVENT = 'css';
  const  DATA_EVENT = 'data';
  const  DBLCLICK_EVENT = 'dblclick';
  const  DELEGATE_EVENT = 'delegate';
  const  DETACH_EVENT = 'detach';
  const  DIE_EVENT = 'die';
  const  EACH_EVENT = 'each';
  const  EMPTY_EVENT = 'empty';
  const  END_EVENT = 'end';
  const  EQ_EVENT = 'eq';
  const  ERROR_EVENT = 'error';
  const  FOCUS_EVENT = 'focus';
  const  FOCUSIN_EVENT = 'focusin';
  const  FOCUSOUT_EVENT = 'focusout';
  const  FIND_EVENT = 'find';
  const  FIRST_EVENT = 'first';
  const  FILTER_EVENT = 'filter';
  const  GET_EVENT = 'get';
  const  GET_JSON_EVENT = 'getJSON';
  const  GET_SCRIPT_EVENT = 'getScript';
  const  HAS_EVENT = 'has';
  const  HAS_CLASS_EVENT = 'hasClass';
  const  HEIGHT_EVENT = 'height';
  const  HOVER_EVENT = 'hover';
  const  INDEX_EVENT = 'index';
  const  INNER_HEIGHT_EVENT = 'innerHeight';
  const  INNER_WIDTH_EVENT = 'innerWidth';
  const  INSERT_AFTER_EVENT =  'insertAfter';
  const  INSERT_BEFORE_EVENT =  'insertBefore';
  const  IS_EVENT = 'is';
  const  JOIN_EVENT = 'join';
  const  KEYDOWN_EVENT = 'keydown';
  const  KEYPRESS_EVENT = 'keypress';
  const  KEYUP_EVENT = 'keyup';
  const  LAST_EVENT = 'last';
  const  LIVE_EVENT = 'live';
  const  LOAD_EVENT = 'load';
  const  MAP_EVENT = 'map';
  const  MOUSEDOWN_EVENT = 'mousedown';
  const  MOUSEENTER_EVENT = 'mouseenter';
  const  MOUSELEAVE_EVENT = 'mouseleave';
  const  MOUSEMOVE_EVENT = 'mousemove';
  const  MOUSEOUT_EVENT = 'mouseout';
  const  MOUSEOVER_EVENT = 'mouseover';
  const  MOUSEUP_EVENT = 'mouseup';
  const  NEXT_EVENT = 'next';
  const  NEXT_ALL_EVENT = 'nextAll';
  const  NEXT_UNTIL_EVENT = 'nextUntil';
  const  NOT_EVENT = 'not';
  const  ONE_EVENT = 'one';
  const  OFFSET_EVENT = 'offset';
  const  OFFSET_PARENT_EVENT = 'offsetParent';
  const  OUTER_HEIGHT_EVENT = 'outerHeight';
  const  OUTER_WIDTH_EVENT = 'outerWidth';
  const  PROXY_EVENT = 'proxy';
  const  PARAM_EVENT = 'param';
  const  PARENT_EVENT = 'parent';
  const  PARENTS_EVENT = 'parents';
  const  PARENTS_UNTIL_EVENT = 'parentsUntil';
  const  POST_EVENT = 'post';
  const  POSITION_EVENT = 'position';
  const  PREPPEND_EVENT = 'prepend';
  const  PREPPEND_TO_EVENT = 'prependTo';
  const  PREV_EVENT = 'prev';
  const  PREV_ALL_EVENT = 'prevAll';
  const  PREV_UNTIL_EVENT = 'prevUntil';
  const  READY_EVENT = 'ready';
  const  RESIZE_EVENT = 'resize';
  const  REMOVE_ATTRIBUTE_EVENT = 'removeAttr';
  const  REMOVE_CLASS_EVENT = 'removeClass';
  const  REMOVE_EVENT = 'remove';
  const  REPLACE_ALL_EVENT = 'replaceAll';
  const  REPLACE_WITH_EVENT = 'replaceWith';
  const  REMOVE_DATA_EVENT = 'removeData';
  const  SCROLL_EVENT = 'scroll';
  const  SCROLL_LEFT_EVENT = 'scrollLeft';
  const  SCROLL_TOP_EVENT = 'scrollTop';
  const  SELECT_EVENT = 'select';
  const  SERIALIZE_EVENT = 'serialize';
  const  SERIALIZE_ARRAY_EVENT = 'serializeArray';
  const  SET_EVENT = 'set';
  const  SIZE_EVENT = 'size';
  const  SIBLINGS_EVENT = 'siblings';
  const  SLICE_EVENT = 'slice';
  const  SUBMIT_EVENT = 'submit';
  const  TEXT_EVENT =   'text';
  const  TOGGLE_EVENT = 'toggle';
  const  TOGGLE_CLASS_EVENT = 'toggleClass';
  const  TRIGGER_EVENT = 'trigger';
  const  TRIGGER_HANDLER_EVENT = 'triggerHandler';
  const  TO_ARRAY_EVENT = 'toArray';
  const  UNBIND_EVENT = 'unbind';
  const  UNDELEGATE_EVENT = 'undelegate';
  const  UNLOAD_EVENT = 'unload';
  const  UNWRAP_EVENT = 'unwrap';
  const  VALUE_EVENT = 'value';
  const  VALUES_EVENT = 'values';
  const  VAL_EVENT = 'val';
  const  WRAP_EVENT = 'wrap';
  const  WRAP_ALL_EVENT = 'wrapAll';
  const  WRAP_INNER_EVENT = 'wrapInner';
  const  WIDTH_EVENT = 'width';
  const  SWITCH_CLASS_EVENT = 'switchClass';
  const  EFFECT_EVENT = 'effect';


  const  AJAX_COMPLETE_EVENT = 'ajaxComplete';
  const  AJAX_SEND_EVENT = 'ajaxSend';
  const  AJAX_STOP_EVENT = 'ajaxStop';
  const  AJAX_SUCCESS_EVENT = 'ajaxSuccess';
  const  AJAX_START_EVENT = 'ajaxStart';
  const  AJAX_ERROR_EVENT = 'ajaxError';

  const  ANIMATE_EFFECT = 'animate';
  const  CLEAR_QUEUE_EFFECT = 'clearQueue';
  const  DELAY_EFFECT = 'delay';
  const  DEQUEUE_EFFECT = 'dequeue';
  const  FADEOUT_EFFECT = 'fadeOut';
  const  FADEIN_EFFECT = 'fadeIn';
  const  FADETO_EFFECT = 'fadeTo';
  const  FADE_TOGGLE_EFFECT = 'fadeToggle';

  const  QUEUE_EFFECT = 'dequeue';

  const  HIDE_EFFECT = 'hide';
  const  TOGGLE_EFFECT = 'toggle';
  const  SLIDE_UP_EFFECT = 'slideUp';
  const  SLIDE_DOWN_EFFECT = 'slideDown';
  const  SLIDE_TOGGLE_EFFECT = 'slideToggle';
  const  SHOW_EFFECT = 'show';

  const  HTML = 'html';
  const  STOP = 'stop';
  const  FAST = 'fast';
  const  SLOW = 'slow';

  const DATA_TYPE_JSON = 'json';
  const DATA_TYPE_JSONP = 'jsonp';
  const DATA_TYPE_SCRIPT = 'scrpipt';
  const DATA_TYPE_XML = 'xml';
  const DATA_TYPE_HTML = 'html';

  const COMPONENT_JQGRID = 'jqGrid';

  const PLUGIN_JQGRID = 'jqGrid';
  const PLUGIN_PNOTYFY = 'pnotify';
  const PLUGIN_JLAYOUT = 'jLayout';
}