<?php

/**

Copyright (c) 2011, Jon Mifsud.
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

class oAuthv2 extends BlowAuth
{
	public static $oauth_version = '2.0';

	/*Do Not think this is required*/
	public function getRequestToken($oauth_callback = null){
	}

	/*This is the V2 Access Token Function*/
	public function getAccessToken($code,$redirectUri){
		// Code should be passed to this function and is obtained from the data passed from the server
		// $code = $_REQUEST['code'];
		
		// $url = 'https://github.com/login/oauth/access_token';
		$url =$this->access_token_url;
		$queryParams = 'client_id=' . $this->consumer_key . '&redirect_uri=' . /*urlencode($redirectUri)*/($redirectUri) . '&client_secret=' . $this->consumer_secret . '&code=' . $code . '&grant_type=authorization_code';
		
		
		// var_dump($url . '?' . $queryParams);die;
		// oAuth 2.0 uses get
		/*if(function_exists('http_get')) {
			$result = http_get($url. '?' . $queryParams);
			$result = http_parse_message($result);
			$result = $result->body;
		}
		else*/ if(function_exists('curl_version')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url . '?' . $queryParams);
			// curl_setopt($ch, CURLOPT_URL, 'http://hubnet.nationalfield.org/oauth/access_token?client_id=d977b3ac9a4ea26208064c0bdcd3c7a6&redirect_uri=http://eurosouth-hub.local/authorize/nationalfield/&client_secret=4899ae4f959d02c7e1ec102e6f164f97&code=9d342cd409a6f293e96ebf84f9064f43&grant_type=authorization_code');
			// curl_setopt($ch, CURLOPT_POSTFIELDS, $queryParams);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			
			//WARNING: this would prevent curl from detecting a 'man in the middle' attack
			curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0); 
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json, * /*'));
			
			$result = curl_exec($ch);
			// var_dump (curl_error ($ch));
			curl_close($ch);
			// var_dump('inhere');
		}
		else {
			echo 'Failed to post HTTP.';
			exit();
		}
		$token=null;
		$header=null;
		$jsonResult = json_decode($result);
		if ($jsonResult->access_token) return $jsonResult->access_token;		
		
		$headers = explode('&',trim($result));
		foreach($headers as $item) {
			$input = str_replace('"','',$input);
			$header = explode('=',$item);
			if($header[0] == 'access_token') {
				$token = $header[1];
				break;
			} 
		}
		// var_dump($url . '?' . $queryParams);
		// var_dump($headers);
		// return ($token);
		
		// var_dump($url . '?' . $queryParams);
		// var_dump($token);die;
		// var_dump($url . '?' . $queryParams);die;

		return $token;
	}

	/*Function Not Used for v2 */
	public function request($api_method, &$ch = NULL,  $http_method = 'GET', $extra_params = array(), $POST_body = ''){
		$params='';
		if (isset($extra_params)) $params = http_build_query($extra_params);
		
		//remove any '/' prior to method to avioid redirects
		if (strpos($api_method,'/')==0){
			$api_method = substr($api_method,1);
		}
		
		$request_url = "{$this->api_base_url}/{$api_method}?access_token={$this->token}"; 
		if ($http_method == 'GET') $request_url .= '&'. $params;
		// var_dump($request_url);die;
		

		$gateway = new Gateway;
		$gateway->init($request_url);

		if(function_exists('curl_version')) {
			if (!isset($ch))
				$ch = curl_init();
			if ($http_method == 'POST'){
				$gateway->setopt('POST',1);
				$gateway->setopt('POSTFIELDS',$params);
			}
			curl_setopt($ch, CURLOPT_URL, $request_url);
			// curl_setopt($ch, CURLOPT_POSTFIELDS, $queryParams);
			$gateway->setopt(CURLOPT_RETURNTRANSFER, TRUE);
			$gateway->setopt(CURLOPT_FOLLOWLOCATION, true); 
			
			//WARNING: this would prevent curl from detecting a 'man in the middle' attack
			$gateway->setopt(CURLOPT_SSL_VERIFYHOST, 0);
			$gateway->setopt(CURLOPT_SSL_VERIFYPEER, 0); 
			// curl_setopt ($ch, CURLINFO_HEADER_OUT, TRUE); 
			$result = $gateway->exec();
			$info = $gateway->getInfoLast();
			// if ($http_method == 'POST'){
				// var_dump(curl_getinfo($ch, CURLINFO_HEADER_OUT));die;}
			// var_dump (curl_error ($ch));
			// curl_close($ch);
		}
		else {
			echo 'Failed to get HTTP.';
			exit();
		}
		return $result;
		// return $this->makeOAuthRequest($request_url, $http_method, $extra_params, $POST_body);
	}
	
	/*Function Not Used for v2 */
	protected function makeOAuthRequest($url, $method, $extra_params = array(), $POST_body = ''){
		
	}

	/*Function Not Used for v2 */
	protected function getOAuthNonce(){
	}

	protected function getOAuthTimestamp(){
		return time();
	}

	/*Function Not used for V2 */
	protected function getOAuthSignature($method, $url, $params){
	}

	/*Does not Exist in v2 */
	public function getAuthorizeUrl($credentials){
		// $query_str = "oauth_token={$credentials['oauth_token']}";
		// return $this->authorize_url . "?$query_str";
	}

	/*No credentials required for 2.0 so changed with a redirect uri*/
	public function getAuthenticateUrl($redirectUri){
		$query_str = "client_id={$this->consumer_key}";
		$query_str .= "&response_type=code";
		$query_str .= "&redirect_uri={$redirectUri}";
		// $query_str .= "&scope={$scope}";
		return $this->authenticate_url . "?$query_str";
	}

}

?>
