<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsGridForm todo description
 *
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: private private $Idprivate private $
 */
class YsGridForm {

  private $top;
  private $left;
  private $width;
  private $height;
  private $dataheight;
  private $modal;
  private $drag;
  private $resize;
  private $url;
  private $mtype;
  private $editData;
  private $recreateForm;
  private $jqModal;
  private $addedrow;
  private $topinfo;
  private $bottominfo;
  private $saveicon;
  private $closeicon;
  private $savekey;
  private $navkeys;
  private $checkOnSubmit;
  private $checkOnUpdate;
  private $closeAfterAdd;
  private $clearAfterAdd;
  private $closeAfterEdit;
  private $reloadAfterSubmit;
  private $closeOnEscape;
  private $ajaxEditOptions;
  private $viewPagerButtons;
  private $labelswidth;
  private $delData;
  private $delicon;
  private $cancelicon;
  private $ajaxDelOptions;
  private $afterclickPgButtons;
  private $afterComplete;
  private $afterShowForm;
  private $afterSubmit;
  private $beforeCheckValues;
  private $beforeInitData;
  private $beforeShowForm;
  private $beforeSubmit;
  private $onclickPgButtons;
  private $onclickSubmit;
  private $onInitializeForm;
  private $onClose;
  private $errorTextFormat;
  private $serializeEditData;
  private $afterShowSearch;
  private $beforeShowSearch;
  private $caption;
  private $closeAfterSearch;
  private $closeAfterReset;
  private $Find;
  private $groupOps;
  private $matchText;
  private $multipleSearch;
  private $cloneSearchRowOnAdd;
  private $odata;
  private $onInitializeSearch;
  private $recreateFilter;
  private $Reset;
  private $rulesText;
  private $sField;
  private $sFilter;
  private $sOper;
  private $sopt;
  private $sValue;
  private $overlay;
  private $dataUrl;
  private $buildSelect;
  private $dataInit;
  private $dataEvents;
  private $attr;
  private $searchhidden;
  private $defaultValue;
  private $value;


  public function getTop() {
    return $this->top;
  }

  /**
   * The initial top position of modal dialog.
   * The default value of 0 mean the top position from the upper left corner
   * of the grid. When jqModal option is true (see below) and jqModal plugin
   * is present any value different from 0 mean the top position from upper
   * left corner of the window.
   * @param integer $top
   */
  public function setTop($top) {
    $this->top = $top;
  }

  public function getLeft() {
    return $this->left;
  }

  /**
   * the initial left position of modal dialog.
   * The default value of 0 mean the left position from the upper left corner
   * of the grid. When jqModal option is true (see below) and jqModal plugin
   * is present any value different from 0 mean the left position from upper
   * left corner of the window.
   * @param integer $left
   */
  public function setLeft($left) {
    $this->left = $left;
  }

  public function getWidth() {
    return $this->width;
  }

  /**
   * The width of confirmation dialog
   * @param integer $width
   */
  public function setWidth($width) {
    $this->width = $width;
  }

  public function getHeight() {
    return $this->height;
  }

  /**
   * The entry height of confirmation dialog
   * @param integer $height
   */
  public function setHeight($height) {
    $this->height = $height;
  }

  public function getDataHeight() {
    return $this->dataheight;
  }

  /**
   * The parameter control the scrolling content - i.e between the modal header
   * and modal footer.
   * @param integer $dataheight
   */
  public function setDataHeight($dataheight) {
    $this->dataheight = $dataheight;
  }

  public function getModal() {
    return $this->modal;
  }

  /**
   * Determines if the dialog will be modal. Also works only if jqModal plugin
   * is present
   * @param boolean $modal
   */
  public function setModal($modal) {
    $this->modal = $modal;
  }

  public function getDrag() {
    return $this->drag;
  }

  /**
   * Determines if the dialog is dragabale. Works only if jqDnR plugin is
   * present or if the dragable widget is present from jQuery UI
   * @param boolean $drag
   */
  public function setDrag($drag) {
    $this->drag = $drag;
  }

