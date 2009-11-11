<?php
class FError {
	/**
	 *
	 * @param $langkey - string or langkey
	 * @param $type - 0-error,1-info
	 * @return void
	 */
	static function addError($langkey,$type=0){
		$pointer = &$_SESSION['errormsg'][$type];
		if(!isset($pointer[$langkey])) $pointer[$langkey]=0;
		$pointer[$langkey]++;
	}

	static function resetError($type=0){
		$_SESSION["errormsg"][$type] = array();
	}

	static function getError($type=0){
		if(!isset($_SESSION["errormsg"][$type])) $_SESSION["errormsg"][$type] = array();
		return $_SESSION["errormsg"][$type];
	}

	static function isError($type=0){
		if(!empty($_SESSION["errormsg"][$type])) return true;
	}
	
	static function debug($die=true) {
		print_r($_SESSION["errormsg"][0]);
		print_r($_SESSION["errormsg"][1]);
		if($die===true) die();
	}
}