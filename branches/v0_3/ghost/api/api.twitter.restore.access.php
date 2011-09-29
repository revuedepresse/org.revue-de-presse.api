<?php

$class_api = $class_application::getApiClass();

// Restore accessing privileges by retrieving existing access tokens
$class_api::unserializeAccessTokens();
