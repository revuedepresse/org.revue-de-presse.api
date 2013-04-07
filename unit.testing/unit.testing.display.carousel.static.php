<?php
$template = new Smarty_SEFI();

$template->assign("title", $lang['title']['form_upload_photos']);        

$template->assign("legend", $lang['legend']['form_upload_photos']);

$template->assign("fields", $lang['photo_fields']);

$template->display(dirname(__FILE__)."/templates/".TPL_CAROUSEL_STATIC);

$template->clear_all_cache();
?>