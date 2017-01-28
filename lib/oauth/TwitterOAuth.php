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

class TwitterOAuth extends oAuthv1 {

    private $_twitter_oauth_base_url = 'https://api.twitter.com/oauth';
    private $_twitter_api_base_url = 'https://api.twitter.com/1.1';

    private $_twitter_request_token_uri  = '/request_token';
    private $_twitter_access_token_uri   = '/access_token';
    private $_twitter_authenticate_uri   = '/authenticate';
    private $_twitter_authorize_uri      = '/authorize';

    function __construct($consumer_key, $consumer_secret, $token = null, $token_secret = null) {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;

        if (!is_null($token) && !is_null($token_secret)) {
            $this->token = $token;
            $this->token_secret = $token_secret;
        }

        $this->oauth_base_url = $this->_twitter_oauth_base_url;
        $this->api_base_url = $this->_twitter_api_base_url;
        $this->request_token_url = $this->_twitter_oauth_base_url . $this->_twitter_request_token_uri;
        $this->access_token_url = $this->_twitter_oauth_base_url . $this->_twitter_access_token_uri;
        $this->authenticate_url = $this->_twitter_oauth_base_url . $this->_twitter_authenticate_uri;
        $this->authorize_url = $this->_twitter_oauth_base_url . $this->_twitter_authorize_uri;
    }


    
    /*Facbook Version*/
    public function getUserID(){
        if (!isset($this->_twitter_user_details)){
            $this->getUserDetails();
        }

        var_dump($this->_twitter_user_details);die;

            die('test');
        return (string)$this->_twitter_user_details->id;
    }
    
    /*Facbeook Version*/
    public function getUserDetails(){
        if (isset($this->_twitter_user_details)){
            return $this->_twitter_user_details;
        } else {
            $twitter_json = $this->request('account/verify_credentials.json',$object, $http_method = 'GET', $extra_params = array('include_email'=>'true','skip_status'=>'true'));
            $this->_twitter_user_details = json_decode($twitter_json);
            return $this->_twitter_user_details;
        }
    }

}

?>