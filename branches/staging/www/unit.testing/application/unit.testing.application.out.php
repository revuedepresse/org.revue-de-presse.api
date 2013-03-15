<?php

$class_dumper = $class_application::getDumperClass();

$command_line = '$du -sk ## FILE ABSOLUTE PATH ##/web/## FILL PROJECT DIR ##/branches/v0.1/sql';

$class_dumper::log(
	__METHOD__,
	array( $class_application::out( $command_line ) ),
	$verbose_mode
);		
