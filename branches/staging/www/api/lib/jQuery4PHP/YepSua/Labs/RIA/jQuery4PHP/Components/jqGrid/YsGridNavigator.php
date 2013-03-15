<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * The Navigator is a user interface feature that allows easy accessibility to
 * record actions such as Find or Edit. The user can activate a grid action by
 * pressing the appropriate icon button in the Navigation layer.
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: private $Idprivate $
 */
class YsGridNavigator {

  private $add;
  private $addicon;
  private $addtext;
  private $alertcap;
  private $addtitle;
  private $alerttext;
  private $cloneToTop;
  private $closeOnEscape;
  private $del;
  private $delicon;
  private $deltext;
  private $deltitle;
  private $edit;
  private $editicon;
  private $edittext;
  private $edittitle;
  private $position;
  private $refresh;
  private $refreshicon;
  private $refreshtext;
  private $refreshtitle;
  private $refreshstate;
  private $afterRefresh;
  private $beforeRefresh;
  private $search;
  private $searchicon;
  private $searchtext;
  private $searchtitle;
  private $view;
  private $viewicon;
  private $viewtext;
  private $viewtitle;
  private $addfunc;
  private $editfunc;
  private $delfunc;

  private $editForm;
  private $addForm;
  private $deleteForm;
  private $searchForm;
  private $customButtons;
  private $searchOptions;

  public function  __construct() {
    $this->renewCustomButtons();
  }

  private function varsToUnset(){
    return array( 'editForm','addForm','deleteForm','searchForm',
                  'customButtons', 'searchOptions');
  }

  public function getAdd() {
    return $this->add;
  }

  /**
   * Enables or disables the add action in the navigator.
   * When the button is clicked a editGridRow with parameter new method is
   * executed
   * @param boolean $add
   */
  public function setAdd($add) {
    $this->add = $add;
  }

  public function getAddIcon() {
    return $this->addicon;
  }

  /**
   * Set a icon to be displayed if the add action is enabled.
   * Note that currently only icons from UI theme images can be added
   * @param string $addicon
   */
  public function setAddIcon($addicon) {
    $this->addicon = $addicon;
  }

  public function getAddText() {
    return $this->addtext;
  }

  /**
   * The text than can be set in the add button
   * @param string $addtext
   */
  public function setAddText($addtext) {
    $this->addtext = $addtext;
  }

  public function getAlertCap() {
    return $this->alertcap;
  }

  /**
   * The header of the message that appear when we try to edit,
   * delete or view a row without to select it
   * @param string $alertcap
   */
  public function setAlertCap($alertcap) {
    $this->alertcap = $alertcap;
  }

  public function getAddTitle() {
    return $this->addtitle;
  }

  /**
   * The title that appear when we mouse over to the add button (if enabled)
   * @param string $addtitle
   */
  public function setAddTitle($addtitle) {
    $this->addtitle = $addtitle;
  }

  public function getAlertText() {
    return $this->alerttext;
  }

  /**
   * The message text that appear when we try to edit,
   * delete or view a row without to select it
   * @param boolean $alerttext
   */
  public function setAlertText($alerttext) {
    $this->alerttext = $alerttext;
  }

  public function getCloneToTop() {
    return $this->cloneToTop;
  }

  /**
   * Clones all the actions from the bottom pager to the top pager if defined.
   * Note that the navGrid can be applied to the top pager only.
   * The id of the top pager is a combination of grid id and “_toppager”
   * @param boolean $cloneToTop
   */
  public function setCloneToTop($cloneToTop) {
    $this->cloneToTop = $cloneToTop;
  }

  public function getCloseOnEscape() {
    return $this->closeOnEscape;
  }

  /**
   * Determine if the alert dialog can be closed if the user pres ESC key
   * @param boolean $closeOnEscape
   */
  public function setCloseOnEscape($closeOnEscape) {
    $this->closeOnEscape = $closeOnEscape;
  }

  public function getDel() {
    return $this->del;
  }

  /**
   * Enables or disables the delete action in the navigator.
   * When the button is clicked a delGridRow method is executed.
   * @param boolean $del
   */
  public function setDel($del) {
    $this->del = $del;
  }

  public function getDelIcon() {
    return $this->delicon;
  }

  /**
   * Set a icon to be displayed if the delete action is enabled.
   * Note that currently only icons from UI theme images can be used
   * @param string $delicon
   */
  public function setDelIcon($delicon) {
    $this->delicon = $delicon;
  }

  public function getDelText() {
    return $this->deltext;
  }

  /**
   * The text than can be set in the delete button
   * @param string $deltext
   */
  public function setDelText($deltext) {
    $this->deltext = $deltext;
  }

  public function getDelTitle() {
    return $this->deltitle;
  }

  /**
   * The text than can be set in the delete button
   * @param string $deltitle
   */
  public function setDelTitle($deltitle) {
    $this->deltitle = $deltitle;
  }

