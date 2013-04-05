<?php

$agent_alpha = $class_application::getAlphaAgent();

$class_dumper = $class_application::getDumperClass();

echo 'date: '.date('Y-m-d_h:i:s')."\n";

$particle = $agent_alpha::spawnParticle();

$class_dumper::log(
	__METHOD__,
	array( $particle ),
	TRUE
);

echo "\n";