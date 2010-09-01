<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set ("display_errors", "1");	
date_default_timezone_set("Europe/London");
ob_start("ob_gzhandler");
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
		$c = ROOT . ROOT_CODE . $c ;
	} else {
		if(strpos($c,'VO')!==false) { $c = 'vo/'.$c; }
		$c = ROOT . LIBSDIR . $c ;
	}
	require( $c . '.php' );
}
spl_autoload_register("class_autoloader");
setlocale(LC_ALL,'cs_CZ.UTF-8');
//--------------------------------------------------------config + constant init
FConf::getInstance(CONFIG_FILENAME);
//-------------------------------------------------------------time for debuging
FProfiler::profile('START');

//---session settings - stored in db
//require_once("fSession.php");
//session_set_save_handler("sess_open", "sess_close", "sess_read", "sess_write", "sess_destroy", "sess_gc");
ini_set("session.gc_maxlifetime",SESSIONLIFETIME);
ini_set('session.gc_probability',1);
ini_set('session.save_path', ROOT_SESSION);

session_start();

if(!empty($_REQUEST["k"])) {
	$kArr = explode(SEPARATOR,$_REQUEST["k"]);
	$pageId = array_shift($kArr);
	while($kArr) {
		$kvArr = explode('=',array_shift($kArr));
		if(isset($kvArr[1])) {
			$_REQUEST[$kvArr[0]] = $kvArr[1];
			$_GET[$kvArr[0]] = $kvArr[1];
		}
	}
}

//---backward compatibility
if(isset($_GET['kam'])) {
	$add = '';
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
$itemVO = false;
if(!empty($_REQUEST["i"])) {
	$itemId = (int) $_REQUEST['i'];
} elseif(isset($_REQUEST['nid'])) {
	//---backwards compatibility
	$itemId = (int) $_REQUEST['nid'];
}

if ($itemId > 0) {
	$itemVO = new ItemVO($itemId);
	$itemVO->checkItem();
	if($itemVO->itemId > 0) {
		if(empty($pageId)) {
			$pageId = $itemVO->pageId;
		}
		$itemVO->load();
		if($itemVO->itemIdTop > 0) {
			$itemVO = new ItemVO( $itemVO->itemIdTop );
		}
	} else {
		$itemVO = false;
	}
}

if(empty($pageId)) $pageId = HOME_PAGE;
$pageId = FSystem::processK($pageId);

//setup userVO
$user = FUser::getInstance();
if( $itemVO ) $user->itemVO = $itemVO;
$user->pageId = $pageId;
if(isset($_REQUEST['who'])) $user->setWhoIs($_REQUEST['who']);
$user->kde(); //---check user / load info / load page content / chechk page exist
FProfiler::profile('USER/PAGE CHECK DONE');