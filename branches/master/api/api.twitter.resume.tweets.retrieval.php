<?php

$class_dumper = $class_application::getDumperClass();

$class_feed_reader = $class_application::getFeedReaderClass();

$date = '2011-03-29_21';

$date_2 = '2011-03-29_19';

$definition = 'user';

$resource = 'timeline';

$user_name = 'junysb3';

$file =
	dirname( __FILE__ ) . '/' .
	DIR_TWITTER . '/' .
	DIR_TIMELINE . '/' .
	$definition . '_' .
	$resource . '_' .
	$user_name . '_' .
	$date
;

$file_2 =
	dirname( __FILE__ ) . '/' .
	DIR_TWITTER . '/' .
	DIR_TIMELINE . '/' .
	$definition . '_' .
	$resource . '_' .
	$user_name . '_' .
	$date_2
;

$destination = 
	dirname( __FILE__ ) . '/' .
	DIR_TWITTER . '/' .
	DIR_TIMELINE . '/' .
	$definition . '_' .
	$resource . '_' .
	$user_name . '_' .
	date( 'Y-m-d_H' )
;

$class_dumper::log(
	__METHOD__,
	array(
		'$class_feed_reader::getLastTweetId( $file );',
		$class_feed_reader::getLastTweetId( $file )
	),
	$verbose_mode
);

//$content = $class_feed_reader::unserializeStore( $file );
//
//$content_2 = $class_feed_reader::unserializeStore( $file_2 );
//
//array_merge( $content_2, $content );
//
//file_put_contents(
//	$destination,
//	serialize( $content_2 )
//);

//
//list( $index ) = each( $content_2 );
//
//$class_dumper::log(
//	__METHOD__,
//	array(
//		'count( $class_feed_reader::unserializeStore( $file ) )',
//		count( $class_feed_reader::unserializeStore( $file ) )
//	),
//	$verbose_mode
//);

$store = $class_feed_reader::unserializeStore( $file );

$item = array_pop( $store );

fprint( $item, $verbose_mode );