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

class BlowAuth
{
	public static $oauth_version = '1.0';
	public $signature_method = 'HMAC-SHA1';

	public $scope_url = null;

	protected $oauth_base_url;
	protected $api_base_url;

	protected $consumer_key;
	protected $consumer_secret;

	protected $token;
	protected $token_secret;

	protected $curl_timeout_ms = 10000;
	protected $curl_connecttimeout_ms = 10000;

	protected $request_token_url;
	protected $access_token_url;
	protected $authenticate_url;
	protected $authorize_url;

	public function getRequestToken($oauth_callback = null)
	{
		$params = array();
		if (!is_null($oauth_callback)) {
			$params['oauth_callback'] = $oauth_callback;
		}

		if (!is_null($this->scope_url)) {
			$params['scope'] = $this->scope_url;
		}

		$response = $this->makeOAuthRequest($this->request_token_url, 'GET', $params);

		$raw_credentials = explode('&', $response);

		$credentials = array();
		foreach ($raw_credentials as $cred_str) {
			$curr_cred = explode('=', $cred_str);
			$credentials[$curr_cred[0]] = rawurldecode($curr_cred[1]);
		}

		return $credentials;
	}

	public function getAccessToken($oauth_verifier)
	{
		$params = array(
			'oauth_verifier'		=> $oauth_verifier,
		);
		$response = $this->makeOAuthRequest($this->access_token_url, 'GET', $params);
		$raw_credentials = explode('&', $response);

		$credentials = array();
		foreach ($raw_credentials as $cred_str) {
			$curr_cred = explode('=', $cred_str);
			$credentials[$curr_cred[0]] = rawurldecode($curr_cred[1]);
		}

		return $credentials;
	}

	//to remove curl from being required
	public function request($api_method, &$curl = NULL, $http_method = 'GET', $extra_params = array(), $POST_body = '')
	{
		$request_url = "{$this->api_base_url}/{$api_method}";
		return $this->makeOAuthRequest($request_url, $http_method, $extra_params, $POST_body);
	}

	protected function makeOAuthRequest($url, $method, $extra_params = array(), $POST_body = '')
	{
		$class = get_class($this);
		$base_params = array(
			'oauth_consumer_key'		=> $this->consumer_key,
			'oauth_nonce'			   => $this->getOAuthNonce(),
			'oauth_signature_method'	=> $this->signature_method,
			'oauth_timestamp'		   => $this->getOAuthTimestamp(),
			'oauth_version'			 => $class::$oauth_version,
		);

		if (isset($this->token)) {
			$base_params['oauth_token'] = $this->token;
		}

		$params = array_merge($base_params, $extra_params);

		$params['oauth_signature'] = $this->getOAuthSignature($method, $url, $params);

		
		$gateway = new Gateway;
		$gateway->init($url);


		$auth_header_params_str = '';
		$rawurlencode = 'rawurlencode';
		foreach ($base_params as $name => $value) {
			$auth_header_params_str .= " $name=\"{$rawurlencode($value)}\",";
		}
		$auth_header_params_str .= ' oauth_signature="' . rawurlencode($params['oauth_signature']) . '"';
		$header_arr = array("Authorization: OAuth $auth_header_params_str");

		// TODO: do it this way with PHP 5.3.6
		//$query_str = http_build_query($extra_params, '', '&', 'PHP_QUERY_RFC3986');
		if (!empty($extra_params)) {
			$query_str = str_replace('+', '%20', http_build_query($extra_params));
		}

		if ($method == 'POST') {

			$gateway->setopt('POST', TRUE);
			// curl_setopt($ci, CURLOPT_POST, TRUE);
			if (!empty($extra_params)) {
				$gateway->setopt('POSTFIELDS', $query_str);
			} else if ($POST_body) {
				$header_arr[] = 'Content-Type: text/xml';
				$gateway->setopt('POSTFIELDS', $POST_body);
				// TODO: set CURLOPT_HEADER only in the LinkedIn case.
				// The meaningful info for these types of requests are in the
				// response headers, not the response body.
				
				$gateway->setopt('HEADER', TRUE);
				// curl_setopt($ci, CURLOPT_HEADER, true);
			}
		} else if ($method == 'GET' && isset($query_str)) {
			$url .= "?$query_str";
		}

		$gateway->setopt('HTTPHEADER', $header_arr);
		$gateway->setopt('URL', $url);
		$gateway->setopt('TIMEOUT_MS', $this->curl_timeout_ms);
		$gateway->setopt('CONNECTTIMEOUT_MS', $this->curl_connecttimeout_ms);
		$gateway->setopt('RETURNTRANSFER', true);
		
		//WARNING: this would prevent curl from detecting a 'man in the middle' attack
		$gateway->setopt('SSL_VERIFYHOST', 0);
		$gateway->setopt('SSL_VERIFYPEER', 0); 


		$response = $gateway->exec();
		$info = $gateway->getInfoLast();
		return $response;
	}

	protected function getOAuthNonce()
	{
		$mt = microtime();
		$rand = mt_rand();

		return md5($mt . $rand);
	}

	protected function getOAuthTimestamp()
	{
		return time();
	}

	protected function getOAuthSignature($method, $url, $params)
	{
		ksort($params);

		$base_string = $method . '&'
					   . rawurlencode($url) . '&'
					   // TODO: get rid of str replace and use $enc_type after PHP 5.3.6
					   . rawurlencode(str_replace('+', '%20', http_build_query($params)));

		$oauth_token_secret = '';
		if (isset($this->token_secret)) {
			$oauth_token_secret = $this->token_secret;
		}

		$key = rawurlencode($this->consumer_secret) . '&' . rawurlencode($oauth_token_secret);
		return base64_encode(hash_hmac('sha1', $base_string, $key, true));
	}

	public function getAuthorizeUrl($credentials)
	{
		$query_str = "oauth_token={$credentials['oauth_token']}";
		return $this->authorize_url . "?$query_str";
	}

	public function getAuthenticateUrl($credentials)
	{
		$query_str = "oauth_token={$credentials['oauth_token']}";
		return $this->authenticate_url . "?$query_str";
	}
	
	/*To Be Overridden as each one has different url structures & XML/JSON*/
	public function getUserID(){
		throw new Exception('getUserID for this oAuth is not set');
		$oauth_xml = $this->request('url',$object);
		return 'userid';
	}

}

?>
