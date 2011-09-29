<?php

require_once dirname(__FILE__).'/../## FILL PROJECT DIR ##/includes/class/class.facebook.inc.php';

define( 'FACEBOOK_APP_ID', '## FILL ME ##' );
define( 'FACEBOOK_SECRET', '## FILL ME ##' );

// Create our Application instance (replace this with your appId and secret).
$facebook = new Facebook(array(
  'appId'  => FACEBOOK_APP_ID,
  'secret' => FACEBOOK_SECRET,
  'cookie' => true,
));

// We may or may not have this data based on a $_GET or $_COOKIE based session.
//
// If we get a session here, it means we found a correctly signed session using
// the Application Secret only Facebook and the Application know. We dont know
// if it is still valid until we make an API call using the session. A session
// can become invalid if it has already expired (should not be getting the
// session back in this case) or if the user logged out of Facebook.
$session = $facebook->getSession();

$me = null;
// Session based API call.
if ($session) {
  try {
    $uid = $facebook->getUser();
    $me = $facebook->api('/me');
  } catch (FacebookApiException $e) {
    error_log($e);
  }
}

// login or logout url will be needed depending on current user state.
if ($me) {
  $logoutUrl = $facebook->getLogoutUrl();
} else {
  $loginUrl = $facebook->getLoginUrl();
}

    //<!--
    //  We use the JS SDK to provide a richer user experience. For more info,
    //  look here: http://github.com/facebook/connect-js
    //-->
    //<div id="fb-root"></div>
    //<script>
    //  window.fbAsyncInit = function() {
    //    FB.init({
    //      appId   : '
          
//          echo $facebook->getAppId(); 
  
          //',
          //session : 
          
//          echo json_encode($session);
          
//          , // don't refetch the session when PHP already has it
    //      status  : true, // check login status
    //      cookie  : true, // enable cookies to allow the server to access the session
    //      xfbml   : true // parse XFBML
    //    });
    //
    //    // whenever the user logs in, we refresh the page
    //    FB.Event.subscribe('auth.login', function() {
    //      window.location.reload();
    //    });
    //  };
    //
    //  (function() {
    //    var e = document.createElement('script');
    //    e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
    //    e.async = true;
    //    document.getElementById('fb-root').appendChild(e);
    //  }());
    //</script>
?>
<!doctype html>
<html xmlns:fb="http://www.facebook.com/2008/fbml">
  <head>
    <title>php-sdk</title>
    <style>
      body {
        font-family: 'Lucida Grande', Verdana, Arial, sans-serif;
      }
      h1 a {
        text-decoration: none;
        color: #3b5998;
      }
      h1 a:hover {
        text-decoration: underline;
      }
    </style>
  </head>
  <body>

    <?php

    if ($me):
    
      echo 'fb session opened' ;// '<pre>', 'Facebook session: ', print_r($session, true), '</pre>';

    ?>
    
    
    <a href="<?php echo $logoutUrl; ?>">
      <img src="http://static.ak.fbcdn.net/rsrc.php/z2Y31/hash/cxrz4k7j.gif">
    </a>
  
     <?php else:
    ?>
    
    <div>
      Using JavaScript &amp; XFBML: <fb:login-button></fb:login-button>
    </div>
    <div>
      Without using JavaScript &amp; XFBML:
      <a href="<?php echo $loginUrl; ?>">
        <img src="http://static.ak.fbcdn.net/rsrc.php/zB6N8/hash/4li2k73z.gif">
      </a>
    </div>
    <?php endif; 

if ( $me )
{
    ?><a href='<?php


    $grant_actions = 
      'Grant permissions to Application Weaving the Web for ' .
      'publishing streams, ' .
      'reading friend list, ' .
      'managing friend lists, ' .
      'accessing user photos?'
    ;

    echo
        'https://graph.facebook.com/oauth/authorize?'.
        'client_id=## FILL ME ##&'.
        'redirect_uri=http://## FILL HOSTNAME ##/fb/api.facebook.get.friendlists.php&'.
        'scope='.
        'user_photos,'.
        'publish_stream,'.
        'read_friendlists,'.
        'manage_friendlists'
    ;
    ?>'><?php echo $grant_actions ?></a><?php
}
   
try { 
    $feed = $facebook->api( '/me/feed' );
} catch ( FacebookApiException  $e)
{
	echo $e;
}

	echo '<pre>', print_r( $feed, TRUE ), '</pre>';
?></body>
</html>