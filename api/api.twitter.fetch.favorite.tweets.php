<?php

$class_feed_reader = $class_application::getFeedReaderClass();

$user_name = '## FILL USERNAME ##';

$class_feed_reader::displayTwitterFavorites($user_name);