  public function getResize() {
    return $this->resize;
  }

  /**
   * Determines if the dialog can be resized. Works only is jqDnR plugin is
   * available or resizable widget is present from jQuery UI
   * @param boolean $resize
   */
  public function setResize($resize) {
    $this->resize = $resize;
  }

  public function getUrl() {
    return $this->url;
  }

  /**
   * Url where to post data. If set, replaces the editurl
   * @param string $url
   */
  public function setUrl($url) {
    $this->url = $url;
  }

  public function getMType() {
    return $this->mtype;
  }

  /**
   * Defines the type of request to make (“POST” or “GET”)
   * when data is sent to the server
   * @param string $mtype
   */
  public function setMType($mtype) {
    $this->mtype = $mtype;
  }

  public function getEditData() {
    return $this->editData;
  }

  /**
   * An array used to add content to the data posted to the server
   * @param array() $editData
   */
  public function setEditData($editData) {
    $this->editData = $editData;
  }

  public function getRecreateForm() {
    return $this->recreateForm;
  }

  /**
   * when set to true the form is recreated every time the dialog is activeted
   * with the new options from colModel (if they are changed)
   * @param boolean $recreateForm
   */
  public function setRecreateForm($recreateForm) {
    $this->recreateForm = $recreateForm;
  }

  public function getJqModal() {
    return $this->jqModal;
  }

  /**
   * If set to true uses jqModal plugin (if present) to creat the dialogs.
   * If set to true and the plugin is not present jqGrid uses its internal
   * function to create dialog
   * @param boolean $jqModal
   */
  public function setJqModal($jqModal) {
    $this->jqModal = $jqModal;
  }

  public function getAddedRow() {
    return $this->addedrow;
  }

  /**
   * Controls where the row just added is placed:
   * 'first' at the top of the gird, 'last' at the bottom.
   * here the new row is to appear in its natural sort order,
   * set reloadAfterSubmit: true
   * @param string $addedrow
   */
  public function setAddedRow($addedrow) {
    $this->addedrow = $addedrow;
  }

  public function getTopInfo() {
    return $this->topinfo;
  }

  /**
   * When set this information is placed just after the modal header as additional row
   * @param string $topinfo
   */
  public function setTopInfo($topinfo) {
    $this->topinfo = $topinfo;
  }

  public function getBottomInfo() {
    return $this->bottominfo;
  }

  /**
   * When set this information is placed just after the buttons of the form
   * as additional row
   * @param string $bottominfo
   */
  public function setBottomInfo($bottominfo) {
    $this->bottominfo = $bottominfo;
  }

  public function getSaveIcon() {
    return $this->saveicon;
  }

  /**
   * Determines the icon of the submit button.
   * The default value is [true,”left”,”ui-icon-disk”].
   * The first item enables/disables the icon
   * The second item tells where to put the icon to left or to right of the text
   * The third item corresponds to valid ui icon from theme roller.
   * @param array() $saveicon
   */
  public function setSaveIcon($saveicon) {
    $this->saveicon = $saveicon;
  }

  public function getCloseIcon() {
    return $this->closeicon;
  }

  /**
   * Determines the icon of the cancel button.
   * The default values are [true,”left”,”ui-icon-close”].
   * @param array() $closeicon
   */
  public function setCloseIcon($closeicon) {
    $this->closeicon = $closeicon;
  }

  public function getSaveKey() {
    return $this->savekey;
  }

  /**
   * Determines the possibility to save the form with pressing a certain key.
   * The first item enables/disables saving with pressing certain key.
   * The second item corresponds to key code for saving.
   * If enabled the default value for saving is [Enter].
   * Note that this binding should be used for both adding and editing a row.
   * Since the binding is for the form, there is no possibility to have one key
   * in add and another in edit mode.
   * @param array() $savekey
   */
  public function setSaveKey($savekey) {
    $this->savekey = $savekey;
  }

  public function getNavKeys() {
    return $this->navkeys;
  }

