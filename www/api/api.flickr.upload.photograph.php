<?php

$f = new phpFlickr('## FILL ME ##', '## FILL ME##');
$f->setToken('## FILL ME ##');

echo $f->sync_upload(

    // path to photo
    //'## FILL ABSOLUTE PATH ##'',
    dirname( __FILE__ ) . '/../img/IMG_1176.jpg',

    // title
    'test',

    // description
    'description',

    // tags
    'nothing',

    // is visible to public
    $is_public = 0,

    // is visible to friends
    $is_friend = 0,

    // is visible to family
    $is_family = 0
);
