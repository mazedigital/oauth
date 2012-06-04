<?php

	require_once TOOLKIT . '/class.datasource.php';
	require_once FACE . '/interface.datasource.php';

	Class OAuthDatasource extends DataSource implements iDatasource {

		private static $url_result = null;

		public static function getName() {
			return __('oAuth Datasource');
		}

		public static function getClass() {
			return __CLASS__;
		}

		public function getSource() {
			return self::getClass();
		}

		public static function getTemplate(){
			return EXTENSIONS . '/oauth/templates/blueprints.datasource.tpl';
		}

		public function settings() {
			$settings = array();

			$settings[self::getClass()]['system'] = $this->dsParamSYSTEM;

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
				return 'oAuth Datasource';
			}
		}


	/*-------------------------------------------------------------------------
		Editor
	-------------------------------------------------------------------------*/

		public static function buildEditor(XMLElement $wrapper, array &$errors = array(), array $settings = null, $handle = null) {
			if(!is_null($handle)) {
				$cache = new Cacheable(Symphony::Database());
				$cache_id = md5(
					$settings[self::getClass()]['system'] 
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

			// System to use for oAuth Login
			$group = new XMLElement('div');
			$group->setAttribute('class', 'two columns');

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
			
			$fieldset->appendChild($group);
			
			$wrapper->appendChild($fieldset);
		}

		public static function validate(array &$settings, array &$errors) {
			if(trim($settings[self::getClass()]['system']) == '') {
				$errors[self::getClass()]['system'] = __('This is a required field');
			}

			return empty($errors[self::getClass()]);
		}

		public static function prepare(array $settings, array $params, $template) {
			
			return sprintf($template,
				$params['rootelement'], // rootelement
				$settings[self::getClass()]['system'] // system
			);
		}

	/*-------------------------------------------------------------------------
		Execution
	-------------------------------------------------------------------------*/

		public function grab(array &$param_pool=null) {
			$result = new XMLElement($this->dsParamROOTELEMENT);

			try {				
				$oAuthExtension = ExtensionManager::create('oauth');
				$oAuthExtension->oAuthStatus($this->dsParamSYSTEM,$result);
			}
			catch(Exception $e){
				$result->appendChild(new XMLElement('error', $e->getMessage()));
			}

			if($this->_force_empty_result) $result = $this->emptyXMLSet();
			
			
			$paramPoolVar = 'ds-' . $this->dsParamROOTELEMENT;
			
			if ($result->getAttribute('logged-in') == 'yes'){
				$param_pool[$paramPoolVar . '.token'] = (string)$result->getAttribute('token');
			}
			/**
			 * Immediately after building entries allow modification of the Data Source entry list
			 *
			 * @delegate DataSourceResultBuilt
			 * @param string $context
			 * '/frontend/'
			 * @param Datasource $datasource
			 * @param array $result
			 * @param array $filters
			 */
		/*	 $page = Frontend::Page();
			Symphony::ExtensionManager()->notifyMembers('DataSourceResultBuilt', '/frontend/', array(
				'datasource' => &$this,
				'result' => &$result,
				'parampool' => &$param_pool,
				// 'parampool' => &$page->_param,
				'filters' => $this->dsParamFILTERS
			));*/
			
			// var_dump($param_pool);die;
			
			return $result;
		}
	}

	return 'RemoteDatasource';