  public function getEdit() {
    return $this->edit;
  }

  /**
   * The title that appear when we mouse over to the delete button (if enabled)
   * @param boolean $edit
   */
  public function setEdit($edit) {
    $this->edit = $edit;
  }

  public function getEditIcon() {
    return $this->editicon;
  }

  /**
   * Enables or disables the edit action in the navigator.
   * When the button is clicked a editGridRow method is executed with parameter
   * the - current selected row
   * @param string $editicon
   */
  public function setEditIcon($editicon) {
    $this->editicon = $editicon;
  }

  public function getEditText() {
    return $this->edittext;
  }

  /**
   * The text than can be set in the edit button
   * @param string $edittext
   */
  public function setEditText($edittext) {
    $this->edittext = $edittext;
  }

  public function getEditTitle() {
    return $this->edittitle;
  }

  /**
   * The title that appear when we mouse over to the edit button (if enabled)
   * @param string $edittitle
   */
  public function setEditTitle($edittitle) {
    $this->edittitle = $edittitle;
  }

  public function getPosition() {
    return $this->position;
  }

  /**
   * Determines the position of the navigator buttons in the pager.
   * Can be left, center and right.
   * @param string $position
   */
  public function setPosition($position) {
    $this->position = $position;
  }

  public function getRefresh() {
    return $this->refresh;
  }

  /**
   * Enables or disables the refresh button in the pager.
   * When the button is clicked a trigger(“reloadGrid”) is executed and the
   * search parameters are cleared
   * @param boolean $refresh
   */
  public function setRefresh($refresh) {
    $this->refresh = $refresh;
  }

  public function getRefreshIcon() {
    return $this->refreshicon;
  }

  /**
   * Set a icon to be displayed if the refresh action is enabled.
   * Note that currently only icons from UI theme images can be used
   * @param string $refreshicon
   */
  public function setRefreshIcon($refreshicon) {
    $this->refreshicon = $refreshicon;
  }

  public function getRefreshText() {
    return $this->refreshtext;
  }

  /**
   * The text than can be set in the refresh button
   * @param string $refreshtext
   */
  public function setRefreshText($refreshtext) {
    $this->refreshtext = $refreshtext;
  }

  public function getRefreshTitle() {
    return $this->refreshtitle;
  }

  /**
   * The title that appear when we mouse over to the refresh button (if enabled)
   * @param string $refreshtitle
   */
  public function setRefreshTitle($refreshtitle) {
    $this->refreshtitle = $refreshtitle;
  }

  public function getRefreshState() {
    return $this->refreshstate;
  }

  /**
   * Determines how the grid should be reloaded
   * <b>firstpage</b>: the grid reload the data from the first page.
   * <b>current</b>: the reloading should save the current page and current
   * selection
   * @param <type> $refreshstate 
   */
  public function setRefreshState($refreshstate) {
    $this->refreshstate = $refreshstate;
  }

  public function getAfterRefresh() {
    return $this->afterRefresh;
  }

  /**
   * If defined this event fire after the refresh button is clicked
   * @param YsJsFunction $afterRefresh
   */
  public function setAfterRefresh($afterRefresh) {
    $this->afterRefresh = $afterRefresh;
  }

  public function getBeforeRefresh() {
    return $this->beforeRefresh;
  }

  /**
   * If defined this event fire before the refresh button is clicked
   * @param YsJsFunction $beforeRefresh
   */
  public function setBeforeRefresh($beforeRefresh) {
    $this->beforeRefresh = $beforeRefresh;
  }

  public function getSearch() {
    return $this->search;
  }

  /**
   * Enables or disables the search button in the pager.
   * When the button is clicked a searchGrid method is executed
   * @param boolean $search
   */
  public function setSearch($search) {
    if($search instanceof YsGridSearch){
      $this->search = true;
      $this->setSearchOptions($search);
    }else{
      $this->search = $search;
    }
  }

  public function getSearchIcon() {
    return $this->searchicon;
  }

  /**
   * Set a icon to be displayed if the search action is enabled.
   * Note that currently only icons from UI theme images can be used
   * @param string $searchicon
   */
  public function setSearchIcon($searchicon) {
    $this->searchicon = $searchicon;
  }

  public function getSearchText() {
    return $this->searchtext;
  }

  /**
   * The text than can be set in the search button
   * @param string $searchtext
   */
  public function setSearchText($searchtext) {
    $this->searchtext = $searchtext;
  }

  public function getSearchTitle() {
    return $this->searchtitle;
  }

  /**
   * The title that appear when we mouse over to the search button (if enabled)
   * @param string $searchtitle
   */
  public function setSearchTitle($searchtitle) {
    $this->searchtitle = $searchtitle;
  }

  public function getView() {
    return $this->view;
  }

  /**
   * Enables or disables the view button in the pager.
   * When the button is clicked a viewGridRow method is executed
   * @param boolean $view
   */
  public function setView($view) {
    $this->view = $view;
  }

