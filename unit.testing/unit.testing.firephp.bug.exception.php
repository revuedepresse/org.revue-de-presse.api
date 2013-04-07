<?php

error_reporting( E_ALL );

$handle = fopen( dirname( __FILE__) . '/test.php', 'w' );
fclose( $handle );

function error_handler( $code, $message, $file_name, $line )
{
	$exception = new Exception( $message, $code );

	$firephp = FirePHP::getInstance( TRUE );
	$firephp->log( $exception );		
}

set_error_handler( 'error_handler' );

echo TEST_;



