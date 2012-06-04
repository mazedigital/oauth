<?php
session_start();
require_once("../TwitterOAuth.php");

define("TWITTER_CONSUMER_KEY", "YOUR CONSUMER KEY");
define("TWITTER_CONSUMER_SECRET", "YOUR CONSUMER SECRET");


$oauth = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);
// your url to twitter-callback.php
$callback = "http://example.com/twitter-callback.php";
// after being directed to the provider's website, the user will be redirected
// to the callback url
$credentials = $oauth->getRequestToken($callback);

// you don't necessary have to use sessions, but it's one way
$_SESSION['twitter_oauth_credentials'] = serialize($credentials);

$authorize_url = $oauth->getAuthorizeURL($credentials);

header("Location: $authorize_url");

?>
