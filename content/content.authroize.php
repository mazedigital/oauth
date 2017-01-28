<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	// $gcenonlineExtension = ExtensionManager::create('gcenonline');

	if (isset($_POST['success']) && $_POST['status']) {

	
		//get params to see what oAuth we are using
		$params = Symphony::Engine()->Page()->Params();
		$oAuthName = $params['source'];
		if ( !isset($oAuthName)) $oAuthName = Symphony::Configuration()->get('main', 'oauth');
		$oAuthExtension = ExtensionManager::create('oauth');
		$oAuthClassname = $oAuthExtension->getClassName($oAuthName) . 'OAuth';
		
		//get the oAuth Configuration stuff
		$clientId = Symphony::Configuration()->get('client_id', $oAuthName . 'oauth');
		$secret = Symphony::Configuration()->get('secret', $oAuthName . 'oauth');
		$scope = Symphony::Configuration()->get('scope', $oAuthName . 'oauth');
		$redirectUrl = Symphony::Configuration()->get('auth_redirect', $oAuthName . 'oauth');
		
		//Check which oAuth is being confirmed to use. (use variable source?)
		
		$oauth_token;
		$oauth_token_secret;
		
		require_once (EXTENSIONS . "/oauth/lib/oauth/{$oAuthClassname}.php");

		$version = $oAuthClassname::$oauth_version;
		// define('LINKEDIN_CONSUMER_KEY', 'hnqmxem16242');
		// define('LINKEDIN_CONSUMER_SECRET', 'KB8lkgKjrSw3TRl4');

		$access_token_credentials = array();
		if ($version == '1.0'){
			//read cookie
			$cookie = new Cookie($oAuthName .'oAuth',TWO_WEEKS, __SYM_COOKIE_PATH__, null, true);
			$token = $cookie->get('token');
			$cookie->expire();
			
			// $request_token_credentials = $cookie->get('token');
			// var_dump($token);die;

			$oauth_token = $token['oauth_token'];
			$oauth_token_secret = $token['oauth_token_secret'];
			$oauth = new $oAuthClassname($clientId, $secret, $oauth_token, $oauth_token_secret); 
			// var_dump($oauth);die;

			
			// var_dump($this->_env['param']['url-oauth_verifier']);die;
			if (isset($params['url-oauth_verifier'])){
				$oauth_verifier = $params['url-oauth_verifier'];
				$access_token_credentials = $oauth->getAccessToken($oauth_verifier);
			}
			
			$oauth_token = $access_token_credentials['oauth_token'];
			$oauth_token_secret = $access_token_credentials['oauth_token_secret'];
		} else {
			$oauth = new $oAuthClassname($clientId, $secret, $oauth_token, $oauth_token_secret); 
			$access_token_credentials = $oauth->getAccessToken($params['url-code'],$params['current-url'] . '/');
			$oauth_token = $access_token_credentials;
			$oauth_token_secret = null;
		}
		
		
		if ($oAuthName == Symphony::Configuration()->get('main', 'oauth')){
			//store token in cookie
			$cookie = new Cookie('oAuth',TWO_WEEKS, __SYM_COOKIE_PATH__, null, true);
			$cookie->set('token',$access_token_credentials);
			
			$oauth = new $oAuthClassname($clientId, $secret, $oauth_token, $oauth_token_secret); 
			// var_dump($access_token_credentials);die;
			$userid;
			if (is_array($access_token_credentials) && isset($access_token_credentials['user_id'])){
				$userid = $access_token_credentials['user_id'];
				// var_dump('array');
			} else	$userid = $oauth->getUserID();
			$cookie->set('userid',$userid);
			// var_dump($userid);die;
		} else {

			// else user member id
			$membersExtensionId = ExtensionManager::fetchExtensionID('members');
			if ($membersExtensionId){
				$membersExtension = ExtensionManager::getInstance('members');
				$memberId = $membersExtension->getMemberDriver()->getMemberID();
				var_dump($memberId);die;
			}

			if (isset($memberId)){
				$userId = $memberId;
			} elseif isset($params['oauth-user-id']){
				//if using main oAuth > $params['oauth-user-id'] should be set
				$userId = $params['oauth-user-id'];
			}

			//TODO Make Sure user-id is set
			if (!isset($userid)){
				if (isset($membersExtensionId)){
					// should either log this user in

					// or create a new entry for this user
				}
			}


			//we gotta store these in db
			$fields = array('user_id'=>$userId,'system'=>$oAuthName,'token'=>$oauth_token,'token_secret'=>$oauth_token_secret);
			Symphony::Database()->insert($fields,'tbl_oauth_token',true);
		}

		//TODO If user OauthMember trigger a 'login' or 'link' to existing member entry

		// $token = $cookie->get('token');
			
		// $request_token_credentials = $cookie->get('token');
		// var_dump($token);die;
		// var_dump($access_token_credentials);die;
		
		// rebuilding the oAuthv1 object with access token credentials enables us to
		// finally make API calls
		// $oauth = new $oAuthClassname($clientId, $secret, $oauth_token, $oauth_token_secret);
		
		// var_dump($access_token_credentials);die;
		$redirectUrl = $params['root'];
		header('Location: ' . $redirectUrl);
		exit;

	} else {

		die('no information posted');
	}