  /**
   * This option works only in edit mode and add keyboard navigation,
   * which allow us to navigate through the records while in form editing
   * pressing certain keys. The default state is disabled.
   * The first item enables/disables the navigation.
   * The second item corresponds to reccord up and by default is the the key
   * code for Up key.
   * The third item corresponds to reccord down and by default is the key code
   * for Down key
   * @param array() $navkeys
   */
  public function setNavKeys($navkeys) {
    $this->navkeys = $navkeys;
  }

  public function getCheckOnSubmit() {
    return $this->checkOnSubmit;
  }

  /**
   * This option work only in editing mode.
   * If Set to true this option will work only when a submit button is clicked
   * and if any data is changed in the form.
   * If the data is changed a dilog message appear where the user is asked to
   * confirm the changes or cancel it. Pressing cancel button of the new dialog
   * will return to the form, but does not set the values to its original state.
   * @param boolean $checkOnSubmit
   */
  public function setCheckOnSubmit($checkOnSubmit) {
    $this->checkOnSubmit = $checkOnSubmit;
  }

  public function getCheckOnUpdate() {
    return $this->checkOnUpdate;
  }

  /**
   * This option is applicable in add and edit mode.
   * When this option is set to true the behaviour as follow:when something is
   * changed in the form and the user click on Cancel button, navigator buttons,
   * close button (on upper right corner of the form), in overlay (if available)
   * or press Esc key (if set) a message box appear asking the user to save the
   * changes, not to save the changes or go back in the grid cancel all changes
   * (this will close the modal form)
   * @param boolean $checkOnUpdate
   */
  public function setCheckOnUpdate($checkOnUpdate) {
    $this->checkOnUpdate = $checkOnUpdate;
  }

  public function getCloseAfterAdd() {
    return $this->closeAfterAdd;
  }

  /**
   * When add mode, close the dialog after add record
   * @param boolean $closeAfterAdd
   */
  public function setCloseAfterAdd($closeAfterAdd) {
    $this->closeAfterAdd = $closeAfterAdd;
  }

  public function getClearAfterAdd() {
    return $this->clearAfterAdd;
  }

  /**
   * When add mode, clear the data after adding data
   * @param boolean $clearAfterAdd
   */
  public function setClearAfterAdd($clearAfterAdd) {
    $this->clearAfterAdd = $clearAfterAdd;
  }

  public function getCloseAfterEdit() {
    return $this->closeAfterEdit;
  }

  /**
   * When in edit mode, close the dialog after editing
   * @param boolean $closeAfterEdit
   */
  public function setCloseAfterEdit($closeAfterEdit) {
    $this->closeAfterEdit = $closeAfterEdit;
  }

  public function getReloadAfterSubmit() {
    return $this->reloadAfterSubmit;
  }

  /**
   * Reload grid data after posting
   * @param boolean $reloadAfterSubmit
   */
  public function setReloadAfterSubmit($reloadAfterSubmit) {
    $this->reloadAfterSubmit = $reloadAfterSubmit;
  }

  public function getCloseOnEscape() {
    return $this->closeOnEscape;
  }

  /**
   * When set to true the modal window can be closed with ESC key from the user.
   * @param boolean $closeOnEscape
   */
  public function setCloseOnEscape($closeOnEscape) {
    $this->closeOnEscape = $closeOnEscape;
  }

  public function getAjaxEditOptions() {
    return $this->ajaxEditOptions;
  }

  /**
   * This option allow to set global ajax settings for the form editiing when
   * we save the data to the server. Note that with this option is possible to
   * overwrite all current ajax setting in the save request including the
   * complete event.
   * @param array() $ajaxEditOptions
   */
  public function setAjaxEditOptions($ajaxEditOptions) {
    $this->ajaxEditOptions = $ajaxEditOptions;
  }

  public function getViewPagerButtons() {
    return $this->viewPagerButtons;
  }

