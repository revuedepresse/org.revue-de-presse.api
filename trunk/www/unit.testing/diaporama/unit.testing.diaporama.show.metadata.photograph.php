<?php

// send headers
header('Content-Type: '.MIME_TYPE_TEXT_HTML.'; charset='.I18N_CHARSET_UTF8);

$photographs = Diaporama::loadPhotosByAuthorId( 1 );

fprint( $photographs[38], $verbose_mode );