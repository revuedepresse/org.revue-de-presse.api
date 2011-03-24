<?php

/**
 *
 * Access token needs to be requested prior to data retrieval
 *
 * http://## FILL HOSTNAME ##/api/api.twitter.request.access.token.php
 *
 */

$class_feed_reader = $class_application::getFeedReaderClass();

$user_name = '## FILL YOUR USERNAME ##';

$class_feed_reader::displayTwitterFavorites(
	$user_name,
	(object) array( PROPERTY_PAGE => 17 )
);