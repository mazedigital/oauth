<?php

	require_once TOOLKIT . '/class.datasource.php';
	require_once FACE . '/interface.datasource.php';

	Class OAuthRemoteDatasource extends DataSource implements iDatasource {

		private static $url_result = null;

		public static function getName() {
			return __('oAuthRemote Datasource');
		}

		public static function getClass() {
			return __CLASS__;
		}

		public function getSource() {
			return self::getClass();
		}

		public static function getTemplate(){
			return EXTENSIONS . '/oauth/templates/blueprints.oauth_remote_datasource.tpl';
		}

		public function settings() {
			$settings = array();

			$settings[self::getClass()]['namespace'] = $this->dsParamNAMESPACES;
			$settings[self::getClass()]['system'] = $this->dsParamSYSTEM;
			$settings[self::getClass()]['path'] = $this->dsParamPATH;
			$settings[self::getClass()]['xpath'] = $this->dsParamXPATH;
			$settings[self::getClass()]['cache'] = $this->dsParamCACHE;
			$settings[self::getClass()]['format'] = $this->dsParamFORMAT;
			$settings[self::getClass()]['timeout'] = isset($this->dsParamTIMEOUT) ? $this->dsParamTIMEOUT : 6;

			return $settings;
		}

	/*-------------------------------------------------------------------------
		Utilities
	-------------------------------------------------------------------------*/

		/**
		 * Returns the source value for display in the Datasources index
		 *
		 * @param string $file
		 *  The path to the Datasource file
		 * @return string
		 */
		public function getSourceColumn($handle) {
			$datasource = DatasourceManager::create($handle, array(), false);

			if(isset($datasource->dsParamSYSTEM)) {
				return $datasource->dsParamSYSTEM;
			}
			else {
				return 'Remote Datasource';
			}
		}

		/**
		 * Given a `$url` and `$timeout`, this function will use the `Gateway`
		 * class to determine that it is a valid URL and returns successfully
		 * before the `$timeout`. If it does not, an error message will be
		 * returned, otherwise true.
		 *
		 * @param string $url
		 * @param integer $timeout
		 *  If not provided, this will default to 6 seconds
		 * @param boolean $fetch_URL
		 *  Defaults to false, but when set to true, this function will use the
		 *  `Gateway` class to attempt to validate the URL's existence and it
		 *  returns before the `$timeout`
		 * @return string|array
		 *  Returns an array with the 'data' if it is a valid URL, otherwise a string
		 *  containing an error message.
		 */
		public static function isValidURL($url, $timeout = 6, $fetch_URL = false) {
			// Check that URL was provided
			if(trim($url) == '') {
				return __('This is a required field');
			}
			// Check to see the URL works.
			else if ($fetch_URL === true) {
				$gateway = new Gateway;
				$gateway->init($url);
				$gateway->setopt('TIMEOUT', $timeout);
				$data = $gateway->exec();

				$info = $gateway->getInfoLast();

				// 28 is CURLE_OPERATION_TIMEOUTED
				if($info['curl_error'] == 28) {
					return __('Request timed out. %d second limit reached.', array($timeout));
				}
				else if($data === false || $info['http_code'] != 200) {
					return __('Failed to load URL, status code %d was returned.', array($info['http_code']));
				}
			}

			return array('data' => $data);
		}

		/**
		 * Builds the namespaces out to be saved in the Datasource file
		 *
		 * @param array $namespaces
		 *  An associative array of where the key is the namespace prefix
		 *  and the value is the namespace URL.
		 * @param string $template
		 *  The template file, as defined by `getTemplate()`
		 * @return string
		 *  The template injected with the Namespaces (if any).
		 */
		public static function injectNamespaces(array $namespaces, &$template) {
			if(empty($namespaces)) return;

			$placeholder = '<!-- NAMESPACES -->';
			$string = 'public $dsParamNAMESPACES = array(' . PHP_EOL;

			foreach($namespaces as $key => $val){
				if(trim($val) == '') continue;
				$string .= "\t\t\t'$key' => '" . addslashes($val) . "'," . PHP_EOL;
			}

			$string .= "\t\t);" . PHP_EOL . "\t\t" . $placeholder;
			$template = str_replace($placeholder, trim($string), $template);
		}

		/**
		 * Helper function to build Cache information block
		 *
		 * @param XMLElement $wrapper
		 * @param Cacheable $cache
		 * @param string $cache_id
		 */
		public static function buildCacheInformation(XMLElement $wrapper, Cacheable $cache, $cache_id) {
			$cachedData = $cache->check($cache_id);
			if(is_array($cachedData) && !empty($cachedData) && (time() < $cachedData['expiry'])) {
				$a = Widget::Anchor(__('Clear now'), SYMPHONY_URL . getCurrentPage() . 'clear_cache/');
				$wrapper->appendChild(
					new XMLElement('p', __('Cache expires in %d minutes. %s', array(
						($cachedData['expiry'] - time()) / 60,
						$a->generate(false)
					)), array('class' => 'help'))
				);
			}
			else {
				$wrapper->appendChild(
					new XMLElement('p', __('Cache has expired or does not exist.'), array('class' => 'help'))
				);
			}
		}

	/*-------------------------------------------------------------------------
		Editor
	-------------------------------------------------------------------------*/

		public static function buildEditor(XMLElement $wrapper, array &$errors = array(), array $settings = null, $handle = null) {
			//TODO if path includes me?? or another similar variable we should maybe suggest users to disable cache.
			//or otherwise add a checkbox that stores the user-id as part of the cache id when active?
			if(!is_null($handle)) {
				$cache = new Cacheable(Symphony::Database());
				$cache_id = md5(
					$settings[self::getClass()]['system'] .
					$settings[self::getClass()]['path'] .
					serialize($settings[self::getClass()]['namespaces']) .
					$settings[self::getClass()]['xpath'] .
					$settings[self::getClass()]['format']
				);
			}

			// If `clear_cache` is set, clear it..
			if(isset($cache_id) && in_array('clear_cache', Administration::instance()->Page->getContext())) {
				$cache->forceExpiry($cache_id);
				Administration::instance()->Page->pageAlert(
					__('Data source cache cleared at %s.', array(DateTimeObj::getTimeAgo()))
					. '<a href="' . SYMPHONY_URL . '/blueprints/datasources/" accesskey="a">'
					. __('View all Data sources')
					. '</a>'
					, Alert::SUCCESS);
			}

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings contextual ' . __CLASS__);
			$fieldset->appendChild(new XMLElement('legend', self::getName()));

			$group = new XMLElement('div');
			$group->setAttribute('class', 'two columns');

			// oAuth System
			$label = Widget::Label(__('oAuth System'));
			$label->setAttribute('class', 'primary column');
			$label->appendChild(
				Widget::Select('fields[' . self::getClass() . '][system]', array(
					array('linkedin', $settings[self::getClass()]['system'] == 'linkedin', 'LinkedIn'),
					array('nationalfield', $settings[self::getClass()]['system'] == 'nationalfield', 'NationalField'),
					array('twitter', $settings[self::getClass()]['system'] == 'twitter', 'Twitter'),
					array('facebook', $settings[self::getClass()]['system'] == 'facebook', 'Facebook')
				))
			);
			if(isset($errors[self::getClass()]['system'])) $group->appendChild(Widget::Error($label, $errors[self::getClass()]['system']));
			else $group->appendChild($label);
			
			// PATH
			$label = Widget::Label(__('PATH'));
			$label->setAttribute('class', 'primary column');
			$label->appendChild(Widget::Input('fields[' . self::getClass() . '][path]', General::sanitize($settings[self::getClass()]['path']), 'text', array('placeholder' => 'http://')));
			if(isset($errors[self::getClass()]['path'])) {
				$group->appendChild(Widget::Error($label, $errors[self::getClass()]['path']));
			}
			else {
				$group->appendChild($label);
			}

			$p = new XMLElement('p',
				__('Use %s syntax to specify dynamic portions of the URL.', array(
					'<code>{' . __('$param') . '}</code>'
				))
			);
			$p->setAttribute('class', 'help');
			$label->appendChild($p);

			$label = Widget::Label(__('Format'));
			$label->setAttribute('class', 'secondary column');
			$label->appendChild(
				Widget::Select('fields[' . self::getClass() . '][format]', array(
					array('xml', $settings[self::getClass()]['format'] == 'xml', 'XML'),
					array('json', $settings[self::getClass()]['format'] == 'json', 'JSON')
				))
			);
			if(isset($errors[self::getClass()]['format'])) $group->appendChild(Widget::Error($label, $errors[self::getClass()]['format']));
			else $group->appendChild($label);

			$fieldset->appendChild($group);

			// Namespaces
			$div = new XMLElement('div');
			$p = new XMLElement('p', __('Namespace Declarations'));
			$p->appendChild(new XMLElement('i', __('Optional')));
			$p->setAttribute('class', 'label');
			$div->appendChild($p);

			$ol = new XMLElement('ol');
			$ol->setAttribute('class', 'filters-duplicator');
			$ol->setAttribute('data-add', __('Add namespace'));
			$ol->setAttribute('data-remove', __('Remove namespace'));

			if(is_array($settings[self::getClass()]['namespace']) && !empty($settings[self::getClass()]['namespace'])){
				$ii = 0;
				foreach($settings[self::getClass()]['namespace'] as $name => $uri) {
					// Namespaces get saved to the file as $name => $uri, however in
					// the $_POST they are represented as $index => array. This loop
					// patches the difference.
					if(is_array($uri)) {
						$name = $uri['name'];
						$uri = $uri['uri'];
					}

					$li = new XMLElement('li');
					$li->setAttribute('class', 'instance');
					$header = new XMLElement('header');
					$header->appendChild(
						new XMLElement('h4', __('Namespace'))
					);
					$li->appendChild($header);

					$group = new XMLElement('div');
					$group->setAttribute('class', 'two columns');

					$label = Widget::Label(__('Name'));
					$label->setAttribute('class', 'column');
					$label->appendChild(Widget::Input("fields[" . self::getClass() . "][namespace][$ii][name]", General::sanitize($name)));
					$group->appendChild($label);

					$label = Widget::Label(__('URI'));
					$label->setAttribute('class', 'column');
					$label->appendChild(Widget::Input("fields[" . self::getClass() . "][namespace][$ii][uri]", General::sanitize($uri)));
					$group->appendChild($label);

					$li->appendChild($group);
					$ol->appendChild($li);
					$ii++;
				}
			}

			$li = new XMLElement('li');
			$li->setAttribute('class', 'template');
			$header = new XMLElement('header');
			$header->appendChild(
				new XMLElement('h4', __('Namespace'))
			);
			$li->appendChild($header);

			$group = new XMLElement('div');
			$group->setAttribute('class', 'two columns');

			$label = Widget::Label(__('Name'));
			$label->setAttribute('class', 'column');
			$label->appendChild(Widget::Input('fields[' . self::getClass() . '][namespace][-1][name]'));
			$group->appendChild($label);

			$label = Widget::Label(__('URI'));
			$label->setAttribute('class', 'column');
			$label->appendChild(Widget::Input('fields[' . self::getClass() . '][namespace][-1][uri]'));
			$group->appendChild($label);

			$li->appendChild($group);
			$ol->appendChild($li);

			$div->appendChild($ol);
			$div->appendChild(
				new XMLElement('p', __('Namespaces will automatically be discovered when saving this datasource if it does not include any dynamic portions.'), array('class' => 'help'))
			);

			$fieldset->appendChild($div);

			// Included Elements
			$label = Widget::Label(__('Included Elements'));
			$label->appendChild(Widget::Input('fields[' . self::getClass() . '][xpath]', General::sanitize($settings[self::getClass()]['xpath'])));
			if(isset($errors[self::getClass()]['xpath'])) $fieldset->appendChild(Widget::Error($label, $errors[self::getClass()]['xpath']));
			else $fieldset->appendChild($label);

			$p = new XMLElement('p', __('Use an XPath expression to select which elements from the source XML to include.'));
			$p->setAttribute('class', 'help');
			$fieldset->appendChild($p);

			// Caching
			$label = Widget::Label();
			$input = Widget::Input('fields[' . self::getClass() . '][cache]', (string)max(1, intval($settings[self::getClass()]['cache'])), NULL, array('size' => '6'));
			$label->setValue(__('Update cached result every %s minutes', array($input->generate(false))));
			if(isset($errors[self::getClass()]['cache'])) $fieldset->appendChild(Widget::Error($label, $errors[self::getClass()]['cache']));
			else $fieldset->appendChild($label);

			// Check for existing Cache objects
			if(isset($cache_id)) {
				self::buildCacheInformation($fieldset, $cache, $cache_id);
			}

			// Timeout
			$label = Widget::Label();
			$input = Widget::Input('fields[' . self::getClass() . '][timeout]', (string)max(1, intval($settings[self::getClass()]['timeout'])), NULL, array('type' => 'hidden'));
			$label->appendChild($input);
			$fieldset->appendChild($label);

			$wrapper->appendChild($fieldset);
		}

		public static function validate(array &$settings, array &$errors) {
			if(trim($settings[self::getClass()]['system']) == '') {
				$errors[self::getClass()]['system'] = __('This is a required field');
			}
			
			if(trim($settings[self::getClass()]['path']) == '') {
				$errors[self::getClass()]['path'] = __('This is a required field');
			}

			// Use the TIMEOUT that was specified by the user for a real world indication
			$timeout = isset($settings[self::getClass()]['timeout'])
				? (int)$settings[self::getClass()]['timeout']
				: 6;

			//NO URL Validation takes place as tokens are dynamically added in

			if(trim($settings[self::getClass()]['xpath']) == '') {
				$errors[self::getClass()]['xpath'] = __('This is a required field');
			}

			if(!is_numeric($settings[self::getClass()]['cache'])) {
				$errors[self::getClass()]['cache'] = __('Must be a valid number');
			}
			else if($settings[self::getClass()]['cache'] < 1) {
				$errors[self::getClass()]['cache'] = __('Must be greater than zero');
			}

			return empty($errors[self::getClass()]);
		}

		public static function prepare(array $settings, array $params, $template) {
			//Automatic Name-Space is not possible as we do not have the tokens

			$namespaces = array();
			if(is_array($settings[self::getClass()]['namespace'])) {
				foreach($settings[self::getClass()]['namespace'] as $index => $data) {
					$namespaces[$data['name']] = $data['uri'];
				}
			}
			self::injectNamespaces($namespaces, $template);

			$timeout = isset($settings[self::getClass()]['timeout'])
				? (int)$settings[self::getClass()]['timeout']
				: 6;

			return sprintf($template,
				$params['rootelement'], // rootelement
				$settings[self::getClass()]['system'], // path
				$settings[self::getClass()]['path'], // system
				$settings[self::getClass()]['format'], // format
				$settings[self::getClass()]['xpath'], // xpath
				$settings[self::getClass()]['cache'], // cache
				$timeout// timeout
			);
		}

	/*-------------------------------------------------------------------------
		Execution
	-------------------------------------------------------------------------*/

		public function grab(array &$param_pool=null) {
			$result = new XMLElement($this->dsParamROOTELEMENT);

			try {
				require_once(TOOLKIT . '/class.gateway.php');
				require_once(TOOLKIT . '/class.xsltprocess.php');
				require_once(CORE . '/class.cacheable.php');

				$this->dsParamPATH = $this->parseParamURL($this->dsParamPATH);

				if(isset($this->dsParamXPATH)) $this->dsParamXPATH = $this->__processParametersInString($this->dsParamXPATH, $this->_env);

				if(!isset($this->dsParamFORMAT)) $this->dsParamFORMAT = 'xml';

				// Builds a Default Stylesheet to transform the resulting XML with
				$stylesheet = new XMLElement('xsl:stylesheet');
				$stylesheet->setAttributeArray(array('version' => '1.0', 'xmlns:xsl' => 'http://www.w3.org/1999/XSL/Transform'));

				$output = new XMLElement('xsl:output');
				$output->setAttributeArray(array('method' => 'xml', 'version' => '1.0', 'encoding' => 'utf-8', 'indent' => 'yes', 'omit-xml-declaration' => 'yes'));
				$stylesheet->appendChild($output);

				$template = new XMLElement('xsl:template');
				$template->setAttribute('match', '/');

				$instruction = new XMLElement('xsl:copy-of');

				// Namespaces
				if(isset($this->dsParamNAMESPACES) && is_array($this->dsParamNAMESPACES)){
					foreach($this->dsParamNAMESPACES as $name => $uri) {
						$instruction->setAttribute('xmlns' . ($name ? ":{$name}" : NULL), $uri);
					}
				}

				// XPath
				$instruction->setAttribute('select', $this->dsParamXPATH);

				$template->appendChild($instruction);
				$stylesheet->appendChild($template);
				$stylesheet->setIncludeHeader(true);

				$xsl = $stylesheet->generate(true);

				// Check for an existing Cache for this Datasource
				$cache_id = md5($this->dsParamSYSTEM .$this->dsParamPATH . serialize($this->dsParamNAMESPACES) . $this->dsParamXPATH . $this->dsParamFORMAT);
				$cache = new Cacheable(Symphony::Database());

				$cachedData = $cache->check($cache_id);
				$writeToCache = false;
				$valid = true;
				$creation = DateTimeObj::get('c');

				// Execute if the cache doesn't exist, or if it is old.
				if(
					(!is_array($cachedData) || empty($cachedData)) // There's no cache.
					|| (time() - $cachedData['creation']) > ($this->dsParamCACHE * 60) // The cache is old.
				){
					if(Mutex::acquire($cache_id, $this->dsParamTIMEOUT, TMP) || true) {
						$oAuthExtension = ExtensionManager::create('oauth');
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
						
						$oAuthClassname= $oAuthExtension->supportedOAuth[$this->dsParamSYSTEM] .'OAuth';
						$oAuthName = $this->dsParamSYSTEM;
						require_once(EXTENSIONS . "/oauth/lib/blowauth/{$oAuthClassname}.php");
										
						$clientId = Symphony::Configuration()->get('client_id', $oAuthName . 'oauth');
						$secret = Symphony::Configuration()->get('secret', $oAuthName . 'oauth');
						
						$oauth = new $oAuthClassname($clientId, $secret, $token, $token_secret);
						// var_dump($clientId);var_dump($secret);var_dump($token);var_dump($token_secret);die;
						$ch = curl_init();
						//we want to handle 400 errors ourselves and parse any expired tokens
						curl_setopt($ch, CURLOPT_FAILONERROR, false);
						curl_setopt($ch, CURLOPT_HTTP200ALIASES, (array)400);
						curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
						
						//extract url params out of path
						$path = explode('?',$this->dsParamPATH);
						$params = array();
						if (!empty($path[1])){
							$parts = explode('&',$path[1]);
							foreach ($parts as $part){
								$queryString = explode('=',$part);
								$params[$queryString[0]]=$queryString[1];
							}
						}
						$data = $oauth->request($path[0],$ch,'GET',$params);
						//TODO handling of 400 Token Expired errors - to obtain new-token and re-direct to the correct page
						// var_dump($data);die;
						/*
						$ch = new Gateway;
						$ch->init($this->dsParamURL);
						$ch->setopt('TIMEOUT', $this->dsParamTIMEOUT);

						//TODO is below really required
						// Set the approtiate Accept: headers depending on the format of the URL.
						if($this->dsParamFORMAT == 'xml') {
							$ch->setopt('HTTPHEADER', array('Accept: text/xml, * /*'));
						}
						else {
							$ch->setopt('HTTPHEADER', array('Accept: application/json, * /*'));
						}

						$data = $ch->exec();*/
						// var_dump($ch);die;
						$info = curl_getinfo($ch);
						// var_dump($info);die;

						Mutex::release($cache_id, TMP);

						$data = trim($data);
						$writeToCache = true;

						// NEED TO FIND AN ALTERNATIVE WAY TO GET THE ERROR CODES 
						
						// Handle any response that is not a 200, or the content type does not include XML, JSON, plain or text
						if((int)$info['http_code'] != 200 || !preg_match('/(xml|json|plain|text)/i', $info['content_type'])){
							$writeToCache = false;

							$result->setAttribute('valid', 'false');

							// 28 is CURLE_OPERATION_TIMEOUTED
							if($info['curl_error'] == 28) {
								$result->appendChild(
									new XMLElement('error',
										sprintf('Request timed out. %d second limit reached.', $timeout)
									)
								);
							}
							else{
								$result->appendChild(
									new XMLElement('error',
										sprintf('Status code %d was returned. Content-type: %s', $info['http_code'], $info['content_type'])
									)
								);
							}

							return $result;
						}

						// Handle where there is `$data`
						else if(strlen($data) > 0) {
							// If it's JSON, convert it to XML
							if($this->dsParamFORMAT == 'json') {
								try {
									require_once TOOLKIT . '/class.json.php';
									$data = JSON::convertToXML($data);
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

								return $result;
							}
						}
						// If `$data` is empty, set the `force_empty_result` to true.
						else if(strlen($data) == 0){
							$this->_force_empty_result = true;
						}
					}

					// Failed to acquire a lock
					else {
						$result->appendChild(
							new XMLElement('error', __('The %s class failed to acquire a lock.', array('<code>Mutex</code>')))
						);
					}
				}

				// The cache is good, use it!
				else {
					$data = trim($cachedData['data']);
					$creation = DateTimeObj::get('c', $cachedData['creation']);
				}

				// If `$writeToCache` is set to false, invalidate the old cache if it existed.
				if(is_array($cachedData) && !empty($cachedData) && $writeToCache === false) {
					$data = trim($cachedData['data']);
					$valid = false;
					$creation = DateTimeObj::get('c', $cachedData['creation']);

					if(empty($data)) $this->_force_empty_result = true;
				}

				// If `force_empty_result` is false and `$result` is an instance of
				// XMLElement, build the `$result`.
				if(!$this->_force_empty_result && is_object($result)) {
					$proc = new XsltProcess;
					$ret = $proc->process($data, $xsl);

					if($proc->isErrors()) {
						$result->setAttribute('valid', 'false');
						$error = new XMLElement('error', __('Transformed XML is invalid.'));
						$result->appendChild($error);
						$errors = new XMLElement('errors');
						foreach($proc->getError() as $e) {
							if(strlen(trim($e['message'])) == 0) continue;
							$errors->appendChild(new XMLElement('item', General::sanitize($e['message'])));
						}
						$result->appendChild($errors);
					}

					else if(strlen(trim($ret)) == 0) {
						$this->_force_empty_result = true;
					}

					else {
						if($writeToCache) $cache->write($cache_id, $data, $this->dsParamCACHE);

						$result->setValue(PHP_EOL . str_repeat("\t", 2) . preg_replace('/([\r\n]+)/', "$1\t", $ret));
						$result->setAttribute('status', ($valid === true ? 'fresh' : 'stale'));
						$result->setAttribute('creation', $creation);
					}
				}
			}
			catch(Exception $e){
				$result->appendChild(new XMLElement('error', $e->getMessage()));
			}

			if($this->_force_empty_result) $result = $this->emptyXMLSet();
			
			return $result;
		}
	}