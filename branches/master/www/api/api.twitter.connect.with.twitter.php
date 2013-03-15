<?php 

echo '<?xml version="1.0"?>';

/*
        <h3>Badge</h3>

        <a class='twitter-anywhere-user' style='font-weight:bold;'>@thierrymarianne</a>
*/
?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <title id="page-title"></title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <script src="http://platform.twitter.com/anywhere.js?id=## FILL ME ##&v=1" type="text/javascript">
        </script>
        <?php /*
        <script type="text/javascript">
            twttr.anywhere(onAnywhereLoad);
            function onAnywhereLoad(twitter) {
                // configure the @Anywhere environment
                twitter("#linkify-this-content").linkifyUsers();
            };
        </script>
         */ ?>
    </head>
    <body>


        <h3>Ouverture de session</h3>

        <span id="login"></span>        

        <h3>Clôture de session</h3>

        <button type="button" onclick="twttr.anywhere.signOut();">Sign out of Twitter</button>

        <script type="text/javascript">
        twttr.anywhere(function (T) {
          T.hovercards();
        });
      
        twttr.anywhere(function (T) {
            T("#login").connectButton({
                authComplete: function(user) {
                  // triggered when auth completed successfully
                },
                signOut: function() {
                  // triggered when user logs out
                }
            });
        });

        twttr.anywhere(function (T) {
      
          T.bind("authComplete", function (e, user) {
            // triggered when auth completed successfully
          });
      
          T.bind("signOut", function (e) {
            // triggered when user logs out
          });
      
        });
        </script>
    </body>
</html>