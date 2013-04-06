<?php

require_once(dirname(__FILE__).'/../includes/sefi.globals.php');

$resource = dirname(__FILE__).'/../'.CHARACTER_SLASH.DIR_CONFIGURATION.CHARACTER_SLASH.'form.sign.up.yaml';

$firephp = FirePHP::getInstance(true);
$firephp->log(yaml::deserialize($resource));