  /**
   * This option enable or disable the appearing of the previous and next
   * buttons (pager buttons) in the form
   * @param boolean $viewPagerButtons
   */
  public function setViewPagerButtons($viewPagerButtons) {
    $this->viewPagerButtons = $viewPagerButtons;
  }

  public function getLabelsWidth() {
      return $this->labelswidth;
  }

  /**
   * Since we construct the view with table element it is difficult to
   * calculate, in this case, how much width is needed for the labels.
   * Depending on the needs this value can be increased or decreased
   * @param string $labelswidth
   */
  public function setLabelsWidth($labelswidth) {
      $this->labelswidth = $labelswidth;
  }

  public function getDelData() {
      return $this->delData;
  }

  /**
   * An array used to add content to the data posted to the server
   * @param array() $delData
   */
  public function setDelData($delData) {
      $this->delData = $delData;
  }

  public function getDelIcon() {
      return $this->delicon;
  }

  /**
   * Determines the icon of the submit button.
   * The default value is [true,”left”,”ui-icon-delete”].
   * The first item enables/disables the icon.
   * The second item tells where to put the icon to left or to right of the
   * text.
   * The third item corresponds to valid ui icon from theme roller
   * @param array() $delicon
   */
  public function setDelIcon($delicon) {
      $this->delicon = $delicon;
  }

  public function getCancelIcon() {
      return $this->cancelicon;
  }

  /**
   * Determines the icon of the cancel button.
   * The default values are [true,”left”,”ui-icon-cancel”].
   * For description of these see delicon
   * @param array() $cancelicon
   */
  public function setCancelIcon($cancelicon) {
      $this->cancelicon = $cancelicon;
  }

  public function getAjaxDelOptions() {
      return $this->ajaxDelOptions;
  }

  /**
   * This option allow to set global ajax settings for the form editiing when
   * we delete the data to the server. Note that with this option
   * is possible to overwrite all current ajax setting in the save request
   * including the complete event.
   * @param array() $ajaxDelOptions
   */
  public function setAjaxDelOptions($ajaxDelOptions) {
      $this->ajaxDelOptions = $ajaxDelOptions;
  }

  public function getAfterClickPgButtons() {
      return $this->afterclickPgButtons;
  }

  /**
   * This event can be used only when we are in edit mode and the navigator
   * buttons are enabled; it fires after the data for the new row is loaded
   * from the grid, allowing modification of the data or form before the form
   * is redisplayed.
   * @param YsJsFunction $afterclickPgButtons
   */
  public function setAfterClickPgButtons($afterclickPgButtons) {
      $this->afterclickPgButtons = $afterclickPgButtons;
  }

  public function getAfterComplete() {
      return $this->afterComplete;
  }

  /**
   * This event fires immediately after all actions and events are completed
   * and the row is inserted or updated in the grid.
   * @param YsJsFunction $afterComplete
   */
  public function setAfterComplete($afterComplete) {
      $this->afterComplete = $afterComplete;
  }

  public function getAfterShowForm() {
      return $this->afterShowForm;
  }

  /**
   * Fires after showing the form.
   * @param YsJsFunction $afterShowForm
   */
  public function setAfterShowForm($afterShowForm) {
      $this->afterShowForm = $afterShowForm;
  }

  public function getAfterSubmit() {
      return $this->afterSubmit;
  }

  /**
   * Fires after response has been received from server
   * @param YsJsFunction $afterSubmit
   */
  public function setAfterSubmit($afterSubmit) {
      $this->afterSubmit = $afterSubmit;
  }

  public function getBeforeCheckValues() {
      return $this->beforeCheckValues;
  }

  /**
   * This event fires before checking the values
   * (if checking is defined in colModel via editrules option).
   * @param YsJsFunction $beforeCheckValues
   */
  public function setBeforeCheckValues($beforeCheckValues) {
      $this->beforeCheckValues = $beforeCheckValues;
  }

  public function getBeforeInitData() {
      return $this->beforeInitData;
  }

  /**
   * Fires before initialize the new form data.
   * @param YsJsFunction $beforeInitData
   */
  public function setBeforeInitData($beforeInitData) {
      $this->beforeInitData = $beforeInitData;
  }

