<?php
session_start();
require_once("../LinkedInOAuth.php");

define('LINKEDIN_CONSUMER_KEY', 'hnqmxem16242');
define('LINKEDIN_CONSUMER_SECRET', 'KB8lkgKjrSw3TRl4');


$oauth = new LinkedInOAuth(LINKEDIN_CONSUMER_KEY, LINKEDIN_CONSUMER_SECRET); 
// your url to linkedin-callback.php
$callback = "http://eurosouth-hub.local/extensions/oauth/lib/blowauth/examples/linkedin-callback.php";
// after being directed to the provider's website, the user will be redirected
// to the callback url
$credentials = $oauth->getRequestToken($callback);
// var_dump($credentials);die;
// $oauth->setToken($credentials["oauth_token"], $credentials["oauth_token_secret"]);

// you don't necessary have to use sessions, but it's one way
$_SESSION['linkedin_oauth_credentials'] = serialize($credentials);
$authorize_url = $oauth->getAuthorizeURL($credentials);

header("Location: $authorize_url");

?>
