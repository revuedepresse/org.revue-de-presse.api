<?php
/**
*************
* Changes log
*
*************
* 2011 03 05
*************
* 
* Start revising the message saving methods from the IMAP API
* for optimizing performances 
*
* (branch 0.1 :: revision :: 570)
* (trunk :: revision :: 89-98)
*
*/

$class_message = $class_application::getMessageClass();

$class_message::import();

$class_message::import( '*', array( 'interests' ) );

$class_message::import( '*', array( 'photography' ) );

$class_message::import( '*', array( 'saas :: Amplify' ) );
