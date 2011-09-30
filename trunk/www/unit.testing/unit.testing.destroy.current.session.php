<?php

$class_field_handler = $class_application::getFieldHandlerClass();

echo $class_field_handler::destroy_session();

echo session_id();