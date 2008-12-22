<?php
error_reporting(E_ALL);
//------------------------------------------------------------------------------
if(!isset($nonDbInit)) $nonDbInit = false;
if(!isset($nonUserInit)) $nonUserInit = false;
if(!isset($xajax)) $xajax = false;
//-------------------------------------------------------------time for debuging
list($usec, $sec) = explode(" ",microtime());
$start = ((float)$usec + (float)$sec);
//--------------------------------------------------------config + constant init
if(file_exists(CONFIG_FILENAME)){
	$conf=parse_ini_file(CONFIG_FILENAME, true);
	foreach ($conf["phpdefined"] as $k=>$v) define(strtoupper($k),$v);
	$conf["phpdefined"] = array();
	set_include_path(get_include_path() . PATH_SEPARATOR . implode(PATH_SEPARATOR,$conf["include_path"]));
} else {
	die('Error: unable to locate config file');
}
//--------------------------------------------------------------class autoloader
function class_autoloader($c) {
	if(!strpos($c,'_')) {
		$filename = $c . '.class.php';
		include $filename;
	}
}
spl_autoload_register("class_autoloader");
//------------------------------------------------------------PEARlibs--required
require_once('DB.php');
//-----------------------------------------------------------------db connection
if(!$nonDbInit) {
  
  	$db = & DB::connect($conf['db'], $conf['dboptions']);
  	if (PEAR::isError($db)) die($db->getMessage());
  	$db->query("set character_set_client = utf8");
  	$db->query("set character_set_connection= utf8");
  	$db->query("set character_set_results = utf8");
  	$db->query("set character_name = utf8");
  	return $db;
	
	//---session settings - stored in db
	  //require_once("fSession.php");
    //session_set_save_handler("sess_open", "sess_close", "sess_read", "sess_write", "sess_destroy", "sess_gc");
    ini_set("session.gc_maxlifetime",SESSIONLIFETIME);
    ini_set('session.gc_probability',1);
    ini_set('session.save_path', ROOT.'tmp/');

    session_start();
    require(ROOT.$conf['language']['path'].$conf['language']['filename']);
}

//---system user init
$user->currentItemId = 0;
if(!$nonUserInit) {
	//require_once('fUser.class.php');
	if(!isset($_SESSION["user"])) $_SESSION["user"] = new fUser();
	$user = & $_SESSION["user"];

	define('ESID',''); //just for use when sid transmit on GET

	if(!$xajax) {
    	$user->currentPageId = HOME_PAGE;
    	$user->currentPageParam = '';
    	//---backward compatibility
    	if(isset($_GET['kam'])) {
    	    if($_GET['kam']>33000) { $add = 'f'; $kam=$_GET['kam']-33000; }
    	    elseif($_GET['kam']>23000 && $_GET['kam']<33000) { $add = 'g'; $kam=$_GET['kam']-23000; }
    	    $els='';
    	    for($x=0;$x<(4-strlen($kam));$x++) $els.='l';
    	    $_GET['k'] = $user->currentPageId = $add . $els . $kam;
    	}
    	//---u=username
    	if(isset($_GET['u'])) {
    	    $userId = $user->getUserIdByName($_GET['u']);
    	    if($userId > 0) {
    	        $arr  = $user->get($userId);
    	        if(!empty($arr['personal']->HomePageId)) {
    	            $user->currentPageId = (string) $arr['personal']->HomePageId;
    	        }
    	    }
    	}
    	if(!empty($_REQUEST["i"])) {
    	    $user->currentItemId = (int) $_REQUEST['i'];
     	    $user->checkItem();
    	}
    	
    	if(!empty($_REQUEST["k"])) $user->currentPageId = $_REQUEST['k'];
    	elseif ($user->currentItemId > 0) {
    	   $user->currentPageId = $user->currentItem['pageId'];
    	}
    	if(isset($user->currentPageId{5})) {
    		//---slice pageid on fiveid and params
    		if($user->currentPageId{5}==';') {
    		  $getArr = explode(";",substr($user->currentPageId,5));
    		  foreach ($getArr as $getVar) {
    		    $getVarArr = explode("=",$getVar);
    		    $_GET[$getVarArr[0]] = $getVarArr[1];
          }
        } else {
    		  $user->currentPageParam = substr($user->currentPageId,5);
    		  $user->currentPageId = substr($user->currentPageId,0,5);
    		}
    	}
	}
	$user->whoIs = 0;
	if(isset($_REQUEST['who'])) $user->setWhoIs($_REQUEST['who']);
	$user->kde($xajax); //---check user / load info / load page content / chechk page exist
}