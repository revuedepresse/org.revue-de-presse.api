<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsGridField todo description
 *
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsGridField extends ArrayObject implements ArrayAccess{
  
  private $align;
  private $classes;
  private $datefmt;
  private $defval;
  private $editable;
  private $editoptions;
  private $editrules;
  private $edittype;
  private $firstsortorder;
  private $fixed;
  private $formoptions;
  private $formatoptions;
  private $formatter;
  private $hidedlg;
  private $hidden;
  private $index;
  private $jsonmap;
  private $key;
  private $label;
  private $name;
  private $resizable;
  private $search;
  private $searchoptions;
  private $sortable;
  private $stype;
  private $surl;
  private $title;
  private $width;
  private $xmlmap;
  private $unformat;
  private $viewable;
  private $colname;
  private $sorttype;
  private $summaryType;
  private $summaryTpl;

  private $gridFormatter;

  public function __construct($id, $colName) {
    $this->index = $id;
    $this->name = $id;
    $this->colname = $colName;
  }
  
  private function varsToUnset(){
    return array( 'colname','gridFormatter');
  }

  public function getAlign() {
    return $this->align;
  }

  /**
   * Defines the alignment of the cell in the Body layer, not in header cell.
   * Possible values: left, center, right.
   * @param string $align
   */
  public function setAlign($align) {
    $this->align = $align;
  }

  public function getClasses() {
    return $this->classes;
  }

  /**
   * This option allow to add classes to the column.
   * If more than one class will be used a space should be set.
   * By example classes:'class1 class2' will set a class1 and class2
   * to every cell on that column. In the grid css there is a predefined
   * class ui-ellipsis which allow to attach ellipsis to a particular row.
   * Also this will work in FireFox too.
   * @param string $classes
   */
  public function setClasses($classes) {
    $this->classes = $classes;
  }

  public function getDateFmt() {
    return $this->datefmt;
  }

  /**
   * Governs format of sorttype:date (when datetype is set to local) and
   * editrules {date:true} fields. Determines the expected date format for
   * that column. Uses a PHP-like date formatting. Currently ”/”, ”-”, and ”.”
   * are supported as date separators. Valid formats are: y,Y,yyyy for four
   * digits year YY, yy for two digits year m,mm for months d,dd for days.
   * @param string $datefmt
   */
  public function setDateFmt($datefmt) {
    $this->datefmt = $datefmt;
  }

  public function getDefVal() {
    return $this->defval;
  }

  /**
   * The default value for the search field.
   * This option is used only in Custom Searching and
   * will be set as initial search.
   * @param string $defVal
   */
  public function setDefVal($defVal) {
    $this->defval = $defVal;
  }

  public function getEditable() {
    return $this->editable;
  }

  /**
   * Defines if the field is editable.
   * This option is used in cell, inline and form modules. See  editing.
   * @param boolean $editable
   */
  public function setEditable($editable) {
    $this->editable = $editable;
  }

  public function getEditOptions() {
    return $this->editoptions;
  }

  /**
   * Array of allowed options (attributes) for edittype option  editing.
   * @param array() $editOptions
   */
  public function setEditOptions($editOptions) {
    $this->editoptions = $editOptions;
  }

  public function getEditRules() {
    return $this->editrules;
  }

  /**
   * Sets additional rules for the editable field  editing.
   * @param array() $editRules
   */
  public function setEditRules($editRules) {
    $this->editrules = $editRules;
  }

  public function getEditType() {
    return $this->edittype;
  }

  /**
   * Defines the edit type for inline and form editing Possible
   * values: text, textarea, select, checkbox, password, button, image and file.
   * @param string $editType
   */
  public function setEditType($editType) {
    $this->edittype = $editType;
  }

  public function getFirstSortOrder() {
    return $this->firstsortorder;
  }

  /**
   * If set to asc or desc, the column will be sorted in that direction on
   * first sort.Subsequent sorts of the column will toggle as usual.
   * @param string $firstSortOrder
   */
  public function setFirstSortOrder($firstSortOrder) {
    $this->firstsortorder = $firstSortOrder;
  }

  public function getFixed() {
    return $this->fixed;
  }

  /**
   * If set to true this option does not allow recalculation of the width of the
   * column if shrinkToFit option is set to true. Also the width does not change
   * if a setGridWidth method is used to change the grid width.
   * @param boolean $fixed
   */
  public function setFixed($fixed) {
    $this->fixed = $fixed;
  }

  public function getFormOptions() {
    return $this->formoptions;
  }

  /**
   * Defines various options for form editing
   * @param array() $formOptions.
   */
  public function setFormOptions($formOptions) {
    $this->formoptions = $formOptions;
  }

  public function getFormatOptions() {
    return $this->formatoptions;
  }

  /**
   * Format options can be defined for particular columns, overwriting
   * the defaults from the language file.
   * @param array() $formatOptions
   */
  public function setFormatOptions($formatOptions) {
    $this->formatoptions = $formatOptions;
  }

  public function getFormatter() {
    return $this->formatter;
  }

  /**
   * The predefined types (string) or custom function name that controls the
   * format of this field. See Formatter for more details.
   * @param string $formatter
   */
  public function setFormatter($formatter) {
    $this->formatter = $formatter;
  }

  public function getHideDlg() {
    return $this->hidedlg;
  }

  /**
   * If set to true this column will not appear in the modal dialog where users
   * can choose which columns to show or hide.
   * @param boolean $hidedlg
   */
  public function setHideDlg($hidedlg) {
    $this->hidedlg = $hidedlg;
  }

  public function getHidden() {
    return $this->hidden;
  }

  /**
   * Defines if this column is hidden at initialization.
   * @param boolean $hidden
   */
  public function setHidden($hidden) {
    $this->hidden = $hidden;
  }

  public function getIndex() {
    return $this->index;
  }

  /**
   * Set the index name when sorting
   * @param string $index
   */
  public function setIndex($index) {
    $this->index = $index;
  }

  public function getJSONMap() {
    return $this->jsonmap;
  }

  /**
   * Defines the json mapping for the column in the incoming json string
   * @param string $jsonMap
   */
  public function setJSONMap($jsonMap) {
    $this->jsonmap = $jsonMap;
  }

  public function getKey() {
    return $this->key;
  }

  /**
   * In case if there is no id from server, this can be set as as id for the
   * unique row id. Only one column can have this property.
   * If there are more than one key the grid finds the first one and the second
   * is ignored
   * @param boolean $key
   */
  public function setKey($key) {
    $this->key = $key;
  }

  public function getLabel() {
    return $this->label;
  }

  /**
   * When colNames array is empty, defines the heading for this column.
   * If both the colNames array and this setting are empty, the heading for
   * this column comes from the name property.
   * @param string $label
   */
  public function setLabel($label) {
    $this->label = $label;
  }

  public function getName() {
    return $this->name;
  }

  /**
   * Set the unique name in the grid for the column. This property is required.
   * As well as other words used as property/event names, the reserved words
   * (which cannot be used for names) include subgrid, cb and rn.
   * @param string $name
   */
  public function setName($name) {
    $this->name = $name;
  }


  public function getResizable() {
    return $this->resizable;
  }

  /**
   * Defines if the column can be re sized.
   * @param boolean $resizable
   */
  public function setResizable($resizable) {
    $this->resizable = $resizable;
  }

  public function getSearch() {
    return $this->search;
  }

  /**
   * When used in search modules, disables or enables searching on that column.
   * @param boolean $search
   */
  public function setSearch($search) {
    $this->search = $search;
  }

  public function getSearchOptions() {
    return $this->searchoptions;
  }

  /**
   * Defines the search options used searching
   * @param array() $searchOptions
   */
  public function setSearchOptions($searchOptions) {
    $this->searchoptions = $searchOptions;
  }

  public function getSortable() {
    return $this->sortable;
  }

  /**
   * Defines is this can be sorted.
   * @param boolean $sortable
   */
  public function setSortable($sortable) {
    $this->sortable = $sortable;
  }

  public function getSortType() {
      return $this->sorttype;
  }

  /**
   * Used when datatype is local. Defines the type of the column for appropriate sorting.
   * Possible values:
   * <br/><b>int/integer</b> - for sorting integer
   * <br/><b>float/number/currency</b> - for sorting decimal numbers
   * <br/><b>date</b> - for sorting date
   * <br/><b>text</b> - for text sorting
   * <br/><b>function</b> - defines a custom function for sorting.
   * To this function we pass the value to be sorted and it should return a
   * value too.
   * @param string/YsJsFunction $stype
   */
  public function setSortType($sorttype) {
      $this->sorttype = $sorttype;
  }

  public function getSummaryType() {
    return $this->summaryType;
  }

  /**
   * The option determines what type of calculation we should do with the
   * current group value applied to column. Currently we support the following
   * build in functions:
   * sum => apply the sum function to the current group value and return the
   * result
   * count => apply the count function to the current group value and return
   * the result
   * avg => apply the average function to the current group value and return
   * the result
   * min => apply the min function to the current group value and return
   * the result
   * max => apply the max function to the current group value and return
   * the result
   * @param string $summaryType
   */
  public function setSummaryType($summaryType) {
    $this->summaryType = $summaryType;
  }

  public function getSummaryTemplate() {
    return $this->summaryTpl;
  }

  /**
   * This option acts as template which can be used in the summary footer row.
   * By default its value is defined as {0} - which means that this will print
   * the summary value. The parameter can contain any valid HTML code.
   * @param string $summaryTpl
   */
  public function setSummaryTemplate($summaryTpl) {
    $this->summaryTpl = $summaryTpl;
  }
  
  public function getSType() {
    return $this->stype;
  }

  /**
   * Determines the type of the element when searching.
   * @param string $stype
   */
  public function setSType($stype) {
    $this->stype = $stype;
  }


  public function getSUrl() {
    return $this->surl;
  }

  /**
   * Valid only in Custom Searching and edittype : 'select' and describes the
   * url from where we can get already-constructed select element
   * @param string $surl
   */
  public function setSUrl($surl) {
    $this->surl = $surl;
  }

  public function getTitle() {
    return $this->title;
  }

  /**
   * If this option is false the title is not displayed in that column when we
   * hover a cell with the mouse
   * @param boolean $title
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  public function getWidth() {
    return $this->width;
  }

  /**
   * Set the initial width of the column, in pixels.
   * This value currently can not be set as percentage
   * @param integer $width
   */
  public function setWidth($width) {
    $this->width = $width;
  }

  public function getXmlMap() {
      return $this->xmlmap;
  }

  /**
   * Defines the xml mapping for the column in the incomming xml file.
   * @param string $xmlmap
   */
  public function setXmlMap($xmlmap) {
      $this->xmlmap = $xmlmap;
  }

  public function getUnformat() {
    return $this->unformat;
  }

  /**
   * Custom function to “unformat” a value of the cell when used in editing
   * @param YsJsFunction $unformat
   */
  public function setUnformat($unformat) {
    $this->unformat = $unformat;
  }

  public function getViewable() {
    return $this->viewable;
  }

  /**
   * This option is valid only when viewGridRow method is activated
   * @param boolean $viewable
   */
  public function setViewable($viewable) {
    $this->viewable = $viewable;
  }

  public function getColName() {
    return $this->colname;
  }

  public function setColName($colName) {
    $this->colname = $colName;
  }

  /* UTILITY */

  public function getGridFormatter() {
    return $this->gridFormatter;
  }

  public function setGridFormatter(YsGridFormatter $gridFormatter) {
    $this->gridFormatter = $gridFormatter;
  }

  private function optionsToArray(){
    $config = array();
    $vars = get_class_vars(__CLASS__);
    foreach($this->varsToUnset() as $value){
      unset($vars[$value]);
    }
    foreach($vars as $var => $value){
      if(isset($this->$var)){
        $config[$var] = $this->$var;
      }
    }
    return $config;
  }

  public function render(){
    if(isset($this->gridFormatter) && $this->gridFormatter !== null){
      $formatter = $this->gridFormatter;
      if($formatter->getType() !== null){
        $this->setFormatter($formatter->getType());
      }
      if($formatter->getFormatterOptions() !== null && sizeof($formatter->getFormatterOptions() > 0)){
        $this->setFormatOptions($formatter->getFormatterOptions());
      }
    }
    return $this->optionsToArray();
  }
}