  public function getViewIcon() {
    return $this->viewicon;
  }

  /**
   * Set a icon to be displayed if the search action is enabled.
   * Note that currently only icons from UI theme images can be used
   * @param string $viewicon
   */
  public function setViewIcon($viewicon) {
    $this->viewicon = $viewicon;
  }

  public function getViewText() {
    return $this->viewtext;
  }

  /**
   * The text that can be set in the view button
   * @param string $viewtext
   */
  public function setViewText($viewtext) {
    $this->viewtext = $viewtext;
  }

  public function getViewTitle() {
    return $this->viewtitle;
  }

  /**
   * The title that appear when we mouse over to the view button (if enabled)
   * @param string $viewtitle
   */
  public function setViewTitle($viewtitle) {
    $this->viewtitle = $viewtitle;
  }

  public function getAddFunc() {
    return $this->addfunc;
  }

  /**
   * If defined replaces the build in add function.
   * No parameters are passed to this function
   * @param YsJsFunction $addfunc
   */
  public function setAddFunc($addfunc) {
    $this->addfunc = $addfunc;
  }

  public function getEditFunc() {
    return $this->editfunc;
  }

  /**
   * If defined replaces the build in edit function.
   * Parameter passed to this function is the id of the edited row
   * @param YsJsFunction $editfunc
   */
  public function setEditFunc($editfunc) {
    $this->editfunc = $editfunc;
  }

  public function getDelFunc() {
    return $this->delfunc;
  }

  /**
   * If defined replaces the build in del function.
   * Parameter passed to this function is the id of the edited row
   * @param YsJsFunction $delfunc
   */
  public function setDelFunc($delfunc) {
    $this->delfunc = $delfunc;
  }

  /* UTILITY */

  public function getEditForm() {
    return $this->editForm;
  }

  public function setEditForm(YsGridForm $editForm) {
    $this->setEdit(true);
    $this->editForm = $editForm;
  }

  public function getAddForm() {
    return $this->addForm;
  }

  public function setAddForm(YsGridForm $addForm) {
    $this->setAdd(true);
    $this->addForm = $addForm;
  }

  public function getDeleteForm() {
    return $this->deleteForm;
  }

  public function setDeleteForm(YsGridForm $deleteForm) {
    $this->setDel(true);
    $this->deleteForm = $deleteForm;
  }

  public function getSearchForm() {
    return $this->searchForm;
  }

  public function setSearchForm(YsGridForm $searchForm) {
    $this->setSearch(true);
    $this->searchForm = $searchForm;
  }

  public function noDefaultButtons(){
    $this->add = false;
    $this->del = false;
    $this->edit = false;
    $this->refresh = false;
    $this->search = false;
  }

  public function getCustomButtons(){
    return $this->customButtons;
  }

  public function setCustomButtons(ArrayObject $customButtons){
    $this->customButtons = $customButtons;
  }
  
  public function renewCustomButtons(){
    $this->customButtons = new ArrayObject();
  }

  public function addCustomButton(YsGridCustomButton $customButtom){
    $this->customButtons->append($customButtom);
  }

  public function getSearchOptions() {
    return $this->searchOptions;
  }

  public function setSearchOptions($searchOptions) {
    $this->searchOptions = $searchOptions;
  }

  public function render($jQuerySelector, $pager){
    $render = YsJQGrid::navGrid($pager,$this->optionsToArray());
    if(isset($this->editForm) && $this->editForm !== null){
      $render->addArgument(new YsArgument($this->editForm->optionsToArray()));
    }else{
      $render->addArgument(new YsArgument('{}',false));
    }
    if(isset($this->addForm) && $this->addForm !== null){
      $render->addArgument(new YsArgument($this->addForm->optionsToArray()));
    }else{
      $render->addArgument(new YsArgument('{}',false));
    }
    if(isset($this->deleteForm) && $this->deleteForm !== null){
      $render->addArgument(new YsArgument($this->deleteForm->optionsToArray()));
    }else{
      $render->addArgument(new YsArgument('{}',false));
    }
    if(isset($this->searchForm) && $this->searchForm !== null){
      $render->addArgument(new YsArgument($this->searchForm->optionsToArray()));
    }else{
      $render->addArgument(new YsArgument('{}',false));
    }
    $render->in($jQuerySelector);
    $customButtons = $this->buildCustomButtons($jQuerySelector, $pager);
    if($customButtons !== null){
      $render->setPostSintax($customButtons);
    }
    return $render;
  }

  private function buildCustomButtons($jQuerySelector, $pager){
    $sintax = null;
    $jqueryDynamic = new YsJQueryDynamic();
    $buttons = new ArrayObject();
    foreach ($this->getCustomButtons()->getArrayCopy() as $buttom){
      $buttons->append($buttom->render($jQuerySelector, $pager));
    }
    return ($buttons->count() > 0) ? $jqueryDynamic->build($buttons->getArrayCopy()): null;
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
  
}