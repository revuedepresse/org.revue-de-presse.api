<?php

/**
*
* Access token needs to be requested prior to data retrieval
*
* http://## FILL HOSTNAME ##/api/api.twitter.request.access.token.php
*
*/

$class_dumper = $class_application::getDumperClass();

$class_api = $class_application::getApiClass();

// verify credentials validity
$class_api::verifyCredentials();