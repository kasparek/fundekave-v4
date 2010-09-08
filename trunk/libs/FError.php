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
		if(!empty($_SESSION["errormsg"][$type])) return true; else return false;
	}
	
	static function debug($die=true) {
		print_r($_SESSION["errormsg"][0]);
		print_r($_SESSION["errormsg"][1]);
		if($die===true) die();
	}
	
	//GLOBAL ERROR HANDLING
	private static $phplog;
	private static $starttime;
	
	static function getmicrotime(){
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}
	
	static function init($filename) {
		self::$phplog = $filename;
		self::$starttime = FError::getmicrotime();
		register_shutdown_function('FError::shutdownFunction');
		set_error_handler("FError::handle_error"); 
	}
	
	static function write_log($errText) {
		$handle = fopen(self::$phplog, "a");
		fwrite($handle,'date='.date(DATE_ATOM).';runtime='.( round(FError::getmicrotime()-self::$starttime,4) ).';memory='.round(memory_get_usage()/1024).';peak='.round(memory_get_peak_usage()/1024)."\n".$errText."\n\n");
		fclose($handle);
	}
	
	static function shutDownFunction() { 
	    $e = error_get_last();
	    if($e['message']) {
	    	FError::write_log($e['type'].'='.$e['message'].';line='.$e['line'].';file='.$e['file']);
	    }
	}
	
	static function handle_error ($errno, $errstr, $errfile, $errline) {
	    FError::write_log("$errstr in $errfile on line $errline");
	}
}