<?
/*******************************************

phpFlickr authentication tool v1.0
created by Dan Coulter (http://www.dancoulter.com)
for use with phpFlickr (http://www.phpflickr.com).

This tool allows you to get an authentication token that you can use with your 
phpFlickr install to allow a certain kind of access to one Flickr account 
through the API to anyone who visits your website.  In other words...You can 
create a photo gallery on your website that shows photos you've marked as 
private and visitors to your website won't have to do any sort of 
authentication to Flickr.

This tool is free for you to use and matches the code used at 
http://www.phpflickr.com/tools/auth/ exactly.  You may not use this to collect
other users' login information without their permission or knowledge.

This file is packaged with a full distribution of phpFlickr v1.5 that you may use
in your application.

*******************************************/

if (!empty($_SESSION['api_key'])) {
    $f = new phpFlickr($_SESSION['api_key'], $_SESSION['secret']);
    $f->auth($_SESSION['perms']);
    $token = $f->auth_checkToken($_SESSION['phpFlickr_auth_token']);
    echo "This is the code you should use to generate your phpFlickr instance: <br />";
    echo "<pre>\$f = new phpFlickr('{$_SESSION['api_key']}', '{$_SESSION['secret']}');\n";
    echo "\$f->setToken('{$_SESSION['phpFlickr_auth_token']}');\n";
    echo "</pre>";
    echo "This code will log your website in as the Flickr user '{$token['user']['username']}' with '{$token['perms']}' permissions";
    exit;
} elseif (!empty($_POST['api_key'])) {
    if (!empty($_POST['secret'])) {

        if ( isset( $_SESSION['phpFlickr_auth_token'] ) )
            unset( $_SESSION['phpFlickr_auth_token'] );

        $_SESSION['api_key'] = $_POST['api_key'];
        $_SESSION['secret'] = $_POST['secret'];
        $_SESSION['perms'] = $_POST['perms'];
        $f = new phpFlickr($_SESSION['api_key'], $_SESSION['secret']);
        $f->auth($_SESSION['perms']);
        echo "Redirecting...";
        exit;
    } else {
        echo "<span style='color: red'>You must enter a \"secret\"</span>";
    }
} 

if ( isset( $_SESSION['phpFlickr_auth_token'] ) ) unset( $_SESSION['phpFlickr_auth_token'] );
if ( isset( $_SESSION['api_key'] ) ) unset( $_SESSION['phpFlickr_auth_token'] );

?>
    <table border="0" align="center" width="600"><tr><td>
        <form style="text-align: center" method='post'>
            API Key<br />
            <input type="text" style="text-align: center" name="api_key" size="40" /><br />
            Secret<br />
            <input type="text" style="text-align: center" name="secret" size="40" /><br />
            Required Permissions<br />
            <select name="perms">
                <option value="read">Read</option>
                <option value="write">Write</option>
                <option value="delete">Delete</option>
            </select><br />
            <input type="submit" value="Submit">
        </form>
    </td></tr></table>
<?
