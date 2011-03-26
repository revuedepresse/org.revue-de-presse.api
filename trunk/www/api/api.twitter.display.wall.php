<?php 

$class_feed_reader = $class_application::getFeedReaderClass();

//$user_name = '## FILL YOUR USERNAME ##';
//
//$class_feed_reader::displayTwitterWall( $user_name );

$kind = 'home';

$class_feed_reader::displayTwitterWall( $kind );
