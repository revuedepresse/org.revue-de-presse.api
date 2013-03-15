<?php

$class_feed = $class_application::getFeedClass();

$class_dumper::log(
   __METHOD__,
   array(
	  '$class_feed::getFromHashMap();',
	  $class_feed::getFromHashMap()
   ),
   $verbose_mode
);