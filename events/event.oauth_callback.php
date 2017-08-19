<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Error</h2><p>You cannot directly access this file</p>');

	require_once(TOOLKIT . '/class.event.php');
	
	Class eventoauth_callback extends Event{

		public function __construct(&$parent, $env = null) {
			parent::__construct($parent, $env);
		}
		
		public static function about(){
			return array(
				'name' => __('oAuth Callback'),
				'author' => array(
					'name' => 'Jon Mifsud',
					'website' => 'http://jonmifsud.com',
					'email' => 'info@jonmifsud.com'
				),
				'version' => '0.1.0',
				'release-date' => '2011-09-28',
				'trigger-condition' => ''
			);
		}

		public function load(){
			return $this->__trigger();
		}

		public static function documentation(){
			return new XMLElement('p', 'This is an event that redirects will verify an autenticated user and redirect to the proper page.');
		}



		public function getRedirect($entry, $oAuthName, $new = false, $linked = false, $XSLTfilename = 'redirect.xsl', $fetch_associated_counts = NULL) {
			$entry_xml = new XMLElement('entry');
			$data = $entry->getData();
			$fields = array();

			$entry_xml->setAttribute('id', $entry->get('id'));
			
			//Add date created and edited values
			$date = new XMLElement('system-date');

			$date->appendChild(
				General::createXMLDateObject(
				DateTimeObj::get('U', $entry->get('creation_date')),
				'created'
				)
			);

			$date->appendChild(
				General::createXMLDateObject(
				DateTimeObj::get('U', $entry->get('modification_date')),
				'modified'
				)
			);

			$entry_xml->appendChild($date);


			// Add associated entry counts
			if($fetch_associated_counts == 'yes') {
				$associated = $entry->fetchAllAssociatedEntryCounts();

				if (is_array($associated) and !empty($associated)) {
					foreach ($associated as $section_id => $count) {
						$section = SectionManager::fetch($section_id);

						if(($section instanceof Section) === false) continue;
						$entry_xml->setAttribute($section->get('handle'), (string)$count);
					}
				}
			}

			// Add fields:
			foreach ($data as $field_id => $values) {
				if (empty($field_id)) continue;

				$field = FieldManager::fetch($field_id);
				$field->appendFormattedElement($entry_xml, $values, false, null, $entry->get('id'));
			}

			$xml = new XMLElement('data');
			$xml->appendChild($entry_xml);

			// Build some context
			$section = SectionManager::fetch($entry->get('section_id'));

			$cookie = new Cookie('oAuthLastURL',TWO_WEEKS, __SYM_COOKIE_PATH__, null, true);
			$lastURL = $cookie->get('oAuthLastURL');

			//generate parameters such as root and add into dom
			$date = new DateTime();
			$params = array(
				'today' => $date->format('Y-m-d'),
				'current-time' => $date->format('H:i'),
				'this-year' => $date->format('Y'),
				'this-month' => $date->format('m'),
				'this-day' => $date->format('d'),
				'timezone' => $date->format('P'),
				'website-name' => Symphony::Configuration()->get('sitename', 'general'),
				'root' => URL,
				'workspace' => URL . '/workspace',
				'http-host' => HTTP_HOST,
				'entry-id' => $entry->get('id'),
				'section-handle' => $section->get('handle'),
				'service' => $oAuthName,
				'new' => $new ? 'yes' : 'no',
				'linked' => $linked ? 'yes' : 'no',
				'last-url' => $lastURL,
			);

			/*$datasources = explode(',',$this->datasources);
			foreach ($datasources as $dsName) {
				$ds = DatasourceManager::create($dsName, $params);
				$arr = array();
				$dsXml = $ds->execute($arr); 
				$xml->appendChild($dsXml);
			}*/

			//in case there are url params they will also be added in the xml
			$paramsXML = new XMLElement('params');
			foreach ($params as $key => $value) {
				$paramsXML->appendChild(new XMLElement($key,$value));
			}
			$xml->appendChild($paramsXML);

			$dom = new DOMDocument();
			$dom->strictErrorChecking = false;
			$dom->loadXML($xml->generate(true));

			if (!empty($XSLTfilename)) {
				$XSLTfilename = WORKSPACE . '/oauth/'. preg_replace(array('%/+%', '%(^|/)../%'), '/', $XSLTfilename);
				// var_dump($XSLTfilename);die;
				if (file_exists($XSLTfilename)) {
					$XSLProc = new XsltProcessor;

					$xslt = new DomDocument;
					$xslt->load($XSLTfilename);

					$XSLProc->importStyleSheet($xslt);

					// Set some context
					$XSLProc->setParameter('', array(
						'section-handle' => $section->get('handle'),
						'entry-id' => $entry->get('id')
					));

					$temp = $XSLProc->transformToDoc($dom);

					if ($temp instanceof DOMDocument) {
						$dom = $temp;
					}
				}
			}

			return XMLElement::convertFromDOMDocument('link',$dom)->getValue();
		}

		protected function __trigger(){
		
			//get params to see what oAuth we are using
			$params = Symphony::Engine()->Page()->Params();
			$oAuthName = $params['source'];
			$signUp = $params['signup'] == 'signup';
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
			

			$oauth = new $oAuthClassname($clientId, $secret, $oauth_token, $oauth_token_secret); 
			
			if ($oAuthName == Symphony::Configuration()->get('main', 'oauth')){
				//store token in cookie
				$cookie = new Cookie('oAuth',TWO_WEEKS, __SYM_COOKIE_PATH__, null, true);
				$cookie->set('token',$access_token_credentials);
				
				// $oauth = new $oAuthClassname($clientId, $secret, $oauth_token, $oauth_token_secret); 
				// var_dump($access_token_credentials);die;
				$userid;
				if (is_array($access_token_credentials) && isset($access_token_credentials['user_id'])){
					$userid = $access_token_credentials['user_id'];
					// var_dump('array');
				} else	$userid = $oauth->getUserID();
				$cookie->set('userid',$userid);
				// var_dump($userid);die;
			} else {

				$linked = false;

				// else user member id
				$membersExtensionId = ExtensionManager::fetchExtensionID('members');
				if ($membersExtensionId){
					$membersExtension = ExtensionManager::getInstance('members');
					$memberDriver = $membersExtension->getMemberDriver();
					$memberId = $memberDriver->getMemberID();
					if (isset($memberId) && !empty($memberId)){
						$linked = true;
						// var_dump($memberId);die;
					}
				}

				if (!isset($memberId) && isset($params['oauth-user-id'])){
					//if using main oAuth > $params['oauth-user-id'] should be set
					$memberId = $params['oauth-user-id'];
				}

				$userId = $oauth->getUserID();

				$memberDriver->initialiseCookie();
				$memberDriver->cookie->set('oauth', $oAuthName);
				$memberDriver->cookie->set('oauth-user-id', $userId);


							/*$userData = $oauth->getUserDetails();
							$xml = new XMLElement('entry');
							General::array_to_xml($xml,(array)$userData);

							$xml = new XMLElement('data',$xml);
							echo($xml->generate());die;*/

				// var_dump($userId);die('here');

				if (!isset($userId) || empty($userId)){
					//need to ask for a re-login as token is invalid
					return;
				}

				$new = false;

				//TODO Make Sure memberid is set
				if (!isset($memberId) || empty($memberId)){
					if (isset($membersExtensionId)){
						// check if this user already has an account
						$memberId = Symphony::Database()->fetchVar('member_id',0,"SELECT member_id FROM `tbl_oauth_token` WHERE user_id='{$userId}' AND `system` = '{$oAuthName}';");
						
						// or create a new entry for this user
						if (!isset($memberId) || empty($memberId)){

							//need to create a new member entry

							$userData = $oauth->getUserDetails();

							$xml = new XMLElement('entry');
							General::array_to_xml($xml,(array)$userData);

							$xml = new XMLElement('data',$xml);

							$dom = new DOMDocument();
							$dom->strictErrorChecking = false;
							$dom->loadXML($xml->generate(true));

							$XSLTfilename = WORKSPACE . '/oauth/'. $oAuthName . '.xsl';
							if (file_exists($XSLTfilename)) {
								$XSLProc = new XsltProcessor;

								$xslt = new DomDocument;
								$xslt->load($XSLTfilename);

								$XSLProc->importStyleSheet($xslt);

								// Set some context
								/*$XSLProc->setParameter('', array(
									'section-handle' => $section->get('handle'),
									'entry-id' => $entry->get('id')
								));*/

								$temp = $XSLProc->transformToDoc($dom);

								if ($temp instanceof DOMDocument) {
									$dom = $temp;
								}
							}
							
							$dataToInsert = XMLElement::convertFromDOMDocument('data',$dom);

							$children = $dataToInsert->getChildren();

							//get section and we can start filling in the data
							$data = array();

							foreach ($children as $key => $child) {
								$data[$child->getName()] = $child->getValue();
							}

							//check if there is already a user with the same email address

							$memberSections = extension_Members::initialiseMemberSections();
							$memberSection = current($memberSections);

							$emailField = current(FieldManager::fetch(null,$memberSection->getData()->id,'ASC','sortorder','memberemail'));

							$email = $data[$emailField->get('element_name')];

							if (isset($email)){
								$memberId = Symphony::Database()->fetchVar('entry_id',0,"SELECT entry_id FROM `tbl_entries_data_{$emailField->get('id')}` WHERE value='{$email}';");
							}


							if (!isset($memberId) || empty($memberId)){
								// if still no member id found proceed with creating a new one

								if (!$signUp){
									$redirectUrl = $params['root'] . '?no-account';
									header('Location: ' . $redirectUrl);
									exit;
								}

								$new = true;

								$entry = EntryManager::create();
								$entry->set('section_id',$memberSection->getData()->id);
								$entry->set('author_id',1);

								$entry->checkPostData($data,$errors);

								if (empty($errors)){
									$entry->setDataFromPost($data,$errors);
									$entry->commit();
									$memberId = $entry->get('id');
								} else {
									//log errors
									var_dump($entry->getData());
									var_dump($errors);die;
								}
							}

						}
					}
				}

				if (empty($memberId) && empty($userId)){
					//do not save if no member id for now
					return;
				}

				//we gotta store these in db
				$fields = array('member_id'=>$memberId,'user_id'=>$userId,'system'=>$oAuthName,'token'=>$oauth_token,'token_secret'=>$oauth_token_secret);
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

			if ($memberId){
				$redirectUrl = $this->getRedirect(current(EntryManager::fetch($memberId)),$oAuthName,$new,$linked);
			}
			
			// var_dump($access_token_credentials);die;
			if (!isset($redirectUrl)){
				$redirectUrl = $params['root'];
			}
			header('Location: ' . $redirectUrl);
			exit;
		}
	}
