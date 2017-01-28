<?php

    if(!defined('__IN_SYMPHONY__')) die('<h2>Error</h2><p>You cannot directly access this file</p>');

	require_once(TOOLKIT . '/class.event.php');
	
	Class eventoauth_event extends Event{

		public function __construct(&$parent, $env = null) {
			parent::__construct($parent, $env);
		}
		
		public static function about(){
			return array(
				'name' => __('oAuth Event'),
				'author' => array(
					'name' => 'Jon Mifsud',
					'website' => 'http://jonmifsud.com',
					'email' => 'info@jonmifsud.com'
				),
				'version' => '0.1.0',
				'release-date' => '2012-07-18',
				'trigger-condition' => ''
			);
		}

		public function load(){
			if(isset($_POST['action']['oauth-event'])) return $this->__trigger();
		}

		public static function documentation(){
			return new XMLElement('p', 'This should be a master Event to allow posting to external oAuth Services');
		}

		protected function __trigger(){
			$oAuthExtension = ExtensionManager::create('oauth');
		
			$provider = Symphony::Configuration()->get('main', "oauth");
			
			if (isset($_POST['oauth']['provider']) && $_POST['oauth']['provider']!='')
				$provider = $_POST['oauth']['provider'];
				
			$path = $_POST['oauth']['path'];
			$format = $_POST['oauth']['format'];
		
			$oAuthClassname= $oAuthExtension->supportedOAuth[$provider] .'OAuth';
			$oAuthName = $provider;
			require_once(EXTENSIONS . "/oauth/lib/oauth/{$oAuthClassname}.php");
								
			$accessToken = $oAuthExtension->getAccessToken($this->dsParamSYSTEM);
			$token = $accessToken;
			$token_secret = null;
			if (is_array($accessToken) && $this->dsParamSYSTEM=='linkedin'){
				$token_secret = $accessToken['token_secret'];
				$token = $accessToken['token'];
			} else
			if (is_array($accessToken) && $this->dsParamSYSTEM=='twitter'){
				$token_secret = $accessToken['oauth_token_secret'];
				$token = $accessToken['oauth_token'];
			} 
						
			$clientId = Symphony::Configuration()->get('client_id', $oAuthName . 'oauth');
			$secret = Symphony::Configuration()->get('secret', $oAuthName . 'oauth');
				
			$oauth = new $oAuthClassname($clientId, $secret, $token, $token_secret);
						
			//params will be posted params to extract from list
			$params = $_POST['fields'];
			
			//add timeout
			set_time_limit ( 300);
			$data = $oauth->request($path,$ch,'POST',$params);
			
			// var_dump($data);
			$format='json';
			if($format == 'json') {
				try {
					require_once TOOLKIT . '/class.json.php';
					$data = JSON::convertToXML($data);
					// var_dump($data);die;
				}
				catch (Exception $ex) {
					$writeToCache = false;
					$errors = array(
						array('message' => $ex->getMessage())
					);
				}
			}
			// If the XML doesn't validate..
			else if(!General::validateXML($data, $errors, false, new XsltProcess)) {
				$writeToCache = false;
			}
			// If the `$data` is invalid, return a result explaining why
			if($writeToCache === false) {
				$result = new XMLElement('errors');
					$result->setAttribute('valid', 'false');
					$result->appendChild(new XMLElement('error', __('Data returned is invalid.')));
					foreach($errors as $e) {
					if(strlen(trim($e['message'])) == 0) continue;
					$result->appendChild(new XMLElement('item', General::sanitize($e['message'])));
				}
				$result->appendChild($result);
				return new XMLElement('oauth-event',$result);
			}
			
			$position = strpos($data,'<data>');
			$data = substr($data,$position);
			// var_dump($data);die;
			
			$result = new XMLElement('oauth-event',$data);
						
			return $result;
			//todo output success/failure??
		}
	}
