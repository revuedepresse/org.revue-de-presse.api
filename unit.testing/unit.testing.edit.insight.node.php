<?php

$context = unserialize('O:8:"stdClass":7:{s:2:"id";s:2:"26";s:4:"type";s:1:"1";s:6:"folder";s:2:"16";s:6:"entity";s:2:"35";s:12:"content_type";N;s:10:"affordance";s:17:"edit-insight-node";s:10:"identifier";s:1:"4";}');

$class_dumper = $class_application::getDumperClass();

$class_insight_node = $class_application::getInsightNodeClass();

$class_dumper::log(
	__METHOD__,
	array($class_insight_node::displayEditionForm($context)),
	$verbose_mode
);