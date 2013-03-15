<?php

$class_dumper = $class_application::getDumperClass();

$class_memento = $class_application::getMementoClass();

$data = 'memcached token';

$class_dumper::log(
	__METHOD__,
	array(
		'[data]',
		$data,
		'[data added to memcached server successfully]',
			$class_memento::storeData( $data )
		?
			'TRUE'
		:
			'FALSE'
	),
	$verbose_mode
);


$class_dumper::log(
	__METHOD__,
	array(
		'[data]',
		$data,
		'[data replaced into memcached server successfully for provided key]',
			$class_memento::storeData( $data, NULL, TRUE )
		?
			'TRUE'
		:
			'FALSE'
	),
	$verbose_mode
);

$class_dumper::log(
	__METHOD__,
	array(
		'[data retrieved from memcached server]',
		$class_memento::retrieveData( sha1( serialize( $data ) ) )
	),
	$verbose_mode
);


$class_dumper::log(
	__METHOD__,
	array(
		'[data deleted from memcached server successfully]',
			$class_memento::removeData( sha1( serialize( $data ) ) )
		?
			'TRUE'
		:
			'FALSE'
	),
	$verbose_mode
);
