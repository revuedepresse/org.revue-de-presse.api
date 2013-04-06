<?php

$template = new Smarty_SEFI();

$template->assign("title", $lang['title']['form_upload_photos']);        
$template->assign("legend", $lang['legend']['form_upload_photos']);
$template->assign("fields", $lang['photo_fields']);

// send headers
header(
	'Content-Type: '.
		MIME_TYPE_TEXT_HTML.
			'; charset='.I18N_CHARSET_UTF8
);

$template->display( dirname(__FILE__) . '/../../templates/' .  TPL_CAROUSEL_STATIC );

$template->clear_all();