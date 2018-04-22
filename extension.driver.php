<?php

	require_once EXTENSIONS . '/oauth/lib/member.oauth.php';
	require_once EXTENSIONS . '/oauth/data-sources/datasource.oauth.php';
	require_once EXTENSIONS . '/oauth/data-sources/datasource.oauth_remote.php';

	class Extension_OAuth extends Extension {
		/* Start of Data-source registration */
		private static $provides = array();

		public static function registerProviders() {
			self::$provides = array(
				'data-sources' => array(
					'OAuthDatasource' => OAuthDatasource::getName(),
					'OAuthRemoteDatasource' => OAuthRemoteDatasource::getName(),
				)
			);

			return true;
		}

		public static function providerOf($type = null) {
			self::registerProviders();

			if(is_null($type)) return self::$provides;

			if(!isset(self::$provides[$type])) return array();

			return self::$provides[$type];
		}
	
		/*End of Datasource registration*/
		public $supportedOAuth = array('linkedin' => 'LinkedIn', //get used id supported
										'facebook' => 'Facebook', //get used id supported
										'twitter' => 'Twitter',
										'nationalfield' => 'NationalField',
										'google' => 'Google',
										'deputy' => 'Deputy',
										'tumblr' => 'Tumblr');
	
		/*
		 * Get the Class name from the list
		 */
		public function getClassName($name){
			return $this->supportedOAuth[$name];
		}
		/*-------------------------------------------------------------------------
			Delegate
		-------------------------------------------------------------------------*/
	
		public function getSubscribedDelegates() {
			return array(
				array(
					'page' => '/frontend/',
					'delegate' => 'InitialiseMember',
					'callback' => 'initialiseMember'
				),
				array(
					'page' => '/system/preferences/',
					'delegate' => 'AddCustomPreferenceFieldsets',
					'callback' => 'appendPreferences'
				),
				array(
					'page' => '/system/preferences/',
					'delegate' => 'Save',
					'callback' => 'savePreferences'
				),
				array(
					'page' => '/frontend/',
					'delegate' => 'FrontendProcessEvents',
					'callback' => 'appendEventXML'
				),
				array(
					'page' => '/frontend/',
					'delegate' => 'FrontendParamsResolve',
					'callback' => 'appendAccessToken'
				),
				array(
					'page' => '/frontend/',
					'delegate' => 'FrontendPageResolved',
					'callback' => 'frontendPageResolved'
				),
				array(
					'page' => '/publish/',
					'delegate' => 'EntryPostDelete',
					'callback' => 'entryPostDelete'
				),
			);
		}
	
		/*-------------------------------------------------------------------------
			Delegated functions
		-------------------------------------------------------------------------*/	

		public function initialiseMember($context){
			$context['member'] = $this->Member = new OauthMember;
		}
	
		public function appendPreferences($context){
			$group = new XMLElement('fieldset',null,array('class'=>'settings'));
			$group->appendChild(new XMLElement('legend', 'oAuth Authentication'));
	
			// Application Client
			$group->appendChild(new XMLElement('h3', 'OAuth Application',array('style'=>'margin-bottom: 5px;')));
			$group->appendChild(new XMLElement('p','You need to provide an Application Client ID & Client Secret for every oAuth type. These can be obtained from the respective systems',array('class'=>'help')));
			
			//generate array for Main oAuth drop-down
			$mainOAuth = array();
			// a blank first entry
			$mainOAuth[] = array("", Symphony::Configuration()->get('main', "oauth") == "", "");
			foreach($this->supportedOAuth as $name => $className){
				$mainOAuth[] = array($name, Symphony::Configuration()->get('main', "oauth") == $name, $className);
			}
				
			$label = Widget::Label(__('Main oAuth Provider / for Login'));
			$label->setAttribute('class', 'primary column');
				$label->appendChild(
					Widget::Select("settings[oauth][main]", $mainOAuth)
				);
			
			// $label = Widget::Label();
				// $input = Widget::Input("settings[oauth][main]", (string)Symphony::Configuration()->get('main', "oauth"), 'text');
				// $label->setValue(__('Main oAuth Provider') . $input->generate());
				$group->appendChild($label);
						
			foreach($this->supportedOAuth as $name => $className){
				$group->appendChild(new XMLElement('h3', "{$className}",array('style'=>'margin-bottom: 5px;')));
				$div = new XMLElement('div',null,array('class'=>'group'));
				$label = Widget::Label();
						$input = Widget::Input("settings[{$name}oauth][client_id]", (string)Symphony::Configuration()->get('client_id', "{$name}oauth"), 'text');
						$label->setValue(__('Client ID') . $input->generate());
						$div->appendChild($label);
				
				$label = Widget::Label();
						$input = Widget::Input("settings[{$name}oauth][secret]", (string)Symphony::Configuration()->get('secret', "{$name}oauth"), 'password');
						$label->setValue(__('Client Secret') . $input->generate());
						$div->appendChild($label);
				$group->appendChild($div);
				
				
				$label = Widget::Label();
					$input = Widget::Input("settings[{$name}oauth][scope]", (string)Symphony::Configuration()->get('scope', "{$name}oauth"), 'text');
					$label->setValue(__('Client Scope') . $input->generate());
					$group->appendChild($label);
			}
	
			// Append preferences
			$context['wrapper']->appendChild($group);
		}
	
		//TODO SET SCOPE IF REQUIRED (comma delimited most likely) + Add FIELD. This was for a prevrious checkbox so savePref might not be required
		public function savePreferences($context){
			if (!is_null($_REQUEST['settings']['oauth']['scope'])){
				$scope = implode(',', $_REQUEST['settings']['oauth']['scope']);
				$context['settings']['oauth']['scope'] = $scope;
			}
		}

		/*Get Access token of particular System*/
		public function getAccessToken($system=null) {
			$main = Symphony::Configuration()->get('main', 'oauth');
			if ($system==null || $system == $main){
				//if main get from cookie
				$cookie = new Cookie('oAuth',TWO_WEEKS, __SYM_COOKIE_PATH__, null, true);
				$token = $cookie->get('token');
			} else {
				//else get from db
				$params = Symphony::Engine()->Page()->Params();

				$orgID = $this->Member->cookie->get('company');

				if (isset($params['oauth-user-id'])){
					$query = "SELECT `token`,`token_secret` FROM `tbl_oauth_token` where `user_id` = {$params['oauth-user-id']}' and `system`='{$system}'";
				} else if (isset($orgID)) {
					$query = "SELECT `token`,`token_secret` FROM `tbl_oauth_token` where `member_id` = '{$orgID}' and `system`='{$system}'";
				} else {
					return null;
				}
				$row = Symphony::Database()->fetchRow(0,$query);
				// var_dump($row);die;//probably check if secret exists if null return just token else array compatible with others
				$token = $row['token'];
				if (isset($row['token_secret'])){
					$token=array('oauth_token'=>$row['token'],'oauth_token_secret'=>$row['token_secret']);
				}				
				/*
				// for testing the id of facebook etc
				// var_dump($token);die;
				$clientId = Symphony::Configuration()->get('client_id', $system . 'oauth');
				$secret = Symphony::Configuration()->get('secret', $system . 'oauth');
				$oAuthClassname = $this->supportedOAuth[$system] .'OAuth';
				require_once("lib/oauth/{$oAuthClassname}.php");
				$oauth = new $oAuthClassname($clientId, $secret, $token, null); 
				// var_dump($access_token_credentials);die;
				$userid = $oauth->getUserID();
				// var_dump($userid);die;
				*/
			}
			return $token;
		}

		public function getOAuthStatus(){
			//TODO check all current active systems

			//return the first valid logged in token if it exists - maybe we need to store the system with the token to speed up ? 
		}
		
		/*This should only be called from front-end events or DS*/
		public function oAuthStatus($system,&$result){
			$token = $this->getAccessToken($system); 
			$params = Symphony::Engine()->Page()->Params();
			//tocheck if token_secret is set in oAuth 1.0 & find alternative check
			if($token && ((is_array($token) && isset($token['oauth_token_secret'])) || !is_array($token) )) {
				if (is_array($token)){
					$token = $token['oauth_token'];
				}
				$result->setAttributearray(array(
					'logged-in' => 'yes',
					'token' => $token
				));
				$cookie = new Cookie('oAuth',TWO_WEEKS, __SYM_COOKIE_PATH__, null, true);
				//output the current user-id
				$userid = $cookie->get('userid');
				Symphony::Engine()->Page()->_param['oauth-user-id'] = $userid;
				// var_dump($userid);die;
			} else if($params['current-page']!='authorize'){
				//The oAuth to be used for main Login Details or from the handle?!
				// $oAuthName = $system;

				// if (!isset($oAuthName)) $oAuthName = Symphony::Configuration()->get('main', 'oauth'); 

				// if (!isset($oAuthName)){
					foreach ($this->supportedOAuth as $oAuthName => $classPrefix) {
						// do not show authentication details if already logged in with the particular service
						if (isset($this->Member->cookie) && $oAuthName !== $this->Member->cookie->get('oauth')){
							$this->getOAuthAuthenticateURL($oAuthName, $result);
						}
					}
				// }
				
				$result->setAttribute('logged-in','no');
			}

		}
		
		public function getOAuthAuthenticateURL($oAuthName, &$result) {
							
			$clientId = Symphony::Configuration()->get('client_id', $oAuthName . 'oauth');
			$secret = Symphony::Configuration()->get('secret', $oAuthName . 'oauth');

			if (!isset($clientId) && !isset($secret)){
				return;
			}

			$oAuthClassname= $this->supportedOAuth[$oAuthName] .'OAuth';
			require_once("lib/oauth/{$oAuthClassname}.php");
			
			$oauth = new $oAuthClassname($clientId, $secret); 
			$callback = Symphony::Engine()->Page()->_param['root'] . "/authorize/{$oAuthName}/";
			
			$authenticate_url;
			//check version of oAuth if V1
			if ($oAuthClassname::$oauth_version == '1.0'){
				// oAuth v1
				$token = $oauth->getRequestToken($callback);
				$token = $oauth->getRequestToken($callback.'signup/');
				
				//v1 requires us to save the token a cookie should do - has to be cleared if it is secondary and will be stored in db
				$cookie = new Cookie($oAuthName . 'oAuth',TWO_WEEKS, __SYM_COOKIE_PATH__, null, true);
				$cookie->set('token',$token);
				$authenticate_url = $oauth->getAuthenticateURL($token);
				$signup_authenticate_url = $authenticate_url;
			}else {
				// oAuth v2
				$authenticate_url = $oauth->getAuthenticateURL($callback,Symphony::Configuration()->get('scope', $oAuthName . 'oauth'));
				$signup_authenticate_url = $oauth->getAuthenticateURL($callback.'signup/',Symphony::Configuration()->get('scope', $oAuthName . 'oauth'));
			}

			$main = (Symphony::Configuration()->get('main', 'oauth') == $oAuthName) ? 'yes' : 'no';

			$result->appendChild(new XMLElement($oAuthName,null,array('url'=>General::sanitize($authenticate_url),'signup-url'=>General::sanitize($signup_authenticate_url),'main'=> $main )));
		}



		public function appendEventXML(array $context = null) {
			$result = new XMLElement('oauth');
			
			$main = Symphony::Configuration()->get('main', 'oauth');
			$this->oAuthStatus($main,$result);
			
			$context['wrapper']->appendChild($result);
		}

		/*TODO consider changing this - which tokens do we really  need to output to the context?? - Maybe Events should handle outputting of tokens accordingly*/
		public function appendAccessToken($context) {
			$token = $this->getAccessToken();
			if($token) {
				//if array output tokenValue 
				if (is_array($token))
					$context['params']['oauth-access-token'] = $token['oauth_token'];
				else $context['params']['oauth-access-token'] = $token;
			}
		}
		
		public function frontendPageResolved($context) {
			if(isset($_REQUEST['oauth-action']) && isset($_REQUEST['oauth-action']) == 'logout'){
				$cookie = new Cookie('oAuth',TWO_WEEKS, __SYM_COOKIE_PATH__, null, true);
				$cookie->expire();
				if(isset($_REQUEST['redirect'])) redirect($_REQUEST['redirect']);
				redirect(URL);
			}

			// set cookie value to last url to be used for redirects if needed. 
			// TODO ONly set if not logged in
			if($context['page_data']['handle'] !== 'authorize'){
				$cookie = new Cookie('oAuthLastURL',TWO_WEEKS, __SYM_COOKIE_PATH__, null, true);
				$cookie->set('oAuthLastURL',URL . $context['page']->Page());
			}
		}
		
		public function entryPostDelete($context){
			$deleted = implode(',', $context['entry_id']);

			Symphony::Database()->delete('tbl_oauth_token', "`member_id` IN ({$deleted})");
		}

		
		/**
		 * Installation
		 */
		public function install()	{
			// A table to keep track of user tokens in relation to the current current user id
			Symphony::Database()->query("CREATE TABLE IF NOT EXISTS `tbl_oauth_token` (
				`member_id` VARCHAR(255) NOT NULL ,
				`user_id` VARCHAR(255) NOT NULL ,
				`system` VARCHAR(30) NOT NULL ,
				`token` VARCHAR(255) NOT NULL ,
				`token_secret` VARCHAR(255),
			PRIMARY KEY (`user_id`,`system`)
			)ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
			
		}
		
		/**
		 * Update
		 */
		public function update()	{
			$this->install();
			
			
		}
		
		/**
		 * Uninstallation
		 */
		public function uninstall()	{
			// Drop all the tables:
			Symphony::Database()->query("DROP TABLE `tbl_oauth_token`");
		}

	}

?>
