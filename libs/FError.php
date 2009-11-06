<?php
class FError {
	function __construct(){
		if(!isset($_SESSION["errormsg"])) $_SESSION["errormsg"] = array();
		if(!isset($_SESSION["sysmsg"])) $_SESSION["sysmsg"] = array();
	}
	function addError($langkey,$systemError=0){
		$mainKey = ($systemError==0)?("errormsg"):('sysmsg');
		$prepend = (($systemError==0)?(''):($systemError.'::'));
		$count=0;		
		if(isset($_SESSION[$mainKey][$langkey])) {
			$arr = explode(' ',$_SESSION[$mainKey][$langkey]);
			$count = str_replace(array('[',']'),array('',''),$arr[count($arr)-1]) + 1;
		}
		
		$_SESSION[$mainKey][$langkey]= $prepend.$langkey. (($count>0)?(' ['.$count.']'):(''));
		return false;
	}
	function resetError(){
		$_SESSION["errormsg"]=array();
		$_SESSION["sysmsg"]=array();
	}
	function getError($sys=false){
    $index = ($sys)?("sysmsg"):("errormsg");
	  if(!isset($_SESSION[$index])) $_SESSION[$index] = array();
		return($_SESSION[$index]);
	}
	function isError($sys=false){
		$ret=false;
		
		if(!isset($_SESSION["errormsg"])) $_SESSION["errormsg"] = array();
		if(!isset($_SESSION["sysmsg"])) $_SESSION["sysmsg"] = array();
		
		if(count($_SESSION[(($sys)?("sysmsg"):("errormsg"))]) > 0) $ret=true;
		return($ret);
	}
}