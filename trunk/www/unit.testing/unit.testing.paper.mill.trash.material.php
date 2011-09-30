<?php

$class_dumper = $class_application::getDumperClass();

$class_paper_maker = $class_application::getPaperMakerClass();

$class_dumper::log(
	__METHOD__,
	array(
		'$class_paper_maker::trashMaterial( STORE_MEMENTO );',
		$class_paper_maker::trashMaterial( STORE_MEMENTO ),
		'isset( $_SESSION[STORE_PAPER][STORE_MEMENTO] )',
		isset( $_SESSION[STORE_PAPER][STORE_MEMENTO] )
	),
	$verbose_mode
);