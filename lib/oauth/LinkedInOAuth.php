<?php

/**

Copyright (c) 2011, PandaWhale, Inc.
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

 - Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer.

 - Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.

 - Neither the name of PandaWhale, Inc. nor the names of its contributors may be
   used to endorse or promote products derived from this software without
   specific prior written permission.

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

require_once("oAuthv1.php");

class LinkedInOAuth extends oAuthv1 {

    private $_linkedin_oauth_base_url = 'https://api.linkedin.com/uas/oauth';
    private $_linkedin_api_base_url = 'https://api.linkedin.com/v1';

    private $_linkedin_request_token_uri  = '/requestToken';
    private $_linkedin_access_token_uri   = '/accessToken';
    private $_linkedin_authenticate_uri   = '/authenticate';
    private $_linkedin_authorize_uri      = '/authorize';

    function __construct($consumer_key, $consumer_secret, $token = null, $token_secret = null) {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;

        if (!is_null($token) && !is_null($token_secret)) {
            $this->token = $token;
            $this->token_secret = $token_secret;
        }

        $this->oauth_base_url = $this->_linkedin_oauth_base_url;
        $this->api_base_url = $this->_linkedin_api_base_url;
        $this->request_token_url = $this->_linkedin_oauth_base_url . $this->_linkedin_request_token_uri;
        $this->access_token_url = $this->_linkedin_oauth_base_url . $this->_linkedin_access_token_uri;
        $this->authenticate_url = $this->_linkedin_oauth_base_url . $this->_linkedin_authenticate_uri;
        $this->authorize_url = $this->_linkedin_oauth_base_url . $this->_linkedin_authorize_uri;
    }
	
	/*Linkedin Version*/
	public function getUserID(){
		$linkedin_xml = $this->request('people/~:(id)',$object);
		$xml = simplexml_load_string ($linkedin_xml);
		// var_dump($xml->id);die;
		return (string)$xml->id;
	}

}

?>
