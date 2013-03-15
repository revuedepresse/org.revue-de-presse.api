<?php

    $api_key                 = $_SESSION['api_key'];
    $api_secret              = $_SESSION['secret'];
    $default_redirect        = dirname($_SERVER['PHP_SELF']);
    $permissions             = $_SESSION['perms'];
    ob_start();
	
	if ( isset( $_SESSION['phpFlickr_auth_token'] ) )
    unset($_SESSION['phpFlickr_auth_token']);
     
	if (!empty($_GET['extra'])) {
		$redirect = $_GET['extra'];
	}
    
    $f = new phpFlickr($api_key, $api_secret);
 
    if (empty($_GET['frob'])) {
        $f->auth($permissions, false);
    } else {
        $f->auth_getToken($_GET['frob']);
	}
    
    if (empty($redirect)) {
		header("Location: " . $default_redirect);
    } else {
		header("Location: " . $redirect);
    }
