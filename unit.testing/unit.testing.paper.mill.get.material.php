<?php

$class_dumper = $class_application::getDumperClass();

$class_paper_maker = $class_application::getPaperMakerClass();

$class_dumper::log(
	__METHOD__,
	array(
		'$class_paper_maker::getMaterial( STORE_MEMENTO );',
		$class_paper_maker::getMaterial( STORE_MEMENTO ),
		'$_SESSION[STORE_PAPER]',
		$_SESSION[STORE_PAPER]
	),
	$verbose_mode
);