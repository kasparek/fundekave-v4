<?php
if(strpos($_SERVER['HTTP_ACCEPT_ENCODING'],'gzip')!==false) {
	ob_start("ob_gzhandler");
	header('Content-Encoding: gzip');
}
//---host name
$host = $_SERVER['HTTP_HOST'];
$host = str_replace(array('www','.'),'',$host);
if($host=='localhost') $host='fundekavenet';
//--------------------------------------------------------------class autoloader
function class_autoloader($c) {
	if(strpos($c,'page_')!==false) $c = ROOT . 'page/' . $c ;
	elseif(strpos($c,'FAjax_')!==false) $c = ROOT . 'libs/FAjax/' . $c;
	elseif(strpos($c,'VO')!==false) $c = ROOT . 'libs/vo/'.$c;
	else $c = ROOT . 'libs/' . $c ;
	require( $c . '.php' );
}
spl_autoload_register("class_autoloader");
//--------------------------------------------------------error handler
FError::init(PHPLOG_FILENAME);
if(isset($_GET['nonInit'])) $nonInit=true;
if(!isset($nonInit)) {
	//--------------------------------------------------------config + constant init
	FConf::getInstance(WEBROOT.'config/'.$host.'.conf.ini');
	date_default_timezone_set(FConf::get('internationalization','timezone'));
	setlocale(LC_CTYPE, FConf::get('internationalization','setlocale'));
	setlocale(LC_COLLATE, FConf::get('internationalization','setlocale'));
	if(FConf::get('internationalization','lang')) require(FConf::get('internationalization','lang'));
	//-------------------------------------------------------------time for debuging
	FProfiler::write('START');
	//---session settings - stored in db
	ini_set("session.gc_maxlifetime",SESSIONLIFETIME);
	ini_set('session.gc_probability',1);
	ini_set('session.save_path', ROOT_SESSION);
	session_start();
	//startup user
	$user = FUser::getInstance();
	$user->init();
	if(isset($_GET['auth'])) $user->setRemoteAuthToken( FSystem::safeText($_GET['auth']) );
	//initial pageid retrieve
	if(isset($_REQUEST['k'])) $pageId = $_REQUEST['k'];
	//---backward compatibility
	if(isset($_GET['kam'])) {
		$add = ''; if($_GET['kam']>33000) { $add = 'f'; $kam=$_GET['kam']-33000; }
		elseif($_GET['kam']>23000 && $_GET['kam']<33000) { $add = 'g'; $kam=$_GET['kam']-23000; }
		$els=''; for($x=0;$x<(4-strlen($kam));$x++) $els.='l'; $pageId = $add . $els . $kam;
	}
	//check for item
	$itemId = 0;
	$itemVO = null;
	if(!empty($_REQUEST["i"])) $itemId = (int) $_REQUEST['i'];
	elseif(isset($_REQUEST['nid'])) $itemId = (int) $_REQUEST['nid']; //---backwards compatibility
	if ($itemId > 0) {
		$itemVO = new ItemVO($itemId);
		if($itemVO->load()) {
			if(empty($pageId)) $pageId = $itemVO->pageId;
			if($itemVO->itemIdTop > 0) $itemVO = new ItemVO( $itemVO->itemIdTop,true );
		} else $itemVO = null;
	}
	//recheck pageId
	if(empty($pageId)) $pageId = HOME_PAGE;
	$pageId = FSystem::processK($pageId);
	//setup userVO
	if($itemVO) $user->itemVO = $itemVO;
	$user->pageId = $pageId;
	if(isset($_REQUEST['who'])) $user->setWhoIs($_REQUEST['who']);
	$user->kde(); //---check user / load info / load page content / chechk page exist
	if($itemVO) $user->itemVO->prepare(); //need to be done after user initialization
	$pageVO = $user->pageVO; 
	FProfiler::write('USER/PAGE CHECK DONE');
	//map commands
	FCommand::getInstance(); //to load up class and get static constants
}