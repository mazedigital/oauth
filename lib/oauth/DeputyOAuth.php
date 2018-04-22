<?php

/**

Copyright (c) 2012, Jon Mifsud.
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

 - Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer.

 - Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.


THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

require_once("oAuthv2.php");

class DeputyOAuth extends oAuthv2 {
	
	private $_deputy_oauth_base_url = 'https://once.deputy.com/my/oauth';
	private $_deputy_api_base_url = '/api/v1';
	private $_endpoint = 'https://';

	private $_deputy_access_token_uri   = '/access_token';
	private $_deputy_authenticate_uri   = '/login';

	public $oAuthHeader = true;
	public $sendByPost = true;
	public $jsonEncoded = true;

	function __construct($consumer_key, $consumer_secret, $token = null, $token_secret = null) {
		/*Token Secret Not used in V2*/
		
		$this->consumer_key = $consumer_key;
		$this->consumer_secret = $consumer_secret;

		if (!is_null($token)) {
			$this->token = $token;

			$query = "SELECT `endpoint` FROM `tbl_oauth_token` where `token` = '{$token}' and `system`='deputy'";
			$endpoint = Symphony::Database()->fetchVar('endpoint',0,$query);

			$this->_endpoint = 'https://'.$endpoint;
			// get endpoint from database.
			// $this->token_secret = $token_secret;
		}

		$this->oauth_base_url = $this->_deputy_oauth_base_url;
		$this->api_base_url = $this->_endpoint . $this->_deputy_api_base_url;
		// $this->request_token_url = $this->_deputy_oauth_base_url . $this->_deputy_request_token_uri;
		$this->access_token_url = $this->_deputy_oauth_base_url . $this->_deputy_access_token_uri;
		$this->authenticate_url = $this->_deputy_oauth_base_url . $this->_deputy_authenticate_uri;
		// $this->authorize_url = $this->_deputy_oauth_base_url . $this->_deputy_authorize_uri;
	}
	
	public function getUserID(){
		return null;
	}


	public function getOrgID(){

		$deputy_json = $this->request('me/');
		$this->_deputy_user_details = json_decode($deputy_json);

		return $this->_deputy_user_details->Company;
	}

}

?>
