<?php

require_once(dirname(__FILE__).'/../includes/sefi.globals.php');

fprint($_SESSION);

$firephp = FirePHP::getInstance(true);
$firephp->log($_SESSION);