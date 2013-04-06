<?php

$class_dumper = $class_application::getDumperClass();

$action_authorize = 'authorize';
$action_request = 'request';
$action_get_orgs = 'get_orgs';
$action_get_repos = 'get_repos';
$action_get_user = 'get_user';

$base_url_github = 'https://github.com';
$base_url_github_api = 'https://api.github.com';
$endpoint_access_token = $base_url_github . '/login/oauth/access_token';
$endpoint_authorize = $base_url_github . '/login/oauth/authorize';
$endoint_get_orgs = $base_url_github_api . '/user/orgs';
$endoint_get_repos = $base_url_github_api . '/user/repos';
$endoint_get_user = $base_url_github_api . '/user';
$redirect_uri = urlencode('https://## FILL HOSTNAME ##/github/session'); 
$client_id = '## FILL ME ##';
$client_secret = '## FILL ME ##';
$state = md5(time());
$scope = 'user,public_repo,repo,delete_repo,gist';
$user_token = API_GITHUB_TOKEN;
$parameter_token = 'access_token=' . $user_token;

$open_url = function ($url) 
{
	$response = '';
	$handle = fopen($url, 'r', false);

	if (is_resource($handle))
	{
		while ( ! feof($handle))
		{
			$response .= fread($handle, 8096);
		}
	}	

	return $response;
};

$crawl_url = function ($url) use ($class_dumper)
{
	$resource = curl_init();

	curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($resource, CURLOPT_URL, $url);
	curl_setopt($resource, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($resource, CURLOPT_SSL_VERIFYPEER, false);

	$response = curl_exec($resource);

	$class_dumper::log(__METHOD__, array(
		'[endpoint]', $url, 
		'[response]', $response));

	curl_close($resource);

	return $response;
};

$action = $_GET['action'];
$response = '';

if (isset($action))
{
	switch($action)
	{
		case $action_authorize:

			$url = 
				$endpoint_authorize . '?' .
				'client_id=' . $client_id;
				'redirect_uri=' . $redirect_uri . '&' .
				'scope=' . $scope . '&' .
				'state=' . $state;

			$_SESSION['api.github.state'] = $state;

			$class_dumper::log(__METHOD__, array('[url]', $url, '[state]', $state), true);

			header('location: ' . $url);

			break;

		case $action_request:

			$resource = curl_init();

			curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($resource, CURLOPT_URL, $endpoint_access_token);

			$post =
				'code=' . $_GET['code'] . '&' .
				'client_id=' . $client_id . '&' .
				'client_secret=' . $client_secret . '&' .
				'state=' . $_SESSION['api.github.state']
			;

			curl_setopt($resource, CURLOPT_POST, true);
			curl_setopt($resource, CURLOPT_POSTFIELDS, $post);
			curl_setopt($resource, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($resource, CURLOPT_SSL_VERIFYPEER, 0);

			$response = curl_exec($resource);

			$class_dumper::log(__METHOD__, array(
				'[endpoint]', $endpoint_access_token, 
				'[POST parameters]', $post, 
				'[received $_GET values]', $_GET,
				'[response]', $response), true);

			curl_close($resource);

			break;

		case $action_get_orgs:

			$request_orgs = $endoint_get_orgs . '?' . $parameter_token;;
            $response = $crawl_url($request_orgs);

			$class_dumper::log(__METHOD__, array(
				'[repositories request]', $endoint_get_orgs,
				'[repositories response]', $response
			), true);

			break;

		case $action_get_repos:

			$request_repos = $endoint_get_repos . '?' . $parameter_token;;
			$response = $crawl_url($request_repos);

			$class_dumper::log(__METHOD__, array(
				'[repositories request]', $endoint_get_repos,
				'[repositories response]', $response
			), true);

			break;

		case $action_get_user:

			$request_user = $endoint_get_user . '?' . $parameter_token;
            $response = $crawl_url($request_user);

            $class_dumper::log(
                __METHOD__,
                array(
                    '[user request]',
                    $endoint_get_user,
                    '[user response]',
                    $response
                )
            );
            header('Content-type: application/javascript');

			break;

        default:

            throw new InvalidArgumentException(sprintf(
                'Sorry, this action is invalid (%s)', $action));
	}

    echo $response;
}

