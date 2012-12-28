<?php 

class oAuthHandler
{

	private $oAuthClassname = null;
	private $oauth = null;

	oAuthHandler($provider){
		$oAuthExtension = ExtensionManager::create('oauth');

		$oAuthClassname= $oAuthExtension->supportedOAuth[$provider] .'OAuth';
		require_once(EXTENSIONS . "/oauth/lib/blowauth/{$oAuthClassname}.php");
	}

	function setToken($clientId, $secret, $token, $token_secret){
		$this->oauth = new $oAuthClassname($clientId, $secret, $token, $token_secret);
	}

	function submitRequest($path,$type='GET',$params= array()){
		if (!isset($this->oauth))return false;
		return $oauth->request($path[0],$ch,$type,$params);
	}


}

?>