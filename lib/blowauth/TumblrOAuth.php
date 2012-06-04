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

require_once("BlowAuth.php");

class TumblrOAuth extends BlowAuth {

    private $_tumblr_oauth_base_url = 'http://www.tumblr.com/oauth';
    private $_tumblr_api_base_url = 'http://www.tumblr.com/api';

    private $_tumblr_request_token_uri  = '/request_token';
    private $_tumblr_access_token_uri   = '/access_token';
    // Tumblr does not currently support the "authenticate" method
    private $_tumblr_authenticate_uri   = '/authorize';
    private $_tumblr_authorize_uri      = '/authorize';

    function __construct($consumer_key, $consumer_secret, $token = null, $token_secret = null) {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;

        if (!is_null($token) && !is_null($token_secret)) {
            $this->token = $token;
            $this->token_secret = $token_secret;
        }

        $this->oauth_base_url = $this->_tumblr_oauth_base_url;
        $this->api_base_url = $this->_tumblr_api_base_url;
        $this->request_token_url = $this->_tumblr_oauth_base_url . $this->_tumblr_request_token_uri;
        $this->access_token_url = $this->_tumblr_oauth_base_url . $this->_tumblr_access_token_uri;
        $this->authenticate_url = $this->_tumblr_oauth_base_url . $this->_tumblr_authenticate_uri;
        $this->authorize_url = $this->_tumblr_oauth_base_url . $this->_tumblr_authorize_uri;
    }

}

?>
