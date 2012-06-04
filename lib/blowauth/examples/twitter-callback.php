<?php
session_start();
require_once("../TwitterOAuth.php");

define("TWITTER_CONSUMER_KEY", "YOUR CONSUMER KEY");
define("TWITTER_CONSUMER_SECRET", "YOUR CONSUMER SECRET");


$request_token_credentials = unserialize($_SESSION['twitter_oauth_credentials']);
$oauth_verifier = $_GET['oauth_verifier'];

$oauth_token = $request_token_credentials['oauth_token'];
$oauth_token_secret = $request_token_credentials['oauth_token_secret'];
$oauth = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $oauth_token, $oauth_token_secret); 

$access_token_credentials = $oauth->getAccessToken($oauth_verifier);
$oauth_token = $access_token_credentials['oauth_token'];
$oauth_token_secret = $access_token_credentials['oauth_token_secret'];
// storing these will let you use the same access token later
$_SESSION['twitter_oauth_credentials'] = serialize($access_token_credentials);

// rebuilding the BlowAuth object with access token credentials enables us to
// finally make API calls
$oauth = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $oauth_token, $oauth_token_secret);
// a comparable call could be made to the twitter API with the following:
// $linkedin_user_xml = $oauth->request('people/~');
$twitter_user_json = $oauth->request('account/verify_credentials.json');

$user = json_decode($twitter_user_json);

echo "Name: $user->name, Twitter uid: $user->id";

?>
