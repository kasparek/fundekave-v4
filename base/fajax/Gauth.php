<?php
class fajax_Gauth extends FAjaxPluginBase {
	static function getClient($data=null) {
		require_once 'Google/Client.php';
		$client = new Google_Client();
		$client->setClientId(FConf::get('google','client_id'));
		$client->setClientSecret(FConf::get('google','client_secret'));
		$client->setRedirectUri(FConf::get('google','redirect_uri'));
		$client->setScopes('email');
		return $client;
	}
	static function connect($data) {
		$client = self::getClient();
		$authUrl = $client->createAuthUrl();
		echo "<a class='login' href='".$authUrl."'>Connect Me!</a>";
		exit;
	}
	static function callback($data) {
		$client = self::getClient();
		if (isset($data['__get']['code'])) {
		  $client->authenticate($data['__get']['code']);
		  $_SESSION['access_token'] = $client->getAccessToken();
		  //$redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
		//  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
		  exit;
		}
	}
	static function ___($data) {
		if(!empty($_SESSION['access_token'])) {
			$client->setAccessToken($_SESSION['access_token']);
		}

		if($client->isAccessTokenExpired()) {
			//get new token
			echo 'token expired';
			exit;
		}

		//validate id_token with certs
		//if fail - refetch certs https://www.googleapis.com/oauth2/v1/certs
		//if fail dont use
		$certs = $client->getAuth()->retrieveCertsFromLocation('certs');

		//$certs = $client->getAuth()->retrieveCertsFromLocation('https://www.googleapis.com/oauth2/v1/certs');
			
		$audience = $client->getClassConfig($client->getAuth, 'client_id');

		$jsondecoded = json_decode($_SESSION['access_token']);

		$b = $client->getAuth()->verifySignedJwtWithCerts($jsondecoded->id_token, $certs, $audience, Google_Auth_OAuth2::OAUTH2_ISSUER); 
		var_dump($b);die();

		if ($client->getAccessToken()) {
		/*
			$oauth2 = new Google_Service_Oauth2($client);
			$user = $oauth2->userinfo->get();
			$email = filter_var($user['email'], FILTER_SANITIZE_EMAIL);
			print $email;
			*/
		} else {
			echo 'not signed in';
		}
	}
	
}