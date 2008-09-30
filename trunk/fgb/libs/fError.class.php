<?php
class fError {
	function __construct(){
		if(!isset($_SESSION["errormsg"])) $_SESSION["errormsg"] = array();
		if(!isset($_SESSION["sysmsg"])) $_SESSION["sysmsg"] = array();
	}
	function addError($langkey,$systemError=0){
		$_SESSION[($systemError==0)?("errormsg"):('sysmsg')][]=(($systemError==0)?(''):($systemError.'::')).$langkey;
		return false;
	}
	function resetError(){
		$_SESSION["errormsg"]=array();
		$_SESSION["sysmsg"]=array();
	}
	function getError($sys=false){
		return($_SESSION[($sys)?("sysmsg"):("errormsg")]);
	}
	function isError($sys=false){
		$ret=false;
		if(count($_SESSION[(($sys)?("sysmsg"):("errormsg"))]) > 0) $ret=true;
		return($ret);
	}
}
?>