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

class NationalFieldOAuth extends oAuthv2 {

	// private $_nationalfield_oauth_base_url = 'https://hubnet.nationalfield.org/oauth';
	// private $_nationalfield_api_base_url = 'https://hubnet.nationalfield.org/v1';
	
	private $_nationalfield_oauth_base_url = 'http://hubnet.nationalfield.org/oauth';
	private $_nationalfield_api_base_url = 'http://hubnet.nationalfield.org/api/v1';

	// private $_nationalfield_request_token_uri  = '/requestToken';
	private $_nationalfield_access_token_uri   = '/access_token';
	private $_nationalfield_authenticate_uri   = '/authenticate';
	// private $_nationalfield_authorize_uri	  = '/authorize';

	function __construct($consumer_key, $consumer_secret, $token = null, $token_secret = null) {
		/*Token Secret Not used in V2*/
		
		$this->consumer_key = $consumer_key;
		$this->consumer_secret = $consumer_secret;

		if (!is_null($token)) {
			$this->token = $token;
			// $this->token_secret = $token_secret;
		}

		$this->oauth_base_url = $this->_nationalfield_oauth_base_url;
		$this->api_base_url = $this->_nationalfield_api_base_url;
		// $this->request_token_url = $this->_nationalfield_oauth_base_url . $this->_nationalfield_request_token_uri;
		$this->access_token_url = $this->_nationalfield_oauth_base_url . $this->_nationalfield_access_token_uri;
		$this->authenticate_url = $this->_nationalfield_oauth_base_url . $this->_nationalfield_authenticate_uri;
		// $this->authorize_url = $this->_nationalfield_oauth_base_url . $this->_nationalfield_authorize_uri;
	}
	
	/*NationalField Version*/
	public function getUserID(){
		$facebook_json = $this->request('users/me');
		$facebookDetails = json_decode($facebook_json);
		// var_dump($facebook_json);die;
		return (string)$facebookDetails->id;
	}

}

?>
