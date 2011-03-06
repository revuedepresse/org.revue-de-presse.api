<?php
/**
*************
* Changes log
*
*************
* 2011 03 06
*************
* 
* Get mailboxes to be imported from pattern
*
* (branch 0.1 :: revision :: 575)
* (trunk :: revision :: 125)
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

$mailboxes = $class_message::getMailboxes( '/^saas ::/');

$class_message::import( '*', $mailboxes );

$class_message::import( '*', array( 'interests' ) );

$class_message::import( '*', array( 'photography' ) );