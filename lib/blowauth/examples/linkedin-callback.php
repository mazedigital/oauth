<?php
session_start();
require_once("../LinkedInOAuth.php");

define('LINKEDIN_CONSUMER_KEY', 'hnqmxem16242');
define('LINKEDIN_CONSUMER_SECRET', 'KB8lkgKjrSw3TRl4');

$request_token_credentials = unserialize($_SESSION['linkedin_oauth_credentials']);
// var_dump($request_token_credentials);die;

$oauth_token = $request_token_credentials['oauth_token'];
$oauth_token_secret = $request_token_credentials['oauth_token_secret'];
$oAuthClassname='LinkedInOAuth';
$oauth = new $oAuthClassname(LINKEDIN_CONSUMER_KEY, LINKEDIN_CONSUMER_SECRET, $oauth_token, $oauth_token_secret); 

if (isset($_GET['oauth_verifier'])){
	$oauth_verifier = $_GET['oauth_verifier'];
	$access_token_credentials = $oauth->getAccessToken($oauth_verifier);
	$oauth_token = $access_token_credentials['oauth_token'];
	$oauth_token_secret = $access_token_credentials['oauth_token_secret'];
	// storing these will let you use the same access token later
	$_SESSION['linkedin_oauth_credentials'] = serialize($access_token_credentials);

	// rebuilding the BlowAuth object with access token credentials enables us to
	// finally make API calls
	$oauth = new $oAuthClassname(LINKEDIN_CONSUMER_KEY, LINKEDIN_CONSUMER_SECRET, $oauth_token, $oauth_token_secret);
}
// a comparable call could be made to the twitter API with the following:
// $twitter_user_json = $oauth->request('account/verify_credentials.json');
$linkedin_xml = $oauth->request('people/~:(id,first-name,last-name,industry,skills:(id,skill,proficiency))');

echo htmlentities($linkedin_xml);
// below is some example code for parsing LinkedIn's xml response, which
// requires the PHP XML Parser lib. uncomment to see in action!

/*
$parser = xml_parser_create();
$linkedin_user_data = array();
xml_parse_into_struct($parser, $linkedin_xml, $linkedin_user_data);
xml_parser_free($parser);

foreach ($linkedin_user_data as $element) {
    switch ($element['tag']) {
        case 'FIRST-NAME':
            $first_name = $element['value'];
            break;
        case 'LAST-NAME':
            $last_name = $element['value'];
            break;
        case 'URL':
            $url_data = parse_url($element['value']);
            $query_data = array();
            parse_str($url_data['query'], $query_data);
            $linkedin_id = $query_data['key'];
            break;
    }
}

echo "Name: $first_name $last_name, LinkedIn id: $linkedin_id";
*/

?>
