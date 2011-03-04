<?php

/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsGrid  todo description
 *
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsGrid {

  private $ajaxGridOptions;
  private $ajaxSelectOptions;
  private $altclass;
  private $altRows;
  private $autoencode;
  private $autowidth;
  private $caption;
  private $cellLayout;
  private $cellEdit;
  private $cellsubmit;
  private $cellurl;
  private $colModel;
  private $colNames;
  private $data;
  private $datastr;
  private $datatype;
  private $deepempty;
  private $deselectAfterSort;
  private $direction;
  private $editurl;
  private $emptyrecords;
  private $ExpandColClick;
  private $ExpandColumn;
  private $footerrow;
  private $forceFit;
  private $gridstate;
  private $gridview;
  private $headertitles;
  private $height;
  private $hiddengrid;
  private $hidegrid;
  private $hoverrows;
  private $inlineData;
  private $jsonReader;
  private $lastpage;
  private $lastsort;
  private $loadonce;
  private $loadtext;
  private $loadui;
  private $mtype;
  private $multikey;
  private $multiboxonly;
  private $multiselect;
  private $multiselectWidth;
  private $page;
  private $pager;
  private $pagerpos;
  private $pgbuttons;
  private $pginput;
  private $pgtext;
  private $prmNames;
  private $postData;
  private $reccount;
  private $recordpos;
  private $records;
  private $recordtext;
  private $resizeclass;
  private $rowList;
  private $rownumbers;
  private $rowNum;
  private $rowTotal;
  private $rownumWidth;
  private $savedRow;
  private $searchdata;
  private $scroll;
  private $scrollOffset;
  private $scrollTimeout;
  private $scrollrows;
  private $selarrrow;
  private $selrow;
  private $shrinkToFit;
  private $sortable;
  private $sortname;
  private $sortorder;
  private $subGrid;
  private $subGridModel;
  private $subGridType;
  private $subGridUrl;
  private $subGridWidth;
  private $toolbar;
  private $toppager;
  private $totaltime;
  private $treedatatype;
  private $treeGrid;
  private $treeGridModel;
  private $treeIcons;
  private $treeReader;
  private $tree_root_level;
  private $url;
  private $userData;
  private $userDataOnFooter;
  private $viewrecords;
  private $viewsortcols;
  private $xmlReader;
  private $width;
  private $grouping;
  private $groupingView;
  private $ignoreCase;
  private $afterInsertRow;
  private $beforeRequest;
  private $beforeSelectRow;
  private $gridComplete;
  private $loadBeforeSend;
  private $loadComplete;
  private $loadError;
  private $onCellSelect;
  private $ondblClickRow;
  private $onHeaderClick;
  private $onPaging;
  private $onRightClickRow;
  private $onSelectAll;
  private $onSelectRow;
  private $onSortCol;
  private $resizeStart;
  private $resizeStop;
  private $serializeGridData;
  private $subGridBeforeExpand;
  private $subGridRowExpanded;
  private $subGridRowColapsed;
  private $serializeSubGridData;
  private $showFooter;
  private $gridId;
  private $gridHtmlProperties;
  private $footerHtmlProperties;
  private $recordList;
  private $gridFields;
  private $jqgridOptions;
  private $ysJQGrid;
  private $gridTemplate;
  private $footerTemplate;
  private $jquerySelector;
  private $filterToolbar;
  private $navigator;
  private $postSintax;
  private $editOnSelectRow;
  private $calendarSupportIn;
  private $calendarDefaultOptions;

  private function varsToUnset() {
    return array('postSintax', 'showFooter', 'gridId', 'gridHtmlProperties',
    'footerHtmlProperties', 'gridRecords', 'gridFields', 'recordList',
    'jqgridOptions', 'ysJQGrid', 'gridTemplate', 'gridTemplate', 'jquerySelector',
    'filterToolbar', 'navigator', 'editOnSelectRow','calendarSupportIn',
    'calendarDefaultOptions');
  }

  public function __construct($gridId, $caption = null, $gridHtmlProperties = null) {
    YsJQuery::usePlugin('jqGrid');
    $this->renewPostSintax();
    $this->renew($gridId, $caption);
  }

  public function getAjaxGridOptions() {
    return $this->ajaxGridOptions;
  }

  /**
   * This option allow to set global ajax settings for the grid when we request
   * data. Note that with this option is possible to overwrite all current ajax
   * setting in the grid including the error, complete and beforeSend events.
   * @param array() $ajaxGridOptions
   */
  public function setAjaxGridOptions($ajaxGridOptions) {
    $this->ajaxGridOptions = $ajaxGridOptions;
  }

  public function getAjaxSelectOptions() {
    return $this->ajaxSelectOptions;
  }

  /**
   * This option allow to set global ajax settings for the select element when
   * the select is obtained via dataUrl option in editoptions or searchoptions
   * objects
   * @param array() $ajaxSelectOptions
   */
  public function setAjaxSelectOptions($ajaxSelectOptions) {
    $this->ajaxSelectOptions = $ajaxSelectOptions;
  }

  public function getAltClass() {
    return $this->altclass;
  }

  /**
   * The class that is used for alternate rows.
   * You can construct your own class and replace this value.
   * This option is valid only if altRows options is set to true
   * @param string $altclass
   */
  public function setAltClass($altclass) {
    $this->altclass = $altclass;
  }

  public function getAltRows() {
    return $this->altRows;
  }

  /**
   * Set a zebra-striped grid
   * @param boolean $altRows
   */
  public function setAltRows($altRows) {
    $this->altRows = $altRows;
  }

  public function getAutoEncode() {
    return $this->autoencode;
  }

  /**
   * When set to true encodes (html encode) the incoming (from server) and
   * posted data (from editing modules). By example < will be converted to &lt;
   * @param boolean $autoencode
   */
  public function setAutoEncode($autoencode) {
    $this->autoencode = $autoencode;
  }

  public function getAutoWidth() {
    return $this->autowidth;
  }

  /**
   * When set to true, the grid width is recalculated automatically to the width
   * of the parent element. This is done only initially when the grid is
   * created. In order to resize the grid when the parent element changes width
   * you should apply custom code and use a setGridWidth method for this purpose
   * @param boolean $autowidth
   */
  public function setAutoWidth($autowidth) {
    $this->autowidth = $autowidth;
  }

  public function getCaption() {
    return $this->caption;
  }

  /**
   * Defines the Caption layer for the grid.
   * This caption appears above the Header layer.
   * If the string is empty the caption does not appear.
   * @param string $caption
   */
  public function setCaption($caption) {
    $this->caption = $caption;
  }

  public function getCellLayout() {
    return $this->cellLayout;
  }

  /**
   * This option determines the padding + border width of the cell.
   * Usually this should not be changed, but if custom changes to td element are
   * made in the grid css file this will need to be changed.
   * The initial value of 5 means paddingLef?2+paddingRight?2+borderLeft?1=5
   * @param integer $cellLayout
   */
  public function setCellLayout($cellLayout) {
    $this->cellLayout = $cellLayout;
  }

  public function getCellEdit() {
    return $this->cellEdit;
  }

  /**
   * Enables (disables) cell editing. See Cell Editing for more details
   * @param boolean $cellEdit
   */
  public function setCellEdit($cellEdit) {
    $this->cellEdit = $cellEdit;
  }

  public function getCellSubmit() {
    return $this->cellsubmit;
  }

  /**
   * Determines where the contents of the cell are saved:
   * 'remote' or 'clientArray'. See Cell Editing for more details
   * @param string $cellsubmit
   */
  public function setCellSubmit($cellsubmit) {
    $this->cellsubmit = $cellsubmit;
  }

  public function getCellUrl() {
    return $this->cellurl;
  }

  /**
   * The url where the cell is to be saved. See Cell Editing for more details
   * @param string $cellurl
   */
  public function setCellUrl($cellurl) {
    $this->cellurl = $cellurl;
  }

  public function getColModel() {
    return $this->colModel;
  }

  /**
   * Array which describes the parameters of the columns.
   * This is the most important part of the grid.
   * For a full description of all valid values see colModel API.
   * @param array() $colModel
   */
  public function setColModel($colModel) {
    $this->colModel = $colModel;
  }

  public function getColNames() {
    return $this->colNames;
  }

  /**
   * An array in which we place the names of the columns.
   * This is the text that appears in the head of the grid (Header layer).
   * The names are separated with commas. Note that the number of element in
   * this array should be equal of the number elements in the colModel array.
   * @param array(array()) $colNames
   */
  public function setColNames($colNames) {
    $this->colNames = $colNames;
  }

  public function getData() {
    return $this->data;
  }

  /**
   * A array that store the local data passed to the grid.
   * You can directly point to this variable in case you want to load a array
   * data. It can replace addRowData method which is slow on relative big data
   * @param array() $data
   */
  public function setData($data) {
    $this->data = $data;
  }

  public function getDataStr() {
    return $this->datastr;
  }

  /**
   * The string of data when datatype parameter is set to
   * xmlstring or jsonstring
   * @param string $datastr
   */
  public function setDataStr($datastr) {
    $this->datastr = $datastr;
  }

  public function getDataType() {
    return $this->datatype;
  }

  /**
   * Defines what type of information to expect to represent data in the grid.
   * Valid options are
   * xml - we expect xml data;
   * xmlstring - we expect xml data as string;
   * json - we expect JSON data;
   * jsonstring - we expect JSON data as string;
   * local - we expect data defined at client side (array data);
   * javascript - we expect javascript as data;
   * function - custom defined function for retrieving data.
   * @param string $datatype
   */
  public function setDataType($datatype) {
    $this->datatype = $datatype;
  }

  public function getDeepEmpty() {
    return $this->deepempty;
  }

  /**
   * This option should be set to true if a event or a plugin is attached to the
   * table cell. The option uses jQuery empty for the the row and all its
   * children elements. This have of course speed overhead, but prevent memory
   * leaks
   * @param boolean $deepempty
   */
  public function setDeepEmpty($deepempty) {
    $this->deepempty = $deepempty;
  }

  public function getDeselectAfterSort() {
    return $this->deselectAfterSort;
  }

  /**
   * Applicable only when we use datatype : local. Deselects currently-selected
   * row(s) when a sort is applied.
   * @param boolean $deselectAfterSort
   */
  public function setDeselectAfterSort($deselectAfterSort) {
    $this->deselectAfterSort = $deselectAfterSort;
  }

  public function getDirection() {
    return $this->direction;
  }

  /**
   * Determines the language direction in grid. The default is �ltr�
   * (Left To Right language). When set to �rtl� (Right To Left) the grid
   * automatically do the needed. It is important to note that in one page we
   * can have two (or more) grids where the one can have direction �ltr� while
   * the other can have direction �rtl�. This option work only in FireFox 3.x
   * versions and Internet Explorer versions >=6. Currently Safai and Google
   * Chrome does not support fully direction �rtl�. Also the same apply to
   * Opera browsers. The most common problem in FireFox is that the default
   * settings of the browser does not support RTL
   * @param string $direction
   */
  public function setDirection($direction) {
    $this->direction = $direction;
  }

  public function getEditUrl() {
    return $this->editurl;
  }

  /**
   * Defines the url for inline and form editing.
   * @param string $editurl
   */
  public function setEditUrl($editurl) {
    $this->editurl = $editurl;
  }

  public function getEmptyRecords() {
    return $this->emptyrecords;
  }

  /**
   * Display the information when the returned (or the current) number of
   * records is zero. This option is valid only if viewrecords option is set to
   * true.
   * @param string $emptyrecords
   */
  public function setEmptyRecords($emptyrecords) {
    $this->emptyrecords = $emptyrecords;
  }

  public function getExpandColClick() {
    return $this->ExpandColClick;
  }

  /**
   * When true, the treeGrid is expanded and/or collapsed when we click on the
   * text of the expanded column, not only on the image
   * @param boolean $expandColClick
   */
  public function setExpandColClick($expandColClick) {
    $this->ExpandColClick = $expandColClick;
  }

  public function getExpandColumn() {
    return $this->ExpandColumn;
  }

  /**
   * Indicates which column (name from colModel) should be used to expand the
   * tree grid. If not set the first one is used. Valid only when treeGrid
   * option is set to true.
   * @param string $expandColumn
   */
  public function setExpandColumn($expandColumn) {
    $this->ExpandColumn = $expandColumn;
  }

  public function getFooterrow() {
    return $this->footerrow;
  }

  /**
   * If set to true this will place a footer table with one row below the gird
   * records and above the pager. The number of columns equal of these from
   * colModel.
   * @param boolean $footerrow
   */
  public function setFooterrow($footerrow) {
    $this->footerrow = $footerrow;
  }

  public function getForcefit() {
    return $this->forceFit;
  }

  /**
   * If set to true, and resizing the width of a column, the adjacent column
   * (to the right) will resize so that the overall grid width is maintained
   * (e.g., reducing the width of column 2 by 30px will increase the size of
   * column 3 by 30px). In this case there is no horizontal scrolbar.
   * 
   * <b>Note:</b> this option is not compatible with shrinkToFit option - i.e if
   * shrinkToFit is set to false, forceFit is ignored.
   * @param boolean $forceFit
   */
  public function setForcefit($forceFit) {
    $this->forceFit = $forceFit;
  }

  public function getGridState() {
    return $this->gridstate;
  }

  /**
   * Determines the current state of the grid
   * (i.e. when used with hiddengrid, hidegrid and caption options).
   * Can have either of two states: 'visible' or 'hidden'
   * @param string $gridstate
   */
  public function setGridState($gridstate) {
    $this->gridstate = $gridstate;
  }

  public function getGridView() {
    return $this->gridview;
  }

  /**
   * In the previous versions of jqGrid including 3.4.X,reading a relatively big
   * data sets (Rows >=100 ) caused speed problems. The reason for this was that
   * as every cell was inserted into the grid we applied about 5-6 jQuery calls
   * to it. Now this problem is resolved; we now insert the entry row at once
   * with a jQuery append. The result is impressive - about 3-5 times faster.
   * What will be the result if we insert all the data at once? Yes, this can be
   * done with a help of gridview option when set to true. The result is a grid
   * that is 5 to 10 times faster. Of course when this option is set to true we
   * have some limitations. If set to true we can not use treeGrid, subGrid, or
   * afterInsertRow event. If you do not use these three options in the grid you
   * can set this option to true and enjoy the speed.
   * @param boolean $gridview
   */
  public function setGridView($gridview) {
    $this->gridview = $gridview;
  }

  public function getGrouping() {
    return $this->grouping;
  }

  /**
   * Enables grouping in grid
   * @param boolean $grouping
   */
  public function setGrouping($grouping) {
    $this->grouping = $grouping;
  }

  public function getGroupingView() {
    return $this->groupingView;
  }

  public function setGroupingView($groupingView) {
    if ($groupingView instanceof YsGridGroupingView) {
      $groupingView = $groupingView->getGroupingViewOptions();
    }
    $this->groupingView = $groupingView;
  }

  public function getHeaderTitles() {
    return $this->headertitles;
  }

  /**
   * If the option is set to true the title attribute is added to the column
   * headers
   * @param boolean $headertitles
   */
  public function setHeaderTitles($headertitles) {
    $this->headertitles = $headertitles;
  }

  public function getHeight() {
    return $this->height;
  }

  /**
   * The height of the grid. Can be set as number (in this case we mean pixels)
   * or as percentage (only 100% is acceped) or value of auto is acceptable.
   * @param string/integer $height
   */
  public function setHeight($height) {
    $this->height = $height;
  }

  public function getHiddenGrid() {
    return $this->hiddengrid;
  }

  /**
   * If set to true the grid initially is hidden.
   * The data is not loaded (no request is sent) and only the caption layer
   * is shown. When the show/hide button is clicked the first time to show grid,
   * the request is sent to the server, the data is loaded, and grid is shown.
   * From this point we have a regular grid. This option has effect only if the
   * caption property is not empty and the hidegrid property (see below)
   * is set to true.
   * @param boolean $hiddengrid
   */
  public function setHiddenGrid($hiddengrid) {
    $this->hiddengrid = $hiddengrid;
  }

  public function getHideGrid() {
    return $this->hidegrid;
  }

  /**
   * Enables or disables the show/hide grid button, which appears on the right
   * side of the Caption layer.
   * Takes effect only if the caption property is not an empty string.
   * @param boolean $hidegrid
   */
  public function setHideGrid($hidegrid) {
    $this->hidegrid = $hidegrid;
  }

  public function getHoverrows() {
    return $this->hoverrows;
  }

  /**
   * When set to false the mouse hovering is disabled in the grid data rows.
   * @param boolean $hoverrows
   */
  public function setHoverrows($hoverrows) {
    $this->hoverrows = $hoverrows;
  }

  public function getIgnoreCase() {
    return $this->ignoreCase;
  }

  /**
   * By default the local searching is case sensitive.
   * To make the local search and sorting not case sensitive set this options
   * to true
   * @param boolean $ignoreCase
   */
  public function setIgnoreCase($ignoreCase) {
    $this->ignoreCase = $ignoreCase;
  }

  public function getInlineData() {
    return $this->inlineData;
  }

  /**
   * An array used to add content to the data posted to the server when we are
   * in inline editing.
   * @param array() $inlineData
   */
  public function setInlineData($inlineData) {
    $this->inlineData = $inlineData;
  }

  public function getJSONReader() {
    return $this->jsonReader;
  }

  /**
   * Array which describes the structure of the expected json data.
   * @param array() $jsonReader
   */
  public function setJSONReader($jsonReader) {
    $this->jsonReader = $jsonReader;
  }

  public function getLastPage() {
    return $this->lastpage;
  }

  /**
   * Readonly property. Determines the total number of pages returned from the
   * request.
   * @param integer $lastpage
   */
  public function setLastPage($lastpage) {
    $this->lastpage = $lastpage;
  }

  public function getLastSort() {
    return $this->lastsort;
  }

  /**
   * Readonly property. Determines the index of last sorted column beginning from 0
   * @param integer $lastsort
   */
  public function setLastSort($lastsort) {
    $this->lastsort = $lastsort;
  }

  public function getLoadOnce() {
    return $this->loadonce;
  }

  /**
   * If this flag is set to true, the grid loads the data from the server only
   * once (using the appropriate datatype). After the first request the datatype
   * parameter is automatically changed to local and all further manipulations
   * are done on the client side. The functions of the pager (if present) are
   * disabled.
   * @param boolean $loadonce
   */
  public function setLoadOnce($loadonce) {
    $this->loadonce = $loadonce;
  }

  public function getLoadText() {
    return $this->loadtext;
  }

  /**
   * The text which appear when requesting and sorting data.
   * This parameter is located in language file
   * @param string $loadtext
   */
  public function setLoadText($loadtext) {
    $this->loadtext = $loadtext;
  }

  public function getLoadUI() {
    return $this->loadui;
  }

  /**
   * This option controls what to do when an ajax operation is in
   * progress.
   * <b>disable</b> - disables the jqGrid progress indicator.
   * This way you can use your own indicator.
   * <b>enable (default)</b> - enables �Loading� message in the center of the grid.
   * <b>block</b> - enables the �Loading� message and blocks all actions in the grid
   * until the ajax request is finished.
   * Note that this disables paging, sorting and all actions on toolbar, if any.
   * @param string $loadui
   */
  public function setLoadUI($loadui) {
    $this->loadui = $loadui;
  }

  public function getMtype() {
    return $this->mtype;
  }

  /**
   * Defines the type of request to make (�POST� or �GET�)
   * @param string $mtype
   */
  public function setMtype($mtype) {
    $this->mtype = $mtype;
  }

  public function getMultikey() {
    return $this->multikey;
  }

  /**
   * This parameter have sense only multiselect option is set to true.
   * Defines the key which will be pressed when we make multiselection.
   * The possible values are:
   * <b>shiftKey</b> - the user should press Shift Key
   * <b>altKey</b> - the user should press Alt Key
   * <b>ctrlKey</b>  - the user should press Ctrl Key
   * @param string $multikey
   */
  public function setMultikey($multikey) {
    $this->multikey = $multikey;
  }

  public function getMultiboxOnly() {
    return $this->multiboxonly;
  }

  /**
   * This option works only when multiselect = true.
   * When multiselect is set to true, clicking anywhere on a row selects that
   * row; when multiboxonly is also set to true, the multiselection is done only
   * when the checkbox is clicked (Yahoo style).
   * Clicking in any other row (suppose the checkbox is not clicked) deselects
   * all rows and the current row is selected.
   * @param boolean $multiboxonly
   */
  public function setMultiboxOnly($multiboxonly) {
    $this->multiboxonly = $multiboxonly;
  }

  public function getMultiselect() {
    return $this->multiselect;
  }

  /**
   * If this flag is set to true a multi selection of rows is enabled.
   * A new column at left side is added. Can be used with any datatype option.
   * @param boolean $multiselect
   */
  public function setMultiselect($multiselect) {
    $this->multiselect = $multiselect;
  }

  public function getMultiselectWidth() {
    return $this->multiselectWidth;
  }

  /**
   * Determines the width of the multiselect column if multiselect is set to true.
   * @param integer $multiselectWidth
   */
  public function setMultiselectWidth($multiselectWidth) {
    $this->multiselectWidth = $multiselectWidth;
  }

  public function getPage() {
    return $this->page;
  }

  /**
   * Set the initial number of page when we make the request.
   * This parameter is passed to the url for use by the server routine
   * retrieving the data
   * @param integer $page
   */
  public function setPage($page) {
    $this->page = $page;
  }

  public function getPager() {
    return $this->pager;
  }

  /**
   * Defines that we want to use a pager bar to navigate through the records.
   * This must be a valid html element; in our example we gave the div the id
   * of �pager�, but any name is acceptable. Note that the Navigation layer
   * (the �pager� div) can be positioned anywhere you want, determined by your
   * html; in our example we specified that the pager will appear after the
   * Table Body layer. The valid calls can be (using our example)
   * 'pager', '#pager', jQuery('#pager'). the jQgrid team recommend to use the
   * second one.
   * @param string/YsJQuery $pager
   */
  public function setPager($pager) {
    $this->pager = $pager;
  }

  public function getPagerPos() {
    return $this->pagerpos;
  }

  /**
   * Determines the position of the pager in the grid. By default the pager
   * element when created is divided in 3 parts (one part for pager, one part
   * for navigator buttons and one part for record information)
   * @param string $pagerpos
   */
  public function setPagerPos($pagerpos) {
    $this->pagerpos = $pagerpos;
  }

  public function getPgbuttons() {
    return $this->pgbuttons;
  }

  /**
   * Determines if the Pager buttons should be shown if pager is available.
   * Also valid only if pager is set correctly. The buttons are placed in the
   * pager bar.
   * @param boolean $pgbuttons
   */
  public function setPgbuttons($pgbuttons) {
    $this->pgbuttons = $pgbuttons;
  }

  public function getPginput() {
    return $this->pginput;
  }

  /**
   * Determines if the input box, where the user can change the number of
   * requested page, should be available. The input box appear in the pager bar.
   * @param boolean $pginput
   */
  public function setPginput($pginput) {
    $this->pginput = $pginput;
  }

  public function getPgtext() {
    return $this->pgtext;
  }

  /**
   * Show information about current page status.
   * The first value is the current loaded page.
   * The second value is the total number of pages
   * @return string $pgtext
   */
  public function setPgtext($pgtext) {
    $this->pgtext = $pgtext;
  }

  public function getPrmnames() {
    return $this->prmNames;
  }

  /**
   * Default values prmNames:
   * {page:�page�,rows:�rows�, sort: �sidx�, order: �sord�, search:�_search�,
   * nd:�nd�, id:�id�, oper:�oper�, editoper:�edit�, addoper:�add�,
   * deloper:�del�, subgridid:�id�, npage: null, totalrows:�totalrows�}
   *
   * Customizes names of the fields sent to the server on a Post.
   * For example, with this setting, you can change the sort order element
   * from �sidx� to �mysort�: prmNames: {sort: �mysort�}.
   * The string that will be posted to the server will then be
   * myurl.php?page=1&rows=10&mysort=myindex&sord=asc
   * rather than myurl.php?page=1&rows=10&sidx=myindex&sord=asc
   * When some parameter is set to null they will be not sended to the server.
   * By example if we set prmNames: { nd:null} the nd parameter will not be
   * sended. For npage option see scroll option.
   * The options meaning the following
   * <br/><b>page</b> - the the requested page - default value page,
   * <br/><b>rows</b> - the number of rows requested - default value rows,
   * <br/><b>sort</b> - the sorting column - default value sidx,
   * <br/><b>order</b> - the sort order default value sord,
   * <br/><b>search</b> - the search indicator - default value _search,
   * <br/><b>nd</b> - the time passed to the request (for IE browsers not to cache
   * the request) - default value nd,
   * <br/><b>id</b> - the name of the id when post data in editing modules -
   * default value id,
   * <br/><b>oper</b> - the operation parameter - default value oper,
   * <br/><b>editoper</b> - the name of operation when the data is posted in
   * edit mode - default value edit,
   * <br/><b>addoper</b> - the name of operation when the data is posted in
   * add mode - default value add,
   * <br/><b>deloper</b> - the name of operation when the data is posted in
   * delete mode - default value del
   * <br/><b>totalrows</b> - the name of the total rows to be obtained from
   * server - see rowTotal - default value totalrows
   * <br/><b>subgridid</b> - the name passed when we click to load data in
   * subgrid - default value id
   * @param array() $prmNames
   */
  public function setPrmnames($prmNames) {
    $this->prmNames = $prmNames;
  }

  public function getPostData() {
    return $this->postData;
  }

  /**
   * This array is passed directly to the url. This is associative array and can
   * be used this way: {name1:value1�}. See API methods for manipulation.
   * @param array() $postData
   */
  public function setPostData($postData) {
    $this->postData = $postData;
  }

  public function getRecount() {
    return $this->reccount;
  }

  /**
   * Readonly property.
   * Determines the exactly number of rows in the grid.
   * Do not mix this with records parameter.
   * Instead that in most cases they are equal there is a case where this is
   * not true. By example you define rowNum parameter 15, but you return from
   * server records parameter = 20, then the records parameter will be 20,
   * the reccount parameter will be 15, and in the grid you will have 15 records
   * @param integer $reccount
   */
  public function setReccount($reccount) {
    $this->reccount = $reccount;
  }

  public function getRecordPos() {
    return $this->recordpos;
  }

  /**
   * Determines the position of the record information in the pager.
   * Can be left, center, right
   * @param string $recordpos
   */
  public function setRecordPos($recordpos) {
    $this->recordpos = $recordpos;
  }

  public function getRecords() {
    return $this->records;
  }

  /**
   * Readonly property.
   * Determines the number of returned records in grid from the request
   * @param integer $records
   */
  public function setRecords($records) {
    $this->records = $records;
  }

  public function getRecordText() {
    return $this->recordtext;
  }

  /**
   * Represent information that can be shown in the pager.
   * Also this option is valid if viewrecords option is set to true.
   * This text appear only if the tottal number of recreds is greater then zero.
   * In order to show or hide some information the items in {} mean the
   * following: {0} the start position of the records depending on page number
   * and number of requested records; {1} - the end position {2} - total
   * records returned from the data
   * @param string $recordtext
   */
  public function setRecordText($recordtext) {
    $this->recordtext = $recordtext;
  }

  public function getResizeClass() {
    return $this->resizeclass;
  }

  /**
   * Assigns a class to columns that are resizable so that we can show a resize
   * handle only for ones that are resizable
   * @param string $resizeclass
   */
  public function setResizeClass($resizeclass) {
    $this->resizeclass = $resizeclass;
  }

  public function getRowList() {
    return $this->rowList;
  }

  /**
   * An array to construct a select box element in the pager in which we can
   * change the number of the visible rows. When changed during the execution,
   * this parameter replaces the rowNum parameter that is passed to the url.
   * If the array is empty the element does not appear in the pager.
   * Typical you can set this like [10,20,30]. If the rowNum parameter is set
   * to 30 then the selected value in the select box is 30.
   * @param array() $rowList
   */
  public function setRowList($rowList) {
    $this->rowList = $rowList;
  }

  public function getRowNumbers() {
    return $this->rownumbers;
  }

  /**
   * If this option is set to true, a new column at left of the grid is added.
   * The purpose of this column is to count the number of available rows,
   * beginning from 1. In this case colModel is extended
   * automatically with new element with name - 'rn'. Also,
   * be careful not to use the name 'rn' in colModel
   * @param boolean $rownumbers
   */
  public function setRowNumbers($rownumbers) {
    $this->rownumbers = $rownumbers;
  }

  public function getRowNum() {
    return $this->rowNum;
  }

  /**
   * Sets how many records we want to view in the grid.
   * This parameter is passed to the url for use by the server routine
   * retrieving the data. Note that if you set this
   * parameter to 10 (i.e. retrieve 10 records) and your server
   * return 15 then only 10 records will be loaded
   * @param integer $rowNum
   */
  public function setRowNum($rowNum) {
    $this->rowNum = $rowNum;
  }

  public function getRowTotal() {
    return $this->rowTotal;
  }

  /**
   * When set this parameter can instruct the server to load the total number
   * of rows needed to work on. Note that rowNum determines the total records
   * displayed in the grid, while rowTotal the total rows on which we operate.
   * When this parameter is set we send a additional parameter to server named
   * totalrows. You can check for this parameter and if it is available you can
   * replace the rows parameter with this one. Mostly this parameter can be
   * combined wit loadonce parameter set to true.
   * @param integer $rowTotal
   */
  public function setRowTotal($rowTotal) {
    $this->rowTotal = $rowTotal;
  }

  public function getRowNumWidth() {
    return $this->rownumWidth;
  }

  /**
   * Determines the width of the row number column if rownumbers option is set
   * to true.
   * @param integer $rownumWidth
   */
  public function setRowNumWidth($rownumWidth) {
    $this->rownumWidth = $rownumWidth;
  }

  public function getSavedRow() {
    return $this->savedRow;
  }

  /**
   * This is read only property and is used in Inline and cell editing modules
   * to store the data, before editing the row or cell.
   * @param array() $savedRow
   */
  public function setSavedRow($savedRow) {
    $this->savedRow = $savedRow;
  }

  public function getSearchData() {
    return $this->searchdata;
  }

  /**
   * This property contain the searched data in pair name:value.
   * @deprecated
   * @param array() $searchdata
   */
  public function setSearchData($searchdata) {
    $this->searchdata = $searchdata;
  }

  public function getScroll() {
    return $this->scroll;
  }

  /**
   * Creates dynamic scrolling grids. When enabled, the pager elements are
   * disabled and we can use the vertical scrollbar to load data.
   * When set to true the grid will always hold all the items from the start
   * through to the latest point ever visited.
   * When scroll is set to value (eg 1), the grid will just hold the visible
   * lines. This allow us to load the data at portions whitout to care about
   * the memory leaks. Additionally this we have optional extension to the
   * server protocol: npage (see prmNames array). If you set the npage option
   * in prmNames, then the grid will sometimes request more than one page at a
   * time, if not it will just perform multiple gets.
   * @param boolean/integer $scroll
   */
  public function setScroll($scroll) {
    $this->scroll = $scroll;
  }

  public function getScrollOffset() {
    return $this->scrollOffset;
  }

  /**
   * Determines the width of the vertical scrollbar.
   * Since different browsers interpret this width differently
   * (and it is difficult to calculate it in all browsers) this can be changed.
   * @param integer $scrollOffset
   */
  public function setScrollOffset($scrollOffset) {
    $this->scrollOffset = $scrollOffset;
  }

  public function getScrollTimeout() {
    return $this->scrollTimeout;
  }

  /**
   * This control the timeout handler when scroll is set to 1.
   * @param integer $scrollTimeout integer (milliseconds)
   */
  public function setScrollTimeout($scrollTimeout) {
    $this->scrollTimeout = $scrollTimeout;
  }

  public function getScrollRows() {
    return $this->scrollrows;
  }

  /**
   * When enabled, selecting a row with setSelection scrolls the grid so that
   * the selected row is visible. This is especially useful when we have a 
   * verticall scrolling grid and we use form editing with navigation buttons
   * (next or previous row). On navigating to a hidden row, the grid scrolls so
   * the selected row becomes visible.
   * @param boolean $scrollrows
   */
  public function setScrollRows($scrollrows) {
    $this->scrollrows = $scrollrows;
  }

  public function getSelArrrow() {
    return $this->selarrrow;
  }

  /**
   * This options is read only. Determines the currently selected rows when
   * multiselect is set to true. This is one dimensional array and the values
   * in the array correspond to the selected id's in the grid.
   * @param array() $selarrrow
   */
  public function setSelArrrow($selarrrow) {
    $this->selarrrow = $selarrrow;
  }

  public function getSelRow() {
    return $this->selrow;
  }

  /**
   * This option is read only. Contain the id of the last selected row.
   * If you sort or apply a pagging this options is set to null
   * @param string $selrow
   */
  public function setSelRow($selrow) {
    $this->selrow = $selrow;
  }

  public function getShrinktofit() {
    return $this->shrinkToFit;
  }

  /**
   * This option describes the type of calculation of the initial width of each
   * column against with the width of the grid. If the value is true and the
   * value in width option is set then: Every column width is scaled according
   * to the defined option width. Example: if we define two columns with a width
   * of 80 and 120 pixels, but want the grid to have a 300 pixels - then the
   * columns are recalculated as follow:
   * 1- column = 300(new width)/200(sum of all width)*80(column width) = 120 and
   * 2 column = 300/200*120 = 180. The grid width is 300px. If the value is
   * false and the value in width option is set then: The width of the grid is
   * the width set in option. The column width are not recalculated and have the
   * values defined in colModel.
   * @param boolean $shrinkToFit
   */
  public function setShrinktofit($shrinkToFit) {
    $this->shrinkToFit = $shrinkToFit;
  }

  public function getSortable() {
    return $this->sortable;
  }

  /**
   * When enabled this option allow column reordering with mouse.
   * Since this option uses jQuery UI sortable widget, be a sure that this
   * widget and the related to widget files are loaded in head tag.
   * Also be a sure too that you mark the grid.jqueryui.js when you download
   * the jqGrid.
   * @param boolean $sortable
   */
  public function setSortable($sortable) {
    $this->sortable = $sortable;
  }

  public function getSortname() {
    return $this->sortname;
  }

  /**
   * The initial sorting name when we use datatypes xml or json
   * (data returned from server). This parameter is added to the url.
   * If set and the index (name) match the name from colModel then to this
   * column by default is added a image sorting icon, according to the
   * parameter sortorder (below).
   * @param string $sortname
   */
  public function setSortname($sortname) {
    $this->sortname = $sortname;
  }

  public function getSortOrder() {
    return $this->sortorder;
  }

  /**
   * The initial sorting order when we use datatypes xml or json
   * (data returned from server).This parameter is added to the url.
   * Two possible values - asc or desc.
   * @param string $sortorder
   */
  public function setSortOrder($sortorder) {
    $this->sortorder = $sortorder;
  }

  public function getSubGrid() {
    return $this->subGrid;
  }

  /**
   * If set to true this enables using a subgrid.
   * If the subGrid is enabled a additional column at left side is added to the
   * basic grid. This column contains a 'plus' image which indicate that the
   * user can click on it to expand the row. By default all rows are collapsed
   * @param boolean $subGrid
   */
  public function setSubGrid($subGrid) {
    $this->subGrid = $subGrid;
  }

  public function getSubGridModel() {
    return $this->subGridModel;
  }

  /**
   * This property, which describes the model of the subgrid,
   * has an effect only if the subGrid property is set to true.
   * It is an array in which we describe the column model for the subgrid data.
   * @param array $subGridModel
   */
  public function setSubGridModel($subGridModel) {
    $this->subGridModel = $subGridModel;
  }

  public function getSubGridType() {
    return $this->subGridType;
  }

  /**
   * This option allow loading subgrid as a service.
   * If not set, the datatype parameter of the parent grid is used.
   * @param string/YsJsFunction $subGridType
   */
  public function setSubGridType($subGridType) {
    $this->subGridType = $subGridType;
  }

  public function getSubGridUrl() {
    return $this->subGridUrl;
  }

  /**
   * This option has effect only if subGrid option is set to true.
   * This option points to the file from which we get the data for the subgrid.
   * jqGrid adds the id of the row to this url as parameter.
   * If there is a need to pass additional parameters,
   * use the params option in subGridModel.
   * @param string $subGridUrl
   */
  public function setSubGridUrl($subGridUrl) {
    $this->subGridUrl = $subGridUrl;
  }

  public function getSubGridWidth() {
    return $this->subGridWidth;
  }

  /**
   * Determines the width of the subGrid column if subgrid is enabled.
   * @param integer $subGridWidth
   */
  public function setSubGridWidth($subGridWidth) {
    $this->subGridWidth = $subGridWidth;
  }

  public function getToolbar() {
    return $this->toolbar;
  }

  /**
   * This option defines the toolbar of the grid.
   * This is array with two values in which the first value enables the toolbar
   * and the second defines the position relative to body Layer.
   * Possible values �top�,�bottom�, �both�. When we set toolbar: [true,�both�]
   * two toolbars are created � one on the top of table data and one of the
   * bottom of the table data. When we have two toolbars then we create two
   * elements (div). The id of the top bar is constructed like �t_�+id of the
   * grid and the bottom toolbar the id is �tb_�+id of the grid. In case when
   * only one toolbar is created we have the id as �t_� + id of the grid,
   * independent of where this toolbar is created (top or bottom)
   * @param array $toolbar
   */
  public function setToolbar($toolbar) {
    $this->toolbar = $toolbar;
  }

  public function getTopPager() {
    return $this->toppager;
  }

  /**
   * When enabled this option place a pager element at top of the grid below
   * the caption (if available). If another pager is defined both can coexists
   * and are refreshed in sync. The id of the new created pager is a
   * combination of the gridid+�_toppager�. 
   * @param boolean $toppager
   */
  public function setTopPager($toppager) {
    $this->toppager = $toppager;
  }

  public function getTotaltime() {
    return $this->totaltime;
  }

  /**
   * Readonly parameter.
   * Measure the loading time of the records - currently available only when we
   * load xml or json data. Also the check begin when the request is complete
   * and we begin to insert data into the grid and ends when the last row is
   * added.
   * @param integer $totaltime
   */
  public function setTotaltime($totaltime) {
    $this->totaltime = $totaltime;
  }

  public function getTreeDataType() {
    return $this->treedatatype;
  }

  /**
   * Determines the initial datatype (see datatype option).
   * Usually this should not be changed.
   * During the reading process this option is equal to the datatype option.
   * @param array() $treedatatype
   */
  public function setTreeDataType($treedatatype) {
    $this->treedatatype = $treedatatype;
  }

  public function getTreeGrid() {
    return $this->treeGrid;
  }

  /**
   * Enables (disables) the tree grid format
   * @param boolean $treeGrid
   */
  public function setTreeGrid($treeGrid) {
    $this->treeGrid = $treeGrid;
  }

  public function getTreeGridModel() {
    return $this->treeGridModel;
  }

  /**
   * Deteremines the method used for the treeGrid. Can be nested or adjacency
   * @param string $treeGridModel
   */
  public function setTreeGridModel($treeGridModel) {
    $this->treeGridModel = $treeGridModel;
  }

  public function getTreeIcons() {
    return $this->treeIcons;
  }

  /**
   * This array set the icons used in the tree.
   * The icons should be a valid names from UI theme roller images.
   * The default values are:
   * {plus:'ui-icon-triangle-1-e',
   *  minus:'ui-icon-triangle-1-s',
   *  leaf:'ui-icon-radio-off'}
   * @param array() $treeIcons
   */
  public function setTreeIcons($treeIcons) {
    $this->treeIcons = $treeIcons;
  }

  public function getTreeReader() {
    return $this->treeReader;
  }

  /**
   * Extends the colModel defined in the basic grid.
   * The fields described here are added to end of the colModel array and are
   * hidden. This means that the data returned from the server should have
   * values for these fields
   * @param array() $treeReader
   */
  public function setTreeReader($treeReader) {
    $this->treeReader = $treeReader;
  }

  public function getTreeRootLevel() {
    return $this->tree_root_level;
  }

  /**
   * Determines the level where the root element begins when treeGrid is enabled
   * @param integer $tree_root_level
   */
  public function setTreeRootLevel($tree_root_level) {
    $this->tree_root_level = $tree_root_level;
  }

  public function getUrl() {
    return $this->url;
  }

  /**
   * The url of the file that holds the request
   * @param string $url
   */
  public function setUrl($url) {
    $this->url = $url;
  }

  public function getUserData() {
    return $this->userData;
  }

  /**
   * This array contain custom information from the request.
   * Can be used at any time.
   * @param array() $userData
   */
  public function setUserData($userData) {
    $this->userData = $userData;
  }

  public function getUserDataonFooter() {
    return $this->userDataOnFooter;
  }

  /**
   * When set to true we directly place the user data array userData at footer.
   * The rules are as follow: If the userData array contain name which is equal
   * to those of colModel then the value is placed in that column.
   * If there are no such values nothing is palced. Note that if this option is
   * used we use the current formatter options (if available) for that column
   * @param boolean $userDataOnFooter
   */
  public function setUserSataonFooter($userDataOnFooter) {
    $this->userDataOnFooter = $userDataOnFooter;
  }

  public function getViewRecords() {
    return $this->viewrecords;
  }

  /**
   * If true, jqGrid displays the beginning and ending record number in the grid
   * out of the total number of records in the query.
   * This information is shown in the pager bar (bottom right by default)in
   * this format: �View X to Y out of Z�. If this value is true, there are
   * other parameters that can be adjusted,
   * including 'emptyrecords' and 'recordtext'.
   * @param boolean $viewrecords
   */
  public function setViewRecords($viewrecords) {
    $this->viewrecords = $viewrecords;
  }

  public function getViewSortCols() {
    return $this->viewsortcols;
  }

  /**
   * The purpose of this parameter is to define different look and behavior of
   * sorting icons that appear near the header. This parameter is array with the
   * following default options viewsortcols : [false,'vertical',true]. The first
   * parameter determines if all icons should be viewed at the same time when
   * all columns have sort property set to true. The default of false determines
   * that only the icons of the current sorting column should be viewed.
   * Setting this parameter to true causes all icons in all sortable columns to
   * be viewed. The second parameter determines how icons should be placed -
   * vertical means that the sorting icons are one under another. 'horizontal'
   * means that the icons should be one near other. The third parameter
   * determines the click functionality. If set to true the columns are sorted
   * if the header is clicked. If set to false the columns are sorted only
   * when the icons are clicked. Important note: When set a third parameter to
   * false be a sure that the first parameter is set to true, otherwise
   * you will loose the sorting.
   * @param array() $viewsortcols
   */
  public function setViewSortCols($viewsortcols) {
    $this->viewsortcols = $viewsortcols;
  }

  public function getWidth() {
    return $this->width;
  }

  /**
   * If this option is not set, the width of the grid is a sum of the widths of
   * the columns defined in the colModel (in pixels). If this option is set,
   * the initial width of each column is set according to the value of
   * shrinkToFit option.
   * @param integer $xmlReader
   */
  public function setWidth($width) {
    $this->width = $width;
  }

  public function getXmlReader() {
    return $this->xmlReader;
  }

  /**
   * Array which describes the structure of the expected xml data
   * @param array() $xmlReader
   */
  public function setXmlReader($xmlReader) {
    $this->xmlReader = $xmlReader;
  }

  /* -EVENTS */

  public function getAfterInsertRow() {
    return $this->afterInsertRow;
  }

  public function setAfterInsertRow($afterInsertRow) {
    $this->afterInsertRow = $afterInsertRow;
  }

  public function getBeforeRequest() {
    return $this->beforeRequest;
  }

  public function setBeforeRequest($beforeRequest) {
    $this->beforeRequest = $beforeRequest;
  }

  public function getBeforeSelectRow() {
    return $this->beforeSelectRow;
  }

  public function setBeforeSelectRow($beforeSelectRow) {
    $this->beforeSelectRow = $beforeSelectRow;
  }

  public function getGridComplete() {
    return $this->gridComplete;
  }

  public function setGridComplete($gridComplete) {
    $this->gridComplete = $gridComplete;
  }

  public function getLoadBeforeSend() {
    return $this->loadBeforeSend;
  }

  public function setLoadBeforeSend($loadBeforeSend) {
    $this->loadBeforeSend = $loadBeforeSend;
  }

  public function getLoadComplete() {
    return $this->loadComplete;
  }

  public function setLoadComplete($loadComplete) {
    $this->loadComplete = $loadComplete;
  }

  public function getLoadError() {
    return $this->loadError;
  }

  public function setLoadError($loadError) {
    $this->loadError = $loadError;
  }

  public function getOnCellSelect() {
    return $this->onCellSelect;
  }

  public function setOnCellSelect($onCellSelect) {
    $this->onCellSelect = $onCellSelect;
  }

  public function getOnDblClickRow() {
    return $this->ondblClickRow;
  }

  public function setOnDblClickRow($ondblClickRow) {
    $this->ondblClickRow = $ondblClickRow;
  }

  public function getOnHeaderClick() {
    return $this->onHeaderClick;
  }

  public function setOnHeaderClick($onHeaderClick) {
    $this->onHeaderClick = $onHeaderClick;
  }

  public function getOnPaging() {
    return $this->onPaging;
  }

  public function setOnPaging($onPaging) {
    $this->onPaging = $onPaging;
  }

  public function getOnRightClickRow() {
    return $this->onRightClickRow;
  }

  public function setOnRightClickRow($onRightClickRow) {
    $this->onRightClickRow = $onRightClickRow;
  }

  public function getOnSelectAll() {
    return $this->onSelectAll;
  }

  public function setOnSelectAll($onSelectAll) {
    $this->onSelectAll = $onSelectAll;
  }

  public function getOnSelectRow() {
    return $this->onSelectRow;
  }

  public function setOnSelectRow($onSelectRow) {
    $this->onSelectRow = $onSelectRow;
  }

  public function getOnSortCol() {
    return $this->onSortCol;
  }

  public function setOnSortCol($onSortCol) {
    $this->onSortCol = $onSortCol;
  }

  public function getResizeStart() {
    return $this->resizeStart;
  }

  public function setResizeStart($resizeStart) {
    $this->resizeStart = $resizeStart;
  }

  public function getResizeStop() {
    return $this->resizeStop;
  }

  public function setResizeStop($resizeStop) {
    $this->resizeStop = $resizeStop;
  }

  public function getSerializeGridData() {
    return $this->serializeGridData;
  }

  public function setSerializeGridData($serializeGridData) {
    $this->serializeGridData = $serializeGridData;
  }

  public function getSubGridBeforeExpand() {
    return $this->subGridBeforeExpand;
  }

  public function setSubGridBeforeExpand($subGridBeforeExpand) {
    $this->subGridBeforeExpand = $subGridBeforeExpand;
  }

  public function getSubGridRowExpanded() {
    return $this->subGridRowExpanded;
  }

  public function setSubGridRowExpanded($subGridRowExpanded) {
    $this->subGridRowExpanded = $subGridRowExpanded;
  }

  public function getSubGridRowColapsed() {
    return $this->subGridRowColapsed;
  }

  public function setSubGridRowColapsed($subGridRowColapsed) {
    $this->subGridRowColapsed = $subGridRowColapsed;
  }

  public function getSerializeSubGridData() {
    return $this->serializeSubGridData;
  }

  public function setSerializeSubGridData($serializeSubGridData) {
    $this->serializeSubGridData = $serializeSubGridData;
  }

  /* -------------------------------------------------------------------------- */


  /* -GRID FIELDS- */

  public function getGridFields() {
    return $this->gridFields;
  }

  public function setGridFields(ArrayObject $gridFields) {
    $this->gridFields = $gridFields;
  }

  public function addGridField(YsGridField $gridFields) {
    $this->gridFields->append($gridFields);
  }

  public function addGridFields() {
    $args = func_get_args();
    foreach ($args as $gridField) {
      if ($gridField instanceof YsGridField) {
        $this->gridFields->append($gridField);
      } else {
        $this->removeGridfields();
        throw new YsException(sprintf(YsException::$INSTANCE_OF, __FUNCTION__, "YsGridField"));
      }
    }
  }

  public function removeGridFields() {
    $this->gridFields = new ArrayObject();
  }

  public function removeGridField($index) {
    $this->gridFields->offsetExists($index);
  }

  /* RENDERER */

  public function isShowFooter() {
    return $this->showFooter;
  }

  public function setShowFooter($showFooter) {
    $this->showFooter = $showFooter;
  }

  public function getGridId() {
    return $this->gridId;
  }

  public function setGridId($gridId) {
    $this->gridId = $gridId;
  }

  public function getGridHtmlProperties() {
    return $this->gridHtmlProperties;
  }

  public function setGridHtmlProperties($gridHtmlProperties) {
    $this->gridHtmlProperties = $gridHtmlProperties;
  }

  public function getFooterHtmlProperties() {
    return $this->footerHtmlProperties;
  }

  public function setFooterHtmlProperties($footerHtmlProperties) {
    $this->footerHtmlProperties = $footerHtmlProperties;
  }

  public function getRecordList() {
    return $this->recordList;
  }

  public function setRecordList(ArrayObject $recordList) {
    $this->recordList = $recordList;
  }

  public function addRecord(YsGridRecord $gridRecord) {
    $this->recordList->append($gridRecord);
  }

  public function getJQgridOptions() {
    return $this->jqgridOptions;
  }

  public function setJQgridOptions($jqgridOptions) {
    $this->jqgridOptions = $jqgridOptions;
  }

  public function getYsJQGrid() {
    return $this->ysJQGrid;
  }

  public function setYsJQGrid(YsJQGrid $ysJQGrid) {
    $this->ysJQGrid = $ysJQGrid;
  }

  public function getJQuerySelector() {
    $template = $this->jquerySelector;
    if (!isset($this->jquerySelector) || $this->jquerySelector === null) {
      $template = sprintf('%s%s', '#', $this->getGridId());
    }
    return $template;
  }

  public function setJQuerySelector($jquerySelector) {
    $this->jquerySelector = $jquerySelector;
  }

  public function getGridTSelector() {
    return sprintf('#t_%s', $this->getGridId());
  }

  public function draw() {
    return sprintf('%s%s', $this->renderTemplate(), $this->execute());
  }

  public function execute() {
    return YsJQuery::newInstance()->execute($this->build());
  }

  public function build() {
    $this->buildCustomOptions();
    $this->buildColModel();
    $this->buildLocalData();
    $this->buildPager();
    $jqgridOptions = array();
    foreach ($this->varsToArray() as $key => $value) {
      $jqgridOptions[$key] = $value;
    }
    $this->setJQgridOptions($jqgridOptions);
    $ysJQGrid = YsJQGrid::build($jqgridOptions)->in($this->getJQuerySelector());
    $this->setYsJQGrid($ysJQGrid);
    $this->buildPreSintax();
    $this->buildPostSintax();
    return $this->getYsJQGrid();
  }

  public function buildCustomOptions() {
    if (isset($this->editOnSelectRow) || $this->editOnSelectRow !== null) {
      if ($this->editOnSelectRow) {
        $this->setOnSelectRow($this->editOnSelectRowFunction());
      }
    }
  }

  public function renew($gridId, $caption = null) {
    $this->recordList = new ArrayObject();
    $this->setGridId($gridId);
    if ($caption !== null) {
      $this->setCaption($caption);
    }
    $this->removeGridfields();
  }

  public function renderTemplate() {
    return sprintf('%s%s', $this->getGridtemplate(), $this->getFootertemplate());
  }

  /* TEMPLATES */

  public function getGridtemplate() {
    $template = $this->gridTemplate;
    if (!isset($this->gridTemplate) || $this->gridTemplate === null) {
      $template = $this->getDefaultGridTemplate();
    }
    return $template;
  }

  public function setGridtemplate($gridTemplate) {
    $this->gridTemplate = $gridTemplate;
  }

  public function getFootertemplate() {
    $template = $this->footerTemplate;
    if (!isset($this->footerTemplate) || $this->footerTemplate === null) {
      $template = $this->getDefaultFooterTemplate();
    }
    return $template;
  }

  public function setFootertemplate($footerTemplate) {
    $this->footerTemplate = $footerTemplate;
  }

  private function getDefaultGridTemplate() {
    return sprintf('<table id="%s" %s></table>', $this->getGridId(), $this->getGridHtmlProperties());
  }

  private function getDefaultFooterTemplate() {
    if (!isset($this->pager) || $this->pager === null) {
      $template = sprintf('<div id="%s" %s></div>', $this->getPagerId(), $this->getFooterHtmlProperties());
    } else {
      $template = sprintf('<div id="%s" %s></div>', $this->getPagerId(), $this->getFooterHtmlProperties());
    }
    return $template;
  }

  public function __toString() {
    return $this->draw();
  }

  /* UTILITY */

  public function noPager() {
    $this->setPager('');
  }

  public function getPagerId() {
    return (!isset($this->pager) || $this->pager === null) ? sprintf('%s%s', 'p', $this->getGridId()) : str_replace(array('#', '.'), array('', ''), $this->pager);
  }

  public function getPagerSelector() {
    return (!isset($this->pager) || $this->pager === null) ? sprintf('%s%s%s', '#', $this->getPagerId()) : $this->getPager();
  }

  public function getFilterToolbar() {
    return $this->filterToolbar;
  }

  public function setFilterToolbar(YsGridFilterToolbar $filterToolbar) {
    $this->filterToolbar = $filterToolbar;
  }

  public function getNavigator() {
    return $this->navigator;
  }

  public function setNavigator(YsGridNavigator $navigator) {
    $this->navigator = $navigator;
  }

  public function showFields($fields) {
    $args = func_get_args();
    $cols = (is_string($fields)) ? $fields : $this->hideOrShowFields($args);
    return $this->invoke('showCol', $cols);
  }

  public function hideFields($fields) {
    $args = func_get_args();
    $cols = (is_string($fields)) ? $fields : $this->hideOrShowFields($args);
    return $this->invoke('hideCol', $cols);
  }

  /* PRIVATE */

  private function hideOrShowFields($fields) {
    $cols = '';
    if (sizeof($fields) <= 1) {
      if ($fields[0] instanceof YsGridField) {
        $cols = $fields[0]->getIndex();
      }
    } else {
      $i = 0;
      foreach ($fields as $field) {
        if ($field instanceof YsGridField) {
          $cols[$i++] = $field->getIndex();
        }
      }
    }
    return $cols;
  }

  private function varsToArray() {
    $config = array();
    $vars = get_class_vars(__CLASS__);
    foreach ($this->varsToUnset() as $value) {
      unset($vars[$value]);
    }
    foreach ($vars as $var => $value) {
      if (isset($this->$var)) {
        $config[$var] = $this->$var;
      }
    }
    return $config;
  }

  private function buildLocalData() {
    $records = $this->recordList->getArrayCopy();
    if (sizeof($records) > 0) {
      $gridData = array();
      $i = 0;
      foreach ($records as $record) {
        $data = array();
        $attributes = $record->getAttributes();
        foreach ($attributes as $key => $value) {
          $data[$key] = $value;
        }
        if (sizeof($data) > 0) {
          $gridData[$i++] = $data;
        }
      }
      $this->setData($gridData);
    }
  }

  private function buildColModel() {
    $fields = $this->getGridFields();
    if (sizeof($fields) > 0) {
      $colNames = array();
      $colModel = array();
      $i = 0;
      foreach ($fields as $field) {
        $colNames[$i] = $field->getColName();
        $colModel[$i++] = $field->render();
      }
      $this->setColNames($colNames);
      $this->setColModel($colModel);
    }
  }

  private function buildPager() {
    if (!isset($this->pager) || $this->pager === null) {
      $this->setPager(sprintf('#p%s', $this->getGridId()));
    }
  }

  private function buildPostSintax() {
    if (isset($this->filterToolbar) || $this->filterToolbar !== null) {
      $this->getYsJQGrid()->addPostSintax($this->filterToolbar->render($this->getJQuerySelector(), true));
    }
    if (isset($this->navigator) || $this->navigator !== null) {
      $this->getYsJQGrid()->addPostSintax($this->navigator->render($this->getJQuerySelector(), $this->getPager()));
    }
    $this->getYsJQGrid()->addPostSintax($this->getPostSintaxAsArray());
  }

  private function buildPreSintax() {
    if (isset($this->editOnSelectRow) || $this->editOnSelectRow !== null) {
      if ($this->editOnSelectRow) {
        $rowId = sprintf('lastRowIn', $this->getGridId());
        $this->getYsJQGrid()->addPreSintax(sprintf('var %s;', $rowId));
      }
    }
  }

  public function searchExpandedContentIn($url) {
    $this->setSubGridRowExpanded($this->subGridRowExpandedFunction($url));
  }

  private function subGridRowExpandedFunction($url) {
    $bodySyntax = <<<EOH
    var data = {subgrid:subgridid, rowid:id};
      if('false' == 'true') {
        var anm= '';
        anm = anm.split(",");
        var rd = jQuery(this).jqGrid('getRowData', id);
        if(rd) {
          for(var i=0; i<anm.length; i++) {
            if(rd[anm[i]]) {
              data[anm[i]] = rd[anm[i]];
            }
          }
        }
      }
      $("#"+jQuery.jgrid.jqID(subgridid)).load('$url',data);
EOH;
    $function = new YsJsFunction();
    $function->setBody($bodySyntax);
    $function->setArguments('subgridid,id');
    return $function;
  }

  private function editOnSelectRowFunction() {
    $rowId = sprintf('lastRowIn', $this->getGridId());
    $gridId = $this->getJQuerySelector();
    $pickdatesFunction = '';
    if (isset($this->calendarSupportIn) || $this->calendarSupportIn !== null) {
      $pickdatesFunction = YsJsFunction::JAVASCRIPT_ARGUMENT_SEPARATOR . $this->pickdatesFunction();
    }
    $bodySyntax = <<<EOH
    if(id && id!==$rowId){
      jQuery('$gridId').jqGrid('restoreRow',$rowId);
      jQuery('$gridId').jqGrid('editRow',id,true$pickdatesFunction);
      $rowId=id;
    }
EOH;
    $function = new YsJsFunction();
    $function->setBody($bodySyntax);
    $function->setArguments('id');
    return $function;
  }

  private function pickdatesFunction(){
    $body = '';
    $calendarOptions = (isset($this->calendarDefaultOptions) && is_array($this->calendarDefaultOptions))
                     ? json_encode($this->calendarDefaultOptions)
                     : '';
    if(is_array($this->calendarSupportIn)){
      foreach($this->calendarSupportIn as $calendars){
        $body .= sprintf('jQuery("#"+id+"_%s","%s").datepicker(%s);',$calendars,$this->getJQuerySelector(),$calendarOptions);
      }
    }else{
      $body = sprintf('jQuery("#"+id+"_%s","%s").datepicker(%s);',$this->calendarSupportIn,$this->getJQuerySelector(),$calendarOptions);
    }
    return new YsJsFunction($body, 'id');
  }

  public function appendInToolbar($content, $jQuerySelector = null) {
    $jQuerySelector = ($jQuerySelector !== null) ? $jQuerySelector : $this->getGridTSelector();
    $this->addPostSintax(YsJQuery::append()->in($jQuerySelector)->content($content));
  }

  public function showInUserData($userData, $align, $userDataVarName = 'userdata') {
    $sintax = new YsJQueryDynamic(
                    YsJQuery::css("text-align", $align)->in($this->getGridTSelector())->setPreSintax(sprintf('var %s = $("%s").jqGrid("getUserData")', $userDataVarName, $this->getJQuerySelector())),
                    YsJQuery::html(YsArgument::value($userData), $align)
    );
    $this->setLoadComplete(new YsJsFunction(YsArgument::value($sintax->getJQueryObject()->getInternalSintax())));
  }

  public function addPostSintax($sintax) {
    $this->postSintax->append($sintax);
  }

  public function renewPostSintax() {
    $this->postSintax = new ArrayObject();
  }

  public function getPostSintax() {
    return $this->postSintax;
  }

  public function invoke($args) {
    return YsJQGrid::buildMethodForGrid(func_get_args())->in($this->getJQuerySelector());
  }

  public function getPostSintaxAsArray() {
    return $this->postSintax->getArrayCopy();
  }

  public function setEditOnSelectRow($editOnSelectRow) {
    $this->editOnSelectRow = $editOnSelectRow;
  }

  public function getEditOnSelectRow() {
    return $this->editOnSelectRow;
  }

  public function setCalendarSupportIn($ids) {
    $this->calendarSupportIn = $ids;
  }
  
  public function getCalendarSupportIn() {
    return $this->calendarSupportIn;
  }

  public function getCalendarDefaultOptions() {
    return $this->calendarDefaultOptions;
  }

  public function setCalendarDefaultOptions($calendarDefaultOptions) {
    $this->calendarDefaultOptions = $calendarDefaultOptions;
  }


  public function editSelectedRow($validateSelection = false, $message = null) {
    $id = $this->getSelectedRowId();
    if ($validateSelection) {
      if ($message === null) {
        $jquery = $this->invoke('editRow', $id)->condition($this->invoke('getGridParam', 'selrow'));
      } else {
        $jquery = $this->invoke('editRow', $id)->condition($this->invoke('getGridParam', 'selrow'), sprintf('alert("%s")', $message));
      }
    } else {
      $jquery = $this->invoke('editRow', $id);
    }
    return $jquery;
  }

  public function cancelEditSelectedRow() {
    $id = $this->getSelectedRowId();
    return $this->invoke('restoreRow', $id);
  }

  public function saveSelectedRow() {
    $id = $this->getSelectedRowId();
    return $this->invoke('saveRow', $id);
  }

  public function editRowById($id) {
    return $this->invoke('editRow', $id);
  }

  public function cancelEditRowById($id) {
    return $this->invoke('restoreRow', $id);
  }

  public function saveRowById($id) {
    return $this->invoke('saveRow', $id);
  }

  public function getSelectedRowId() {
    return $this->invoke('getGridParam', 'selrow');
  }

}