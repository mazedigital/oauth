<?php

	require_once(EXTENSIONS . '/oauth/data-sources/datasource.oauth.php');

	Class datasource<!-- CLASS NAME --> extends OAuthDatasource {

		public $dsParamROOTELEMENT = '%s';
		public $dsParamSYSTEM = '%s';

		<!-- NAMESPACES -->

		public function __construct($env=NULL, $process_params=true){
			parent::__construct($env, $process_params);
			$this->_dependencies = array(<!-- DS DEPENDENCY LIST -->);
		}

		public function about(){
			return array(
				'name' => '<!-- NAME -->',
				'author' => array(
					'name' => '<!-- AUTHOR NAME -->',
					'website' => '<!-- AUTHOR WEBSITE -->',
					'email' => '<!-- AUTHOR EMAIL -->'),
				'version' => '<!-- VERSION -->',
				'release-date' => '<!-- RELEASE DATE -->'
			);
		}

		public function allowEditorToParse(){
			return true;
		}

	}
