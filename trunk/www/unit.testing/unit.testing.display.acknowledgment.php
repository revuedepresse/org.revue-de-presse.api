<?php

$class_dumper = $class_application::getDumperClass();

$class_smarty_sefi = $class_application::getSmartySefiClass();

$class_dumper::log(
	__METHOD__,
	array('http://## FILL HOSTNAME ##/dialog.acknowledgment.php?acknowledgment=photos.saved'),
	$verbose_mode
);

$class_smarty_sefi::displayAcknowledgment();
?>