  public function getBeforeShowForm() {
      return $this->beforeShowForm;
  }

  /**
   * Fires before showing the form with the new data
   * @param YsJsFunction $beforeShowForm
   */
  public function setBeforeShowForm($beforeShowForm) {
      $this->beforeShowForm = $beforeShowForm;
  }

  public function getBeforeSubmit() {
      return $this->beforeSubmit;
  }

  /**
   * Fires before the data is submitted to the server
   * @param YsJsFunction $beforeSubmit
   */
  public function setBeforeSubmit($beforeSubmit) {
      $this->beforeSubmit = $beforeSubmit;
  }

  public function getOnClickPgButtons() {
      return $this->onclickPgButtons;
  }

  /**
   * This event can be used only when we are in edit mode; it fires immediately
   * after the previous or next button is clicked, before leaving the current
   * row, allowing working with (e.g., saving) the currently loaded values in
   * the form.
   * @param YsJsFunction $onclickPgButtons
   */
  public function setOnClickPgButtons($onclickPgButtons) {
      $this->onclickPgButtons = $onclickPgButtons;
  }

  public function getOnClickSubmit() {
      return $this->onclickSubmit;
  }

  /**
   * Fires after the submit button is clicked and the postdata is constructed
   * @param YsJsFunction $onclickSubmit
   */
  public function setOnClickSubmit($onclickSubmit) {
      $this->onclickSubmit = $onclickSubmit;
  }

  public function getOnInitializeForm() {
      return $this->onInitializeForm;
  }

  /**
   * Fires only once when creating the data for editing and adding
   * @param YsJsFunction $onInitializeForm
   */
  public function setOnInitializeForm($onInitializeForm) {
      $this->onInitializeForm = $onInitializeForm;
  }

  public function getOnClose() {
      return $this->onClose;
  }

  /**
   * This event is called just before closing the form and when a close icon
   * is clicked, a cancel button is clicked, ESC key is pressed or click on
   * overlay (if jqModal is present)
   * @param YsJsFunction $onClose
   */
  public function setOnClose($onClose) {
      $this->onClose = $onClose;
  }

  public function getErrorTextFormat() {
      return $this->errorTextFormat;
  }

  /**
   * The event (can) fire when error occurs from the ajax call and can be
   * used for better formatting of the error messages
   * @param YsJsFunction $errorTextFormat
   */
  public function setErrorTextFormat($errorTextFormat) {
      $this->errorTextFormat = $errorTextFormat;
  }

  public function getSerializeEditData() {
      return $this->serializeEditData;
  }

  /**
   * If set this event can serialize the data passed to the ajax request
   * when we save a form data. The function should return the serialized data.
   * @param YsJsFunction $serializeEditData
   */
  public function setSerializeEditData($serializeEditData) {
      $this->serializeEditData = $serializeEditData;
  }

  public function getAfterShowSearch() {
    return $this->afterShowSearch;
  }

  public function setAfterShowSearch($afterShowSearch) {
    $this->afterShowSearch = $afterShowSearch;
  }

  public function getBeforeShowSearch() {
    return $this->beforeShowSearch;
  }

  public function setBeforeShowSearch($beforeShowSearch) {
    $this->beforeShowSearch = $beforeShowSearch;
  }

  public function getCaption() {
    return $this->caption;
  }

  public function setCaption($caption) {
    $this->caption = $caption;
  }

  public function getCloseAfterSearch() {
    return $this->closeAfterSearch;
  }

  public function setCloseAfterSearch($closeAfterSearch) {
    $this->closeAfterSearch = $closeAfterSearch;
  }

  public function getCloseAfterReset() {
    return $this->closeAfterReset;
  }

  public function setCloseAfterReset($closeAfterReset) {
    $this->closeAfterReset = $closeAfterReset;
  }

  public function getFind() {
    return $this->Find;
  }

  public function setFind($Find) {
    $this->Find = $Find;
  }

