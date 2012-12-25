<?php

if ( isset( $class_application ) ) $class_application::route();
else error_log('bootstrap script not loaded from "' . __FILE__ . '"');
