<?php

// http://## FILL HOSTNAME ##/fb/api.facebook.login.php

require_once $directory_web_service_tifa . '/includes/class/class.facebook.inc.php';

define( 'FACEBOOK_APP_ID', '## FILL ME##' );
define( 'FACEBOOK_SECRET', '## FILL ME##' );

// Create our Application instance (replace this with your appId and secret).
$facebook = new Facebook(array(
  'appId'  => FACEBOOK_APP_ID,
  'secret' => FACEBOOK_SECRET
));

// Get User ID
$user = $facebook->getUser();

// We may or may not have this data based on whether the user is logged in.
//
// If we have a $user id here, it means we know the user is logged into
// Facebook, but we don't know if the access token is valid. An access
// token is invalid if the user logged out of Facebook.


if ($user) {
  try {
      // Proceed knowing you have a logged in user who's authenticated.
      $user_profile = $facebook->api('/me');
  } catch (FacebookApiException $e) {
      error_log($e);
      $user = null;
  }
}

// Login or logout url will be needed depending on current user state.
if ($user) {
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

?><!doctype html>
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

//    if ($me):
    
//      echo '<pre>', 'Facebook session: ', print_r($session, true), '</pre>';

    ?>
    
    
    <a href="<?php echo $logoutUrl; ?>">
      Logout
      <?php //<img src="http://static.ak.fbcdn.net/rsrc.php/z2Y31/hash/cxrz4k7j.gif">?>
    </a>
  
	<?php if ( ! $me):
    // <?php else:
    ?>
    
    <div>
      Using JavaScript &amp; XFBML: <fb:login-button></fb:login-button>
    </div>
    <div>
      Without using JavaScript &amp; XFBML:
      <a href="<?php echo $loginUrl; ?>">Login 
        <img src="http://static.ak.fbcdn.net/rsrc.php/zB6N8/hash/4li2k73z.gif">
      </a>
    </div>
    <?php endif;

      //'https://graph.facebook.com/oauth/access_token?             
      //client_id=' .FACEBOOK_APP_ID  . '&
      //client_secret=' . FACEBOOK_SECRET . '&
      //grant_type=fb_exchange_token&
      //fb_exchange_token=EXISTING_ACCESS_TOKEN'
//'https://graph.facebook.com/oauth/authorize?'.
        //'client_id=' . FACEBOOK_APP_ID. '&'.
        //'redirect_uri=https://## FILL HOSTNAME ##/fb/api.facebook.login.php&'.
        //'scope='.
        //'user_photos,'.
        //'publish_stream,'.
        //'read_friendlist,'.
        //'manage_friendlists'
        

    $code = $_REQUEST["code"];
    $endpoint = urlencode('https://## FILL HOSTNAME ##/fb/api.facebook.login.php');

    if (empty($code)) {
      $_SESSION['state'] = md5(uniqid(rand(), TRUE)); 
    } //CSRF protection

   if (
       isset($_SESSION['state']) && $_SESSION['state'] &&
       isset($_REQUEST['state']) && ($_SESSION['state'] === $_REQUEST['state'])
    ) {

      $token_url = "https://graph.facebook.com/oauth/access_token?"
         . "client_id=" . FACEBOOK_APP_ID . "&redirect_uri=" . $endpoint
         . "&client_secret=" . FACEBOOK_SECRET . "&code=" . $code;
  
       $response = file_get_contents($token_url);
       $params = null;
       parse_str($response, $params);
  
       $graph_url = "https://graph.facebook.com/me?access_token=" 
         . $params['access_token'];
  
        $friends = 'https://graph.facebook.com/me/friends?access_token=' . $params['access_token'];

        $user = json_decode(file_get_contents($graph_url));
        echo("Hello " . $user->name);

        //try { 
        //    $friends = $facebook->api('/me/friends', 'GET', array('access_token' => $params['access_token']));
        //    echo '<pre>', print_r( $friends, TRUE ), '</pre>';
        //}
        //catch (FacebookApiException  $e)
        //{
        //    echo '<br / >', 'An error occurred when calling graph API: ', $e->getMessage(), '<br / >';
        //}

        //try { 
        //    $feed = $facebook->api('/me/feed', 'GET', array('access_token' => $params['access_token']));
        //    echo '<pre>', print_r( $feed, TRUE ), '</pre>';
        //}
        //catch (FacebookApiException  $e)
        //{
        //    echo '<br / >', 'An error occurred when calling graph API: ', $e->getMessage(), '<br / >';
        //}

        //try { 
        //    $feed = $facebook->api( '/me/feed?access_token=' . $params['access_token'] );
        //    echo '<pre>', print_r( $feed, TRUE ), '</pre>';
        //}
        //catch ( FacebookApiException  $e) {
        //    echo '<pre>', print_r($e), '</pre>';
        //}

   } else {

      $grant_actions = 
        'Grant permissions to Application Weaving the Web for ' .
        'publishing streams, ' .
        'reading friend list, ' .
        'managing friend lists, ' .
        'accessing user photos?'
      ;
      
      $link = 
        'https://www.facebook.com/dialog/oauth?' .
           'client_id=' . FACEBOOK_APP_ID .
           '&redirect_uri=' . $endpoint .
           '&scope=user_about_me,user_activities,user_birthday,user_checkins,user_education_history,user_events,user_groups,user_hometown,user_interests,user_likes,user_location,user_notes,user_photos,user_questions,user_relationships,user_relationship_details,user_religion_politics,user_status,user_subscriptions,user_videos,user_website,user_work_history,email,read_friendlists,read_insights,read_mailbox,read_requests,read_stream,xmpp_login,ads_management,create_event,manage_friendlists,manage_notifications,user_online_presence,friends_online_presence,publish_checkins,publish_stream' . 
           '&state=' . $_SESSION['state'] . '
       '    
      ;
      
      echo sprintf('<a href="%s">%s</a>', $link, $grant_actions); 
    }

?></body>
</html>
