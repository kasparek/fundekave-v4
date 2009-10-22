<?php
error_reporting(E_ALL ^ E_DEPRECATED);
date_default_timezone_set("Europe/London");
//----error catching
function obHandler($buffer) {
	$arr = explode('<?xml',$buffer);
	file_put_contents(ROOT."tmp/php.log",file_get_contents(ROOT."tmp/php.log")."\n\n".$arr[0]);
	return $buffer;
}
ob_start("obHandler");
//--------------------------------------------------------------class autoloader
function class_autoloader($c) {
	if(strpos($c,'page_')!==false) {
		$c = ROOT.ROOT_CODE . $c ;
	} else {
		if(strpos($c,'VO')!==false) { $c = 'vo/'.$c; }
		$c = LIBSDIR . $c ;
	}
	include  $c . '.php';
}
spl_autoload_register("class_autoloader");
setlocale(LC_ALL,'cs_CZ.UTF-8');

//--------------------------------------------------------config + constant init
FConf::getInstance();

//-------------------------------------------------------------time for debuging
FProfiler::profile('START');

//---session settings - stored in db
//require_once("fSession.php");
//session_set_save_handler("sess_open", "sess_close", "sess_read", "sess_write", "sess_destroy", "sess_gc");
ini_set("session.gc_maxlifetime",SESSIONLIFETIME);
ini_set('session.gc_probability',1);
ini_set('session.save_path', ROOT.'tmp');

session_start();
FProfiler::profile('FUse::before instance');
$user = FUser::getInstance();
FProfiler::profile('FUse::after instance');

if(!empty($_REQUEST["k"])) {
	$kArr = explode(SEPARATOR,$_REQUEST["k"]);
	$pageId = array_shift($kArr);
	while($kArr) {
		$kvArr = explode('=',array_shift($kArr));
		if(isset($kvArr[1])) {
			$_REQUEST[$kvArr[0]] = $kvArr[1];
		}
	}
}

if(isset($_REQUEST['m']) && empty($pageId)) {
	$cache = FCache::getInstance('s');
	if(false !== ($pageIdTmp = $cache->getData('lastPage'))) {
		$pageId = $pageIdTmp;
	}
}

$user->pageParam = '';
//---backward compatibility
if(isset($_GET['kam'])) {
	if($_GET['kam']>33000) { $add = 'f'; $kam=$_GET['kam']-33000; }
	elseif($_GET['kam']>23000 && $_GET['kam']<33000) { $add = 'g'; $kam=$_GET['kam']-23000; }
	$els='';
	for($x=0;$x<(4-strlen($kam));$x++) $els.='l';
	$pageId = $add . $els . $kam;
}

//---u=username
//TODO:refactor
/*
if(isset($_GET['u'])) {
	$userId = FUser::getUserIdByName($_GET['u']);
	if($userId > 0) {
		$userVO = new UserVO();
		$userVO->userId = $userId;
		$userVO->load();
		$usersPageId = $userVO->getXMLVal('personal','HomePageId');
		if(!empty($usersPageId)) {
			$pageId = (string) $usersPageId;
		}
	}
}*/
$itemId = 0;

if(!empty($_REQUEST["i"])) {
	$itemId = (int) $_REQUEST['i'];
} elseif(isset($_REQUEST['nid'])) {
	//---backwards compatibility
	$itemId = (int) $_REQUEST['nid'];
}

if ($itemId > 0) {
  $user->itemVO = new ItemVO($itemId);
	$user->itemVO->checkItem();
	if($user->itemVO->itemId > 0) {
  	if(empty($pageId)) {
  		$pageId = $user->itemVO->pageId;
  	}
	} else {
    $user->itemVO = false;
  }
}

if(empty($pageId)) $pageId = HOME_PAGE;

if(isset($pageId{5})) {
	//---remove the part behind - it is just nice link
	if(false!==($pos=strpos($pageId,'-'))) {
		$textLink = substr($pageId,$pos+1);
		//TODO: security check if textlink match with pageid -  otherwise do redirect
		$pageId = substr($pageId,0,$pos);
	}
	//---slice pageid on fiveid and params
	if(isset($pageId{5})) {
		if($pageId{5}==';') {
			$getArr = explode(";",substr($pageId,5));
			foreach ($getArr as $getVar) {
				$getVarArr = explode("=",$getVar);
				$_GET[$getVarArr[0]] = $getVarArr[1];
			}
		} else {
			$user->pageParam = substr($pageId,5);
			$pageId = substr($pageId,0,5);
		}
	}
}

$user->whoIs = 0;
if(isset($_REQUEST['who'])) $user->setWhoIs($_REQUEST['who']);
FProfiler::profile('PARAMS/SESSION INIT DONE');
$user->pageId = $pageId;
$user->kde(); //---check user / load info / load page content / chechk page exist
FProfiler::profile('USER/PAGE CHECK DONE');