<?php

$class_api = $class_application::getApiClass();

$class_dumper = $class_application::getDumperClass();

// forget possible existing token already signed by Twitter
$class_api::forgetAccessToken();