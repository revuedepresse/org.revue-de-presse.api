<?php

$class_dumper = $class_application::getDumperClass();

$class_feed_reader = $class_application::getFeedReaderClass();;

$url =
    'http://## FILL HOSTNAME ##/unit.testing/'.
    'unit.testing.display.rdf.php?i=1&p=117yyj90m-1gd%2Fphotography'
;

$feed_reader = $class_feed_reader::parse($url, true);

$dom = $feed_reader->getDOM();

$raw_contents = $feed_reader->getRawContents();

$tags = $dom->getElementsByTagNameNS(
    'http://www.## FILL HOSTNAME ##/2007/09/12/basic#',
    'url'
);

$class_dumper::log(
    __METHOD__,
    array($tags->length),
    $verbose_mode
);

foreach ($tags as $index => $tag)

    $class_dumper::log(
        __METHOD__,
        array(
            $tag->getAttributeNodeNS(
                'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
                'resource')->value
        ),
        $verbose_mode
    );