<pre>
curl \
		-F 'access_token=## FILL ACCESS_TOKEN ##' \
		-F 'message=Snapshot' \
		-F 'link=http://example.com/snapshots/img.jpg' \
		-F 'picture=http://example.com/snapshots/img.jpg' \
		-F 'name=Triceratops' \
		-F 'caption=It turns out, she never really existed' \
		-F 'description=nothing more to say about this sad story' \
		-F 'actions={"name": "View on Weaving the Web", "link": "http://example.com/snapshots/img.jpg?param=%20"}' \
		-F 'privacy={"value": "SELF"}' \
		https://graph.facebook.com/me/feed
</pre>
<?php

$resource = curl_init();

$url = 'https://graph.facebook.com';

curl_setopt(
		$resource,
		CURLOPT_RETURNTRANSFER,
		TRUE
);

curl_setopt(
		$resource,
		CURLOPT_HEADER,
		FALSE
);

curl_setopt(
		$resource,
		CURLOPT_URL,
		$url.'/me/feed'
);

// the access token is available from a session opened with Facebook
// @see also api.facebook.sandbox.php

$post =
		'access_token=## FILL ACCESS_TOKEN ##&'.
		'message=Snapshot&'.
		'link=http://example.com/snapshots/img.jpg&'.
		'picture=http://example.com/snapshots/img.jpg&'.
		'name=Triceratops&'.
		'caption=It turns out, she never really existed&'.
		'description=nothing more to say about this sad story&'.
		'actions={"name": "View on Weaving the Web", "link": "http://example.com/snapshots/img.jpg?param=%20"}&'.
		'privacy={"value": "SELF"}'
;

curl_setopt(
		$resource,
		CURLOPT_POST,
		TRUE
);

curl_setopt(
		$resource,
		CURLOPT_POSTFIELDS,
		$post
);

echo '<pre>', print_r( $resource, TRUE ), '</pre>';

echo '<pre>', print_r( curl_exec( $resource ), TRUE ), '</pre>';

curl_close($resource);
