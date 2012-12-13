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
		$url . '/me/friendlists?access_token=## FILL ME #### FILL ME #### FILL ME #### FILL ME ##'
);

// the access token is available from a session opened with Facebook
// @see also api.facebook.sandbox.php

//$post =
//	'access_token=## FILL ME #### FILL ME #### FILL ME #### FILL ME ##'
//;
//
//curl_setopt(
//	$resource,
//	CURLOPT_POST,
//	TRUE
//);
//
//curl_setopt(
//	$resource,
//	CURLOPT_POSTFIELDS,
//	$post
//);

echo '<pre>', print_r( $resource, TRUE ), '</pre>';

echo '<pre>', print_r( curl_exec( $resource ), TRUE ), '</pre>';

curl_close($resource);
