<?php

$class_db = $class_application::getDbClass();

$header = 0;

$type = MESSAGE_TYPE_TWEET;

$insert_message_model = '
    INSERT INTO
        '.TABLE_MESSAGE.'
    SET
        msg_body_text = "{text}"
        msg_type = {type}
        hdr_id = {header}
';

$insert_message = str_replace(
    array(
        '{text}',
        '{type}',
        '{header}'
    ),
    array(
        $text,
        $type,
        $header
    ),
    $insert_message_model
);

//$results = $class_db::query($insert_message);