<?php

global $class_application;

$class_api_facebook = $class_application::getFacebookClass( NAMESPACE_API );

$class_api_facebook::getNewsfeed();