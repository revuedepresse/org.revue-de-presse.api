<?php

$resource =
	dirname( __FILE__ ) .
	'/../../' .
	CHARACTER_SLASH.DIR_CONFIGURATION.CHARACTER_SLASH.'form.sign.up.yaml'
;

$firephp = FirePHP::getInstance( TRUE );
$firephp->log( yaml::deserialize( $resource ) );