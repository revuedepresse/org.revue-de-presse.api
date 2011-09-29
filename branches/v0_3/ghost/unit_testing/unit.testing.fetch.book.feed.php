<?php

$class_book = $class_application::getBookClass();

$class_book::fetchBookFeed( 900, FALSE );

# cli mode

# php -c /etc/php_5.3.8.ini -d output_buffering=On -f ## FILE ABSOLUTE PATH ##/web/## FILL PROJECT DIR ##/branches/v0.1/unit.testing/unit.testing.fetch.book.feed.php