  public function getGroupOps() {
    return $this->groupOps;
  }

  public function setGroupOps($groupOps) {
    $this->groupOps = $groupOps;
  }

  public function getMatchText() {
    return $this->matchText;
  }

  public function setMatchText($matchText) {
    $this->matchText = $matchText;
  }

  public function getMultipleSearch() {
    return $this->multipleSearch;
  }

  public function setMultipleSearch($multipleSearch) {
    $this->multipleSearch = $multipleSearch;
  }

  public function getCloneSearchRowOnAdd() {
    return $this->cloneSearchRowOnAdd;
  }

  public function setCloneSearchRowOnAdd($cloneSearchRowOnAdd) {
    $this->cloneSearchRowOnAdd = $cloneSearchRowOnAdd;
  }

  public function getOdata() {
    return $this->odata;
  }

  public function setOdata($odata) {
    $this->odata = $odata;
  }

  public function getOnInitializeSearch() {
    return $this->onInitializeSearch;
  }

  public function setOnInitializeSearch($onInitializeSearch) {
    $this->onInitializeSearch = $onInitializeSearch;
  }

  public function getRecreateFilter() {
    return $this->recreateFilter;
  }

  public function setRecreateFilter($recreateFilter) {
    $this->recreateFilter = $recreateFilter;
  }

  public function getReset() {
    return $this->Reset;
  }

  public function setReset($Reset) {
    $this->Reset = $Reset;
  }

  public function getRulesText() {
    return $this->rulesText;
  }

  public function setRulesText($rulesText) {
    $this->rulesText = $rulesText;
  }

  public function getSearchField() {
    return $this->sField;
  }

  public function setSearchField($sField) {
    $this->sField = $sField;
  }

  public function getSearchFilter() {
    return $this->sFilter;
  }

  public function setSearchFilter($sFilter) {
    $this->sFilter = $sFilter;
  }

  public function getSearchOper() {
    return $this->sOper;
  }

  public function setSearchOper($sOper) {
    $this->sOper = $sOper;
  }

  public function getSearchOpt() {
    return $this->sopt;
  }

  public function setSearchOptions($sopt) {
    $this->sopt = $sopt;
  }

  public function getSearchValue() {
    return $this->sValue;
  }

  public function setSearchValue($sValue) {
    $this->sValue = $sValue;
  }

  public function getOverlay() {
    return $this->overlay;
  }

  public function setOverlay($overlay) {
    $this->overlay = $overlay;
  }

  public function getDataUrl() {
    return $this->dataUrl;
  }

  public function setDataUrl($dataUrl) {
    $this->dataUrl = $dataUrl;
  }

  public function getBuildSelect() {
    return $this->buildSelect;
  }

  public function setBuildSelect($buildSelect) {
    $this->buildSelect = $buildSelect;
  }

  public function getDataInit() {
    return $this->dataInit;
  }

  public function setDataInit($dataInit) {
    $this->dataInit = $dataInit;
  }

  public function getDataEvents() {
    return $this->dataEvents;
  }

  public function setDataEvents($dataEvents) {
    $this->dataEvents = $dataEvents;
  }

  public function getAttr() {
    return $this->attr;
  }

  public function setAttr($attr) {
    $this->attr = $attr;
  }

  public function getSearchHidden() {
    return $this->searchhidden;
  }

  public function setSearchHidden($searchhidden) {
    $this->searchhidden = $searchhidden;
  }

  public function getDefaultValue() {
    return $this->defaultValue;
  }

  public function setDefaultValue($defaultValue) {
    $this->defaultValue = $defaultValue;
  }

  public function getValue() {
    return $this->value;
  }

  public function setValue($value) {
    $this->value = $value;
  }


  public function optionsToArray(){
    $config = array();
    $vars = get_class_vars(__CLASS__);
    foreach($vars as $var => $value){
      if(isset($this->$var)){
        $config[$var] = $this->$var;
      }
    }
    return $config;
  }

  public function render(){
    return $this->optionsToArray();
  }

}