<?php

/**
*
* Access token needs to be requested prior to data retrieval
*
* http://## FILL HOSTNAME ##/api/api.twitter.request.access.token.php
*
*/

$class_feed_reader = $class_application::getFeedReaderClass();

$kind = API_TWITTER_TIMELINE_HOME;

$class_feed_reader::displayTwitterTimeline(
	$kind,
	(object) array(
		//PROPERTY_COUNT => 200,
		PROPERTY_PAGE => 1,
	)
);