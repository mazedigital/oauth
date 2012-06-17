<?php

/**

Copyright (c) 2012, Jon Mifsud
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

class FacebookOAuth extends oAuthv2 {

	private $_facebook_oauth_base_url = 'https://graph.facebook.com/oauth';
	private $_facebook_api_base_url = 'https://graph.facebook.com';

	// private $_facebook_request_token_uri  = '/requestToken';
	private $_facebook_access_token_uri   = '/access_token';
	private $_facebook_authenticate_uri   = 'https://www.facebook.com/dialog/oauth';
	private $_facebook_logout_uri = 'https://www.facebook.com/logout.php?next=';
	// private $_facebook_authorize_uri	  = '/authorize';

	function __construct($consumer_key, $consumer_secret, $token = null, $token_secret = null) {
		/*Token Secret Not used in V2*/
		
		$this->consumer_key = $consumer_key;
		$this->consumer_secret = $consumer_secret;

		if (!is_null($token)) {
			$this->token = $token;
			// $this->token_secret = $token_secret;
		}

		$this->oauth_base_url = $this->_facebook_oauth_base_url;
		$this->api_base_url = $this->_facebook_api_base_url;
		// $this->request_token_url = $this->_facebook_oauth_base_url . $this->_facebook_request_token_uri;
		$this->access_token_url = $this->_facebook_oauth_base_url . $this->_facebook_access_token_uri;
		$this->authenticate_url = $this->_facebook_authenticate_uri;
		// $this->authorize_url = $this->_facebook_oauth_base_url . $this->_facebook_authorize_uri;
	}
	
	
	/*Linkedin Version*/
	public function getUserID(){
		$facebook_json = $this->request('me/',$object);
		$facebookDetails = json_decode($facebook_json);
		// var_dump($facebookDetails->id);die;
		return (string)$facebookDetails->id;
	}

}

?>
