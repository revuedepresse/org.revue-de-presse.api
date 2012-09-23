<?php

svn_auth_set_parameter( SVN_AUTH_PARAM_DEFAULT_USERNAME, SVN_USER_NAME );
svn_auth_set_parameter( SVN_AUTH_PARAM_DEFAULT_PASSWORD, SVN_PASSWORD );
svn_auth_set_parameter( SVN_AUTH_PARAM_DONT_STORE_PASSWORDS, TRUE );
svn_auth_set_parameter( PHP_SVN_AUTH_PARAM_IGNORE_SSL_VERIFY_ERRORS, TRUE );
svn_auth_set_parameter( SVN_AUTH_PARAM_NON_INTERACTIVE, TRUE );
svn_auth_set_parameter( SVN_AUTH_PARAM_NO_AUTH_CACHE, TRUE );

$url_repository = SVN_PROTOCOL .'://' . SVN_HOST . '/' . SVN_REPOSITORY;

$results = svn_log( $url_repository, 661 );

echo '<pre>', print_r( $results, TRUE ), '</pre>';