<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsJQGrid todo description
 *
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsJQGrid extends YsJQueryPlugin {

  const VERSION = "3.8.1";
  const LICENSE = "MIT & GPL License";

  public static $event = 'jqGrid';
  public static $filterToolbarEvent = 'filterToolbar';
  public static $getGridParamEvent = 'getGridParam';
  public static $setGridParamEvent = 'setGridParam';
  public static $triggerEvent  = 'trigger';

     /**
     *
  ajaxGridOptions
  ajaxSelectOptions
  altclass
  altRows
  autoencode
  autowidth
  caption
  cellLayout
  cellEdit
  cellsubmit
  cellurl
  colModel
  colNames
  data
  datastr
  datatype
  deepempty
  deselectAfterSort
  direction
  editurl
  emptyrecords
  expandColClick
  expandColumn
  footerrow
  forceFit
  gridstate
  gridview
  headertitles
  height
  hiddengrid
  hidegrid
  hoverrows
  inlineData
  jsonReader
  lastpage
  lastsort
  loadonce
  loadtext
  loadui
  mtype
  multikey
  multiboxonly
  multiselect
  multiselectWidth
  page
  pager
  pagerpos
  pgbuttons
  pginput
  pgtext
  prmNames
  postData
  reccount
  recordpos
  records
  recordtext
  resizeclass
  rowList
  rownumbers
  rowNum
  rowTotal
  rownumWidth
  savedRow
  searchdata
  scroll
  scrollOffset
  scrollTimeout
  scrollrows
  selarrrow
  selrow
  shrinkToFit
  sortable
  sortname
  sortorder
  subGrid
  subGridModel
  subGridType
  subGridUrl
  subGridWidth
  toolbar
  toppager
  totaltime
  treedatatype
  treeGrid
  treeGridModel
  treeIcons
  treeReader
  tree_root_level
  url
  userData
  userDataOnFooter
  viewrecords
  viewsortcols
  xmlReader
  grouping

  afterInsertRow
  beforeRequest
  beforeSelectRow
  gridComplete
  loadBeforeSend
  loadComplete
  loadError
  onCellSelect
  ondblClickRow
  onHeaderClick
  onPaging
  onRightClickRow
  onSelectAll
  onSelectRow
  onSortCol
  resizeStart
  resizeStart
  serializeGridData */

  public function registerOptions(){
    return   array(
              //options
               '_ajaxGridOptions' =>  array('key' => 'ajaxGridOptions', 'is_quoted' => false),
               '_ajaxSelectOptions' =>  array('key' => 'ajaxSelectOptions', 'is_quoted' => false),
               '_altclass' =>  array('key' => 'altclass', 'is_quoted' => false),
               '_altRows' =>  array('key' => 'altRows', 'is_quoted' => false),
               '_autoencode' =>  array('key' => 'autoencode', 'is_quoted' => false),
               '_autowidth' =>  array('key' => 'autowidth', 'is_quoted' => false),
               '_caption' =>  array('key' => 'caption', 'is_quoted' => false),
               '_cellLayout' =>  array('key' => 'cellLayout', 'is_quoted' => false),
               '_cellEdit' =>  array('key' => 'cellEdit', 'is_quoted' => false),
               '_cellsubmit' =>  array('key' => 'cellsubmit', 'is_quoted' => false),
               '_cellurl' =>  array('key' => 'cellurl', 'is_quoted' => false),
               '_colModel' =>  array('key' => 'colModel', 'is_quoted' => false),
               '_colNames' =>  array('key' => 'colNames', 'is_quoted' => false),
               '_data' =>  array('key' => 'data', 'is_quoted' => false),
               '_datastr' =>  array('key' => 'datastr', 'is_quoted' => false),
               '_datatype' =>  array('key' => 'datatype', 'is_quoted' => false),
               '_deepempty' =>  array('key' => 'deepempty', 'is_quoted' => false),
               '_deselectAfterSort' =>  array('key' => 'deselectAfterSort', 'is_quoted' => false),
               '_direction' =>  array('key' => 'direction', 'is_quoted' => false),
               '_editurl' =>  array('key' => 'editurl', 'is_quoted' => false),
               '_emptyrecords' =>  array('key' => 'emptyrecords', 'is_quoted' => false),
               '_ExpandColClick' =>  array('key' => 'ExpandColClick', 'is_quoted' => false),
               '_ExpandColumn' =>  array('key' => 'ExpandColumn', 'is_quoted' => false),
               '_footerrow' =>  array('key' => 'footerrow', 'is_quoted' => false),
               '_forceFit' =>  array('key' => 'forceFit', 'is_quoted' => false),
               '_gridstate' =>  array('key' => 'gridstate', 'is_quoted' => false),
               '_gridview' =>  array('key' => 'gridview', 'is_quoted' => false),
               '_grouping' =>  array('key' => 'grouping', 'is_quoted' => false),
               '_groupingView' =>  array('key' => 'groupingView', 'is_quoted' => false),
               '_headertitles' =>  array('key' => 'headertitles', 'is_quoted' => false),
               '_height' =>  array('key' => 'height', 'is_quoted' => false),
               '_hiddengrid' =>  array('key' => 'hiddengrid', 'is_quoted' => false),
               '_hidegrid' =>  array('key' => 'hidegrid', 'is_quoted' => false),
               '_hoverrows' =>  array('key' => 'hoverrows', 'is_quoted' => false),
               '_ignoreCase' =>  array('key' => 'ignoreCase', 'is_quoted' => false),
               '_inlineData' =>  array('key' => 'inlineData', 'is_quoted' => false),
               '_jsonReader' =>  array('key' => 'jsonReader', 'is_quoted' => false),
               '_lastpage' =>  array('key' => 'lastpage', 'is_quoted' => false),
               '_lastsort' =>  array('key' => 'lastsort', 'is_quoted' => false),
               '_loadonce' =>  array('key' => 'loadonce', 'is_quoted' => false),
               '_loadtext' =>  array('key' => 'loadtext', 'is_quoted' => false),
               '_loadui' =>  array('key' => 'loadui', 'is_quoted' => false),
               '_mtype' =>  array('key' => 'mtype', 'is_quoted' => false),
               '_multikey' =>  array('key' => 'multikey', 'is_quoted' => false),
               '_multiboxonly' =>  array('key' => 'multiboxonly', 'is_quoted' => false),
               '_multiselect' =>  array('key' => 'multiselect', 'is_quoted' => false),
               '_multiselectWidth' =>  array('key' => 'multiselectWidth', 'is_quoted' => false),
               '_page' =>  array('key' => 'page', 'is_quoted' => false),
               '_pager' =>  array('key' => 'pager', 'is_quoted' => false),
               '_pagerpos' =>  array('key' => 'pagerpos', 'is_quoted' => false),
               '_pgbuttons' =>  array('key' => 'pgbuttons', 'is_quoted' => false),
               '_pginput' =>  array('key' => 'pginput', 'is_quoted' => false),
               '_pgtext' =>  array('key' => 'pgtext', 'is_quoted' => false),
               '_prmNames' =>  array('key' => 'prmNames', 'is_quoted' => false),
               '_postData' =>  array('key' => 'postData', 'is_quoted' => false),
               '_recordpos' =>  array('key' => 'recordpos', 'is_quoted' => false),
               '_records' =>  array('key' => 'records', 'is_quoted' => false),
               '_recordtext' =>  array('key' => 'recordtext', 'is_quoted' => false),
               '_reccount' =>  array('key' => 'reccount', 'is_quoted' => false),
               '_resizeclass' =>  array('key' => 'resizeclass', 'is_quoted' => false),
               '_rowList' =>  array('key' => 'rowList', 'is_quoted' => false),
               '_rownumbers' =>  array('key' => 'rownumbers', 'is_quoted' => false),
               '_rowNum' =>  array('key' => 'rowNum', 'is_quoted' => false),
               '_rowTotal' =>  array('key' => 'rowTotal', 'is_quoted' => false),
               '_rownumWidth' =>  array('key' => 'rownumWidth', 'is_quoted' => false),
               '_savedRow' =>  array('key' => 'savedRow', 'is_quoted' => false),
               '_searchdata' =>  array('key' => 'searchdata', 'is_quoted' => false),
               '_scroll' =>  array('key' => 'scroll', 'is_quoted' => false),
               '_scrollOffset' =>  array('key' => 'scrollOffset', 'is_quoted' => false),
               '_scrollTimeout' =>  array('key' => 'scrollTimeout', 'is_quoted' => false),
               '_scrollrows' =>  array('key' => 'scrollrows', 'is_quoted' => false),
               '_selarrrow' =>  array('key' => 'selarrrow', 'is_quoted' => false),
               '_selrow' =>  array('key' => 'selrow', 'is_quoted' => false),
               '_shrinkToFit' =>  array('key' => 'shrinkToFit', 'is_quoted' => false),
               '_sortable' =>  array('key' => 'sortable', 'is_quoted' => false),
               '_sortname' =>  array('key' => 'sortname', 'is_quoted' => false),
               '_sortorder' =>  array('key' => 'sortorder', 'is_quoted' => false),
               '_subGrid' =>  array('key' => 'subGrid', 'is_quoted' => false),
               '_subGridModel' =>  array('key' => 'subGridModel', 'is_quoted' => false),
               '_subGridType' =>  array('key' => 'subGridType', 'is_quoted' => false),
               '_subGridUrl' =>  array('key' => 'subGridUrl', 'is_quoted' => false),
               '_subGridWidth' =>  array('key' => 'subGridWidth', 'is_quoted' => false),
               '_toolbar' =>  array('key' => 'toolbar', 'is_quoted' => false),
               '_toppager' =>  array('key' => 'toppager', 'is_quoted' => false),
               '_totaltime' =>  array('key' => 'totaltime', 'is_quoted' => false),
               '_treedatatype' =>  array('key' => 'treedatatype', 'is_quoted' => false),
               '_treeGrid' =>  array('key' => 'treeGrid', 'is_quoted' => false),
               '_treeGridModel' =>  array('key' => 'treeGridModel', 'is_quoted' => false),
               '_treeIcons' =>  array('key' => 'treeIcons', 'is_quoted' => false),
               '_treeReader' =>  array('key' => 'treeReader', 'is_quoted' => false),
               '_tree_root_level' =>  array('key' => 'tree_root_level', 'is_quoted' => false),
               '_url' =>  array('key' => 'url', 'is_quoted' => false),
               '_userData' =>  array('key' => 'userData', 'is_quoted' => false),
               '_userDataOnFooter' =>  array('key' => 'userDataOnFooter', 'is_quoted' => false),
               '_viewrecords' =>  array('key' => 'viewrecords', 'is_quoted' => false),
               '_viewsortcols' =>  array('key' => 'viewsortcols', 'is_quoted' => false),
               '_width' =>  array('key' => 'width', 'is_quoted' => false),
               '_xmlReader' =>  array('key' => 'xmlReader', 'is_quoted' => false),
              //events
               'afterInsertRow' =>  array('key' => 'afterInsertRow', 'is_quoted' => false),
               'beforeRequest' =>  array('key' => 'beforeRequest', 'is_quoted' => false),
               'beforeSelectRow' =>  array('key' => 'beforeSelectRow', 'is_quoted' => false),
               'gridComplete' =>  array('key' => 'gridComplete', 'is_quoted' => false),
               'loadBeforeSend' =>  array('key' => 'loadBeforeSend', 'is_quoted' => false),
               'loadComplete' =>  array('key' => 'loadComplete', 'is_quoted' => false),
               'loadError' =>  array('key' => 'loadError', 'is_quoted' => false),
               'onCellSelect' =>  array('key' => 'onCellSelect', 'is_quoted' => false),
               'ondblClickRow' =>  array('key' => 'ondblClickRow', 'is_quoted' => false),
               'onHeaderClick' =>  array('key' => 'onHeaderClick', 'is_quoted' => false),
               'onPaging' =>  array('key' => 'onPaging', 'is_quoted' => false),
               'onRightClickRow' =>  array('key' => 'onRightClickRow', 'is_quoted' => false),
               'onSelectAll' =>  array('key' => 'onSelectAll', 'is_quoted' => false),
               'onSelectRow' =>  array('key' => 'onSelectRow', 'is_quoted' => false),
               'onSortCol' =>  array('key' => 'onSortCol', 'is_quoted' => false),
               'resizeStart' =>  array('key' => 'resizeStart', 'is_quoted' => false),
               'resizeStop' =>  array('key' => 'resizeStop', 'is_quoted' => false),
               'serializeGridData' =>  array('key' => 'serializeGridData', 'is_quoted' => false));
  }

  /**
   * Retrieves a instance of this class.
   * @return object self::$instance
   */
  public static function getInstance()
  {
    $object = __CLASS__;
    self::$instance = new $object();
    return self::$instance;
  }

  public static function build($options = null){
    $jquery = self::getInstance();
    $jquery->setEvent(self::$event);
    if($options !== null){
      $jquery->setOptions($options);
    }
    return $jquery;
  }

  public static function filterGrid($options, $newApi = false){
    $jquery = new YsJQueryCore();
    if($newApi){
      $jquery->setEvent(self::$event);
      $jquery->addArgument(new YsArgument(self::$filterToolbarEvent));
    }else{
      $jquery->setEvent(self::$filterToolbarEvent);
    }
    $jquery->addArgument(new YsArgument($options));
    return $jquery;
  }

  public static function navGrid($args){
    $funcArgs = func_get_args();
    $args = array_merge(array('navGrid'),$funcArgs);
    return self::setAndGetMethods($args);
  }

  public static function navButtonAdd($args){
    $funcArgs = func_get_args();
    $args = array_merge(array('navButtonAdd'),$funcArgs);
    return self::setAndGetMethods($args);
  }

  public static function navSeparatorAdd($args){
    $funcArgs = func_get_args();
    $args = array_merge(array('navSeparatorAdd'),$funcArgs);
    return self::setAndGetMethods($args);
  }

  public static function buildMethod($args){
    return self::setAndGetMethods(func_get_args());
  }

  public static function buildMethodForGrid($args){
    $funcArgs = func_get_args();
    if(isset($funcArgs[0]) && is_array($funcArgs[0])){
      $i=0;
      $arrayArgs = array();
      foreach($funcArgs[0] as $funcArg){
        $arrayArgs[$i++] = $funcArg;
      }
      $funcArgs = array();
      $funcArgs = $arrayArgs;
    }
    return self::setAndGetMethods($funcArgs);
  }
  
  private static function setAndGetMethods($arguments){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$event);
    foreach($arguments as $argument){
      $jquery->addArgument(new YsArgument($argument));
    }
    return $jquery;
  }

  public static function trigger($triggerName){
    $jquery = new YsJQueryCore();
    $jquery->setEvent(self::$triggerEvent);
    $jquery->addArgument(new YsArgument($triggerName));
    return $jquery;
  }

  public static function reloadGrid($jQuerySelector){
    return new YsJQueryDynamic(YsJQGrid::build(null)->in($jQuerySelector), YsJQGrid::trigger('reloadGrid'));
  }

}