<?php

require_once(dirname(__FILE__).'/../includes/sefi.globals.php');

fprint( $_SESSION, $verbose_mode );

$firephp = FirePHP::getInstance( TRUE );
$firephp->log( $_SESSION );